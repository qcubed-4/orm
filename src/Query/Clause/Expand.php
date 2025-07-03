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
 * Class Expand
 * @package QCubed\Query\Clause
 */
class Expand extends ObjectBase implements ClauseInterface
{
    /** @var Node\NodeBase */
    protected Node\NodeBase $objNode;
    protected ?iCondition $objJoinCondition = null;
    protected ?Select $objSelect = null;

    /**
     * Constructor for initializing the class with the provided node, join condition, and select clause.
     *
     * @param Node\NodeBase $objNode An object representing the QQNode to be expanded. Must not be null and must adhere to accepted QQNode types.
     * @param iCondition|null $objJoinCondition An optional join condition to define the relationship criteria.
     * @param Select|null $objSelect An optional select clause to define fields to be selected in the query.
     *
     * @return void
     *
     * @throws Caller If the provided QQNode is an association table node or not of type QQNode.
     * @throws InvalidCast If the QQNode provided does not have a valid parent node.
     */
    public function __construct(Node\NodeBase $objNode, ?iCondition $objJoinCondition = null, ?Select $objSelect = null)
    {
        // Check against root and table QQNodes
        if ($objNode instanceof Node\Association) {
            throw new Caller('Expand clause parameter cannot be an association table node. Try expanding one level deeper.',
                2);
        } else {
            if (!($objNode instanceof Node\NodeBase)) {
                throw new Caller('Expand clause parameter must be a QQNode object', 2);
            } else {
                if (!$objNode->_ParentNode) {
                    throw new InvalidCast('Cannot expand on this kind of node.', 3);
                }
            }
        }

        $this->objNode = $objNode;
        $this->objJoinCondition = $objJoinCondition;
        $this->objSelect = $objSelect;
    }

    /**
     * Updates the given query builder by applying the appropriate joins, conditions, and select clauses.
     *
     * @param Builder $objBuilder The query builder instance to be updated with the defined node, join conditions, and select fields.
     *
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $this->objNode->join($objBuilder, true, $this->objJoinCondition, $this->objSelect);
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Expand Clause';
    }
}
