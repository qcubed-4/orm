    /**
    * Deletes the current record from the database. Ensures that the record's primary key is set before proceeding
    * with the deletion. This method also updates any associated cache and broadcasts the deletion event.
    *
    * @return void
    * @throws Caller
    * @throws UndefinedPrimaryKey If the primary key of the record is unset.
    */
    public function delete(): void
    {
        if (<?= $objCodeGen->ImplodeObjectArray(' || ', '(is_null($this->', '))', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)
            throw new UndefinedPrimaryKey('Cannot delete this <?= $objTable->ClassName ?> with an unset primary key.');

        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

<?php foreach ($objTable->ReverseReferenceArray as $objReverseReference) { ?>
<?php if ($objReverseReference->Unique) { ?>
<?php if (!$objReverseReference->NotNull) { ?>
<?php $objReverseReferenceTable = $objCodeGen->TableArray[strtolower($objReverseReference->Table)]; ?>
<?php $objReverseReferenceColumn = $objReverseReferenceTable->ColumnArray[strtolower($objReverseReference->Column)]; ?>
        // Update the adjoined <?= $objReverseReference->ObjectDescription ?> object (if applicable) and perform the unassociation

        // Optional -- if you **KNOW** that you do not want to EVER run any level of business logic on the disassociation,
        // you *could* override delete() so that this step can be a single hard-coded query to optimize performance.
        if ($objAssociated = <?= $objReverseReference->VariableType ?>::loadBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)) {
            $objAssociated-><?= $objReverseReferenceColumn->PropertyName ?> = null;
            $objAssociated->save();
        }
<?php } ?><?php if ($objReverseReference->NotNull) { ?>
<?php $objReverseReferenceTable = $objCodeGen->TableArray[strtolower($objReverseReference->Table)]; ?>
<?php $objReverseReferenceColumn = $objReverseReferenceTable->ColumnArray[strtolower($objReverseReference->Column)]; ?>


        // Update the adjoined <?= $objReverseReference->ObjectDescription ?> object (if applicable) and perform a delete

        // Optional -- if you **KNOW** that you do not want to EVER run any level of business logic on the disassociation,
        // you *could* override Delete() so that this step can be a single hard coded query to optimize performance.
        if ($objAssociated = <?= $objReverseReference->VariableType ?>::loadBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)) {
            $objAssociated->Delete();
        }
<?php } ?>
<?php } ?>
<?php } ?>
        // Perform the SQL Query
        $objDatabase->NonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

            WHERE
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->PrimaryKey) { ?>
                <?= $strEscapeIdentifierBegin ?><?= $objColumn->Name ?><?= $strEscapeIdentifierEnd ?> = ' . $objDatabase->SqlVariable($this-><?= $objColumn->VariableName ?>) . ' AND
<?php } ?>
<?php } ?><?php GO_BACK(5); ?>');

        $this->deleteFromCache();
        static::broadcastDelete($this->primaryKey());
    }

    /**
    * Delete all records from the '<?= strtolower($objTable->ClassName) ?>' table.
    *
    * @return void No value is returned as this method performs a deletion operation.
    * @throws Caller
    */
    public static function deleteAll(): void
    {
        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the Query
        $objDatabase->NonQuery('
            DELETE FROM
                <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>');

        static::clearCache();
        static::broadcastDeleteAll();
    }

    /**
    * Truncates all data in the '<?= strtolower($objTable->ClassName) ?>' table.
    *
    * @return void
    * @throws Caller
    */
    public static function truncate(): void
    {
        // Get the Database Object for this Class
        $objDatabase = <?= $objTable->ClassName ?>::getDatabase();

        // Perform the Query
        $objDatabase->NonQuery('
            TRUNCATE <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>');

        static::clearCache();
        static::broadcastDeleteAll();
    }