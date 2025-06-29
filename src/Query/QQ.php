<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Query;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\Query\Clause\GroupBy;
use QCubed\Query\Clause\OrderBy;
use QCubed\Query\Clause\Select;
use QCubed\Query\Condition as Cond;
use QCubed\Query\Condition\AndCondition;
use QCubed\Query\Condition\OrCondition;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Node\NodeBase;

/**
 * Class QQ
 * Factory class of shortcuts for generating the various classes that eventually go in to a query.
 *
 * @package QCubed\Query
 */
class QQ
{
    /////////////////////////
    // Condition Factories
    /////////////////////////

    /**
     * Combines multiple conditions into a single logical "AND" condition.
     *
     * @return Cond\All
     * @throws Caller
     */
    static public function all(): Cond\All
    {
        return new Cond\All(func_get_args());
    }

    /**
     * Creates a condition that returns true if none of the provided conditions are met.
     *
     * @return Cond\None
     * @throws Caller
     */
    static public function none(): Cond\None
    {
        return new Cond\None(func_get_args());
    }

    /**
     * Combine multiple conditions with an OR logical operator
     *
     * @return OrCondition
     * @throws Caller
     */
    static public function orCondition(/* array and/or parameterized list of objects*/): Cond\OrCondition
    {
        return new Cond\OrCondition(func_get_args());
    }

    /**
     * Combines multiple conditions using a logical AND operation.
     *
     * @return AndCondition
     * @throws Caller
     */
    static public function andCondition(/* array and/or parameterized list of objects*/): Cond\AndCondition
    {
        return new Cond\AndCondition(func_get_args());
    }

    /**
     * Negates the given condition
     *
     * @param iCondition $objCondition The condition to be negated
     * @return Cond\Not
     */
    static public function not(iCondition $objCondition): Cond\Not
    {
        return new Cond\Not($objCondition);
    }

    /**
     * Create a condition to determine equality
     *
     * @param Node\Column $objQueryNode The column to compare
     * @param mixed $mixValue The value to compare against
     * @return Cond\Equal
     * @throws InvalidCast
     */
    static public function equal(Node\Column $objQueryNode, mixed $mixValue): Cond\Equal
    {
        return new Cond\Equal($objQueryNode, $mixValue);
    }

    /**
     * Create a condition to check inequality
     *
     * @param Node\Column $objQueryNode The column node to compare
     * @param mixed $mixValue The value to compare against
     * @return Cond\NotEqual
     * @throws InvalidCast
     */
    static public function notEqual(Node\Column $objQueryNode, mixed $mixValue): Cond\NotEqual
    {
        return new Cond\NotEqual($objQueryNode, $mixValue);
    }

    /**
     * Create a condition that checks if a column value is greater than a given value
     *
     * @param Node\Column $objQueryNode The column node to compare
     * @param mixed $mixValue The value to compare against
     * @return Cond\GreaterThan
     * @throws InvalidCast
     */
    static public function greaterThan(Node\Column $objQueryNode, mixed $mixValue): Cond\GreaterThan
    {
        return new Cond\GreaterThan($objQueryNode, $mixValue);
    }

    /**
     * Create a condition to check if the query node is greater than or equal to a given value
     *
     * @param Node\Column $objQueryNode The query node to compare
     * @param mixed $mixValue The value to compare against
     * @return Cond\GreaterOrEqual
     * @throws InvalidCast
     */
    static public function greaterOrEqual(Node\Column $objQueryNode, mixed $mixValue): Cond\GreaterOrEqual
    {
        return new Cond\GreaterOrEqual($objQueryNode, $mixValue);
    }

    /**
     * Creates a condition to check if a column's value is less than a specified value.
     *
     * @param Node\Column $objQueryNode The column node to compare.
     * @param mixed $mixValue The value to compare against.
     * @return Cond\LessThan
     * @throws InvalidCast
     */
    static public function lessThan(Node\Column $objQueryNode, mixed $mixValue): Cond\LessThan
    {
        return new Cond\LessThan($objQueryNode, $mixValue);
    }

    /**
     * Create a condition node that checks if the query node is less than or equal to the given value
     *
     * @param Node\Column $objQueryNode The column node to compare
     * @param mixed $mixValue The value to compare against
     * @return Cond\LessOrEqual
     * @throws InvalidCast
     */
    static public function lessOrEqual(Node\Column $objQueryNode, mixed $mixValue): Cond\LessOrEqual
    {
        return new Cond\LessOrEqual($objQueryNode, $mixValue);
    }

    /**
     * Check if the given column node is null
     *
     * @param Node\Column $objQueryNode
     * @return Cond\IsNull
     * @throws InvalidCast
     */
    static public function isNull(Node\Column $objQueryNode): Cond\IsNull
    {
        return new Cond\IsNull($objQueryNode);
    }

    /**
     * Check if the given query node is not null
     *
     * @param Node\Column $objQueryNode The query node to check
     * @return Cond\IsNotNull
     * @throws InvalidCast
     */
    static public function isNotNull(Node\Column $objQueryNode): Cond\IsNotNull
    {
        return new Cond\IsNotNull($objQueryNode);
    }

    /**
     * Creates an "IN" condition for a query filtering.
     *
     * @param Node\Column $objQueryNode The column to apply the condition on.
     * @param mixed $mixValuesArray The array of values for the "IN" condition.
     * @return Cond\In
     * @throws Caller
     */
    static public function in(Node\Column $objQueryNode, mixed $mixValuesArray): Cond\In
    {
        return new Cond\In($objQueryNode, $mixValuesArray);
    }

    /**
     * Evaluates whether the value of a column is not contained within a specified list of values.
     *
     * @param Node\Column $objQueryNode The column node to evaluate.
     * @param mixed $mixValuesArray The list of values to compare against.
     * @return Cond\NotIn
     * @throws Caller
     */
    static public function notIn(Node\Column $objQueryNode, mixed $mixValuesArray): Cond\NotIn
    {
        return new Cond\NotIn($objQueryNode, $mixValuesArray);
    }

    /**
     * Perform an SQL LIKE comparison
     *
     * @param Node\Column $objQueryNode
     * @param mixed $strValue
     * @return Cond\Like
     * @throws Caller
     */
    static public function like(Node\Column $objQueryNode, mixed $strValue): Cond\Like
    {
        return new Cond\Like($objQueryNode, $strValue);
    }

    /**
     * Constructs a NOT LIKE condition
     *
     * @param Node\Column $objQueryNode The column node to apply the condition on
     * @param mixed $strValue The value to compare against
     * @return Cond\NotLike
     * @throws Caller
     */
    static public function notLike(Node\Column $objQueryNode, mixed $strValue): Cond\NotLike
    {
        return new Cond\NotLike($objQueryNode, $strValue);
    }

    /**
     * Creates a condition to check if a value is between a minimum and maximum range.
     *
     * @param Node\Column $objQueryNode The column node to check the value from.
     * @param mixed $mixMinValue The minimum value of the range.
     * @param mixed $mixMaxValue The maximum value of the range.
     * @return Cond\Between
     * @throws Caller
     */
    static public function between(Node\Column $objQueryNode, mixed $mixMinValue, mixed $mixMaxValue): Cond\Between
    {
        return new Cond\Between($objQueryNode, $mixMinValue, $mixMaxValue);
    }

    /**
     * Create a condition to check if a value is not between the specified minimum and maximum range.
     *
     * @param Node\Column $objQueryNode The column node to apply the condition on.
     * @param mixed $strMinValue The minimum value of the range.
     * @param mixed $strMaxValue The maximum value of the range.
     * @return Cond\NotBetween
     * @throws Caller
     */
    static public function notBetween(Node\Column $objQueryNode, mixed $strMinValue, mixed $strMaxValue): Cond\NotBetween
    {
        return new Cond\NotBetween($objQueryNode, $strMinValue, $strMaxValue);
    }

    /**
     * Check for the existence of a subquery
     *
     * @param Node\SubQuerySql $objQueryNode
     * @return Cond\Exists
     */
    static public function exists(Node\SubQuerySql $objQueryNode): Cond\Exists
    {
        return new Cond\Exists($objQueryNode);
    }

    /**
     * Checks if a sub-query does not exist.
     *
     * @param Node\SubQuerySql $objQueryNode The sub-query node to evaluate.
     * @return Cond\NotExists
     */
    static public function notExists(Node\SubQuerySql $objQueryNode): Cond\NotExists
    {
        return new Cond\NotExists($objQueryNode);
    }

    /////////////////////////
    // QQSubQuery Factories
    /////////////////////////

    /**
     * Processes a subquery SQL string with provided query nodes.
     *
     * @param string $strSql SQL string. Use {1}, {2}, etc. to represent nodes inside of the SQL string.
     * @param mixed|null $objParentQueryNodes Optional query nodes for the subquery.
     * @return Node\SubQuerySql
     */
    static public function subSql(string $strSql, mixed $objParentQueryNodes = null): Node\SubQuerySql
    {
        $objParentQueryNodeArray = func_get_args();
        return new Node\SubQuerySql($strSql, $objParentQueryNodeArray);
    }

    /**
     * Create a virtual node
     *
     * @param string $strName The name of the virtual node
     * @param Node\SubQueryBase|null $objSubQueryDefinition An optional sub-query definition for the virtual node
     * @return Node\Virtual
     */
    static public function virtual(string $strName, ?Node\SubQueryBase $objSubQueryDefinition = null): Node\Virtual
    {
        return new Node\Virtual($strName, $objSubQueryDefinition);
    }

    /**
     * Converts a given string into a virtual alias by trimming, replacing spaces with underscores, and converting to lowercase.
     *
     * @param string $strName The original string to be transformed into a virtual alias.
     * @return string The transformed virtual alias string.
     */
    static public function getVirtualAlias(string $strName): string
    {
        $strName = trim($strName);
        $strName = str_replace(" ", "_", $strName);
        return strtolower($strName);
    }

    /////////////////////////
    // Clause\Base Factories
    /////////////////////////

    /**
     * Combines a list of Clause objects into an array
     *
     * @return array The array of Clause objects
     * @throws Caller If a non-Clause object is passed
     */
    static public function clause(/* parameterized list of Clause\Base objects */): array
    {
        $objClauseArray = array();

        foreach (func_get_args() as $objClause) {
            if ($objClause) {
                if (!($objClause instanceof Clause\ClauseInterface)) {
                    throw new Caller('Non-Clause object was passed in to QQ::Clause');
                } else {
                    $objClauseArray[] = $objClause;
                }
            }
        }

        return $objClauseArray;
    }

    /**
     * Create an ORDER BY clause with the provided arguments
     *
     * @return OrderBy
     * @throws Caller
     * @throws InvalidCast
     */
    static public function orderBy(/* array and/or parameterized list of Node\NodeBase objects*/): Clause\OrderBy
    {
        return new Clause\OrderBy(func_get_args());
    }

    /**
     * Group by the specified columns or expressions
     *
     * @return GroupBy
     * @throws Caller
     * @throws InvalidCast
     */
    static public function groupBy(/* array and/or parameterized list of Node\NodeBase objects*/): Clause\GroupBy
    {
        return new Clause\GroupBy(func_get_args());
    }

    /**
     * Create a "HAVING" clause instance with the given subquery node.
     *
     * @param Node\SubQuerySql $objNode The subquery SQL node.
     * @return Clause\Having
     */
    static public function having(Node\SubQuerySql $objNode): Clause\Having
    {
        return new Clause\Having($objNode);
    }

    /**
     * Calculate the count based on the provided column and attribute name.
     *
     * @param Node\Column $objNode The column node to count.
     * @param string $strAttributeName The attribute name to include in the count calculation.
     * @return Clause\Count
     */
    static public function count(Node\Column $objNode, string $strAttributeName): Clause\Count
    {
        return new Clause\Count($objNode, $strAttributeName);
    }

    /**
     * Calculate the sum of a specified attribute in a given column
     *
     * @param Node\Column $objNode Column node to perform the sum operation on
     * @param string $strAttributeName Name of the attribute to sum
     * @return Clause\Sum
     */
    static public function sum(Node\Column $objNode, string $strAttributeName): Clause\Sum
    {
        return new Clause\Sum($objNode, $strAttributeName);
    }

    /**
     * Calculate the minimum value based on the given node and attribute.
     *
     * @param Node\Column $objNode The column node used to determine the minimum value.
     * @param mixed $strAttributeName The attribute name for which the minimum value is calculated.
     * @return Clause\Minimum
     */
    static public function minimum(Node\Column $objNode, mixed $strAttributeName): Clause\Minimum
    {
        return new Clause\Minimum($objNode, $strAttributeName);
    }

    /**
     * Return the maximum value
     *
     * @param Node\Column $objNode The column node to evaluate
     * @param string $strAttributeName The attribute name for which the maximum value is calculated
     * @return Clause\Maximum
     */
    static public function maximum(Node\Column $objNode, string $strAttributeName): Clause\Maximum
    {
        return new Clause\Maximum($objNode, $strAttributeName);
    }

    /**
     * Calculate the average value
     *
     * @param Node\Column $objNode The column object for which the average is calculated
     * @param mixed $strAttributeName The name of the attribute
     * @return Clause\Average
     */
    static public function average(Node\Column $objNode, mixed $strAttributeName): Clause\Average
    {
        return new Clause\Average($objNode, $strAttributeName);
    }

    /**
     * Expand a node with optional join condition and select clause.
     *
     * @param Node\NodeBase $objNode The node to expand.
     * @param iCondition|null $objJoinCondition Optional join condition for the node.
     * @param Clause\Select|null $objSelect Optional select clause for the expansion.
     * @return Clause\ExpandVirtualNode|Clause\Expand
     * @throws Caller
     */
    static public function expand(
        Node\NodeBase $objNode,
        ?iCondition $objJoinCondition = null,
        ?Clause\Select $objSelect = null
    ): Clause\ExpandVirtualNode|Clause\Expand
    {
//			if (gettype($objNode) == 'string')
//				return new Clause\ExpandVirtualNode(new Node\Virtual($objNode));

        if ($objNode instanceof Node\Virtual) {
            return new Clause\ExpandVirtualNode($objNode);
        } else {
            return new Clause\Expand($objNode, $objJoinCondition, $objSelect);
        }
    }

    /**
     * Expands the given node as an array with optional conditions and selection.
     *
     * @param Node\NodeBase $objNode The node to be expanded.
     * @param mixed $objCondition Optional condition to be applied while expanding the node.
     * @param Clause\Select|null $objSelect Optional selection to be applied while expanding the node.
     * @return Clause\ExpandAsArray
     * @throws Caller
     */
    static public function expandAsArray(
        Node\NodeBase  $objNode,
        mixed          $objCondition = null,
        ?Clause\Select $objSelect = null
    ): Clause\ExpandAsArray
    {
        return new Clause\ExpandAsArray($objNode, $objCondition, $objSelect);
    }

    /**
     * Construct a Select clause from an array or a parameterized list of Node\NodeBase objects.
     *
     * @return Select
     * @throws Caller
     */
    static public function select(/* array and/or parameterized list of Node\NodeBase objects*/): Clause\Select
    {
        if (func_num_args() == 1 && is_array($a = func_get_arg(0))) {
            return new Clause\Select($a);
        } else {
            return new Clause\Select(func_get_args());
        }
    }

    /**
     * Create a limit clause for a query
     *
     * @param int $intMaxRowCount The maximum number of rows to retrieve
     * @param int $intOffset The starting row offset, default is 0
     * @return Clause\Limit
     * @throws Caller
     */
    static public function limitInfo(int $intMaxRowCount, int $intOffset = 0): Clause\Limit
    {
        return new Clause\Limit($intMaxRowCount, $intOffset);
    }

    /**
     * Create and return a new DISTINCT clause instance
     *
     * @return Clause\Distinct
     */
    static public function distinct(): Clause\Distinct
    {
        return new Clause\Distinct();
    }


    /**
     * Extracts and returns a Select clause from the provided clauses if present.
     *
     * @param mixed $objClauses The clauses to search for and extract a Select clause from. It can be an instance of Select or an array of clauses.
     * @return ?Select The extracted Select clause, or null if no Select clause is found.
     * @throws Caller
     */
    public static function extractSelectClause(mixed $objClauses): ?Select
    {
        if ($objClauses instanceof Select) {
            return $objClauses;
        }

        if (is_array($objClauses)) {
            $hasSelects = false;
            $objSelect = QQ::select();
            foreach ($objClauses as $objClause) {
                if ($objClause instanceof Clause\Select) {
                    $hasSelects = true;
                    $objSelect->merge($objClause);
                }
            }
            if (!$hasSelects) {
                return null;
            }
            return $objSelect;
        }
        return null;
    }

    /////////////////////////
    // Aliased QQ Node
    /////////////////////////
    /**
     * Assign an alias to the provided node.
     *
     * @param Node\NodeBase $objNode The node to assign an alias to.
     * @param mixed $strAlias The alias to assign to the node.
     * @return Node\NodeBase
     * @throws Caller
     */
    static public function alias(Node\NodeBase $objNode, mixed $strAlias): NodeBase
    {
        $objNode->setAlias($strAlias);
        return $objNode;
    }

    /////////////////////////
    // NamedValue QQ Node
    /////////////////////////
    /**
     * Create a new named value node
     *
     * @param mixed $strName
     * @return Node\NamedValue
     */
    static public function namedValue(mixed $strName): Node\NamedValue
    {
        return new Node\NamedValue($strName);
    }

    /**
     * Apply an arbitrary scalar function using the given parameters. See below for functions that let you apply
     * common SQL functions. The list below only includes SQL operations that are generic to all supported versions
     * of SQL. However, you can call Func directly with any named function that works in your current SQL version,
     * knowing that it might not be cross-platform compatible if you ever change SQL engines.
     *
     * @param mixed $strName The name of the function.
     * @param mixed $param1 The first parameter of the function.
     * @return Node\FunctionNode
     */
    static public function func(mixed $strName, mixed $param1/** ... */): Node\FunctionNode
    {
        $args = func_get_args();
        $strFunc = array_shift($args);
        return new Node\FunctionNode($strFunc, $args);
    }

    //////////////////////////////
    // Various common functions
    //////////////////////////////

    /**
     * Return the absolute value
     *
     * @param mixed $param
     * @return Node\FunctionNode
     */
    static public function abs(mixed $param): Node\FunctionNode
    {
        return QQ::func('ABS', $param);
    }

    /**
     * Rounds a number of upwards to the nearest integer
     *
     * @param mixed $param The value to be rounded
     * @return Node\FunctionNode
     */
    static public function ceil(mixed $param): Node\FunctionNode
    {
        return QQ::func('CEIL', $param);
    }

    /**
     * Rounds the value down to the nearest integer
     *
     * @param mixed $param
     * @return Node\FunctionNode
     */
    static public function floor(mixed $param): Node\FunctionNode
    {
        return QQ::func('FLOOR', $param);
    }

    /**
     * Calculates the modulus (remainder) of the division of two numbers.
     *
     * @param mixed $dividend The number to be divided.
     * @param mixed $divider The number by which to divide.
     * @return Node\FunctionNode The remainder of the division operation.
     */
    static public function mod(mixed $dividend, mixed $divider): Node\FunctionNode
    {
        return QQ::func('MOD', $dividend, $divider);
    }

    /**
     * Raises a base number to the power of a given exponent.
     *
     * @param mixed $base The base number.
     * @param mixed $exponent The exponent to which the base will be raised.
     * @return Node\FunctionNode The result of raising the base to the given power.
     */
    static public function power(mixed $base, mixed $exponent): Node\FunctionNode
    {
        return QQ::func('POWER', $base, $exponent);
    }

    /**
     * Calculates the square root of a given number.
     *
     * @param mixed $param The number for which the square root is to be calculated.
     * @return Node\FunctionNode The square root of the given number.
     */
    static public function sqrt(mixed $param): Node\FunctionNode
    {
        return QQ::func('SQRT', $param);
    }

    /**
     * Performs a mathematical operation using the specified function and parameters.
     *
     * @param string $strOperation The name of the mathematical operation to perform.
     * @param mixed ...$param1 Additional parameters required for the operation.
     * @return Node\Math The result of the mathematical operation encapsulated in a Node\Math object.
     */
    static public function mathOp(string $strOperation, mixed $param1/** ... */): Node\Math
    {
        $args = func_get_args();
        $strFunc = array_shift($args);
        return new Node\Math($strFunc, $args);
    }

    /**
     * Multiplies two operands.
     *
     * @param mixed $op1 The first operand.
     * @param mixed $op2 The second operand.
     * @return Node\Math The result of multiplying the two operands.
     */
    static public function mul(mixed $op1, mixed $op2/** ... */): Node\Math
    {
        return new Node\Math('*', func_get_args());
    }

    /**
     * Performs the division operation on two operands.
     *
     * @param mixed $op1 The numerator or the value to be divided.
     * @param mixed $op2 The denominator or the value to divide by.
     * @return Node\Math The result of the division operation.
     */
    static public function div(mixed $op1, mixed $op2/** ... */): Node\Math
    {
        return new Node\Math('/', func_get_args());
    }

    /**
     * Performs subtraction between two or more operands.
     *
     * @param mixed $op1 The first operand in the subtraction operation.
     * @param mixed $op2 The second operand in the subtraction operation.
     * @return Node\Math A node representing the result of the subtraction operation.
     */
    static public function sub(mixed $op1, mixed $op2/** ... */): Node\Math
    {
        return new Node\Math('-', func_get_args());
    }

    /**
     * Adds two or more values together.
     *
     * @param mixed $op1 The first operand.
     * @param mixed $op2 The second operand.
     * @return Node\Math The result of the addition operation.
     */
    static public function add(mixed $op1, mixed $op2/** ... */): Node\Math
    {
        return new Node\Math('+', func_get_args());
    }

    /**
     * Negates a given operand.
     *
     * @param mixed $op1 The operand to be negated.
     * @return Node\Math A new instance representing the negated value.
     */
    static public function neg(mixed $op1): Node\Math
    {
        return new Node\Math('-', [$op1]);
    }

}