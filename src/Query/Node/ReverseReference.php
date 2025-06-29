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
 * Class ReverseReference
 * Describes a foreign key relationship that links to the primary key in the parent table. Relationship can be unique (one-to-one) or
 * not unique (many-to-one).
 * @package QCubed\Query\Node
 */
class ReverseReference extends Table
{
    /** @var string The name of the foreign key in the linked table. */
    protected string $strForeignKey;

    /**
     * Constructor for initializing a ReverseReferenceNode.
     *
     * @param NodeBase $objParentNode The parent node with which this reverse reference node is associated.
     * @param string $strName The name of the node.
     * @param string $strType The type of the node.
     * @param string $strForeignKey The foreign key associated with this reverse reference.
     * @param string|null $strPropertyName Optional property name for the node.
     *
     * @return void
     *
     * @throws Caller If the parent node is not provided.
     */
    public function __construct(
        NodeBase $objParentNode,
        string $strName,
        string $strType,
        string $strForeignKey,
        ?string $strPropertyName = null
    ) {
        parent::__construct($strName, $strPropertyName, $strType, $objParentNode);
        if (!$objParentNode) {
            throw new Caller('ReverseReferenceNodes must have a Parent Node');
        }
        $objParentNode->objChildNodeArray[$strName] = $this;
        $this->strForeignKey = $strForeignKey;
    }

    /**
     * Determines if the property is unique based on its value.
     *
     * @return bool True if the property value is unique; otherwise, false.
     */
    public function isUnique(): bool
    {
        return !empty($this->strPropertyName);
    }

    /**
     * Join a node to the query. Since this is a reverse-looking node, conditions control which items are joined.
     *
     * @param Builder $objBuilder
     * @param bool $blnExpandSelection
     * @param iCondition|null $objJoinCondition
     * @param Select|null $objSelect
     * @return ReverseReference
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
        $objParentNode->join($objBuilder, $blnExpandSelection, null, $objSelect);
        if ($objJoinCondition && !$objJoinCondition->equalTables($this->fullAlias())) {
            throw new Caller("The join condition on the \"" . $this->strTableName . "\" table must only contain conditions for that table.");
        }

        try {
            $strParentAlias = $objParentNode->fullAlias();
            $strAlias = $this->fullAlias();
            //$strJoinTableAlias = $strParentAlias . '__' . ($this->strAlias ? $this->strAlias : $this->strName);
            $objBuilder->addJoinItem($this->strTableName, $strAlias,
                $strParentAlias, $this->objParentNode->_PrimaryKey, $this->strForeignKey, $objJoinCondition);

            if ($blnExpandSelection) {
                $this->putSelectFields($objBuilder, $strAlias, $objSelect);
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }

        return $this;
    }

}
