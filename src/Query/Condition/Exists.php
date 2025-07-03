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
use QCubed\Query\Builder;
use QCubed\Query\Node;

/**
 * Class Exists
 * Represent a test for an item being in a set of values.
 * @package QCubed\Query\Condition
 */
class Exists extends ConditionBase implements ConditionInterface
{
    /** @var Node\SubQuerySql */
    protected Node\SubQuerySql $objNode;

    /**
     * @param Node\SubQuerySql $objNode
     */
    public function __construct(Node\SubQuerySql $objNode)
    {
        $this->objNode = $objNode;
    }

    /**
     * Updates the given query builder with a conditional "EXISTS" clause.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem('EXISTS ' . $this->objNode->getColumnAlias($objBuilder));
    }
}
