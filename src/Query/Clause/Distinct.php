<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\ObjectBase;
use QCubed\Query\Builder;

/**
 * Class Distinct
 * @package QCubed\Query\Clause
 */
class Distinct extends ObjectBase implements ClauseInterface
{
    /**
     * Updates the given query builder instance by setting the distinct flag.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $objBuilder->setDistinctFlag();
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Distinct Clause';
    }
}

