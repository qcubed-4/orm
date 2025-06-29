<?php
// associated_object_type_manytomany.tpl
$objManyToManyReferenceTable = $objCodeGen->TypeTableArray[strtolower($objManyToManyReference->AssociatedTable)];
?>   
    // Related Many-to-Many Object Methods for <?= $objManyToManyReference->ObjectDescription; ?>

    //-------------------------------------------------------------------

    /**
    * Gets all many-to-many related <?= $objManyToManyReference->ObjectDescription; ?> objects as an array of <?= $objTable->ClassName ?> objects
    *
    * @param iClause[]|null $objOptionalClauses additional optional iClause objects for this query
    * @return string[] an array where keys are <?= strtolower($objTable->ClassName) ?> type IDs and values are the corresponding <?= $objManyToManyReference->ObjectDescription; ?> strings.
    * @throws Exception if optional clauses are passed to the method.
    */
    public function get<?= $objManyToManyReference->ObjectDescription; ?>Array(?array $objOptionalClauses = null): array
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            return array();

        if($objOptionalClauses)
            throw new Exception('Unable to call get<?= $objManyToManyReference->ObjectDescription; ?>Array with parameters.');

        $rowArray = array();

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName; ?>::getDatabase();

        $strQuery = sprintf("SELECT <?= $objManyToManyReference->OppositeColumn; ?> FROM <?= $objManyToManyReference->Table; ?> WHERE <?= $objManyToManyReference->Column; ?> = %s", $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>);

        // Perform the Query
        $objDbResult = $objDatabase->Query($strQuery);

        while ($mixRow = $objDbResult->FetchArray()) {
            $rowArray[$mixRow['<?= $objManyToManyReference->OppositeColumn; ?>']] =   <?= $objManyToManyReference->VariableType; ?>::ToString($mixRow['<?= $objManyToManyReference->OppositeColumn; ?>']);
        }

        return $rowArray;
    }

    /**
    * Count the number of <?= $objManyToManyReference->ObjectDescription; ?> associations linked to this <?= $objTable->ClassName ?>

    *
    * @return int The total number of associated <?= $objManyToManyReference->ObjectDescription; ?> records for this <?= $objTable->ClassName ?>

    * @throws Caller
    */
    public function count<?= $objManyToManyReference->ObjectDescriptionPlural; ?>(): int
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            return 0;

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName; ?>::getDatabase();

        $strQuery = sprintf("SELECT count(*) as total_count FROM <?= $objManyToManyReference->Table; ?> WHERE <?= $objManyToManyReference->Column; ?> = %s", $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>);

        // Perform the Query
        $objDbResult = $objDatabase->Query($strQuery);
        $row = $objDbResult->FetchArray();
        return $row['total_count'];
    }

    /**
    * Check if a specified <?= $objManyToManyReference->ObjectDescription; ?> is associated with this <?= $objTable->ClassName ?>

    *
    * @param int $intId The ID of the <?= $objManyToManyReference->ObjectDescription; ?> to check association with
    * @return bool True if the specified <?= $objManyToManyReference->ObjectDescription; ?> is associated, false otherwise
    * @throws Caller
    * @throws InvalidCast
    * @throws UndefinedPrimaryKey If this <?= $objTable->ClassName ?> object does not have a defined primary key.
    */
    public function is<?= $objManyToManyReference->ObjectDescription; ?>Associated(int $intId): bool
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            throw new UndefinedPrimaryKey('Unable to call is<?= $objManyToManyReference->ObjectDescription; ?>Associated on this unsaved <?= $objTable->ClassName; ?>.');


        $intRowCount = <?= $objTable->ClassName; ?>::queryCount(
            QQ::AndCondition(
                QQ::Equal(QQN::<?= $objTable->ClassName; ?>()-><?= $objTable->PrimaryKeyColumnArray[0]->PropertyName; ?>, $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>),
                QQ::Equal(QQN::<?= $objTable->ClassName; ?>()-><?= $objManyToManyReference->ObjectDescription; ?>-><?= $objManyToManyReference->OppositePropertyName; ?>, $intId )
            )
        );

        return ($intRowCount > 0);
    }

    /**
    * Associates one or more <?= $objManyToManyReference->ObjectDescription; ?> objects with the current <?= $objTable->ClassName; ?> object
    *
    * @param mixed $mixId A single <?= $objManyToManyReference->ObjectDescription; ?> ID or an array of <?= $objManyToManyReference->ObjectDescription; ?> IDs to associate with this <?= $objTable->ClassName; ?>

    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey if this <?= $objTable->ClassName; ?> object is not saved.
    */
    public function associate<?= $objManyToManyReference->ObjectDescription; ?>(mixed $mixId): void
    {

        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            throw new UndefinedPrimaryKey('Unable to call associate<?= $objManyToManyReference->ObjectDescription; ?> on this unsaved <?= $objTable->ClassName; ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName; ?>::getDatabase();

        if(!is_array($mixId)) {
            $mixId = array($mixId);
        }
        foreach ($mixId as $intId) {
            // Perform the SQL Query
            $objDatabase->NonQuery('
                INSERT INTO <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Table; ?><?= $strEscapeIdentifierEnd; ?> (
                    <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Column; ?><?= $strEscapeIdentifierEnd; ?>,
                    <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->OppositeColumn; ?><?= $strEscapeIdentifierEnd; ?>
                ) VALUES (
                    ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>) . ',
                    ' . $objDatabase->SqlVariable($intId) . '
                )
            ');
        }
    }

    /**
    * Unassociates one or more <?= $objManyToManyReference->ObjectDescription; ?>(s) from this <?= $objTable->ClassName; ?>

    *
    * @param mixed $mixId The ID or array of IDs representing the <?= $objManyToManyReference->ObjectDescription; ?>(s) to unassociate from this <?= $objTable->ClassName; ?>

    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If called on an unsaved <?= $objTable->ClassName; ?> object
    */
    public function unassociate<?= $objManyToManyReference->ObjectDescription; ?>(mixed $mixId): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            throw new UndefinedPrimaryKey('Unable to call unassociate<?= $objManyToManyReference->ObjectDescription; ?> on this unsaved <?= $objTable->ClassName; ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName; ?>::getDatabase();

        if(!is_array($mixId)) {
            $mixId = array($mixId);
        }
        foreach ($mixId as $intId) {
            // Perform the SQL Query
            $objDatabase->NonQuery('
                DELETE FROM
                    <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Table; ?><?= $strEscapeIdentifierEnd; ?>
                WHERE
                    <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Column; ?><?= $strEscapeIdentifierEnd; ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>) . ' AND
                    <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->OppositeColumn; ?><?= $strEscapeIdentifierEnd; ?> = ' . $objDatabase->SqlVariable($intId) . '
            ');
        }
    }

    /**
    * Unassociates all <?= $objManyToManyReference->ObjectDescription; ?> objects related to this <?= $objTable->ClassName; ?> object
    *
    * Removes all associations between this <?= $objTable->ClassName; ?> object and any associated <?= $objManyToManyReference->ObjectDescription; ?> objects in the database.
    *
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey if this <?= $objTable->ClassName; ?> object does not have a defined primary key.
    */
    public function unassociateAll<?= $objManyToManyReference->ObjectDescriptionPlural; ?>(): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', 'is_null($this->', ')', 'VariableName', $objTable->PrimaryKeyColumnArray); ?>)
            throw new UndefinedPrimaryKey('Unable to call unassociateAll<?= $objManyToManyReference->ObjectDescription; ?>Array on this unsaved <?= $objTable->ClassName; ?>.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName; ?>::getDatabase();

        // Perform the SQL Query
        $objDatabase->NonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Table; ?><?= $strEscapeIdentifierEnd; ?>
            WHERE
                <?= $strEscapeIdentifierBegin; ?><?= $objManyToManyReference->Column; ?><?= $strEscapeIdentifierEnd; ?> = ' . $objDatabase->SqlVariable($this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName; ?>) . '
        ');
    }