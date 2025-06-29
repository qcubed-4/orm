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

/**
 * Class None
 * @package QCubed\Query\Condition
 */
class None extends ConditionBase implements ConditionInterface
{
    /**
     * Constructor for the class.
     *
     * @param array $mixParameterArray An array of parameters passed to the constructor.
     *
     * @return void
     *
     * @throws Caller If parameters are passed to the constructor.
     */
    public function __construct(array $mixParameterArray)
    {
        if (count($mixParameterArray)) {
            throw new Caller('None clause takes in no parameters', 3);
        }
    }

    /**
     * Updates the query builder by adding a default condition.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     *
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem('1=0');
    }
}
