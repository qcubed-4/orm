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
 * Class NotEqual
 * Represent a test for an item being equal to a value.
 * @package QCubed\Query\Condition
 */
class NotEqual extends ComparisonBase
{
    protected string $strOperator = ' != ';

    /**
     * Updates the query builder to include a "where" condition based on the column alias and operand value.
     *
     * @param Builder $objBuilder An instance of the query Builder to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' ' . Node\NodeBase::getValue($this->mixOperand,
                $objBuilder, false));
    }
}
