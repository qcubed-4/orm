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
use QCubed\Exception\InvalidCast;
use QCubed\Query\Builder;
use QCubed\Query\Node\Column;
use QCubed\Type;
use QCubed\Query\Node;


/**
 * Class In
 * Represent a test for an item being in a set of values.
 * @package QCubed\Query\Condition
 */
class In extends ComparisonBase
{
    /**
     * Constructor for the class.
     *
     * @param Column $objQueryNode An instance of the query node used for initialization.
     * @param mixed $mixValuesArray The values to be processed can be of various types, such as Node\NamedValue, Node\SubQueryBase, or an array.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(Node\Column $objQueryNode, mixed $mixValuesArray)
    {
        parent::__construct($objQueryNode);

        if ($mixValuesArray instanceof Node\NamedValue) {
            $this->mixOperand = $mixValuesArray;
        } else {
            if ($mixValuesArray instanceof Node\SubQueryBase) {
                $this->mixOperand = $mixValuesArray;
            } else {
                try {
                    $this->mixOperand = Type::cast($mixValuesArray, Type::ARRAY_TYPE);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    $objExc->incrementOffset();
                    throw $objExc;
                }
            }
        }
    }

    /**
     * Updates the query builder with the corresponding SQL conditions based on the operand.
     *
     * @param Builder $objBuilder The query builder instance to be updated.
     * @return void
     * @throws Caller
     */
    public function updateQueryBuilder(Builder $objBuilder): void
    {
        $mixOperand = $this->mixOperand;
        if ($mixOperand instanceof Node\NamedValue) {
            $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' IN (' . $mixOperand->parameter() . ')');
        } else {
            if ($mixOperand instanceof Node\SubQueryBase) {
                $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' IN ' . $mixOperand->getColumnAlias($objBuilder));
            } else {
                $strParameters = array();
                foreach ($mixOperand as $mixParameter) {
                    $strParameters[] = $objBuilder->Database->sqlVariable($mixParameter);
                }
                if (count($strParameters)) {
                    $objBuilder->addWhereItem($this->objQueryNode->getColumnAlias($objBuilder) . ' IN (' . implode(',',
                            $strParameters) . ')');
                } else {
                    $objBuilder->addWhereItem('1=0');
                }
            }
        }
    }
}
