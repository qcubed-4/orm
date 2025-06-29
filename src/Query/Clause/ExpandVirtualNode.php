<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\Exception\Caller;
use QCubed\ObjectBase;
use QCubed\Query\Builder;
use QCubed\Query\Node\Virtual;

/**
 * Class ExpandVirtualNode
 * Node representing an expansion on a virtual node
 * @package QCubed\Query\Clause
 */
class ExpandVirtualNode extends ObjectBase implements ClauseInterface
{
    protected Virtual $objNode;

    /**
     * Constructor method to initialize the object with a Virtual node.
     *
     * @param Virtual $objNode The Virtual node to associate with this object.
     * @return void
     */
    public function __construct(Virtual $objNode)
    {
        $this->objNode = $objNode;
    }

    /**
     * Updates the query builder by adding a select function with specified parameters
     * based on the column alias and attribute name provided by the node.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller If an error occurs while adding the select function, the exception will be rethrown with incremented offset.
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        try {
            $objBuilder->addSelectFunction(null, $this->objNode->getColumnAlias($objBuilder),
                $this->objNode->getAttributeName());
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'ExpandVirtualNode Clause';
    }
}
