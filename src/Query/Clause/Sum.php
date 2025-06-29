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
 * Class Sum
 * @package QCubed\Query\Clause
 */
class Sum extends AggregationBase
{
    protected string $strFunctionName = 'SUM';

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Sum Clause';
    }
}
