<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Condition;

use QCubed\Query\Builder;

/**
 * Interface ConditionInterface
 * @package QCubed\Query\Condition
 *
 * This interface is here simply to let parts of the framework refer to a general condition as a ConditionInterface,
 * instead of a Condition\Base class, which is just ugly.
 */
interface ConditionInterface
{
    public function updateQueryBuilder(Builder $objBuilder): void;

    public function __toString(): string;

    public function getWhereClause(Builder $objBuilder, bool $blnProcessOnce = false): ?string;

    public function equalTables(string $strTableName): bool;
}