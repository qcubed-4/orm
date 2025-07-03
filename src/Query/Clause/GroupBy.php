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
use QCubed\Query\Node;

/**
 * Class GroupBy
 * @package QCubed\Query\Clause
 */
class GroupBy extends ObjectBase implements ClauseInterface
{
    /** @var Node\Column[] */
    protected array $objNodeArray;

    /**
     * Processes an array of parameters, collapsing it into a single array of QQNode objects, ensuring
     * validity and handling special cases such as table primary key nodes. Throws exceptions for invalid inputs.
     *
     * @param array $mixParameterArray An array of parameters, which can include QQNode objects or nested arrays of QQNode objects.
     * @return array An array of validated QQNode objects, including table primary key nodes if applicable.
     * @throws Caller Thrown if an association table node is passed, or if any parameter is not a QQNode object.
     * @throws InvalidCast Thrown if a table QQNode cannot be cast to a column-based QQNode.
     */
    protected function collapseNodes(array $mixParameterArray): array
    {
        $objNodeArray = array();
        foreach ($mixParameterArray as $mixParameter) {
            if (is_array($mixParameter)) {
                $objNodeArray = array_merge($objNodeArray, $mixParameter);
            } else {
                $objNodeArray[] = $mixParameter;
            }
        }

        $objFinalNodeArray = array();
        foreach ($objNodeArray as $objNode) {
            /** @var Node\NodeBase $objNode */
            if ($objNode instanceof Node\Association) {
                throw new Caller('GroupBy clause parameter cannot be an association table node.', 3);
            } else {
                if (!($objNode instanceof Node\NodeBase)) {
                    throw new Caller('GroupBy clause parameters must all be QQNode objects.', 3);
                }
            }

            if (!$objNode->_ParentNode) {
                throw new InvalidCast('Unable to cast "' . $objNode->_Name . '" table to Column-based QQNode', 4);
            }

            if ($objNode->_PrimaryKeyNode) {
                $objFinalNodeArray[] = $objNode->_PrimaryKeyNode;    // if a table node, use the primary key of the table instead
            } else {
                $objFinalNodeArray[] = $objNode;
            }
        }

        if (count($objFinalNodeArray)) {
            return $objFinalNodeArray;
        } else {
            throw new Caller('No parameters passed in to Expand clause', 3);
        }
    }

    /**
     * Constructor method to initialize the object with collapsed nodes.
     *
     * @param mixed $mixParameterArray The input parameter array to be processed.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(mixed $mixParameterArray)
    {
        $this->objNodeArray = $this->collapseNodes($mixParameterArray);
    }

    /**
     * Updates the query builder by adding group-by items for each node in the node array.
     *
     * @param Builder $objBuilder The query builder instance to be updated with group-by items.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $intLength = count($this->objNodeArray);
        for ($intIndex = 0; $intIndex < $intLength; $intIndex++) {
            $objBuilder->addGroupByItem($this->objNodeArray[$intIndex]->getColumnAlias($objBuilder));
        }
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'GroupBy Clause';
    }
}

