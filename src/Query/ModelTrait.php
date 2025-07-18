<?php
declare(strict_types=1);

namespace QCubed\Query;

use QCubed\Cache\LocalMemoryCache;
use QCubed\Database\DatabaseBase;
use QCubed\Database\ResultBase;
use QCubed\Database\RowBase;
use QCubed\Exception\Caller;
use LogicException;
use QCubed\Exception\InvalidCast;
use QCubed\Query\Clause\GroupBy;
use QCubed\Query\Node\NodeBase;
use QCubed\Query\Condition\ConditionInterface as iCondition;
use QCubed\Query\Clause\ClauseInterface as iClause;
use QCubed\Query\Clause\AggregationBase;

/**
 * Class ModelTrait
 *
 * This trait class is a mixin helper for all the generated Model classes. It works together with the code generator
 * to create particular functions that are common to all the classes. For historical reasons and to prevent problems
 * with polymorphism, this is a trait and not a base class.
 */
trait ModelTrait
{
    /** Requirements of Model classes */

    /**
     * The generated model classes must implement the following functions and members.
     */

    protected static ?LocalMemoryCache $cacheInstance = null;

    /**
     * Returns the value of the primary key for this object.
     * If a composite primary key, this should return a string representation of the combined keys.
     *
     * @return int|string
     */
    protected function primaryKey(): int|string
    {
        throw new LogicException('PrimaryKey() must be implemented in the model class.');
    }

    /**
     * Helper to get the primary key from a query result row.
     *
     * @param RowBase $objDbRow
     * @param string $strAliasPrefix
     * @param string[] $strColumnAliasArray
     * @return int|string
     */
    protected static function getRowPrimaryKey(mixed $objDbRow, string $strAliasPrefix, array $strColumnAliasArray): int|string
    {
        throw new LogicException('getRowPrimaryKey() must be implemented in the model class.');
    }

    /**
     * Return the database object associated with this object.
     *
     * @return DatabaseBase|null
     */
    public static function getDatabase(): ?DatabaseBase
    {
        return null;
    }

    /**
     * Return the name of the database table associated with this object.
     *
     * @return string|null
     */
    public static function getTableName(): ?string
    {
        return '';
    }

    /**
     * Builds the SQL query statement based on the provided conditions, optional clauses, and other parameters.
     *
     * @param Builder|null &$objQueryBuilder A reference to the QueryBuilder object which gets initialized
     *                                        and populated with the query's SELECT, FROM, and other clauses.
     * @param mixed $objConditions The conditions used to filter the query results, typically instances of QQCondition.
     * @param mixed|null $objOptionalClauses Optional clauses that modify the query (e.g., sorting, grouping)
     *                                       which may include an array of objects such as clauses or null.
     * @param mixed|null $mixParameterArray An optional array of parameters to bind to the query, or null.
     * @param bool $blnCountOnly Flag specifying if the query is a count-only query.
     * @return string The complete SQL query string generated based on the inputs.
     * @throws Caller If there are unresolved named parameters in the query or other invalid input scenarios.
     */
    protected static function buildQueryStatement(
        ?Builder    &$objQueryBuilder,
        iCondition  $objConditions,
        mixed       $objOptionalClauses,
        mixed       $mixParameterArray,
        bool        $blnCountOnly
    ): string
    {
        $objDatabase   = static::getDatabase();
        $strTableName  = static::getTableName();

        // Create/Build out the QueryBuilder object with class-specific SELECT and FROM fields
        if (!$objQueryBuilder) {
            $objQueryBuilder = new Builder($objDatabase, $strTableName);
        }

        $blnAddAllFieldsToSelect = true;
        if ($objDatabase->OnlyFullGroupBy && $objOptionalClauses) {
            foreach ($objOptionalClauses as $objClause) {
                if ($objClause instanceof AggregationBase || $objClause instanceof Clause\GroupBy) {
                    $blnAddAllFieldsToSelect = false;
                    break;
                }
            }
        }

        $objQueryBuilder->addFromItem($strTableName);

        if ($blnCountOnly) {
            $objQueryBuilder->setCountOnlyFlag();
        }

        $objConditions->updateQueryBuilder($objQueryBuilder);

        // Add only if array
        if (is_array($objOptionalClauses)) {
            foreach ($objOptionalClauses as $objClause) {
                $objClause->updateQueryBuilder($objQueryBuilder);
            }
        }

        $objSelectClauses = QQ::extractSelectClause($objOptionalClauses);
        if ($objSelectClauses || $blnAddAllFieldsToSelect) {
            static::baseNode()->putSelectFields($objQueryBuilder, null, $objSelectClauses);
        }

        $strQuery = $objQueryBuilder->getStatement();

        if (is_array($mixParameterArray) && count($mixParameterArray) > 0) {
            $strQuery = $objDatabase->prepareStatement($strQuery, $mixParameterArray);
            if (str_contains($strQuery, chr(Node\NamedValue::DELIMITER_CODE) . '{')) {
                throw new Caller('Unresolved named parameters in the query');
            }
        }

        return $strQuery;
    }

    /**
     * Static QCubed Query method to query for a single object.
     * Uses BuildQueryStatement to perform most of the work and retrieve the first result from the query.
     *
     * @param iCondition $objConditions any conditions for the query
     * @param iClause|null $objOptionalClauses additional optional iClause objects or array of clauses for the query
     * @param array|null $mixParameterArray an array of name-value pairs for parameterized queries
     * @return mixed the single object from the query or null if no object is found
     * @throws Caller
     */
    protected static function _QuerySingle(
        iCondition      $objConditions,
        mixed           $objOptionalClauses = null,
        ?array          $mixParameterArray = null
    ): mixed
    {
        // Get the Query Statement
        try {
            $strQuery = static::buildQueryStatement($objQueryBuilder, $objConditions, $objOptionalClauses,
                $mixParameterArray, false);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }

        // Perform the Query, Get the First Row, and Instantiate a new object
        $objDbResult = $objQueryBuilder->Database->query($strQuery);

        // Do we have to expand anything?
        if ($objQueryBuilder->ExpandAsArrayNode) {
            $objToReturn = array();
            $objPrevItemArray = array();
            while ($objDbRow = $objDbResult->getNextRow()) {
                $objItem = static::instantiateDbRow($objDbRow, null, $objQueryBuilder->ExpandAsArrayNode,
                    $objPrevItemArray, $objQueryBuilder->ColumnAliasArray);
                if ($objItem) {
                    $objToReturn[] = $objItem;
                    $pk = $objItem->primaryKey();
                    if ($pk) {
                        $objPrevItemArray[$pk][] = $objItem;
                    } else {
                        $objPrevItemArray[] = $objItem;
                    }
                }
            }
            if (count($objToReturn)) {
                // Since we only want the object to return, let's return the object and not the array.
                return $objToReturn[0];
            } else {
                return null;
            }
        } else {
            // No expands just return the first row
            $objDbRow = $objDbResult->getNextRow();
            if (null === $objDbRow) {
                return null;
            }
            return static::instantiateDbRow($objDbRow, null, null, null, $objQueryBuilder->ColumnAliasArray);
        }
    }

    /**
     * Executes a query to retrieve an array of results from the database based on the provided conditions and optional clauses.
     *
     * @param iCondition $objConditions The conditions used to filter the query results.
     * @param mixed|null $objOptionalClauses Optional clauses that modify the query, such as sorting or grouping.
     *                                       These can be iClause instances, an array of iClause objects, or null.
     * @param array|null $mixParameterArray An optional array of parameters to bind to the query, or null.
     *
     * @return array An array of results based on the executed query.
     * @throws Caller If an error occurs while building the query statement.
     */
    protected static function _QueryArray(
        iCondition      $objConditions,
        mixed           $objOptionalClauses = null,
        ?array          $mixParameterArray = null
    ): array
    {
        // Get the Query Statement
        try {
            $strQuery = static::buildQueryStatement($objQueryBuilder, $objConditions, $objOptionalClauses,
                $mixParameterArray, false);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }

        // Perform the Query and Instantiate the Array Result
        $objDbResult = $objQueryBuilder->Database->query($strQuery);

        return static::instantiateDbResult($objDbResult, $objQueryBuilder->ExpandAsArrayNode, $objQueryBuilder->ColumnAliasArray);
    }

    /**
     * Static QCubed Query method to query for a cursor.
     * Uses BuildQueryStatement to perform most of the work.
     *
     * @param mixed $objConditions any conditions on the query, itself
     * @param array|null $objOptionalClauses additional optional iClause object(s) for this query
     * @param array|null $mixParameterArray an array of name-value pairs to perform PrepareStatement with
     * @return ResultBase the database result cursor object to iterate through the rows
     * @throws Caller
     */
    public static function queryCursor(
        iCondition      $objConditions,
        ?array        $objOptionalClauses = null,
        ?array          $mixParameterArray = null
    ): ResultBase
    {
        // Get the query statement
        try {
            $strQuery = static::buildQueryStatement($objQueryBuilder, $objConditions, $objOptionalClauses,
                $mixParameterArray, false);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }

        // Pull Expansions
        $objExpandAsArrayNode = $objQueryBuilder->ExpandAsArrayNode;
        if (!empty ($objExpandAsArrayNode)) {
            throw new Caller ("Cannot use QueryCursor with ExpandAsArray");
        }

        // Perform the query
        $objDbResult = $objQueryBuilder->Database->query($strQuery);

        // Get the alias array so we know how to instantiate a row from the result
        $objDbResult->ColumnAliasArray = $objQueryBuilder->ColumnAliasArray;
        return $objDbResult;
    }

    /**
     * Executes a query to count rows in the database based on the provided conditions and optional clauses.
     *
     * @param mixed $objConditions The conditions used to filter the query results.
     * @param mixed|null $objOptionalClauses Optional clauses that modify the query, such as sorting or grouping.
     *                                       These can be iClause instances, an array of iClause objects, or null.
     * @param mixed|null $mixParameterArray An optional array of parameters to bind to the query, or null.
     * @return int The count of rows returned by the query, or the number of grouped rows if a GROUP BY clause is used.
     * @throws Caller If an invalid optional clause is provided.
     */
    public static function queryCount(
        iCondition      $objConditions,
        mixed           $objOptionalClauses = null,
        mixed            $mixParameterArray = null
    ): int
    {
        // Get the Query Statement
        try {
            $strQuery = static::buildQueryStatement($objQueryBuilder, $objConditions, $objOptionalClauses,
                $mixParameterArray, true);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }

        // Perform the Query and return the row count
        $objDbResult = $objQueryBuilder->Database->query($strQuery);

        // Check if GROUP BY exists
        $blnGrouped = false;

        if ($objOptionalClauses instanceof GroupBy) {
            $blnGrouped = true;
        } elseif (is_array($objOptionalClauses)) {
            foreach ($objOptionalClauses as $objClause) {
                if ($objClause instanceof GroupBy) {
                    $blnGrouped = true;
                    break;
                }
            }
        } elseif ($objOptionalClauses !== null && !($objOptionalClauses instanceof iClause)) {
            // If $objOptionalClauses is not null, an array, or an iClause instance, throw an exception
            throw new Caller('Optional Clauses must be an iClause object or an array of iClause objects');
        }

        if ($blnGrouped) {
            return $objDbResult->countRows();
        } else {
            $strDbRow = $objDbResult->fetchRow();
            return (int)$strDbRow[0];
        }
    }

    /**
     * Expands a database row into an array of objects based on the provided node structure.
     *
     * @param mixed $objDbRow the database row to be expanded
     * @param string|null $strAliasPrefix the alias prefix to identify the column values
     * @param object $objNode the node structure defining how the expansion should occur
     * @param array $objPreviousItemArray the array of previously instantiated objects for reference during expansion
     * @param array $strColumnAliasArray an array mapping column aliases to their corresponding database column names
     * @return bool|null whether something was expanded (true) or not (false), or null if no child nodes exist
     */
    public static function expandArray(
        mixed       $objDbRow,
        ?string     $strAliasPrefix,
        object      $objNode,
        array       $objPreviousItemArray,
        array       $strColumnAliasArray
    ): ?bool
    {
        if (!$objNode->ChildNodeArray) {
            return null;
        }
        $blnExpanded = null;

        $pk = static::getRowPrimaryKey($objDbRow, $strAliasPrefix, $strColumnAliasArray);

        foreach ($objPreviousItemArray as $objPreviousItem) {
            if ($pk != $objPreviousItem->primaryKey()) {
                continue;
            }

            foreach ($objNode->ChildNodeArray as $objChildNode) {
                $strPropName = $objChildNode->_PropertyName;
                $strClassName = $objChildNode->_ClassName;
                $strLongAlias = $objChildNode->fullAlias();
                $blnExpandAsArray = false;

                $strPostfix = $objChildNode->ExpandAsArray ? 'Array' : '';
                if ($objChildNode->ExpandAsArray) {
                    $blnExpandAsArray = true;
                }

                // type-dependent variable name construction
                $nodeType = $objChildNode->_Type;
                if ($nodeType == 'reverse_reference') {
                    $strPrefix = '_obj';
                } elseif ($nodeType == 'association') {
                    $objChildNode = $objChildNode->firstChild();
                    $strPrefix = $objChildNode->IsType ? '_int' : '_obj';
                } else {
                    $strPrefix = 'obj';
                }
                $strVarName = $strPrefix . $strPropName . $strPostfix;

                if ($blnExpandAsArray) {
                    if (null === $objPreviousItem->$strVarName) {
                        $objPreviousItem->$strVarName = [];
                    }
                    if (count($objPreviousItem->$strVarName)) {
                        $objPreviousChildItems = $objPreviousItem->$strVarName;
                        $nextAlias = $objChildNode->fullAlias() . '__';

                        $objChildItem = $strClassName::instantiateDbRow(
                            $objDbRow, $nextAlias, $objChildNode, $objPreviousChildItems, $strColumnAliasArray, true
                        );

                        if ($objChildItem) {
                            $objPreviousItem->{$strVarName}[] = $objChildItem;
                            $blnExpanded = true;
                        } elseif ($objChildItem === false) {
                            $blnExpanded = true;
                        }
                    }
                } elseif (!$objChildNode->IsType) {
                    if (null === $objPreviousItem->$strVarName) {
                        return false;
                    }
                    $objPreviousChildItems = [$objPreviousItem->$strVarName];
                    $blnResult = $strClassName::expandArray(
                        $objDbRow, $strLongAlias . '__', $objChildNode, $objPreviousChildItems, $strColumnAliasArray
                    );
                    if ($blnResult) {
                        $blnExpanded = true;
                    }
                }
            }
        }
        return $blnExpanded;
    }

    /**
     * Instantiates an object from a row in the database, populating its properties
     * based on the column data and optional parameters.
     *
     * @param RowBase $objDbRow the database row object containing column data
     * @param string|null $strAliasPrefix an optional alias prefix for the table's columns
     * @param mixed|null $objExpandAsArrayNode optional expansion nodes for complex relationships
     * @param array|null $objPreviousItemArray an optional array of previously instantiated items to check for duplicates
     * @param array $strColumnAliasArray an optional array for handling column aliases
     * @param bool $blnCheckDuplicate whether to check for and prevent instantiating duplicate objects
     * @param string|null $strParentExpansionKey optional key for parent expansion relationships
     * @param mixed|null $objExpansionParent optional parent object for expansion
     * @return static|null the instantiated object, or null if the row is empty or a duplicate is found
     */
    public static function instantiateDbRow(
        RowBase     $objDbRow,
        ?string     $strAliasPrefix = null,
        mixed       $objExpandAsArrayNode = null,
        ?array      $objPreviousItemArray = null,
        array       $strColumnAliasArray = [],
        ?bool       $blnCheckDuplicate = false,
        ?string     $strParentExpansionKey = null,
        mixed       $objExpansionParent = null
    ): null|static
    {
        // If the row is empty, return null
        if (count($objDbRow->getColumnNameArray()) === 0) {
            return null;
        }

        // New object
        $objToReturn = new static();

        // Assign data from the database to the attributes
        $columns = $objDbRow->getColumnNameArray();
        foreach ($columns as $columnName => $value) {
            // Rigidly/logically: Use property existence check
            $property = $strAliasPrefix ? substr($columnName, strlen($strAliasPrefix)) : $columnName;
            if (property_exists($objToReturn, $property)) {
                $objToReturn->$property = $value;
            }
        }

        // Check for duplicates if necessary
        if ($blnCheckDuplicate && $objPreviousItemArray) {
            foreach ($objPreviousItemArray as $objPreviousItem) {
                if ($objToReturn->primaryKey() == $objPreviousItem->primaryKey()) {
                    return null;
                }
            }
        }

        return $objToReturn;
    }

    /**
     * Retrieves the base node for the model. This method must be implemented
     * in the derived model class to define the specific NodeBase instance.
     *
     * @return NodeBase The base node representing the model's structure or definition.
     * @throws Caller If the method is not implemented in the model class.
     */
    protected static function baseNode(): NodeBase
    {
        throw new Caller('baseNode() must be implemented in the model class');
    }

    /**
     * Instantiates database result rows into an array of objects, optionally expanding nodes for array-based relationships.
     *
     * @param mixed $objDbResult The database result object containing the rows to be instantiated.
     * @param mixed|null $objExpandAsArrayNode Optional node structure used for expanding array properties during instantiation.
     * @param array|null $strColumnAliasArray An optional array of column aliases for resolving database column names.
     * @return array An array of instantiated objects based on the database result rows.
     */
    public static function instantiateDbResult(
        mixed       $objDbResult,
        mixed       $objExpandAsArrayNode = null,
        ?array      $strColumnAliasArray = null
    ): array
    {
        $objToReturn = [];
        $objPrevItemArray = [];

        if (!$strColumnAliasArray) {
            $strColumnAliasArray = [];
        }

        if ($objExpandAsArrayNode) {
            while ($objDbRow = $objDbResult->getNextRow()) {
                $objItem = static::instantiateDbRow(
                    $objDbRow,
                    null,
                    $objExpandAsArrayNode,
                    $objPrevItemArray,
                    $strColumnAliasArray
                );
                if ($objItem) {
                    $objToReturn[] = $objItem;
                    // NB! The previous item linked by ID will be saved: (NB! check that the primary key exists)
                    if (property_exists($objItem, 'intId')) {
                        $objPrevItemArray[$objItem->intId][] = $objItem;
                    }
                }
            }
        } else {
            while ($objDbRow = $objDbResult->getNextRow()) {
                $objToReturn[] = static::instantiateDbRow(
                    $objDbRow,
                    null,
                    null,
                    null,
                    $strColumnAliasArray
                );
            }
        }

        return $objToReturn;
    }

    /**
     * Retrieves a singleton instance of the local memory cache.
     * If the cache instance does not exist, it initializes a new LocalMemoryCache.
     *
     * @return LocalMemoryCache the singleton instance of the LocalMemoryCache
     */
    protected static function cache(): LocalMemoryCache
    {
        if (!static::$cacheInstance) {
            static::$cacheInstance = new LocalMemoryCache([]);
        }
        return static::$cacheInstance;
    }

    /**
     * Retrieves a value from the cache based on the specified key.
     *
     * @param mixed $key The key used to look up the cached value. It will be cast to a string.
     * @return ModelTrait|null The value retrieved from the cache or null if the key is not found.
     */
    protected static function _GetFromCache(mixed $key): ?static
    {
        // if (!is_scalar($key)) {
            // throw new InvalidArgumentException('Cache key must be scalar, '.gettype($key).' given');
        //}

        return static::cache()->get((string)$key, null);
    }

    /**
     * Writes the current object instance to the cache using its primary key as the cache key.
     * The method ensures that the `PrimaryKey` method exists and uses its return value to generate the cache key.
     *
     * @return void
     * @throws Caller
     * @throws InvalidCast
     */
    public function writeToCache(): void
    {
        if (method_exists($this, 'PrimaryKey')) {
            $key = $this->PrimaryKey();
            static::cache()->set((string)$key, $this);
        }
    }

    /**
     * Deletes the current object's data from the cache using its primary key.
     * If the object has a PrimaryKey method, the cache key is derived from it
     * and used to remove the associated data from the cache.
     *
     * @return void
     */
    public function deleteFromCache(): void
    {
        if (method_exists($this, 'PrimaryKey')) {
            $key = $this->PrimaryKey();
            static::cache()->delete((string)$key);
        }
    }

    /**
     * Clears all entries from the cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        static::cache()->clear();
    }
}