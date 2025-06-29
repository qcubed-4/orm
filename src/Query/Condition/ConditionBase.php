<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Condition;

use Exception;
use QCubed\Exception\Caller;
use QCubed\ObjectBase;
use QCubed\Query\Builder;
use QCubed\Query\PartialBuilder;

/**
 * Class Base
 * @package QCubed\Query\Condition
 * @abstract
 */
abstract class ConditionBase extends ObjectBase
{
    protected string $strOperator;
    protected bool $blnProcessed;

    /**
     * Updates the provided query builder instance with specific modifications or configurations.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     */
    abstract public function updateQueryBuilder(Builder $objBuilder): void;

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'Condition Object';
    }


    /**
     * Used internally by QCubed Query to get an individual where clause for a given condition
     * Mostly used for conditional joins.
     *
     * @param Builder $objBuilder
     * @param bool $blnProcessOnce
     * @return null|string
     * @throws Exception
     * @throws Caller
     */
    public function getWhereClause(Builder $objBuilder, bool $blnProcessOnce = false): ?string
    {
        if ($blnProcessOnce && $this->blnProcessed) {
            return null;
        }

        $this->blnProcessed = true;

        try {
            $objConditionBuilder = new PartialBuilder($objBuilder);
            $this->updateQueryBuilder($objConditionBuilder);
            return $objConditionBuilder->getWhereStatement();
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * @abstract
     * @param string $strTableName
     * @return bool
     */
    public function equalTables(string $strTableName): bool
    {
        return true;
    }
}