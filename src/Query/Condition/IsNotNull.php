<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Condition;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\Query\Builder;
use QCubed\Query\Node;

/**
 * Class IsNotNull
 * Represent a test for a not null item in the database.
 * @package QCubed\Query\Condition
 */
class IsNotNull extends ComparisonBase
{
    /**
     * Constructor method.
     *
     * @param Node\Column $objQueryNode The query node object.
     * @return void
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode)
    {
        parent::__construct($objQueryNode);
    }

    /**
     * Updates the query builder by adding a condition to check for non-null values.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' IS NOT NULL');
    }
}
