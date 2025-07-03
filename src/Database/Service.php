<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database;

use Exception;
use QCubed\ObjectBase;

/**
 * Class Service
 *
 * This service initializes and provides the singleton instances of the databases in use. This was
 * provided by Application static variables in older versions.
 *
 * The order of the databases is taken from the config file and is very important to not change after
 * codegen. You must codegen again if they change.
 *
 * @package QCubed\Database
 */
class Service extends ObjectBase
{

    protected static array $Database = [];

    /**
     * This call will initialize the database connection(s) as defined by
     * the constants DB_CONNECTION_X, where "X" is the index number of a
     * particular database connection.
     *
     * @throws Exception
     * @return void
     */
    public static function initializeDatabaseConnections(): void
    {
        // for backward compatibility, don't use MAX_DB_CONNECTION_INDEX directly,
        // but check if MAX_DB_CONNECTION_INDEX is defined
        $intMaxIndex = defined('MAX_DB_CONNECTION_INDEX') ? constant('MAX_DB_CONNECTION_INDEX') : 9;

        if (defined('DB_CONNECTION_0')) {
            // This causes a conflict with how DbBackedSessionHandler works.
            throw new Exception('Do not define DB_CONNECTION_0. Start at DB_CONNECTION_1');
        }

        for ($intIndex = 1; $intIndex <= $intMaxIndex; $intIndex++) {
            $strConstantName = sprintf('DB_CONNECTION_%s', $intIndex);

            if (defined($strConstantName)) {
                // Expected Keys to be Set
                $strExpectedKeys = array(
                    'adapter',
                    'server',
                    'port',
                    'database',
                    'username',
                    'password',
                    'profiling',
                    'dateformat'
                );

                // Look up the Serialized Array from the DB CONFIG constants and unserialize it
                $strSerialArray = constant($strConstantName);
                $objConfigArray = unserialize($strSerialArray);

                // Set All Expected Keys
                foreach ($strExpectedKeys as $strExpectedKey) {
                    if (!array_key_exists($strExpectedKey, $objConfigArray)) {
                        $objConfigArray[$strExpectedKey] = null;
                    }
                }

                if (!$objConfigArray['adapter']) {
                    throw new Exception('No Adapter Defined for ' . $strConstantName . ': ' . var_export($objConfigArray,
                            true));
                }

                if (!$objConfigArray['server']) {
                    throw new Exception('No Server Defined for ' . $strConstantName . ': ' . constant($strConstantName));
                }

                $strDatabaseType = 'QCubed\\Database\\' . $objConfigArray['adapter'] . '\\Database';

                // If (!class_exists($strDatabaseType)) {
                //	throw new \Exception('Database adapter was not found: '. $objConfigArray['adapter']);
                //}

                self::$Database[$intIndex] = new $strDatabaseType($intIndex, $objConfigArray);
            }
        }
    }

    /**
     * Retrieves the database instance at the specified index.
     *
     * @param int $intIndex The index of the database instance to retrieve.
     * @return mixed Returns the database instance if it exists, or null otherwise.
     */
    public static function getDatabase(int $intIndex): mixed
    {
        return self::$Database[$intIndex] ?? null;
    }

    /**
     * Checks whether the database instances are initialized.
     *
     * @return bool Returns true if the database instances are initialized, otherwise false.
     */
    public static function isInitialized(): bool
    {
        return !empty(self::$Database);
    }

    /**
     * Counts the total number of database instances.
     *
     * @return int Returns the number of database instances currently available.
     */
    public static function count(): int
    {
        return count(self::$Database);
    }
}