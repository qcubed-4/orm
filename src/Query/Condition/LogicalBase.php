<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query\Condition;

use QCubed\Exception\Caller;
use QCubed\Query\Builder;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Type;

/**
 * Class LogicalBase
 * @package QCubed\Query\Condition
 */
abstract class LogicalBase extends ConditionBase implements ConditionInterface
{
    /** @var iCondition[] */
    protected mixed $objConditionArray;

    public function __construct($mixParameterArray)
    {
        $objConditionArray = $this->collapseConditions($mixParameterArray);
        try {
            $this->objConditionArray = Type::cast($objConditionArray, Type::ARRAY_TYPE);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Updates the provided query builder object by appending the conditions stored within the current object.
     *
     * @param Builder $objBuilder The query builder object to update with conditions.
     * @return void
     * @throws Caller If the object contains elements that are not valid conditions.
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $intLength = count($this->objConditionArray);
        if ($intLength) {
            $objBuilder->addWhereItem('(');
            for ($intIndex = 0; $intIndex < $intLength; $intIndex++) {
                if (!($this->objConditionArray[$intIndex] instanceof iCondition)) {
                    throw new Caller($this->strOperator . ' clause has elements that are not Conditions');
                }
                try {
                    $this->objConditionArray[$intIndex]->updateQueryBuilder($objBuilder);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
                if (($intIndex + 1) != $intLength) {
                    $objBuilder->addWhereItem($this->strOperator);
                }
            }
            $objBuilder->addWhereItem(')');
        }
    }

    /**
     * Processes the input parameter array to create a consolidated array of conditions, ensuring all elements are of type iCondition.
     *
     * @param array $mixParameterArray The input array containing parameters, which can be either individual iCondition objects or arrays of them.
     * @return array An array of iCondition objects extracted and validated from the input parameter array.
     * @throws Caller If any parameter is not an instance of iCondition, or if the input array contains no valid conditions.
     */
    protected function collapseConditions(array $mixParameterArray): array
    {
        $objConditionArray = array();
        foreach ($mixParameterArray as $mixParameter) {
            if (is_array($mixParameter)) {
                $objConditionArray = array_merge($objConditionArray, $mixParameter);
            } else {
                $objConditionArray[] = $mixParameter;
            }
        }

        foreach ($objConditionArray as $objCondition) {
            if (!($objCondition instanceof iCondition)) {
                throw new Caller('Logical Or/And clause parameters must all be iCondition objects', 3);
            }
        }

        if (count($objConditionArray)) {
            return $objConditionArray;
        } else {
            throw new Caller('No parameters passed in to logical Or/And clause', 3);
        }
    }

    /**
     * Checks if all conditions within the object reference the specified table name.
     *
     * @param string $strTableName The name of the table to compare against.
     * @return bool Returns true if all conditions reference the specified table name, false otherwise.
     */
    public function equalTables(string $strTableName): bool
    {
        foreach ($this->objConditionArray as $objCondition) {
            if (!$objCondition->equalTables($strTableName)) {
                return false;
            }
        }
        return true;
    }
}