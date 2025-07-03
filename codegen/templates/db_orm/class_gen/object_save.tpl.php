<?php
$idsAsSql = [];
$idsAsParams = [];
foreach ($objTable->PrimaryKeyColumnArray as $objPkColumn) {
    $strLocal = '$this->' . ($objPkColumn->Identity ? '' : '__') . $objPkColumn->VariableName;
    $strCol = $strEscapeIdentifierBegin . $objPkColumn->Name . $strEscapeIdentifierEnd;
    $strValue = '$objDatabase->SqlVariable(' . $strLocal . ')';
    $idsAsSql[] = $strCol . ' = \' . ' . $strValue;
    $idsAsParams[] = $strLocal;
}

$strIds = implode(" . ' AND \n", $idsAsSql);
$strIdsAsParams = implode(", ", $idsAsParams);

foreach ($objTable->ColumnArray as $objColumn) {
    if ($objColumn->Timestamp) {
        $timestampColumn = $objColumn;
    }
    if ($objColumn->Identity) {
        $identityColumn = $objColumn;
    }
}

$blnHasUniqueReverseReference = false;
foreach ($objTable->ReverseReferenceArray as $objReverseReference) {
    if ($objReverseReference->Unique) {
        $blnHasUniqueReverseReference = true;
        break;
    }
}
?>

    /**
    * Save the current object to the database, inserting or updating as necessary.
    *
    * @param bool|null $blnForceInsert If true, forces an INSERT operation even if the object appears to already exist.
    * @param bool|null $blnForceUpdate If true, forces an UPDATE operation even if the object appears to be new.
    * @return int|null The ID of the newly inserted record, or null if no ID is generated (e.g., for updates).
    * @throws Caller
    */
<?php
$returnType = 'void';
foreach ($objArray = $objTable->ColumnArray as $objColumn) {
    if ($objColumn->Identity) {
        $returnType = 'int';
        break;
    }
}
//print '    * @return ' . $returnType;

$strCols = '';
$strValues = '';
$strColUpdates = '';
foreach ($objTable->ColumnArray as $objColumn) {
    if ((!$objColumn->Identity) &&
        !($objColumn->Timestamp && !$objColumn->AutoUpdate)
    ) { // If the timestamp column is updated by the sql database, then don't do an insert on that column (AutoUpdate here actually means we manually update it in PHP)
        if ($strCols) {
            $strCols .= ",\n";
        }
        if ($strValues) {
            $strValues .= ",\n";
        }
        if ($strColUpdates) {
            $strColUpdates .= ",\n";
        }
        $strCol = '							' . $strEscapeIdentifierBegin . $objColumn->Name . $strEscapeIdentifierEnd;
        $strCols .= $strCol;
        $strValue = '\' . $objDatabase->SqlVariable($this->' . $objColumn->VariableName . ') . \'';
        $strValues .= '							' . $strValue;
        $strColUpdates .= $strCol . ' = ' . $strValue;
    }
}
if ($strValues) {
    $strCols = " (\n" . $strCols . "\n						)";
    $strValues = " VALUES (\n" . $strValues . "\n						)\n";
} else {
    $strValues = " DEFAULT VALUES";
}
?>
    public function save(?bool $blnForceInsert = false, ?bool $blnForceUpdate = false): ?int
    {
        $mixToReturn = null;
        try {
            if ((!$this->__blnRestored && !$blnForceUpdate) || ($blnForceInsert)) {
                $mixToReturn = $this->insert();
            } else {
                $this->update($blnForceUpdate);
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
        // Update __blnRestored and any Non-Identity PK Columns (if applicable)
        $this->__blnRestored = true;
<?php foreach ($objTable->PrimaryKeyColumnArray as $objColumn) { ?>
<?php   if ((!$objColumn->Identity) && ($objColumn->PrimaryKey)) { ?>
        $this->__<?= $objColumn->VariableName ?> = $this-><?= $objColumn->VariableName ?>;
<?php   } ?>
<?php } ?>

        $this->deleteFromCache();

        $this->__blnDirty = null; // reset dirty values

        return $mixToReturn;
    }

    /**
    * Inserts a new record into the `<?= strtolower($objTable->ClassName) ?>` table in the database with the current object's property values.
    * After the record is successfully inserted, the auto-incremented ID of the new record is updated in the object's `intId` field.
    * The insert operation also triggers a broadcast notification for the inserted primary key.
    *
    * @return mixed The auto-generated ID of the newly inserted record.
    * @throws Caller
    */
    protected function insert(): mixed
    {
        $mixToReturn = null;
        $objDatabase = <?= $objTable->ClassName ?>::GetDatabase();
<?php if (isset($timestampColumn) && $timestampColumn->AutoUpdate) { // We are manually updating a timestamp column here?>
        $this-><?= $timestampColumn->VariableName ?> = QDateTime::nowToString(QDateTime::FormatIso);
        $this->__blnValid[self::<?= strtoupper($timestampColumn->Name) ?>_FIELD] = true;
<?php  } ?>

        $objDatabase->NonQuery('
            INSERT INTO <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?><?= $strCols . $strValues; ?>
        ');
<?php if (isset($identityColumn)) { ?>
        // Update the Identity column and return its value
        $mixToReturn = $this-><?= $identityColumn->VariableName ?> = $objDatabase->InsertId('<?= $objTable->Name ?>', '<?= $identityColumn->Name ?>');
        $this->__blnValid[self::<?= strtoupper($identityColumn->Name) ?>_FIELD] = true;
<?php  } ?>

<?php if (isset($timestampColumn) && !$timestampColumn->AutoUpdate) { ?>
        // Update Timestamp value that was set by database
        $objResult = $objDatabase->Query('
        SELECT
        <?= $strEscapeIdentifierBegin ?><?= $timestampColumn->Name ?><?= $strEscapeIdentifierEnd ?>

        FROM
        <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

        WHERE
        <?= _indent_($strIds, 6); ?>

        );

        $objRow = $objResult->FetchArray();
        $this-><?= $timestampColumn->VariableName ?> = $objRow[0];
        $this->__blnValid[self::<?= strtoupper($timestampColumn->Name) ?>_FIELD] = true;

<?php } ?>
        static::broadcastInsert($this->PrimaryKey());

        return $mixToReturn;
    }

    /**
    * Update the current object in the database.
    *
    * @param bool $blnForceUpdate Whether to force update, regardless of the dirty state of the object.
    * @return void
    * @throws Exception if the update transaction fails.
    */
    protected function update(?bool $blnForceUpdate = false): void
    {
        $objDatabase = static::getDatabase();

        if (empty($this->__blnDirty)) {
            return; // nothing has changed
        }

        $strValues = $this->getValueClause();

        $strSql = '
        UPDATE
            <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

        SET
        ' . $strValues . '

        WHERE
<?= _indent_($strIds, 4) ?>;
<?php
    $blnNeedsTransaction = false;
    if ($blnHasUniqueReverseReference || isset($timestampColumn)) {
        $blnNeedsTransaction = true;
    }
    if (!$blnNeedsTransaction) { ?>
        $objDatabase->NonQuery($strSql);
<?php  } else { ?>
        $objDatabase->TransactionBegin();
        try {
<?php   if (isset($timestampColumn)) { ?>
            if (!$blnForceUpdate) {
                $this->OptimisticLockingCheck();
            }
<?php       if ($timestampColumn->AutoUpdate) { // manually udpate the timestamp value before saving?>
                $this-><?= $timestampColumn->VariableName ?> = QDateTime::NowToString(QDateTime::FormatIso);
                $this->__blnValid[self::<?= strtoupper($timestampColumn->Name) ?>_FIELD] = true;
<?php       } ?>
<?php   } ?>

       $objDatabase->NonQuery($strSql);

<?php foreach ($objTable->ReverseReferenceArray as $objReverseReference) { ?>
<?php     if ($objReverseReference->Unique) { ?>
<?php       $objReverseReferenceTable = $objCodeGen->TableArray[strtolower($objReverseReference->Table)]; ?>
<?php       $objReverseReferenceColumn = $objReverseReferenceTable->ColumnArray[strtolower($objReverseReference->Column)]; ?>
        // Update the foreign key in the <?= $objReverseReference->ObjectDescription ?> object (if applicable)
        if ($this->blnDirty<?= $objReverseReference->ObjectPropertyName ?>) {
            // Unassociate the old one (if applicable)
            if ($objAssociated = <?= $objReverseReference->VariableType ?>::loadBy<?= $objReverseReferenceColumn->PropertyName ?>(<?= $objCodeGen->ImplodeObjectArray(', ', '$this->', '', 'VariableName', $objTable->PrimaryKeyColumnArray) ?>)) {
                // TODO: Select and update only the foreign key rather than the whole record
                $objAssociated-><?= $objReverseReferenceColumn->PropertyName ?> = null;
                $objAssociated->save();
            }

            // Associate the new one (if applicable)
            if ($this-><?= $objReverseReference->ObjectMemberVariable ?>) {
                $this-><?= $objReverseReference->ObjectMemberVariable ?>-><?= $objReverseReferenceColumn->PropertyName ?> = $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>;
                $this-><?= $objReverseReference->ObjectMemberVariable ?>->save();
            }

            // Reset the "Dirty" flag
            $this->blnDirty<?= $objReverseReference->ObjectPropertyName ?> = false;
        }
<?php } ?>
<?php } ?>

<?php if (isset($timestampColumn) && !($timestampColumn->AutoUpdate)) { ?>
            // Update Local Timestamp
            $objResult = $objDatabase->query('
                SELECT
                    <?= $strEscapeIdentifierBegin ?><?= $timestampColumn->Name ?><?= $strEscapeIdentifierEnd ?>

                FROM
                    <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

                WHERE
<?= _indent_($strIds, 5); ?>

            );

            $objRow = $objResult->fetchArray();
            $this-><?= $timestampColumn->VariableName ?> = $objRow[0];
            $this->__blnValid[self::<?= strtoupper($timestampColumn->Name) ?>_FIELD] = true;

<?php } ?>
        $objDatabase->transactionCommit();

        }
        catch (Exception $e) {
            $objDatabase->transactionRollback();
            throw($e);
        }
<?php } ?>
        static::broadcastUpdate($this->primaryKey(), array_keys($this->__blnDirty));
    }

    /**
    * Generates a value clause string for use in SQL queries based on the modified fields of the current object.
    *
    * @return string A formatted string containing the column-value assignments for modified fields,
    *                or an empty string if no fields have been modified.
    */
	protected function getValueClause(): string
    {
		$values = [];
		$objDatabase = static::getDatabase();

<?php
foreach ($objTable->ColumnArray as $objColumn) {
	if ((!$objColumn->Identity) && !($objColumn->Timestamp && !$objColumn->AutoUpdate)) {
?>
		if (isset($this->__blnDirty[self::<?= strtoupper($objColumn->Name) ?>_FIELD])) {
			$strCol = '<?= $strEscapeIdentifierBegin ?><?= $objColumn->Name ?><?= $strEscapeIdentifierEnd ?>';
			$strValue = $objDatabase->sqlVariable($this-><?= $objColumn->VariableName ?>);
			$values[] = $strCol . ' = ' . $strValue;
		}
<?php
	}
}
?>
		if ($values) {
			return implode(",\n", $values);
		}
		else {
			return "";
		}
	}

<?php if (isset($timestampColumn)) { ?>
    /**
    * Performs an optimistic locking check to ensure data integrity when updating
    * the `person_with_lock` table. This method compares the current object's
    * timestamp and fields to the values in the database to detect conflicts
    * caused by concurrent updates. If discrepancies are found that indicate
    * another update has taken place, the method throws an exception to prevent
    * overwriting data unintentionally.
    *
    * @return void
    * @throws Caller If the `sys_timestamp` column is not selected in the query.
    * @throws OptimisticLocking If a conflict is detected due to optimistic locking violation.
    */
	protected function optimisticLockingCheck(): void
    {
        if (empty($this->__blnValid[self::<?= strtoupper($timestampColumn->Name) ?>_FIELD])) {
            throw new Caller("To be able to update table '<?= $objTable->Name ?>' you must have previously selected the <?= $timestampColumn->Name ?> column because its used to detect optimistic locking collisions.");
        }

        $objDatabase = static::getDatabase();
		$objResult = $objDatabase->query('
            SELECT
                <?= $strEscapeIdentifierBegin ?><?= $timestampColumn->Name ?><?= $strEscapeIdentifierEnd ?>

            FROM
                <?= $strEscapeIdentifierBegin ?><?= $objTable->Name ?><?= $strEscapeIdentifierEnd ?>

            WHERE
                <?= $strIds; ?>

		);

		$objRow = $objResult->fetchArray();
		if ($objRow[0] != $this-><?= $timestampColumn->VariableName ?>) {
			// Row was updated since we got the row, now check to see if we actually changed fields that were previously changed.
			$changed = false;
			$obj<?= $objTable->ClassName ?> = <?= $objTable->ClassName ?>::load(<?= $strIdsAsParams ?>);
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
			$changed = $changed || (isset($this->__blnDirty[self::<?= strtoupper($objColumn->Name) ?>_FIELD]) && ($this-><?= $objColumn->VariableName ?> !== $obj<?= $objTable->ClassName ?>-><?= $objColumn->VariableName ?>));
<?php } ?>
			if ($changed) {
				throw new OptimisticLocking('<?= $objTable->ClassName ?>');
			}
		}
	}
<?php } ?>