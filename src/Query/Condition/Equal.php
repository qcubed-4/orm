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
use QCubed\Query\Node;
use QCubed\Query\Builder;

/**
 * Class Equal
 * Represent a test for an item being equal to a value.
 * @package QCubed\Query\Condition
 */
class Equal extends ComparisonBase
{
    protected string $strOperator = ' = ';

    /**
     * @param Builder $objBuilder
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' ' . Node\NodeBase::getValue($this->mixOperand,
                $objBuilder, true));
    }
}
