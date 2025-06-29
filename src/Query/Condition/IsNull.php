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
use QCubed\Query\Node\Column;

/**
 * Class IsNull
 * Represent a test for a null item in the database.
 * @package QCubed\Query\Condition
 */
class IsNull extends ComparisonBase
{
    /**
     * Constructor method.
     *
     * @param Column $objQueryNode The query node object.
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode)
    {
        parent::__construct($objQueryNode);
    }

    /**
     * Updates the query builder with a condition.
     *
     * @param Builder $objBuilder The query builder instance to update.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' IS NULL');
    }
}
