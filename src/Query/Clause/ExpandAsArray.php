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
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Node;

/**
 * Class ExpandAsArray
 * @package QCubed\Query\Clause
 */
class ExpandAsArray extends ObjectBase implements ClauseInterface
{
    /** @var Node\NodeBase */
    protected Node\NodeBase $objNode;
    protected ?iCondition $objCondition = null;
    protected ?Select $objSelect = null;

    /**
     * Constructor for initializing an ExpandAsArray clause.
     *
     * @param Node\NodeBase $objNode The node representing the association or reverse reference.
     * @param mixed|null $objCondition Optional condition, which must be an instance of iCondition or null.
     * @param Select|null $objSelect Optional select object for specifying the fields to be selected.
     *
     * @return void
     *
     * @throws Caller Throws an exception if the provided node is not an Association or ReverseReference,
     *                or if the condition is not an instance of iCondition when provided.
     */
    public function __construct(Node\NodeBase $objNode, mixed $objCondition = null, ?Select $objSelect = null)
    {
        // For backwards compatibility with v2, which did not have a condition parameter, we will detect what the 2nd param is.
        // Ensure that this is an Association
        if ((!($objNode instanceof Node\Association)) && (!($objNode instanceof Node\ReverseReference))) {
            throw new Caller('ExpandAsArray clause parameter must be an Association or ReverseReference node', 2);
        }

        if ($objCondition instanceof Select) {
            $this->objNode = $objNode;
            $this->objSelect = $objCondition;
        } else {
            if (!is_null($objCondition)) {
                /*
                If ($objNode instanceof Association) {
                    throw new Caller ('Join conditions can only be applied to reverse reference nodes here. Try putting a condition on the next level down.', 2);
                }*/
                if (!($objCondition instanceof iCondition)) {
                    throw new Caller('Condition clause parameter must be an iCondition derived class.', 2);
                }
            }
            $this->objNode = $objNode;
            $this->objSelect = $objSelect;
            $this->objCondition = $objCondition;
        }

    }

    /**
     * Updates the query builder by applying the specified node joins and expand-as-array logic.
     *
     * @param Builder $objBuilder The query builder to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        if ($this->objNode instanceof Node\Association) {
            // The below works because all code generated association nodes will have a _ChildTableNode parameter.
            // TODO: Make this an interface
            $this->objNode->_ChildTableNode->join($objBuilder, true, $this->objCondition, $this->objSelect);
        } else {
            $this->objNode->join($objBuilder, true, $this->objCondition, $this->objSelect);
        }
        $objBuilder->addExpandAsArrayNode($this->objNode);
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'ExpandAsArray Clause';
    }
}
