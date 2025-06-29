<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */

if ($objTable->PrimaryKeyColumnArray)  {
if (count($objTable->PrimaryKeyColumnArray) == 1) {
    $pkType = $objTable->PrimaryKeyColumnArray[0]->VariableType;
} else {
    $pkType = 'string';	// combined pk
}

?>
<?php if (count ($objTable->PrimaryKeyColumnArray) > 1) { ?>

    /**
    * Generate a multi-key string by concatenating an array of key values with a colon separator.
    * @param array $keyValues Array of key values to be concatenated
    * @return string Concatenated multi-key string
    */
    protected static function makeMultiKey(array $keyValues): string
    {
        return implode (':', $keyValues);
    }
<?php } ?>

    /**
    * Retrieve the primary key for the current object.
    * @return string|null The primary key value, or null if not set.
    */
    public function primaryKey(): ?string
    {
<?php if (count ($objTable->PrimaryKeyColumnArray) == 1) { ?>
        return $this-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>;
<?php 	} else {
        $aItems = array();
        foreach ($objTable->PrimaryKeyColumnArray as $objPKColumn) {
            $aItems[] = '$this->' . $objPKColumn->VariableName;
        }
?>
        return static::makeMultiKey (array(<?= implode (', ', $aItems) ?>));
<?php } ?>
    }

    /**
    * Returns the primary key directly from a database row.
    * @param RowBase $objDbRow
    * @param string|null $strAliasPrefix
    * @param string[] $strColumnAliasArray
    * @return integer|null
    */
    protected static function getRowPrimaryKey(RowBase $objDbRow, ?string $strAliasPrefix, array $strColumnAliasArray): ?string
    {
<?php if (count ($objTable->PrimaryKeyColumnArray) == 1) { ?>
        $strAlias = $strAliasPrefix . '<?= $objTable->PrimaryKeyColumnArray[0]->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        $strColumns = $objDbRow->GetColumnNameArray();
        return ($strColumns[$strAliasName] ?? null);

<?php } else { ?>
        $strColumns = $objDbRow->GetColumnNameArray();
<?php foreach ($objTable->PrimaryKeyColumnArray as $objPKColumn) {?>
        $strAlias = $strAliasPrefix . '<?= $objPKColumn->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        $mixVal = (isset ($strColumns[$strAliasName]) ?? null);
        if ($mixVal === null) return null;
<?php if ($s = \QCubed\Codegen\DatabaseCodeGen::GetCastString($objPKColumn)) echo $s; ?>
        $values[] = $mixVal;
<?php } ?>

        return static::MakeMultiKey ($values);
<?php } ?>
    }
<?php } else { ?>
    /**
    * Retrieve the primary key value for the current object.
    *
    * @return null Returns the primary key value, or null if not applicable.
    */
    protected function primaryKey(): null
    {
        return null;
    }

    /**
    * Retrieve the primary key for a given database row.
    *
    * @param RowBase $objDbRow The database row object containing the data.
    * @param string $strAliasPrefix Optional prefix for column aliases in the row.
    * @param string[] $strColumnAliasArray Array of column aliases, used to map database columns.
    * @return null The primary key for the given row or null if not applicable.
    */
    protected static function getRowPrimaryKey(RowBase $objDbRow, string $strAliasPrefix, array $strColumnAliasArray): null
    {
        return null;
    }
<?php }