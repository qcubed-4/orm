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
use QCubed\Exception\InvalidCast;
use QCubed\Query\Builder;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Node;

/**
 * Class OrderBy
 * Sort clause
 * @package QCubed\Query\Clause
 */
class OrderBy extends ObjectBase implements ClauseInterface
{
    /** @var array */
    protected array $objNodeArray;

    /**
     * Processes and collapses a mixed array of parameters into a structured array of nodes and conditions.
     * Validates that all parameters are of type Node\NodeBase or iCondition with optional ascending/descending order
     * indicators, and re-structures the nodes for proper handling.
     *
     * @param array $mixParameterArray A mixed array of parameters which may include Node\NodeBase objects,
     *                                  iCondition objects, or optional true/false ascending order indicators.
     * @return array An array of structured nodes or conditions for further processing.
     * @throws Caller If the parameters are not valid Node\NodeBase or iCondition objects, or if invalid order indicators are used.
     * @throws InvalidCast If a Node\NodeBase parameter cannot be associated with a parent table or has no primary key.
     */
    protected function collapseNodes(array $mixParameterArray): array
    {
        /** @var Node\NodeBase[] $objNodeArray */
        $objNodeArray = array();
        foreach ($mixParameterArray as $mixParameter) {
            if (is_array($mixParameter)) {
                $objNodeArray = array_merge($objNodeArray, $mixParameter);
            } else {
                $objNodeArray[] = $mixParameter;
            }
        }

        $blnPreviousIsNode = false;
        $objFinalNodeArray = array();
        foreach ($objNodeArray as $objNode) {
            if (!($objNode instanceof Node\NodeBase || $objNode instanceof iCondition)) {
                if (!$blnPreviousIsNode) {
                    throw new Caller('OrderBy clause parameters must all be Node\NodeBase or iCondition objects followed by an optional true/false "Ascending Order" option',
                        3);
                }
                $blnPreviousIsNode = false;
                $objFinalNodeArray[] = $objNode;
            } elseif ($objNode instanceof iCondition) {
                $blnPreviousIsNode = true;
                $objFinalNodeArray[] = $objNode;
            } else {
                if (!$objNode->_ParentNode) {
                    throw new InvalidCast('Unable to cast "' . $objNode->_Name . '" table to Column-based Node\NodeBase',
                        4);
                }
                if ($objNode->_PrimaryKeyNode) { // if a table node, then use the primary key of the table
                    $objFinalNodeArray[] = $objNode->_PrimaryKeyNode;
                } else {
                    $objFinalNodeArray[] = $objNode;
                }
                $blnPreviousIsNode = true;
            }
        }

        if (count($objFinalNodeArray)) {
            return $objFinalNodeArray;
        } else {
            throw new Caller('No parameters passed in to OrderBy clause', 3);
        }
    }

    /**
     * Initializes the object and processes the provided parameters to prepare
     * nodes for internal use.
     *
     * @param mixed $mixParameterArray An array or structure containing the
     * input parameters that need to be processed into nodes.
     *
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(mixed $mixParameterArray)
    {
        $this->objNodeArray = $this->collapseNodes($mixParameterArray);
    }

    /**
     * Updates the provided query builder object with the current order by a clause.
     *
     * @param Builder $objBuilder The query builder object to be updated.
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->setOrderByClause($this);
    }

    /**
     * Updates the query builder to include order by clauses based on the nodes in the current object.
     * This method parses the provided node array and appends the appropriate order by statements
     * to the query being constructed. It accounts for column nodes, virtual nodes, and conditions,
     * and applies optional ASC/DESC sort order directives where specified.
     *
     * @param Builder $objBuilder The query builder object being updated with order by clauses.
     * @return void
     * @throws Caller
     */
    public function _UpdateQueryBuilder(Builder $objBuilder): void
    {
        $intLength = count($this->objNodeArray);
        for ($intIndex = 0; $intIndex < $intLength; $intIndex++) {
            $objNode = $this->objNodeArray[$intIndex];
            if ($objNode instanceof Node\Virtual) {
                if ($objNode->hasSubquery()) {
                    throw new Caller('You cannot define a virtual node in an order by a clause. You must use an Expand clause to define it.');
                }
                $strOrderByCommand = '__' . $objNode->getAttributeName();
            } elseif ($objNode instanceof Node\Column) {
                $strOrderByCommand = $objNode->getColumnAlias($objBuilder);
            } elseif ($objNode instanceof iCondition) {
                $strOrderByCommand = $objNode->getWhereClause($objBuilder);
            } else {
                $strOrderByCommand = '';
            }

            // Check to see if they want an ASC/DESC declarator
            if ((($intIndex + 1) < $intLength) &&
                !($this->objNodeArray[$intIndex + 1] instanceof Node\NodeBase)
            ) {
                if ((!$this->objNodeArray[$intIndex + 1]) ||
                    (trim(strtolower($this->objNodeArray[$intIndex + 1])) == 'desc')
                ) {
                    $strOrderByCommand .= ' DESC';
                } else {
                    $strOrderByCommand .= ' ASC';
                }
                $intIndex++;
            }

            $objBuilder->addOrderByItem($strOrderByCommand);
        }
    }

    /**
     * Generates a SQL ORDER BY clause manually by processing an array of nodes and determining
     * the appropriate column order and sorting direction (ASC or DESC).
     *
     * @return string Returns the constructed SQL ORDER BY clause as a string.
     */
    public function getAsManualSql(): string
    {
        $strOrderByArray = array();
        $intLength = count($this->objNodeArray);
        for ($intIndex = 0; $intIndex < $intLength; $intIndex++) {
            $strOrderByCommand = $this->objNodeArray[$intIndex]->getAsManualSqlColumn();

            // Check to see if they want an ASC/DESC declarator
            if ((($intIndex + 1) < $intLength) &&
                !($this->objNodeArray[$intIndex + 1] instanceof Node\NodeBase)
            ) {
                if ((!$this->objNodeArray[$intIndex + 1]) ||
                    (trim(strtolower($this->objNodeArray[$intIndex + 1])) == 'desc')
                ) {
                    $strOrderByCommand .= ' DESC';
                } else {
                    $strOrderByCommand .= ' ASC';
                }
                $intIndex++;
            }

            $strOrderByArray[] = $strOrderByCommand;
        }

        return implode(',', $strOrderByArray);
    }

    /**
     * Provides a string representation of the object.
     *
     * @return string A string describing the QQOrderBy Clause.
     */
    public function __toString(): string
    {
        return 'OrderBy Clause';
    }
}
