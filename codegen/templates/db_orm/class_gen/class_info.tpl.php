
    ///////////////////////////////////////////
    // METHODS TO EXTRACT INFO ABOUT THE CLASS
    ///////////////////////////////////////////

    /**
     * Static method to retrieve the table name that owns this class.
     * @return string Name of the table from which this class has been created.
     */
    public static function getTableName(): string
    {
        return "<?= $objTable->Name; ?>";
    }

    /**
     * Static method to retrieve the Database name from which this class has been created.
     * @return string Name of the database from which this class has been created.
     */
    public static function getDatabaseName(): string
    {
        return self::getDatabase()->Database;
    }

    /**
    * Static method to get the database index from configuration.inc.php.
    * This can be useful if there are two databases with the same name, which can cause
    * confusion for the developer. This function has no internal use, but it is
    * here to get the information if needed!
     * @return int position or index of the database in the config file.
     */
    public static function getDatabaseIndex(): int
    {
        return <?= $objCodeGen->DatabaseIndex; ?>;
    }

    /**
     * Return the base node corresponding to this table.
     * @return Node<?= $objTable->ClassName; ?>

     */
    public static function baseNode(): Node<?= $objTable->ClassName; ?>

    {
        return QQN::<?= $objTable->ClassName; ?>();
    }