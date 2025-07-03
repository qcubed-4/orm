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
 * Class NotExists
 * Represent a test for an item being in a set of values.
 * @package QCubed\Query\Condition
 */
class NotExists extends ConditionBase
{
    /** @var Node\SubQuerySql */
    protected Node\SubQuerySql $objNode;

    /**
     * Constructor method for initializing the class with a SubQuerySql node object.
     *
     * @param Node\SubQuerySql $objNode The SubQuerySql node object to be used for initialization.
     * @return void
     */
    public function __construct(Node\SubQuerySql $objNode)
    {
        $this->objNode = $objNode;
    }

    /**
     * Updates the query builder with a condition to check for non-existence.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem('NOT EXISTS ' . $this->objNode->getColumnAlias($objBuilder));
    }
}
