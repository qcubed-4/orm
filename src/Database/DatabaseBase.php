<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database;

use QCubed\Database\Mysqli5\MysqliResult;
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\ObjectBase;
use QCubed\QCubed;
use QCubed\QDateTime;
use QCubed\Timer;
use QCubed\Type;

/**
 * Every database adapter must implement the following 5 classes (all of which are abstract):
 * DatabaseBase
 * DatabaseFieldBase
 * DatabaseResultBase
 * DatabaseRowBase
 * DatabaseExceptionBase
 * This Database library also has the following classes already defined, and
 * Database adapters are assumed to use them internally:
 * DatabaseIndex
 * DatabaseForeignKey
 * DatabaseFieldType (which is an abstract class that solely contains constants)
 *
 * @property-read string $EscapeIdentifierBegin
 * @property-read string $EscapeIdentifierEnd
 * @property-read boolean $EnableProfiling
 * @property-read int $AffectedRows
 * @property-read string $Profile
 * @property-read int $DatabaseIndex
 * @property-read int $Adapter
 * @property-read string $Server
 * @property-read string $Port
 * @property-read string $Database
 * @property-read string $Service
 * @property-read string $Protocol
 * @property-read string $Host
 * @property-read string $Username
 * @property-read string $Password
 * @property boolean $Caching         if true objects loaded from this database are kept in a cache (assuming a cache provider is also configured)
 * @property-read string $DateFormat
 * @property-read boolean $OnlyFullGroupBy database adapter subclasses can override and set this property to true
 *          to prevent the behavior of automatically adding all the columns to the select clause when the query has
 *          an aggregation clause.
 * @package DatabaseAdapters
 */
abstract class DatabaseBase extends ObjectBase
{
    // Must be updated for all Adapters
    /** Adapter name */
    const ADAPTER = 'Generic Database Adapter (Abstract)';

    // Protected Member Variables for ALL Database Adapters
    /** @var int Database Index according to the configuration file */
    protected int $intDatabaseIndex;
    /** @var bool Has the profiling been enabled? */
    protected bool $blnEnableProfiling;
    protected array $strProfileArray;

    protected array $objConfigArray;
    protected bool $blnConnectedFlag = false;

    /** @var string The beginning part of characters which can escape identifiers in an SQL query for the database */
    protected string $strEscapeIdentifierBegin = '"';
    /** @var string The final part of characters that can be used instead of identifiers in a database SQL query */
    protected string $strEscapeIdentifierEnd = '"';
    protected bool $blnOnlyFullGroupBy = false; // should be set in subclasses as appropriate

    /**
     * @var int The transaction depth value.
     * It is incremented on a transaction beginning,
     * decremented on a transaction commit, and reset to zero on a rollback.
     * It is used to implement the recursive transaction functionality.
     */
    protected int $intTransactionDepth = 0;

    // Abstract Methods that ALL Database Adapters MUST implement

    /**
     * Establishes a connection to a data source.
     *
     * This method must be implemented by any subclass to define
     * the process of establishing a connection specific to the data source.
     *
     * @return void
     */
    abstract public function connect(): void;
    // these are protected - externally, the "Query/NonQuery" wrappers are meant to be called

    /**
     * Executes the provided query string and returns the result.
     *
     * @param string $strQuery The SQL query to be executed.
     *
     * @return mixed The result of the query execution.
     */
    abstract protected function executeQuery(string $strQuery): MysqliResult;

    /**
     * Sends a non-SELECT query (such as INSERT, UPDATE, DELETE, TRUNCATE) to the DB server.
     * In most cases, the results of this function are not used, and you should not send
     * 'SELECT' queries using this method because a result is not guaranteed to be returned
     *
     * If there was an error, it would most probably be caught as an exception.
     *
     * @param string $strNonQuery The Query to be executed
     *
     * @return mixed Result that the database returns after running the query
     */
    abstract protected function executeNonQuery(string $strNonQuery): MysqliResult;

    /**
     * Returns the list of tables in the database (as string)
     *
     * @return mixed|string[] List of tables
     */
    abstract public function getTables(): mixed;

    /**
     * Returns the ID to be inserted in a table column (normally it an autoincrement column)
     *
     * @param string|null $strTableName Table name where the ID has to be inserted
     * @param string|null $strColumnName Column name where the ID has to be inserted
     *
     * @return mixed
     */
    abstract public function insertId(?string $strTableName = null, ?string $strColumnName = null): mixed;

    /**
     * Get the list of columns/fields for a given table
     *
     * @param string $strTableName Name of table whose fields we have to get
     *
     * @return mixed
     */
    abstract public function getFieldsForTable(string $strTableName): mixed;

    /**
     * Get a list of indexes for a table
     *
     * @param string $strTableName Name of the table whose column indexes we have to get
     *
     * @return mixed
     */
    abstract public function getIndexesForTable(string $strTableName): mixed;

    /**
     * Get a list of foreign keys for a table
     *
     * @param string $strTableName Name of the table whose foreign keys we are trying to get
     *
     * @return mixed
     */
    abstract public function getForeignKeysForTable(string $strTableName): mixed;

    /**
     * This function actually begins the database transaction.
     * Must be implemented in all subclasses.
     * The "TransactionBegin" wrapper is meant to be called by end-user code
     *
     * @return void Nothing
     */
    abstract protected function executeTransactionBegin(): void;

    /**
     * This function actually commits the database transaction.
     * Must be implemented in all subclasses.
     * The "TransactionCommit" wrapper is meant to be called by end-user code
     * @return void Nothing
     */
    abstract protected function executeTransactionCommit(): void;

    /**
     * This function actually rolls back the database transaction.
     * Must be implemented in all subclasses.
     * The "TransactionRollBack" wrapper is meant to be called by end-user code
     *
     * @return void Nothing
     */
    abstract protected function executeTransactionRollBack(): void;

    /**
     * Template for executing stored procedures. Optional for those database drivers that support it.
     * @param string $strProcName
     * @param array|null $params
     * @return null
     */
    public function executeProcedure(string $strProcName, ?array $params = null): null
    {
        return null;
    }

    /**
     * This function begins the database transaction.
     *
     * @return void Nothing
     */
    public final function transactionBegin(): void
    {
        if (0 == $this->intTransactionDepth) {
            $this->executeTransactionBegin();
        }
        $this->intTransactionDepth++;
    }

    /**
     * This function commits the database transaction.
     *
     * @throws Caller
     * @return void Nothing
     */
    public final function transactionCommit(): void
    {
        if (1 == $this->intTransactionDepth) {
            $this->executeTransactionCommit();
        }
        if ($this->intTransactionDepth <= 0) {
            throw new Caller("The transaction commit call is called before the transaction beginning was called.");
        }
        $this->intTransactionDepth--;
    }

    /**
     * This function rolls back the database transaction.
     *
     * @return void Nothing
     */
    public final function transactionRollBack(): void
    {
        $this->executeTransactionRollBack();
        $this->intTransactionDepth = 0;
    }

    /**
     * Prepares and returns the prefix syntax for limiting SQL query results.
     *
     * @param string $strLimitInfo Information related to the SQL limit clause
     *
     * @return string|null Prepared prefix string for the SQL limit clause
     */
    abstract public function sqlLimitVariablePrefix(string $strLimitInfo): ?string;

    /**
     * Appends or modifies the SQL query string with the limit clause based on the provided limit information.
     *
     * @param string $strLimitInfo Limit clause information to be appended or modified in the SQL query.
     *
     * @return string|null Returns the modified SQL query string with the limit clause or null if no modification is applied.
     */
    abstract public function sqlLimitVariableSuffix(string $strLimitInfo): string|null;

    /**
     * Sort database query results based on the provided sort information.
     *
     * @param string $strSortByInfo Sorting information used to order query results.
     *
     * @return mixed
     */
    abstract public function sqlSortByVariable(string $strSortByInfo): mixed;

    /**
     * Closes the current connection or resource.
     *
     * This method is responsible for terminating any active connections
     * and releasing associated resources to ensure proper cleanup.
     *
     * @return void
     */
    abstract public function close(): void;

    /**
     * Escapes a database identifier (e.g., table or column name) to ensure it is properly quoted.
     *
     * @param string $strIdentifier The identifier to be escaped.
     *
     * @return string The escaped identifier.
     */
    public function escapeIdentifier(string $strIdentifier): string
    {
        return $this->strEscapeIdentifierBegin . $strIdentifier . $this->strEscapeIdentifierEnd;
    }

    /**
     * Escapes database identifiers to prevent SQL injection or syntax errors.
     *
     * @param array|string $mixIdentifiers Single identifier or an array of identifiers to be escaped
     *
     * @return string|array Escaped identifier(s)
     */
    public function escapeIdentifiers(array|string $mixIdentifiers): array|string
    {
        if (is_array($mixIdentifiers)) {
            return array_map(array($this, 'EscapeIdentifier'), $mixIdentifiers);
        } else {
            return $this->escapeIdentifier($mixIdentifiers);
        }
    }

    /**
     * Escapes single or multiple values for safe inclusion in SQL queries.
     *
     * @param mixed $mixValues A single value or an array of values to be escaped
     *
     * @return string|array Escaped value(s); returns a single escaped value if input is not an array,
     *               or an array of escaped values if input is an array
     */
    public function escapeValues(mixed $mixValues): string|array
    {
        if (is_array($mixValues)) {
            return array_map(array($this, 'SqlVariable'), $mixValues);
        } else {
            return $this->sqlVariable($mixValues);
        }
    }

    /**
     * Escapes column identifiers and their corresponding values for use in a SQL query.
     *
     * @param array $mixColumnsAndValuesArray An associative array where the keys are column identifiers
     *                                        and the values are the associated data to be escaped.
     *
     * @return array An associative array with column identifiers and values properly escaped for safe use in an SQL query.
     */
    public function escapeIdentifiersAndValues(array $mixColumnsAndValuesArray): array
    {
        $result = array();
        foreach ($mixColumnsAndValuesArray as $strColumn => $mixValue) {
            $result[$this->escapeIdentifier($strColumn)] = $this->sqlVariable($mixValue);
        }
        return $result;
    }

    /**
     * Inserts a new record or updates an existing record in the specified table based on the primary key match condition.
     *
     * @param string $strTable The name of the table where the operation will be performed.
     * @param array $mixColumnsAndValuesArray An associative array of column names and their corresponding values for the operation.
     * @param array|string|null $strPKNames The primary key column(s) used to match records for the update operation.
     *                                      It can be a single column name, an array of column names, or null to default
     *                                      to the first column in $mixColumnsAndValuesArray.
     *
     * @return void
     */
    public function insertOrUpdate(string $strTable, array $mixColumnsAndValuesArray, mixed $strPKNames = null): void
    {
        $strEscapedArray = $this->escapeIdentifiersAndValues($mixColumnsAndValuesArray);
        $strColumns = array_keys($strEscapedArray);
        $strUpdateStatement = '';
        foreach ($strEscapedArray as $strColumn => $strValue) {
            if ($strUpdateStatement) {
                $strUpdateStatement .= ', ';
            }
            $strUpdateStatement .= $strColumn . ' = ' . $strValue;
        }
        if (is_null($strPKNames)) {
            $strMatchCondition = 'target_.' . $strColumns[0] . ' = source_.' . $strColumns[0];
        } else {
            if (is_array($strPKNames)) {
                $strMatchCondition = '';
                foreach ($strPKNames as $strPKName) {
                    if ($strMatchCondition) {
                        $strMatchCondition .= ' AND ';
                    }
                    $strMatchCondition .= 'target_.' . $this->escapeIdentifier($strPKName) . ' = source_.' . $this->escapeIdentifier($strPKName);
                }
            } else {
                $strMatchCondition = 'target_.' . $this->escapeIdentifier($strPKNames) . ' = source_.' . $this->escapeIdentifier($strPKNames);
            }
        }
        $strTable = $this->EscapeIdentifierBegin . $strTable . $this->EscapeIdentifierEnd;
        $strSql = sprintf('MERGE INTO %s AS target_ USING %s AS a source_ ON %s WHEN MATCHED THEN UPDATE SET %s WHEN NOT MATCHED THEN INSERT (%s) VALUES (%s)',
            $strTable, $strTable,
            $strMatchCondition, $strUpdateStatement,
            implode(', ', $strColumns),
            implode(', ', array_values($strEscapedArray))
        );
        $this->executeNonQuery($strSql);
    }

    /**
     * Executes a database query and handles profiling if enabled.
     *
     * @param string $strQuery The SQL query to execute.
     * @return mixed The result of the query execution.
     * @throws Caller
     */
    public final function query(string $strQuery): mixed
    {
        $timerName = null;
        if (!$this->blnConnectedFlag) {
            $this->connect();
        }


        if ($this->blnEnableProfiling) {
            $timerName = 'queryExec' . mt_rand();
            Timer::start($timerName);
        }

        $result = $this->executeQuery($strQuery);

        if ($this->blnEnableProfiling) {
            $dblQueryTime = Timer::stop($timerName);
            Timer::reset($timerName);

            // Log Query (for Profiling, if applicable)
            $this->logQuery($strQuery, $dblQueryTime);
        }

        return $result;
    }

    /**
     * Executes a database non-query operation and handles profiling if enabled.
     *
     * @param string $strNonQuery The SQL non-query statement to execute.
     * @return mixed The result of the non-query execution.
     * @throws Caller
     */
    public final function nonQuery(string $strNonQuery): mixed
    {
        if (!$this->blnConnectedFlag) {
            $this->connect();
        }
        $timerName = '';
        if ($this->blnEnableProfiling) {
            $timerName = 'queryExec' . mt_rand();
            Timer::start($timerName);
        }

        $result = $this->executeNonQuery($strNonQuery);

        if ($this->blnEnableProfiling) {
            $dblQueryTime = Timer::stop($timerName);
            Timer::reset($timerName);

            // Log Query (for Profiling, if applicable)
            $this->logQuery($strNonQuery, $dblQueryTime);
        }

        return $result;
    }

    /**
     * Magic method to retrieve the value of a property dynamically.
     *
     * @param string $strName The name of the property to retrieve.
     * @return mixed The value of the specified property.
     * @throws Caller Thrown when the property is not defined or inaccessible.
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'EscapeIdentifierBegin':
                return $this->strEscapeIdentifierBegin;
            case 'EscapeIdentifierEnd':
                return $this->strEscapeIdentifierEnd;
            case 'EnableProfiling':
                return $this->blnEnableProfiling;
            case 'AffectedRows':
                return -1;
            case 'Profile':
                return $this->strProfileArray;
            case 'DatabaseIndex':
                return $this->intDatabaseIndex;
            case 'Adapter':
                $strConstantName = get_class($this) . '::ADAPTER';
                return constant($strConstantName) . ' (' . $this->objConfigArray['adapter'] . ')';
            case 'Server':
            case 'Port':
            case 'Database':
                // Informix naming
            case 'Service':
            case 'Protocol':
            case 'Host':

            case 'Username':
            case 'Password':
            case 'Caching':
                return $this->objConfigArray[strtolower($strName)];
            case 'DateFormat':
                return (is_null($this->objConfigArray[strtolower($strName)])) ? (QDateTime::FORMAT_ISO) : ($this->objConfigArray[strtolower($strName)]);
            case 'OnlyFullGroupBy':
                return (!isset($this->objConfigArray[strtolower($strName)])) ? $this->blnOnlyFullGroupBy : $this->objConfigArray[strtolower($strName)];

            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    /**
     * Sets the value of a property, handling specific cases or delegating to the parent implementation.
     *
     * @param string $strName The name of the property to set.
     * @param mixed $mixValue The value to assign to the property.
     * @return void
     * @throws Caller If the parent::__set method throws an exception.
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        switch ($strName) {
            case 'Caching':
                $this->objConfigArray[strtolower($strName)] = $mixValue;
                break;

            default:
                try {
                    parent::__set($strName, $mixValue);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    /**
     * Initializes a database connection object with the specified configuration.
     *
     * @param int $intDatabaseIndex The index of the database to initialize.
     * @param array $objConfigArray An array of configuration settings for the database connection.
     * @throws Caller
     * @throws InvalidCast
     */
    public function __construct(int $intDatabaseIndex, array $objConfigArray)
    {
        // Setup DatabaseIndex
        $this->intDatabaseIndex = $intDatabaseIndex;

        // Save the ConfigArray
        $this->objConfigArray = $objConfigArray;

        // Setup Profiling Array (if applicable)
        $this->blnEnableProfiling = Type::cast($objConfigArray['profiling'], Type::BOOLEAN);
        if ($this->blnEnableProfiling) {
            $this->strProfileArray = array();
        }
    }

    /**
     * Enables profiling for tracking and analyzing the execution of operations.
     *
     * @return void
     */
    public function enableProfiling(): void
    {
        // Only perform profiling initialization if profiling is not yet enabled
        if (!$this->blnEnableProfiling) {
            $this->blnEnableProfiling = true;
            $this->strProfileArray = array();
        }
    }

    /**
     * Logs query profiling information including backtrace and execution time.
     *
     * @param string $strQuery The SQL query that was executed.
     * @param float $dblQueryTime The time taken to execute the query, in seconds.
     * @return void
     */
    private function logQuery(string $strQuery, float $dblQueryTime): void
    {
        if ($this->blnEnableProfiling) {
            // Dereference-ize Backtrace Information
            $objDebugBacktrace = debug_backtrace();

            // get rid of unnecessary backtrace info in case of:
            // a query
            if ((count($objDebugBacktrace) > 3) &&
                (array_key_exists('function', $objDebugBacktrace[2])) &&
                (($objDebugBacktrace[2]['function'] == 'QueryArray') ||
                    ($objDebugBacktrace[2]['function'] == 'QuerySingle') ||
                    ($objDebugBacktrace[2]['function'] == 'QueryCount'))
            ) {
                $objBacktrace = $objDebugBacktrace[3];
            } else {
                $objBacktrace = $objDebugBacktrace[2] ?? $objDebugBacktrace[1];
            }

            // get rid of reference to the current object in a backtrace array
            if (isset($objBacktrace['object'])) {
                $objBacktrace['object'] = null;
            }

            for ($intIndex = 0, $intMax = count($objBacktrace['args']); $intIndex < $intMax; $intIndex++) {
                $obj = $objBacktrace['args'][$intIndex];

                if (is_null($obj)) {
                    $obj = 'null';
                } else {
                    if (gettype($obj) == 'integer') {
                    } else {
                        if (gettype($obj) == 'object') {
                            $obj = 'Object: ' . get_class($obj);
                            if (method_exists($obj, '__toString')) {
                                $obj .= '- ' . $obj;
                            }
                        } else {
                            if (is_array($obj)) {
                                $obj = 'Array';
                            } else {
                                $obj = sprintf("'%s'", $obj);
                            }
                        }
                    }
                }
                $objBacktrace['args'][$intIndex] = $obj;
            }

            // Push it onto the profiling information array
            $arrProfile = array(
                'objBacktrace' => $objBacktrace,
                'strQuery' => $strQuery,
                'dblTimeInfo' => $dblQueryTime
            );

            $this->strProfileArray[] = $arrProfile;
        }
    }

    /**
     * Converts a given value into an SQL-safe representation (string, numeric, boolean, date, array, etc.) with optional handling of equality expressions.
     *
     * @param mixed $mixData The data to be converted into an SQL variable. Accepts various types, including strings, numbers, booleans, dates, arrays, or null.
     * @param bool $blnIncludeEquality Whether to include an equality operator (=, =, IS, IS NOT) in the SQL representation.
     * @param bool $blnReverseEquality Whether to reverse the equality operator (!=, IS NOT instead of =, IS).
     * @return string The SQL-safe representation of the input value, ready for inclusion in an SQL query.
     */
    public function sqlVariable(mixed $mixData, bool $blnIncludeEquality = false, bool $blnReverseEquality = false): string
    {
        // Are we SqlRivalling a BOOLEAN value?
        if (is_bool($mixData)) {
            // Yes
            if ($blnIncludeEquality) {
                // We must include the inequality

                if ($blnReverseEquality) {
                    // Do a "Reverse Equality"

                    // Check against NULL, True then False
                    if (is_null($mixData)) {
                        return 'IS NOT NULL';
                    } else {
                        if ($mixData) {
                            return '= 0';
                        } else {
                            return '!= 0';
                        }
                    }
                } else {
                    // Check against NULL, True then False
                    if (is_null($mixData)) {
                        return 'IS NULL';
                    } else {
                        if ($mixData) {
                            return '!= 0';
                        } else {
                            return '= 0';
                        }
                    }
                }
            } else {
                // Check against NULL, True then False
                if (is_null($mixData)) {
                    return 'NULL';
                } else {
                    if ($mixData) {
                        return '1';
                    } else {
                        return '0';
                    }
                }
            }
        }

        // Check for Equality Inclusion
        if ($blnIncludeEquality) {
            if ($blnReverseEquality) {
                if (is_null($mixData)) {
                    $strToReturn = 'IS NOT ';
                } else {
                    $strToReturn = '!= ';
                }
            } else {
                if (is_null($mixData)) {
                    $strToReturn = 'IS ';
                } else {
                    $strToReturn = '= ';
                }
            }
        } else {
            $strToReturn = '';
        }

        // Check for NULL Value
        if (is_null($mixData)) {
            return $strToReturn . 'NULL';
        }

        // Check for NUMERIC Value
        if (is_integer($mixData) || is_float($mixData)) {
            return $strToReturn . sprintf('%s', $mixData);
        }

        // Check for DATE Value
        if ($mixData instanceof QDateTime) {
            if ($mixData->isTimeNull()) {
                if ($mixData->isDateNull()) {
                    return $strToReturn . 'NULL'; // null date and time is a null value
                }
                return $strToReturn . sprintf("'%s'", $mixData->qFormat('YYYY-MM-DD'));
            } elseif ($mixData->isDateNull()) {
                return $strToReturn . sprintf("'%s'", $mixData->qFormat('hhhh:mm:ss'));
            }
            return $strToReturn . sprintf("'%s'", $mixData->qFormat(QDateTime::FORMAT_ISO));
        }

        // An array. Assume we are using it in an array context, like an IN clause
        if (is_array($mixData)) {
            $items = [];
            foreach ($mixData as $item) {
                $items[] = $this->sqlVariable($item);    // recurse
            }
            return '(' . implode(',', $items) . ')';
        }

        // Assume it's some kind of string value
        return $strToReturn . sprintf("'%s'", addslashes($mixData));
    }

    /**
     * Prepares an SQL statement by replacing placeholders in the query with the provided parameters.
     *
     * @param string $strQuery The SQL query containing placeholders to be replaced.
     * @param array $mixParameterArray An associative array where keys correspond to the placeholders in the query,
     *                                  and values are the corresponding replacement values.
     * @return string The prepared SQL query with placeholders replaced by their respective values.
     */
    public function prepareStatement(string $strQuery, array $mixParameterArray): string
    {
        foreach ($mixParameterArray as $strKey => $mixValue) {
            if (is_array($mixValue)) {
                $strParameters = array();
                foreach ($mixValue as $mixParameter) {
                    $strParameters[] = $this->sqlVariable($mixParameter);
                }
                $strQuery = str_replace(chr(QCubed::NAMED_VALUE_DELIMITER) . '{' . $strKey . '}',
                    implode(',', $strParameters) . ')', $strQuery);
            } else {
                $strQuery = str_replace(chr(QCubed::NAMED_VALUE_DELIMITER) . '{=' . $strKey . '=}',
                    $this->sqlVariable($mixValue, true, false), $strQuery);
                $strQuery = str_replace(chr(QCubed::NAMED_VALUE_DELIMITER) . '{!' . $strKey . '!}',
                    $this->sqlVariable($mixValue, true, true), $strQuery);
                $strQuery = str_replace(chr(QCubed::NAMED_VALUE_DELIMITER) . '{' . $strKey . '}',
                    $this->sqlVariable($mixValue), $strQuery);
            }
        }

        return $strQuery;
    }

    /**
     * Outputs profiling information for the database connection if profiling is enabled.
     * Generates either an HTML snippet displaying the profiling information or outputs it directly.
     *
     * @param bool $blnPrintOutput Determines whether to directly output the profiling information (true)
     *                             or return it as a string (false).
     * @return string|null Returns the profiling information as a string if $blnPrintOutput is false,
     *                     otherwise, returns null after directly outputting the profiling information.
     */
    public function outputProfiling(bool $blnPrintOutput = true): ?string
    {
        $strPath = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];

        $strOut = '<div class="qDbProfile">';
        if ($this->blnEnableProfiling) {
            $strOut .= sprintf('<form method="post" id="frmDbProfile%s" action="%s/profile.php"><div>',
                $this->intDatabaseIndex, QCUBED_PHP_URL);
            $strOut .= sprintf('<input type="hidden" name="strProfileData" value="%s" />',
                base64_encode(serialize($this->strProfileArray)));
            $strOut .= sprintf('<input type="hidden" name="intDatabaseIndex" value="%s" />', $this->intDatabaseIndex);
            $strOut .= sprintf('<input type="hidden" name="strReferrer" value="%s" /></div></form>',
                htmlentities($strPath));

            $intCount = round(count($this->strProfileArray));
            if ($intCount == 0) {
                $strQueryString = 'No queries';
            } else {
                if ($intCount == 1) {
                    $strQueryString = '1 query';
                } else {
                    $strQueryString = $intCount . ' queries';
                }
            }

            $strOut .= sprintf('<b>PROFILING INFORMATION FOR DATABASE CONNECTION #%s</b>: %s performed.  Please <a href="#" onclick="var frmDbProfile = document.getElementById(\'frmDbProfile%s\'); frmDbProfile.target = \'_blank\'; frmDbProfile.submit(); return false;">click here to view profiling detail</a><br />',
                $this->intDatabaseIndex, $strQueryString, $this->intDatabaseIndex);
        } else {
            $strOut .= '<form></form><b>Profiling was not enabled for this database connection (#' . $this->intDatabaseIndex . ').</b>  To enable, ensure that ENABLE_PROFILING is set to TRUE.';
        }
        $strOut .= '</div>';

        $strOut .= '<script>$j(function() {$j(".qDbProfile").draggable();});</script>';    // make it draggable so you can move it out of the way if needed.

        if ($blnPrintOutput) {
            print ($strOut);
            return null;
        } else {
            return $strOut;
        }
    }

    /**
     * Executes the explain statement for a given query and returns the output without any transformation.
     * If the database adapter does not support EXPLAIN statements, it returns null.
     *
     * @param string $strSql
     *
     * @return MysqliResult|null
     */
    public function explainStatement(string $strSql): ?MysqliResult
    {
        return null;
    }


    /**
     * Utility function to extract the JSON embedded options structure from the comments.
     *
     * Usage:
     * <code>
     *    list($strComment, $options) = DatabaseBase::extractCommentOptions($strComment);
     * </code>
     *
     * @param string $strComment The comment to analyze
     * @return array A two-item array, with the first item the comment with the options removed, and the 2nd item the options array.
     *
     */
    public static function extractCommentOptions(string $strComment): array
    {
        $ret[0] = null; // comment string without options
        $ret[1] = null; // the option array
        if (($strComment) &&
            ($pos1 = strpos($strComment, '{')) !== false &&
            ($pos2 = strrpos($strComment, '}', $pos1))
        ) {

            $strJson = substr($strComment, $pos1, $pos2 - $pos1 + 1);
            $a = json_decode($strJson, true);

            if ($a) {
                $ret[0] = substr($strComment, 0, $pos1) . substr($strComment,
                        $pos2 + 1); // return comment without options
                $ret[1] = $a;
            } else {
                $ret[0] = $strComment;
            }
        }

        return $ret;
    }

}

