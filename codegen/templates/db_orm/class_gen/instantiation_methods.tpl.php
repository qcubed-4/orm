<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */

// Preliminary calculations and helper routines here

$blnImmediateExpansions = $objTable->HasImmediateArrayExpansions();
$blnExtendedExpansions = $objTable->hasExtendedArrayExpansions($objCodeGen);

if (count($objTable->PrimaryKeyColumnArray) > 1 && $blnImmediateExpansions) {
    throw new QCubed\Exception\Caller ("Multi-key table with array expansion is not supported.");
}
?>

    /**
    * Instantiate an object from a database row.
    * This method includes support for object expansion, early/virtual binding, and caching mechanisms.
    *
    * @param RowBase $objDbRow The database row to be converted into an object instance.
    * @param string|null $strAliasPrefix Alias prefix for the columns in the database row (optional).
    * @param mixed $objExpandAsArrayNode Node used for expanding objects as arrays (if applicable).
    * @param array|null $objPreviousItemArray Array of previously instantiated items for duplication checks (optional).
    * @param array $strColumnAliasArray Array mapping column aliases to column names (optional).
    * @param bool|null $blnCheckDuplicate A flag indicating whether duplicate checks should be performed (optional).
    * @param string|null $strParentExpansionKey Parent key for object expansion (optional).
    * @param mixed $objExpansionParent The parent object in the context of object expansion (optional).
    * @return <?= $objTable->ClassName ?>|false|null Returns the instantiated Person object, or null if there was no valid database row or if it was a duplicate in a complex join.
    * @throws Caller
    * @throws DateMalformedStringException
    * @throws InvalidCast
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
    ): <?= $objTable->ClassName ?>|false|null
    {
        $strColumns = $objDbRow->GetColumnNameArray();
        $strColumnKeys = array_fill_keys(array_keys($strColumns), 1); // to be able to use isset

<?php if ($objTable->PrimaryKeyColumnArray)  { // Optimize top level accesses?>
        $key = static::getRowPrimaryKey ($objDbRow, $strAliasPrefix, $strColumnAliasArray);
        if (empty ($strAliasPrefix) && $objPreviousItemArray) {
            $objPreviousItemArray = (!empty ($objPreviousItemArray[$key]) ? $objPreviousItemArray[$key] : null);
        }
<?php } ?>
<?php 
if ($blnImmediateExpansions || $blnExtendedExpansions) {
?>
        // See if we're doing an array expansion on the previous item
        if ($objExpandAsArrayNode &&
                is_array($objPreviousItemArray) &&
                count($objPreviousItemArray)) {

            $expansionStatus = static::expandArray ($objDbRow, $strAliasPrefix, $objExpandAsArrayNode, $objPreviousItemArray, $strColumnAliasArray);
            if ($expansionStatus) {
                return false; // db row was used but no new object was created
            } elseif ($expansionStatus === null) {
                $blnCheckDuplicate = true;
            }
        }
<?php 
} // if
?>
<?php if ($objTable->PrimaryKeyColumnArray)  { ?>

        $objToReturn = static::getFromCache ($key);
        if (empty($objToReturn)) {
<?php } ?>
            // Create a new instance of the <?= $objTable->ClassName ?> object
            $objToReturn = new <?= $objTable->ClassName ?>(false);
            $objToReturn->__blnRestored = true;
            $blnNoCache = false;

<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
            $strAlias = $strAliasPrefix . '<?= $objColumn->Name ?>';
            $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
            if (isset ($strColumnKeys[$strAliasName])) {
                $mixVal = $strColumns[$strAliasName];
<?php if ($objColumn->VariableType == \QCubed\Type::BOOLEAN) { ?>
                $objToReturn-><?= $objColumn->VariableName ?> = $objDbRow->ResolveBooleanValue($mixVal);
<?php } else { ?>
<?php 	if ($s = $objCodeGen->getCastString($objColumn)) { ?>
                if ($mixVal !== null) {
                    <?= $s ?>

                }
<?php } ?>
                $objToReturn-><?= $objColumn->VariableName ?> = $mixVal;
<?php } ?>
<?php if (($objColumn->PrimaryKey) && (!$objColumn->Identity)) { ?>
                $objToReturn->__<?= $objColumn->VariableName ?> = $mixVal;
<?php } ?>
                $objToReturn->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
            }
            else {
                $blnNoCache = true;
            }
<?php } ?>
<?php if ($objTable->PrimaryKeyColumnArray)  { ?>

            if (!$blnNoCache) {
                $objToReturn->WriteToCache();
            }
        }
<?php } ?>

        if (isset($objPreviousItemArray) && is_array($objPreviousItemArray) && $blnCheckDuplicate) {
            foreach ($objPreviousItemArray as $objPreviousItem) {
<?php foreach ($objTable->PrimaryKeyColumnArray as $col) { ?>
                if ($objToReturn-><?= $col->PropertyName ?> != $objPreviousItem-><?= $col->PropertyName ?>) {
                    continue;
                }
<?php } ?>
                // this is a duplicate in a complex join
                return null; // indicates no object created, and the db row has not been used
            }
        }

        // Instantiate Virtual Attributes
        $strVirtualPrefix = $strAliasPrefix . '__';
        $strVirtualPrefixLength = strlen($strVirtualPrefix);
        foreach ($objDbRow->GetColumnNameArray() as $strColumnName => $mixValue) {
            if (strncmp($strColumnName, $strVirtualPrefix, $strVirtualPrefixLength) == 0)
                $objToReturn->__strVirtualAttributeArray[substr($strColumnName, $strVirtualPrefixLength)] = $mixValue;
        }

        // Prepare to Check for Early/Virtual Binding

        $objExpansionAliasArray = array();
        if ($objExpandAsArrayNode) {
            $objExpansionAliasArray = $objExpandAsArrayNode->ChildNodeArray;
        }

        if (!$strAliasPrefix)
            $strAliasPrefix = '<?= $objTable->Name ?>__';

<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->Reference && !$objColumn->Reference->IsType) { ?>
        // Check for <?= $objColumn->Reference->PropertyName ?> Early Binding
        $strAlias = $strAliasPrefix . '<?= $objColumn->Name ?>__<?= $objCodeGen->GetTable($objColumn->Reference->Table)->PrimaryKeyColumnArray[0]->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        if (isset ($strColumns[$strAliasName])) {
            $objExpansionNode = (empty($objExpansionAliasArray['<?= $objColumn->Name ?>']) ? null : $objExpansionAliasArray['<?= $objColumn->Name ?>']);
            $objToReturn-><?= $objColumn->Reference->VariableName ?> = <?= $objColumn->Reference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= $objColumn->Name ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= strtolower($objColumn->Reference->ReverseReference->ObjectDescription) ?>', $objToReturn);
        }
        elseif ($strParentExpansionKey === '<?= $objColumn->Name ?>' && $objExpansionParent) {
            $objToReturn-><?= $objColumn->Reference->VariableName ?> = $objExpansionParent;
        }

<?php } ?>
<?php } ?>
<?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?><?php if ($objReference->Unique) { ?>
        // Check for <?= $objReference->ObjectDescription ?> Unique ReverseReference Binding
        $strAlias = $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objCodeGen->GetTable($objReference->Table)->PrimaryKeyColumnArray[0]->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        if (array_key_exists ($strAliasName, $strColumns)) {
            if (!is_null($strColumns[$strAliasName])) {
                $objExpansionNode = (empty($objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']) ? null : $objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']);
                $objToReturn->obj<?= $objReference->ObjectDescription ?> = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= $objReference->Column ?>', $objToReturn);
            }
            else {
                // We ATTEMPTED to do an Early Bind, but the Object Doesn't Exist
                // Let's set to FALSE so that the object knows not to try and re-query again
                $objToReturn->obj<?= $objReference->ObjectDescription ?> = false;
            }
        }

<?php } ?><?php } ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
<?php 
$objAssociatedTable = $objCodeGen->GetTable($objReference->AssociatedTable);
if (is_a($objAssociatedTable, '\QCubed\Codegen\TypeTable') ) {
    $blnIsType = true;
    $varPrefix = '_int';
} else {
    $blnIsType = false;
    $varPrefix = '_obj';} ?>
        // Check for <?= $objReference->ObjectDescription ?> Virtual Binding
        $strAlias = $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objReference->OppositeColumn ?>__<?= $objCodeGen->GetTable($objReference->AssociatedTable)->PrimaryKeyColumnArray[0]->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        $objExpansionNode = (empty($objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']) ? null : $objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']);
        $blnExpanded = ($objExpansionNode && $objExpansionNode->ExpandAsArray);
        if ($blnExpanded && null === $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?>Array) {
            $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?>Array = array();
        }
        if (isset ($strColumns[$strAliasName])) {
            if ($blnExpanded) {
<?php if ($blnIsType) { ?>
                $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?>Array[] = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objReference->OppositeColumn ?>__', $objExpansionNode, null, $strColumnAliasArray);
<?php } else { ?>
                $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?>Array[] = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objReference->OppositeColumn ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= strtolower($objReference->OppositeObjectDescription) ?>', $objToReturn);
<?php } ?>
            } elseif (is_null($objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?>)) {
<?php if ($blnIsType) { ?>
                $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?> = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objReference->OppositeColumn ?>__', $objExpansionNode, null, $strColumnAliasArray);
<?php } else { ?>
                $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?> = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objReference->OppositeColumn ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= strtolower($objReference->OppositeObjectDescription) ?>', $objToReturn);
<?php } ?>

            }
        }
        elseif ($strParentExpansionKey === '<?= strtolower($objReference->ObjectDescription) ?>' && $objExpansionParent) {
            $objToReturn-><?= $varPrefix . $objReference->ObjectDescription ?> = $objExpansionParent;
        }

<?php } ?>
<?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?><?php if (!$objReference->Unique) { ?>
        // Check for <?= $objReference->ObjectDescription ?> Virtual Binding
        $strAlias = $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__<?= $objCodeGen->GetTable($objReference->Table)->PrimaryKeyColumnArray[0]->Name ?>';
        $strAliasName = !empty($strColumnAliasArray[$strAlias]) ? $strColumnAliasArray[$strAlias] : $strAlias;
        $objExpansionNode = (empty($objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']) ? null : $objExpansionAliasArray['<?= strtolower($objReference->ObjectDescription) ?>']);
        $blnExpanded = ($objExpansionNode && $objExpansionNode->ExpandAsArray);
        if ($blnExpanded && null === $objToReturn->_obj<?= $objReference->ObjectDescription ?>Array)
            $objToReturn->_obj<?= $objReference->ObjectDescription ?>Array = array();
        if (isset ($strColumns[$strAliasName])) {
            if ($blnExpanded) {
                $objToReturn->_obj<?= $objReference->ObjectDescription ?>Array[] = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= $objReference->Column ?>', $objToReturn);
            } elseif (is_null($objToReturn->_obj<?= $objReference->ObjectDescription ?>)) {
                $objToReturn->_obj<?= $objReference->ObjectDescription ?> = <?= $objReference->VariableType ?>::instantiateDbRow($objDbRow, $strAliasPrefix . '<?= strtolower($objReference->ObjectDescription) ?>__', $objExpansionNode, null, $strColumnAliasArray, false, '<?= $objReference->Column ?>', $objToReturn);
            }
        }
        elseif ($strParentExpansionKey === '<?= strtolower($objReference->ObjectDescription) ?>' && $objExpansionParent) {
            $objToReturn->_obj<?= $objReference->ObjectDescription ?> = $objExpansionParent;
        }

<?php } ?><?php } ?>
        return $objToReturn;
    }

    /**
    * Instantiate an array of <?= $objTable->ClassNamePlural ?> from a Database Result
    * @param ResultBase $objDbResult
    * @param NodeBase|null $objExpandAsArrayNode
    * @param string[] $strColumnAliasArray
    * @return <?= $objTable->ClassName ?>[]
    * @throws Caller
    * @throws DateMalformedStringException
    * @throws InvalidCast
    */
    public static function instantiateDbResult(
        ResultBase $objDbResult,
        ?Node\NodeBase $objExpandAsArrayNode = null,
        ?array $strColumnAliasArray = null
    ): array
    {
        $objToReturn = array();
        $objPrevItemArray = array();

        if (!$strColumnAliasArray)
            $strColumnAliasArray = array();

        // Load up the return array with each row
        if ($objExpandAsArrayNode) {
            while ($objDbRow = $objDbResult->GetNextRow()) {
                $objItem = <?= $objTable->ClassName ?>::instantiateDbRow($objDbRow, null, $objExpandAsArrayNode, $objPrevItemArray, $strColumnAliasArray);
                if ($objItem) {
                    $objToReturn[] = $objItem;
<?php if ($objTable->PrimaryKeyColumnArray)  {?>
                    $objPrevItemArray[$objItem-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>][] = $objItem;
<?php } else { ?>
                    $objPrevItemArray[] = $objItem;

<?php } ?>
                }
            }
        } else {
            while ($objDbRow = $objDbResult->GetNextRow())
                $objToReturn[] = <?= $objTable->ClassName ?>::instantiateDbRow($objDbRow, null, null, null, $strColumnAliasArray);
        }

        return $objToReturn;
    }

    /**
    * Instantiate a single <?= $objTable->ClassName ?> object from a query cursor (e.g., a DB ResultSet).
    * The Cursor is automatically moved to the "next row" of the result set.
    * Will return NULL if no cursor or if the cursor has no more rows in the resultset.
    * @param ResultBase $objDbResult cursor resource
    * @return <?= $objTable->ClassName ?>|null next row resulting from the query
    * @throws Caller
    * @throws DateMalformedStringException
    * @throws InvalidCast
    */
    public static function instantiateCursor(ResultBase $objDbResult): ?<?= $objTable->ClassName ?>

    {
        // If an empty resultset, then return an empty result
        $objDbRow = $objDbResult->GetNextRow();
        if (!$objDbRow) return null;

        // We need the Column Aliases
        $strColumnAliasArray = $objDbResult->ColumnAliasArray;
        if (!$strColumnAliasArray) $strColumnAliasArray = array();

        // Load up the return result with a row and return it
        return <?= $objTable->ClassName ?>::instantiateDbRow($objDbRow, null, null, null, $strColumnAliasArray);
    }