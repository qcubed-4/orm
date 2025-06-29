<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

/**
 * Class Average
 * @package QCubed\Query\Clause
 */
class Average extends AggregationBase
{
    protected string $strFunctionName = 'AVG';

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Average Clause';
    }
}
