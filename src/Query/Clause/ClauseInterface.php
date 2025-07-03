<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\Query\Builder;

/**
 * Class Base * class for all query clauses
 * @package QCubed\Query\Clause
 */
interface ClauseInterface
{
    public function updateQueryBuilder(Builder $objBuilder): void;

    public function __toString(): string;
}
