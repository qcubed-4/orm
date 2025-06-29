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
 * Class Minimum
 * @package QCubed\Query\Clause
 */
class Minimum extends AggregationBase
{
    protected string $strFunctionName = 'MIN';

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Minimum Clause';
    }
}
