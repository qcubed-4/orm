<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Node;

use QCubed\Exception\Caller;
use QCubed\Query\Clause;
use QCubed\Query\Builder;
use QCubed\Query\Clause\Select;
use QCubed\Query\Condition\ConditionInterface as iCondition;

/**
 * Class Table
 * A node that represents a regular table. This can either be a root of the query node chain or a forward-looking
 * foreign key (as in a one-to-one relationship).
 * @package QCubed\Query\Node
 */
abstract class Table extends NodeBase
{
    /**
     * Constructor method for initializing a node instance.
     *
     * @param string $strName The name of the node.
     * @param string|null $strPropertyName The property name associated with the node.
     * @param string|null $strType The type associated with the node.
     * @param NodeBase|null $objParentNode The parent node instance, if any.
     *
     * @return void
     */
    public function __construct(string $strName, ?string $strPropertyName = null, ?string $strType = null, ?NodeBase $objParentNode = null)
    {
        $this->objParentNode = $objParentNode;
        $this->strName = $strName;
        $this->strAlias = $strName;
        if ($objParentNode) {
            $objParentNode->objChildNodeArray[$strName] = $this;
        }

        $this->strPropertyName = $strPropertyName;
        $this->strType = $strType;
        if ($objParentNode) {
            $this->strRootTableName = $objParentNode->strRootTableName;
        } else {
            $this->strRootTableName = $strName;
        }
    }

    /**
     * Join the node to the query.
     * Otherwise, it's a straightforward
     * one-to-one join. Conditional joins in this situation are really only useful when combined with condition
     * clauses that select out rows that were not joined (null FK).
     *
     * @param Builder $objBuilder
     * @param bool $blnExpandSelection
     * @param iCondition|null $objJoinCondition
     * @param Select|null $objSelect
     * @return Table
     * @throws Caller
     */
    public function join(
        Builder        $objBuilder,
        ?bool           $blnExpandSelection = false,
        ?iCondition    $objJoinCondition = null,
        ?Clause\Select $objSelect = null
    ): static
    {
        $objParentNode = $this->objParentNode;
        if (!$objParentNode) {
            if ($this->strTableName != $objBuilder->RootTableName) {
                throw new Caller('Cannot use Node for "' . $this->strTableName . '"when querying against the"' . $objBuilder->RootTableName . '" table',
                    3);
            }
        } else {

            // Special case situation to allow applying a join condition on an association table.
            // The condition must be tested against the primary key of the joined table.
            if ($objJoinCondition &&
                $this->objParentNode instanceof Association &&
                $objJoinCondition->equalTables($this->objParentNode->fullAlias())
            ) {

                $objParentNode->join($objBuilder, $blnExpandSelection, $objJoinCondition, $objSelect);
                $objJoinCondition = null; // prevent passing join condition to this level
            } else {
                $objParentNode->join($objBuilder, $blnExpandSelection, null, $objSelect);
                if ($objJoinCondition && !$objJoinCondition->equalTables($this->fullAlias())) {
                    throw new Caller("The join condition on the \"" . $this->strTableName . "\" table must only contain conditions for that table.");
                }
            }

            try {
                $strParentAlias = $objParentNode->fullAlias();
                $strAlias = $this->fullAlias();
                //$strJoinTableAlias = $strParentAlias . '__' . ($this->strAlias ? $this->strAlias : $this->strName);
                $objBuilder->addJoinItem($this->strTableName, $strAlias,
                    $strParentAlias, $this->strName, $this->strPrimaryKey, $objJoinCondition);

                if ($blnExpandSelection) {
                    $this->putSelectFields($objBuilder, $strAlias, $objSelect);
                }
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
                throw $objExc;
            }
        }

        return $this;
    }
}
