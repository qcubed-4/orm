<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\ObjectBase;
use QCubed\Exception\Caller;
use QCubed\Query\Builder;
use QCubed\Query\Node;

/**
 * Class Select
 * @package QCubed\Query\Clause
 * now was clause here! It has a name conflict
 */
class Select extends ObjectBase implements ClauseInterface
{
    /** @var Node\NodeBase[] */
    protected array $arrNodeObj = array();
    protected bool $blnSkipPrimaryKey = false;

    /**
     * @param Node\NodeBase[] $arrNodeObj
     * @throws Caller
     */
    public function __construct(array $arrNodeObj)
    {
        $this->arrNodeObj = $arrNodeObj;
        foreach ($this->arrNodeObj as $objNode) {
            if (!($objNode instanceof Node\Column)) {
                throw new Caller('Select nodes must be column nodes.', 3);
            }
        }
    }

    /**
     * Updates the given query builder instance with additional modifications or parameters.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
    }

    /**
     * Adds select items to the query builder for the specified table and alias prefix.
     *
     * @param Builder $objBuilder The query builder object to which select items will be added.
     * @param string $strTableName The name of the table for which select items are being added.
     * @param string $strAliasPrefix The prefix to be used for aliases of the select items.
     *
     * @return void
     */
    public function addSelectItems(Builder $objBuilder, string $strTableName, string $strAliasPrefix): void
    {
        foreach ($this->arrNodeObj as $objNode) {
            $strNodeTable = $objNode->getTable();
            if ($strNodeTable == $strTableName) {
                $objBuilder->addSelectItem($strTableName, $objNode->_Name, $strAliasPrefix . $objNode->_Name);
            }
        }
    }

    /**
     * Merges the data from the provided Select object into the current instance.
     *
     * @param Select|null $objSelect Optional Select an object whose nodes and settings will be merged.
     * @return void
     */
    public function merge(?Select $objSelect = null): void
    {
        if ($objSelect) {
            foreach ($objSelect->arrNodeObj as $objNode) {
                $this->arrNodeObj[] = $objNode;
            }
            if ($objSelect->blnSkipPrimaryKey) {
                $this->blnSkipPrimaryKey = true;
            }
        }
    }

    /**
     * Determines whether the primary key should be skipped.
     *
     * @return bool Returns true if the primary key is set to be skipped, otherwise false.
     */
    public function skipPrimaryKey(): bool
    {
        return $this->blnSkipPrimaryKey;
    }

    /**
     * @param boolean $blnSkipPrimaryKey
     */
    public function setSkipPrimaryKey(bool $blnSkipPrimaryKey): void
    {
        $this->blnSkipPrimaryKey = $blnSkipPrimaryKey;
    }

    public function __toString(): string
    {
        return 'QQSelectColumn Clause';
    }
}
