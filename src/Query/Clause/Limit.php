<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Clause;

use QCubed\Exception\InvalidCast;
use QCubed\ObjectBase;
use QCubed\Exception\Caller;
use QCubed\Query\Builder;
use QCubed\Type;

/**
 * Class Limit
 * @package QCubed\Query\Clause
 */
class Limit extends ObjectBase implements ClauseInterface
{
    protected mixed $intMaxRowCount;
    protected mixed $intOffset;

    /**
     * Constructs a new instance of the class and initializes the max row count and offset.
     *
     * @param int $intMaxRowCount The maximum number of rows to process.
     * @param int $intOffset The offset to start from. Default is 0.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(int $intMaxRowCount, int $intOffset = 0)
    {
        try {
            $this->intMaxRowCount = Type::cast($intMaxRowCount, Type::INTEGER);
            $this->intOffset = Type::cast($intOffset, Type::INTEGER);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Updates the query builder with constraint and offset information.
     *
     * @param Builder $objBuilder The query builder to be updated.
     * @return void
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        if ($this->intOffset) {
            $objBuilder->setLimitInfo($this->intOffset . ',' . $this->intMaxRowCount);
        } else {
            $objBuilder->setLimitInfo($this->intMaxRowCount);
        }
    }

    /**
     * Converts the object to its string representation.
     *
     * @return string The string representation of the object.
     */
    public function __toString(): string
    {
        return 'LimitInfo Clause';
    }

    /**
     * Retrieves the value of a property dynamically.
     *
     * @param string $strName The name of the property to retrieve.
     * @return mixed The value of the requested property.
     * @throws Caller If the property does not exist or is inaccessible.
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'MaxRowCount':
                return $this->intMaxRowCount;
            case 'Offset':
                return $this->intOffset;
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }
}
