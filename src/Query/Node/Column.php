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
use QCubed\Query\Builder;
use QCubed\Query\Clause\Select;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Clause;

/**
 * Class Column
 * A node that represents a column in a table.
 * @package QCubed\Query\Condition
 */
class Column extends NodeBase
{
    /**
     * Initialize a column node.
     * @param string $strName
     * @param string $strPropertyName
     * @param string $strType
     * @param NodeBase|null $objParentNode
     */
    public function __construct(string $strName, string $strPropertyName, string $strType, ?NodeBase $objParentNode = null)
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
     * @param Builder $objBuilder
     * @return string
     * @throws Caller
     */
    public function getColumnAlias(Builder $objBuilder): string
    {
        $this->join($objBuilder);
        $strParentAlias = $this->objParentNode->fullAlias();
        $strTableAlias = $objBuilder->getTableAlias($strParentAlias);
        // Pull the Beginning and End Escape Identifiers from the Database Adapter
        return $this->makeColumnAlias($objBuilder, $strTableAlias);
    }

    /**
     * Generates an alias for a column by combining the table alias and column name.
     * @param Builder $objBuilder Instance of the query builder containing database escape characters.
     * @param string $strTableAlias Alias of the table to be prefixed with the column name.
     * @return string The fully qualified alias for the column.
     */
    public function makeColumnAlias(Builder $objBuilder, string $strTableAlias): string
    {
        $strBegin = $objBuilder->Database->EscapeIdentifierBegin;
        $strEnd = $objBuilder->Database->EscapeIdentifierEnd;

        return sprintf('%s%s%s.%s%s%s',
            $strBegin, $strTableAlias, $strEnd,
            $strBegin, $this->strName, $strEnd);
    }

    /**
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->objParentNode->fullAlias();
    }

    /**
     * Join the node to the given query. Since this is a leaf node, we pass on the join to the parent.
     *
     * @param Builder $objBuilder
     * @param bool $blnExpandSelection
     * @param iCondition|null $objJoinCondition
     * @param Select|null $objSelect
     * @return Column
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
            throw new Caller('A column node must have a parent node.');
        } else {
            // Here we pass the join condition on to the parent object
            $objParentNode->join($objBuilder, $blnExpandSelection, $objJoinCondition, $objSelect);
        }

        return $this;
    }

    /**
     * Get the unaliased column name. For special situations, like order by, since you can't order by aliases.
     * @return string
     */
    public function getAsManualSqlColumn(): string
    {
        if ($this->strTableName) {
            return $this->strTableName . '.' . $this->strName;
        } else {
            if (($this->objParentNode) && ($this->objParentNode->strTableName)) {
                return $this->objParentNode->strTableName . '.' . $this->strName;
            } else {
                return $this->strName;
            }
        }
    }

}