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
use QCubed\Query\Node\SubQueryBase;

/**
 * Class Having
 * Allows a custom SQL injection as a having clause. It's up to you to make sure it's correct, but you can use subquery placeholders
 * to expand column names. Standard SQL has limited Having capabilities, but many SQL engines have useful extensions.
 * @package QCubed\Query\Clause
 */
class Having extends ObjectBase implements ClauseInterface
{
    protected SubQueryBase $objNode;

    /**
     * Constructor method for initializing the object with a sub-query definition.
     *
     * @param SubQueryBase $objSubQueryDefinition The sub-query definition to initialize the object with.
     * @return void
     */
    public function __construct(SubQueryBase $objSubQueryDefinition)
    {
        $this->objNode = $objSubQueryDefinition;
    }

    /**
     * Updates the query builder by adding a having condition based on the column alias.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addHavingItem(
            $this->objNode->getColumnAlias($objBuilder)
        );
    }

    /**
     * Retrieves the name of the attribute from the object's node.
     *
     * @return string The name of the attribute.
     */
    public function getAttributeName(): string
    {
        return $this->objNode->_Name;
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return "Having Clause";
    }

}

