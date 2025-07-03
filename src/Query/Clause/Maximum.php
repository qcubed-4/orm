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
 * Class Maximum
 * @package QCubed\Query\Clause
 */
class Maximum extends AggregationBase
{
    protected string $strFunctionName = 'MAX';

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Maximum Clause';
    }
}
