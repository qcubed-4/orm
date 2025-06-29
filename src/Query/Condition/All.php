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
 * Class All
 * @package QCubed\Query\Condition
 */
class All extends ConditionBase implements ConditionInterface
{
    /**
     * Constructor for initializing the class.
     *
     * @param array $mixParameterArray An array of parameters. Must be empty or an exception is thrown.
     * @return void
     * @throws Caller If the parameter array is not empty.
     */
    public function __construct(array $mixParameterArray)
    {
        if (count($mixParameterArray)) {
            throw new Caller('All clauses take in no parameters', 3);
        }
    }

    /**
     * @param Builder $objBuilder
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->addWhereItem('1=1');
    }
}