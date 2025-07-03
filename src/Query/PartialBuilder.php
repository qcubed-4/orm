<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query;

/**
 * Class PartialBuilder
 *    Subclasses Builder to handle the building of conditions for conditional expansions, subqueries, etc.
 *    Since regular queries use WhereClauses for conditions, we just use the where clause portion and
 *    only build a condition clause appropriate for a conditional expansion.
 */
class PartialBuilder extends Builder
{
    protected Builder $objParentBuilder;

    /**
     * Constructor method.
     *
     * @param Builder $objBuilder The builder object used for initialization.
     * @return void
     */
    public function __construct(Builder $objBuilder)
    {
        parent::__construct($objBuilder->objDatabase, $objBuilder->strRootTableName);
        $this->objParentBuilder = $objBuilder;
        $this->strColumnAliasArray = &$objBuilder->strColumnAliasArray;
        $this->strTableAliasArray = &$objBuilder->strTableAliasArray;

        $this->intTableAliasCount = &$objBuilder->intTableAliasCount;
        $this->intColumnAliasCount = &$objBuilder->intColumnAliasCount;
    }

    /**
     * Constructs and returns a SQL WHERE clause statement by joining the elements
     * of the internal strWhereArray property with a space delimiter.
     *
     * @return string The complete WHERE clause as a string.
     */
    public function getWhereStatement(): string
    {
        return implode(' ', $this->strWhereArray);
    }

    /**
     * Constructs and returns a SQL FROM clause statement by combining the elements
     * of the internal strFromArray property and appending joined elements from the
     * strJoinArray property, separated by spaces.
     *
     * @return string The complete FROM clause as a string.
     */
    public function getFromStatement(): string
    {
        return implode(' ', $this->strFromArray) . ' ' . implode(' ', $this->strJoinArray);
    }
}