<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Mysqli5;


use Exception;
use MySqli;
use QCubed\Database\DatabaseBase;
use QCubed\Database\ForeignKey;
use QCubed\Database\Index;
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\QString;
use QCubed\Type;

if (!defined('MYSQLI_ON_UPDATE_NOW_FLAG')) {
    define('MYSQLI_ON_UPDATE_NOW_FLAG', 8192);
}

/**
 * Class MySqliDatabase
 */
class MysqliDatabase extends DatabaseBase
{
    const ADAPTER = 'MySql Improved Database Adapter for MySQL 5';

    /** @var  MySqli */
    protected MySqli $objMySqli;

    protected string $strEscapeIdentifierBegin = '`';
    protected string $strEscapeIdentifierEnd = '`';

    /**
     * Generates and returns the SQL_CALC_FOUND_ROWS directive if the configuration specifies its usage.
     * This method determines whether a prefix keyword is needed for SQL limit queries, primarily used in MySQL.
     *
     * @param string $strLimitInfo Information regarding SQL limit handling.
     * @return string|null Returns 'SQL_CALC_FOUND_ROWS' if the configuration specifies its usage; otherwise, returns null.
     */
    public function sqlLimitVariablePrefix(string $strLimitInfo): ?string
    {
        // MySQL uses Limit by Suffixes (via a LIMIT clause)

        // If requested, use SQL_CALC_FOUND_ROWS directive to utilize GetFoundRows() method
        if (array_key_exists('usefoundrows', $this->objConfigArray) && $this->objConfigArray['usefoundrows']) {
            return 'SQL_CALC_FOUND_ROWS';
        }

        return null;
    }

    /**
     * Generates a SQL LIMIT clause suffix based on the provided limit information.
     *
     * @param string $strLimitInfo The limit information to append. Should not contain semicolons or backticks.
     * @return string|null Returns the LIMIT clause as a string if valid or null if no limit information is provided.
     * @throws Exception Thrown if the limit information contains invalid characters such as semicolons or backticks.
     */
    public function sqlLimitVariableSuffix(string $strLimitInfo): ?string
    {
        // Setup limit suffix (if applicable) via a LIMIT clause
        if (strlen($strLimitInfo)) {
            if (str_contains($strLimitInfo, ';')) {
                throw new Exception('Invalid Semicolon in LIMIT Info');
            }
            if (str_contains($strLimitInfo, '`')) {
                throw new Exception('Invalid Backtick in LIMIT Info');
            }
            return "LIMIT $strLimitInfo";
        }

        return null;
    }

    /**
     * Generates an ORDER BY clause based on the provided sorting information.
     * Throws an exception if the sorting information contains invalid characters such as
     * semicolons or backticks, which may be used for SQL injection.
     *
     * @param string $strSortByInfo Sorting criteria to be included in the ORDER BY clause.
     * @return string|null Returns the ORDER BY clause as a string if valid sorting information is provided, otherwise null.
     * @throws Exception If the sorting information contains a semicolon (;) or backtick (`).
     */
    public function sqlSortByVariable(string $strSortByInfo): ?string
    {
        // Setup sorting information (if applicable) via an ORDER BY clause
        if (strlen($strSortByInfo)) {
            if (str_contains($strSortByInfo, ';')) {
                throw new Exception('Invalid Semicolon in ORDER BY Info');
            }
            if (str_contains($strSortByInfo, '`')) {
                throw new Exception('Invalid Backtick in ORDER BY Info');
            }

            return "ORDER BY $strSortByInfo";
        }

        return null;
    }

    /**
     * Inserts a new record into the specified table or updates an existing record if a primary key conflict occurs.
     *
     * @param string $strTable The name of the target table.
     * @param array $mixColumnsAndValuesArray An associative array of column names and their corresponding values to insert or update.
     * @param array|string|null $strPKNames Optional. The name(s) of the primary key column(s). If not provided, the method will use the table's defined primary key.
     * @return void
     * @throws MysqliException
     */
    public function insertOrUpdate(string $strTable, array $mixColumnsAndValuesArray, mixed $strPKNames = null): void
    {
        $strEscapedArray = $this->escapeIdentifiersAndValues($mixColumnsAndValuesArray);
        $strUpdateStatement = '';
        foreach ($strEscapedArray as $strColumn => $strValue) {
            if ($strUpdateStatement) {
                $strUpdateStatement .= ', ';
            }
            $strUpdateStatement .= $strColumn . ' = ' . $strValue;
        }
        $strSql = sprintf('INSERT INTO %s%s%s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $this->EscapeIdentifierBegin, $strTable, $this->EscapeIdentifierEnd,
            implode(', ', array_keys($strEscapedArray)),
            implode(', ', array_values($strEscapedArray)),
            $strUpdateStatement
        );
        $this->executeNonQuery($strSql);
    }


    /**
     * Establishes a connection to the database server using the provided configuration parameters.
     * Throws an exception if the connection fails or an error occurs during the connection process.
     *
     * @return void
     * @throws MysqliException|Caller if unable to connect to the database, or if an error occurs during the connection initialization.
     */
    public function connect(): void
    {
        // Connect to the Database Server
        $this->objMySqli = new MySqli($this->Server, $this->Username, $this->Password, $this->Database, $this->Port);

        if (!$this->objMySqli) {
            throw new MysqliException("Unable to connect to Database", -1, null);
        }

        if ($this->objMySqli->error) {
            throw new MysqliException($this->objMySqli->error, $this->objMySqli->errno, null);
        }

        // Update "Connected" Flag
        $this->blnConnectedFlag = true;

        // Set to AutoCommit
        $this->nonQuery('SET AUTOCOMMIT=1;');

        // Set NAMES (if applicable)
        if (array_key_exists('encoding', $this->objConfigArray)) {
            $this->nonQuery('SET NAMES ' . $this->objConfigArray['encoding'] . ';');
        }
    }

    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'AffectedRows':
                return $this->objMySqli->affected_rows;
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
     * Executes the given SQL query and returns the result.
     *
     * @param string $strQuery The SQL query to be executed.
     * @return MysqliResult The result object containing the query results.
     * @throws MysqliException If an error occurs during query execution.
     */
    protected function executeQuery(string $strQuery): MysqliResult
    {
        // Perform the Query
        $objResult = $this->objMySqli->query($strQuery);
        if ($this->objMySqli->error) {
            throw new MysqliException($this->objMySqli->error, $this->objMySqli->errno, $strQuery);
        }

        // Return the Result
        return new MysqliResult($objResult, $this);
    }

    /**
     * Executes a non-query SQL statement (e.g., INSERT, UPDATE, DELETE).
     * If an error occurs during execution, a MysqliException is thrown.
     *
     * @param string $strNonQuery The SQL query to execute, which should not return a result set.
     * @return MysqliResult
     * @throws MysqliException If a database error occurs during execution.
     */
    protected function executeNonQuery(string $strNonQuery): MysqliResult
    {
        // Perform the Query
        $this->objMySqli->query($strNonQuery);
        if ($this->objMySqli->error) {
            throw new MysqliException($this->objMySqli->error, $this->objMySqli->errno, $strNonQuery);
        }

        return new MysqliResult(null, $this);
    }

    /**
     * Retrieves a list of all tables in the current database.
     *
     * @return string[] An array of table names.
     * @throws Caller
     */
    public function getTables(): array
    {
        // Use the MySQL "SHOW TABLES" functionality to get a list of all the tables in this database
        $objResult = $this->query("SHOW TABLES");
        $strToReturn = array();
        while ($strRowArray = $objResult->fetchRow()) {
            $strToReturn[] = $strRowArray[0];
        }
        return $strToReturn;
    }

    /**
     * Retrieves the fields of a given table by executing a query that fetches a single row from the table.
     *
     * @param string $strTableName The name of the table for which to retrieve the fields.
     * @return mixed The fetched fields for the specified table.
     * @throws Caller
     */
    public function getFieldsForTable(string $strTableName): mixed
    {
        $objResult = $this->query(sprintf('SELECT * FROM %s%s%s LIMIT 1', $this->strEscapeIdentifierBegin,
            $strTableName, $this->strEscapeIdentifierEnd));
        return $objResult->fetchFields();
    }

    /**
     * Retrieves the ID generated by the last INSERT operation for an optional table and column.
     *
     * @param string|null $strTableName The optional name of the table associated with the insert operation.
     * @param string|null $strColumnName The optional name of the column associated with the insert operation.
     * @return string|int The ID generated for the last INSERT operation.
     */
    public function insertId(?string $strTableName = null, ?string $strColumnName = null): string|int
    {
        return $this->objMySqli->insert_id;
    }

    /**
     * Closes the current MySQLi connection and updates the connected flag to indicate the disconnection state.
     *
     * @return void
     */
    public function close(): void
    {
        $this->objMySqli->close();

        // Update Connected Flag
        $this->blnConnectedFlag = false;
    }

    /**
     * Initiates the beginning of a database transaction by disabling auto-commit mode.
     *
     * @return void
     * @throws Caller
     */
    protected function executeTransactionBegin(): void
    {
        // Set to AutoCommit
        $this->nonQuery('SET AUTOCOMMIT=0;');
    }

    /**
     * Commits the current database transaction and resets the connection to autocommit mode.
     *
     * @return void This method does not return a value.
     * @throws Caller
     */
    protected function executeTransactionCommit(): void
    {
        $this->nonQuery('COMMIT;');
        // Set to AutoCommit
        $this->nonQuery('SET AUTOCOMMIT=1;');
    }

    /**
     * Executes a transaction rollback and resets the database connection to autocommit mode.
     *
     * @return void
     * @throws Caller
     */
    protected function executeTransactionRollBack(): void
    {
        $this->nonQuery('ROLLBACK;');
        // Set to AutoCommit
        $this->nonQuery('SET AUTOCOMMIT=1;');
    }

    /**
     * Retrieves the number of rows found by the last executed query, provided the "usefoundrows" configuration is enabled.
     * Throws an exception if the configuration is not set or set to false.
     *
     * @return mixed The number of rows found as returned by the database.
     * @throws Caller If the "usefoundrows" configuration is not enabled.
     */
    public function getFoundRows(): mixed
    {
        if (array_key_exists('usefoundrows', $this->objConfigArray) && $this->objConfigArray['usefoundrows']) {
            $objResult = $this->query('SELECT FOUND_ROWS();');
            $strRow = $objResult->fetchArray();
            return $strRow[0];
        } else {
            throw new Caller('Cannot call GetFoundRows() on the database when the "usefoundrows" configuration was not set to true.');
        }
    }

    /**
     * Retrieves the indexes of a specified table by determining the table type and parsing its creation statement.
     *
     * @param string $strTableName The name of the table for which to retrieve the indexes.
     * @return array The parsed indexes for the specified table.
     * @throws Exception If the table type is not supported.
     */
    public function getIndexesForTable(string $strTableName): array
    {
        // Figure out the Table Type (InnoDB, MyISAM, etc.) by parsing the Create Table description
        $strCreateStatement = $this->getCreateStatementForTable($strTableName);
        $strTableType = $this->getTableTypeForCreateStatement($strCreateStatement);

        return match (true) {
            str_starts_with($strTableType, 'INNODB'), str_starts_with($strTableType, 'HEAP'), str_starts_with($strTableType, 'MEMORY'), str_starts_with($strTableType, 'MYISAM') => $this->parseForIndexes($strCreateStatement),
            default => throw new Exception("Table Type is not supported: $strTableType"),
        };
    }

    /**
     * Retrieves the foreign keys for a given table by analyzing its creation table statement
     * and determining the table type.
     *
     * @param string $strTableName The name of the table for which to retrieve foreign keys.
     * @return array An array of foreign key definitions for the specified table.
     * @throws Exception If the table type is not supported.
     */
    public function getForeignKeysForTable(string $strTableName): array
    {
        // Figure out the Table Type (InnoDB, MyISAM, etc.) by parsing the Create Table description
        $strCreateStatement = $this->getCreateStatementForTable($strTableName);
        $strTableType = $this->getTableTypeForCreateStatement($strCreateStatement);

        return match (true) {
            str_starts_with($strTableType, 'MYISAM') => array(),
            str_starts_with($strTableType, 'MEMORY'), str_starts_with($strTableType, 'HEAP') => array(),
            str_starts_with($strTableType, 'INNODB') => $this->parseForInnoDbForeignKeys($strCreateStatement),
            default => throw new Exception("Table Type is not supported: $strTableType"),
        };
    }

    // MySql defines KeyDefinition to be [OPTIONAL_NAME] ([COL], ...)
    // If the key name exists, this will parse it out and return it
    /**
     * Parses and extracts the name of a key from a given key definition string.
     *
     * @param string $strKeyDefinition The key definition string to be parsed.
     * @return string|null The extracted key name, or null if no key name is defined.
     * @throws Exception If the key definition is invalid or improperly formatted.
     */
    private function parseNameFromKeyDefinition(string $strKeyDefinition): ?string
    {
        $strKeyDefinition = trim($strKeyDefinition);

        $intPosition = strpos($strKeyDefinition, '(');

        if ($intPosition === false) {
            throw new Exception("Invalid Key Definition: $strKeyDefinition");
        } else {
            if ($intPosition == 0) // No Key Name Defined
            {
                return null;
            }
        }

        // If we're here, then we have a key name defined
        $strName = trim(substr($strKeyDefinition, 0, $intPosition));

        // Rip Out leading and trailing "`" character (if applicable)
        if (str_starts_with($strName, '`')) {
            return substr($strName, 1, strlen($strName) - 2);
        } else {
            return $strName;
        }
    }

    // MySql defines KeyDefinition to be [OPTIONAL_NAME] ([COL], ...)
    // This will return an array of strings that are the names [COL], etc.
    /**
     * Parses a key definition string to extract an array of column names.
     *
     * @param string $strKeyDefinition The key definition string from which column names will be extracted.
     * @return array The array of column names extracted from the key definition.
     * @throws Exception If the key definition is invalid and cannot be processed.
     */
    private function parseColumnNameArrayFromKeyDefinition(string $strKeyDefinition): array
    {
        $strKeyDefinition = trim($strKeyDefinition);

        // Get rid of the opening "(" and the closing ")"
        $intPosition = strpos($strKeyDefinition, '(');
        if ($intPosition === false) {
            throw new Exception("Invalid Key Definition: $strKeyDefinition");
        }
        $strKeyDefinition = trim(substr($strKeyDefinition, $intPosition + 1));

        $intPosition = strpos($strKeyDefinition, ')');
        if ($intPosition === false) {
            throw new Exception("Invalid Key Definition: $strKeyDefinition");
        }
        $strKeyDefinition = trim(substr($strKeyDefinition, 0, $intPosition));

        // Create the Array
        // TODO: Current method doesn't support key names with commas or parenthesis in them!
        $strToReturn = explode(',', $strKeyDefinition);

        // Take out the trailing and leading "`" character in each name (if applicable)
        for ($intIndex = 0; $intIndex < count($strToReturn); $intIndex++) {
            $strColumn = $strToReturn[$intIndex];

            if (str_starts_with($strColumn, '`')) {
                $strColumn = substr($strColumn, 1, strpos($strColumn, '`', 1) - 1);
            }

            $strToReturn[$intIndex] = $strColumn;
        }

        return $strToReturn;
    }

    private function parseForIndexes($strCreateStatement): array
    {
        // MySql nicely splits each object in a table into its own line
        // Split the creation statement into lines, and then pull out anything
        // that says "PRIMARY KEY", "UNIQUE KEY", or just plain ol' "KEY"
        $strLineArray = explode("\n", $strCreateStatement);

        $objIndexArray = array();

        // We don't care about the first line or the last line
        for ($intIndex = 1; $intIndex < (count($strLineArray) - 1); $intIndex++) {
            $strLine = $strLineArray[$intIndex];

            // Each object has a two-space indent,
            // So this is a key object if any of those key-related words exist at position 2
            switch (2) {
                case (strpos($strLine, 'PRIMARY KEY')):
                    $strKeyDefinition = substr($strLine, strlen('  PRIMARY KEY '));

                    $strKeyName = $this->parseNameFromKeyDefinition($strKeyDefinition);
                    $strColumnNameArray = $this->parseColumnNameArrayFromKeyDefinition($strKeyDefinition);

                    $objIndex = new Index($strKeyName, $blnPrimaryKey = true, $blnUnique = true, $strColumnNameArray);
                    $objIndexArray[] = $objIndex;
                    break;

                case (strpos($strLine, 'UNIQUE KEY')):
                    $strKeyDefinition = substr($strLine, strlen('  UNIQUE KEY '));

                    $strKeyName = $this->parseNameFromKeyDefinition($strKeyDefinition);
                    $strColumnNameArray = $this->parseColumnNameArrayFromKeyDefinition($strKeyDefinition);

                    $objIndex = new Index($strKeyName, $blnPrimaryKey = false, $blnUnique = true, $strColumnNameArray);
                    $objIndexArray[] = $objIndex;
                    break;

                case (strpos($strLine, 'KEY')):
                    $strKeyDefinition = substr($strLine, strlen('  KEY '));

                    $strKeyName = $this->parseNameFromKeyDefinition($strKeyDefinition);
                    $strColumnNameArray = $this->parseColumnNameArrayFromKeyDefinition($strKeyDefinition);

                    $objIndex = new Index($strKeyName, $blnPrimaryKey = false, $blnUnique = false, $strColumnNameArray);
                    $objIndexArray[] = $objIndex;
                    break;
            }
        }

        return $objIndexArray;
    }

    /**
     * Parses a CREATE TABLE statement for InnoDB foreign key definitions and extracts them.
     *
     * @param string $strCreateStatement The CREATE TABLE statement used to define the database table and its constraints.
     * @return array An array of ForeignKey objects representing the foreign keys defined in the CREATE TABLE statement.
     * @throws Exception If a foreign key definition has invalid or mismatched column definitions.
     */
    private function parseForInnoDbForeignKeys(string $strCreateStatement): array
    {
        // MySql nicely splits each object in a table into its own line
        // Split the creation statement into lines, and then pull out anything
        // that starts with "CONSTRAINT" and contains "FOREIGN KEY"
        $strLineArray = explode("\n", $strCreateStatement);

        $objForeignKeyArray = array();

        // We don't care about the first line or the last line
        for ($intIndex = 1; $intIndex < (count($strLineArray) - 1); $intIndex++) {
            $strLine = $strLineArray[$intIndex];

            // Check to see if the line:
            // * Starts with "CONSTRAINT" at position 2 AND
            // * contains "FOREIGN KEY"
            if ((strpos($strLine, "CONSTRAINT") == 2) &&
                (str_contains($strLine, "FOREIGN KEY"))
            ) {
                $strLine = substr($strLine, strlen('  CONSTRAINT '));

                // By the end of the following lines, we will end up with strTokenArray
                // Index 0: the FK name
                // Index 1: the list of columns that are the foreign key
                // Index 2: the table which this FK references
                // Index 3: the list of columns which this FK references
                $strTokenArray = explode(' FOREIGN KEY ', $strLine);
                $strTokenArray[1] = explode(' REFERENCES ', $strTokenArray[1]);
                $strTokenArray[2] = $strTokenArray[1][1];
                $strTokenArray[1] = $strTokenArray[1][0];
                $strTokenArray[2] = explode(' ', $strTokenArray[2]);
                $strTokenArray[3] = $strTokenArray[2][1];
                $strTokenArray[2] = $strTokenArray[2][0];

                // Clean up and change Index 1 and Index 3 to be an array based on the
                // parsed column name list
                if (str_starts_with($strTokenArray[0], '`')) {
                    $strTokenArray[0] = substr($strTokenArray[0], 1, strlen($strTokenArray[0]) - 2);
                }
                $strTokenArray[1] = $this->parseColumnNameArrayFromKeyDefinition($strTokenArray[1]);
                if (str_starts_with($strTokenArray[2], '`')) {
                    $strTokenArray[2] = substr($strTokenArray[2], 1, strlen($strTokenArray[2]) - 2);
                }
                $strTokenArray[3] = $this->parseColumnNameArrayFromKeyDefinition($strTokenArray[3]);

                // Create the FK object and add it to the return array
                $objForeignKey = new ForeignKey($strTokenArray[0], $strTokenArray[1], $strTokenArray[2],
                    $strTokenArray[3]);
                $objForeignKeyArray[] = $objForeignKey;

                // Ensure the FK object has matching column numbers (or else, throw)
                if ((count($objForeignKey->ColumnNameArray) == 0) ||
                    (count($objForeignKey->ColumnNameArray) != count($objForeignKey->ReferenceColumnNameArray))
                ) {
                    throw new Exception("Invalid Foreign Key definition: $strLine");
                }
            }
        }
        return $objForeignKeyArray;
    }

    /**
     * Retrieves the CREATE statement for a specified table using the MySQL "SHOW CREATE TABLE" functionality.
     *
     * @param string $strTableName The name of the table for which to retrieve the CREATE statement.
     * @return array|string The CREATE statement for the specified table.
     * @throws Caller
     */
    private function getCreateStatementForTable(string $strTableName): array|string
    {
        // Use the MySQL "SHOW CREATE TABLE" functionality to get the table's Create statement
        $objResult = $this->query(sprintf('SHOW CREATE TABLE `%s`', $strTableName));
        $objRow = $objResult->fetchRow();
        $strCreateTable = $objRow[1];
        return str_replace("\r", "", $strCreateTable);
    }

    /**
     * Extracts and returns the table type from a given SQL CREATE statement string.
     *
     * @param string $strCreateStatement The SQL CREATE statement from which the table type is to be extracted.
     * @return string The extracted table type from the CREATE statement.
     * @throws Exception If the table type cannot be determined from the statement.
     */
    private function getTableTypeForCreateStatement(string $strCreateStatement): string
    {
        // Table Type is in the last line of the Create Statement, "TYPE=DbTableType"
        $strLineArray = explode("\n", $strCreateStatement);
        $strFinalLine = strtoupper($strLineArray[count($strLineArray) - 1]);

        if (str_starts_with($strFinalLine, ') TYPE=')) {
            return trim(substr($strFinalLine, 7));
        } else {
            if (str_starts_with($strFinalLine, ') ENGINE=')) {
                return trim(substr($strFinalLine, 9));
            } else {
                throw new Exception("Invalid Table Description");
            }
        }
    }

    /**
     *
     * @param string $sql
     * @return MysqliResult|null
     * @throws Caller
     * @throws InvalidCast
     */
    public function explainStatement(string $sql): ?MysqliResult
    {
        // As of MySQL 5.6.3, EXPLAIN provides information about
        // SELECT, DELETE, INSERT, REPLACE, and UPDATE statements.
        // Before MySQL 5.6.3, EXPLAIN provides information only about SELECT statements.

        if (!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE)\b/i', $sql)) {
            return null;
        }

        $objDbResult = $this->query("select version()");
        $strDbRow = $objDbResult->fetchRow();
        $strVersion = Type::cast($strDbRow[0], Type::STRING);
        $strVersionArray = explode('.', $strVersion);
        $strMajorVersion = null;
        if (count($strVersionArray) > 0) {
            $strMajorVersion = $strVersionArray[0];
        }
        if (null === $strMajorVersion) {
            return null;
        }
        if (intval($strMajorVersion) > 5) {
            return $this->query("EXPLAIN " . $sql);
        } else {
            if (5 == intval($strMajorVersion)) {
                $strMinorVersion = null;
                if (count($strVersionArray) > 1) {
                    $strMinorVersion = $strVersionArray[1];
                }
                if (null === $strMinorVersion) {
                    return null;
                }
                if (intval($strMinorVersion) > 6) {
                    return $this->query("EXPLAIN " . $sql);
                } else {
                    if (6 == intval($strMinorVersion)) {
                        $strSubMinorVersion = null;
                        if (count($strVersionArray) > 2) {
                            $strSubMinorVersion = $strVersionArray[2];
                        }
                        if (null === $strSubMinorVersion) {
                            return null;
                        }
                        if (!QString::isInteger($strSubMinorVersion)) {
                            $strSubMinorVersionArray = explode("-", $strSubMinorVersion);
                            if (count($strSubMinorVersionArray) > 1) {
                                $strSubMinorVersion = $strSubMinorVersionArray[0];
                                if (!is_integer($strSubMinorVersion)) {
                                    // Failed to determine the sub-minor version.
                                    return null;
                                }
                            } else {
                                // Failed to determine the sub-minor version.
                                return null;
                            }
                        }
                        if (intval($strSubMinorVersion) > 2) {
                            return $this->query("EXPLAIN " . $sql);
                        } else {
                            // We have the version before 5.6.3
                            // let's check if it is a SELECT-only request
                            if (0 == substr_count($sql, "DELETE") &&
                                0 == substr_count($sql, "INSERT") &&
                                0 == substr_count($sql, "REPLACE") &&
                                0 == substr_count($sql, "UPDATE")
                            ) {
                                return $this->query("EXPLAIN " . $sql);
                            }
                        }
                    }
                }
            }
        }
        // Return null by default
        return null;
    }
}