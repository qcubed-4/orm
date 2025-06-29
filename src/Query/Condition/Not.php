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
use QCubed\Query\Condition\ConditionInterface as iCondition;

/**
 * Class Not
 * @package QCubed\Query\Condition
 */
class Not extends LogicalBase
{
    public function __construct(iCondition $objCondition)
    {
        parent::__construct([$objCondition]);
    }

    /**
     * Updates the given query builder by adding specific conditions.
     *
     * @param Builder $objBuilder The query builder object that will be modified.
     * @return void
     * @throws Caller Thrown if an error occurs while updating the query builder.
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem('(NOT');
        try {
            $this->objConditionArray[0]->updateQueryBuilder($objBuilder);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
        $objBuilder->addWhereItem(')');
    }
}
