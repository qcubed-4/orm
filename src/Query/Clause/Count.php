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
 * Class Count * aggregate items
 * @package QCubed\Query\Clause
 */
class Count extends AggregationBase
{
    protected string $strFunctionName = 'COUNT';

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Count Clause';
    }
}

