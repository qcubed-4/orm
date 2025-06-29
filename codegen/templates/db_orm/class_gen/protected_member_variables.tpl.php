
    ////////////////////////////////////////////////////
    // PROTECTED AND PRIVATE MEMBER VARIABLES and CONSTS
    ////////////////////////////////////////////////////
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php
    $strConstName = strtoupper($objColumn->Name);	// Produce an uppercase name in the PHP const style

    // Override type specification:
    $displayType = $objColumn->VariableType;
    if ($displayType === 'integer') {
        $displayType = 'int';
    } elseif ($displayType === 'double') {
        $displayType = 'float';
    } elseif ($displayType === 'boolean') {
        $displayType = 'bool';
    } elseif ($displayType === '\QCubed\QDateTime') {
        $displayType = 'QDateTime';
    }
    // add more else-ifs if necessary!
?>

    /**
     * Protected member variable that maps to the database <?php if ($objColumn->PrimaryKey) print 'PK '; ?><?php if ($objColumn->Identity) print 'Identity '; ?>column <?= $objTable->Name ?>.<?= $objColumn->Name ?>

<?php if ($objColumn->Comment) { ?>		 * <?= $objColumn->Comment ?>
<?php } ?>
     * @var <?= $displayType ?? $objColumn->VariableType ?>|null <?= $objColumn->VariableName ?>

     */
<?php if ($objCodeGen->PrivateColumnVars) { ?>
    private ?<?=  $displayType ?> $<?= $objColumn->VariableName ?> = null;
<?php } else { ?>
    protected ?<?= $displayType  ?> $<?= $objColumn->VariableName ?> = null;
<?php   } ?>
<?php if (($objColumn->VariableType == \QCubed\Type::STRING) && (is_numeric($objColumn->Length))) { ?>
    const <?= $strConstName ?>_MAX_LENGTH = <?= $objColumn->Length ?>;
<?php } ?>

    const <?= $strConstName ?>_DEFAULT = <?php
if (is_null($objColumn->Default)) {
    print 'null';
}
elseif ($objColumn->Default === 'CURRENT_TIMESTAMP') {
    print 'QDateTime::NOW';
}
elseif (strtoupper($objColumn->Default) === 'TRUE' || (
        is_numeric($objColumn->Default) &&
        $objColumn->Default == 1 &&
        $objColumn->DbType == \QCubed\Database\FieldType::BIT)
    ) {
    print 'true';
}
elseif (strtoupper($objColumn->Default) === 'FALSE' || (
        is_numeric($objColumn->Default) &&
        $objColumn->Default == 0 &&
        $objColumn->DbType == \QCubed\Database\FieldType::BIT)
) {
    print 'false';
}
elseif (is_numeric($objColumn->Default)) {
    print $objColumn->Default;
}
else {
    print "'" . addslashes($objColumn->Default) . "'";
}
?>;
    const <?= $strConstName ?>_FIELD = '<?= addslashes($objColumn->Name) ?>';
<?php if ((!$objColumn->Identity) && ($objColumn->PrimaryKey)) { ?>

    /**
     * Protected internal member variable that stores the original version of the PK column value (if restored)
     * Used by Save() to update a PK column during UPDATE and Reload() to reload the PK.
     * @var <?= $displayType ?? $objColumn->VariableType ?>|null __<?= $objColumn->VariableName ?>;
     */
    protected ?<?= $displayType ?? $objColumn->VariableType ?> $__<?= $objColumn->VariableName ?> = null;
<?php }
} ?>
<?php foreach ($objTable->ManyToManyReferenceArray as $objReference) { ?>
<?php 
        $objAssociatedTable = $objCodeGen->GetTable($objReference->AssociatedTable);
        if (is_a($objAssociatedTable, '\QCubed\Codegen\TypeTable')) {
?>
    /**
     * Protected member variable that stores a <?= $objReference->VariableType ?> id,
     * if this <?= $objTable->ClassName ?> object was restored with
     * an expansion on the <?= $objReference->Table ?> association table.
     * @var integer|null _int<?= $objReference->ObjectDescription ?>;
     */
    protected ?int $_int<?= $objReference->ObjectDescription ?> = null;

    /**
     * Protected member variable that stores an array of <?= $objReference->VariableType ?> IDs
     * if this <?= $objTable->ClassName ?> object was restored with
     * an ExpandAsArray on the <?= $objReference->ObjectDescription ?> association table.
     * @var integer[] _int<?= $objReference->ObjectDescription ?>Array;
     */
    protected ?array $_int<?= $objReference->ObjectDescription ?>Array = null;

<?php 	} else { ?>
    /**
     * Protected member variable that stores a reference to a single <?= $objReference->ObjectDescription ?> object
     * (of type <?= $objReference->VariableType ?>) if this <?= $objTable->ClassName ?> object was restored with
     * an expansion on the <?= $objReference->Table ?> association table.
     * @var <?= $objReference->VariableType ?>|null _obj<?= $objReference->ObjectDescription ?>;
     */
    protected ?<?= $objReference->VariableType ?> $_obj<?= $objReference->ObjectDescription ?> = null;

    /**
     * Protected member variable that stores a reference to an array of <?= $objReference->ObjectDescription ?> objects
     * (of type <?= $objReference->VariableType ?>[]) if this <?= $objTable->ClassName ?> object was restored with
     * an ExpandAsArray on the <?= $objReference->Table ?> association table.
     * @var <?= $objReference->VariableType ?>[] _obj<?= $objReference->ObjectDescription ?>Array;
     */
    protected ?array $_obj<?= $objReference->ObjectDescription ?>Array = null;

<?php 	} ?>
<?php } ?>
<?php foreach ($objTable->ReverseReferenceArray as $objReference) { ?><?php if (!$objReference->Unique) { ?>
    /**
     * Protected member variable that stores a reference to a single <?= $objReference->ObjectDescription ?> object
     * (of type <?= $objReference->VariableType ?>) if this <?= $objTable->ClassName ?> object was restored with
     * an expansion on the <?= $objReference->Table ?> association table.
     * @var <?= $objReference->VariableType ?>|null _obj<?= $objReference->ObjectDescription ?>;
     */
    protected ?<?= $objReference->VariableType ?> $_obj<?= $objReference->ObjectDescription ?> = null;

    /**
     * Protected member variable that stores a reference to an array of <?= $objReference->ObjectDescription ?> objects
     * (of type <?= $objReference->VariableType ?>[]) if this <?= $objTable->ClassName ?> object was restored with
     * an ExpandAsArray on the <?= $objReference->Table ?> association table.
     * @var <?= $objReference->VariableType ?>[] _obj<?= $objReference->ObjectDescription ?>Array;
     */
    protected ?array $_obj<?= $objReference->ObjectDescription ?>Array = null;

<?php } ?><?php } ?>
    /**
     * A protected array of virtual attributes for this object (e.g., extra/other calculated and/or non-object bound
     * columns from the run-time database query result for this object).  Used by InstantiateDbRow and
     * GetVirtualAttribute.
     * @var string[] $__strVirtualAttributeArray
     */
    protected array $__strVirtualAttributeArray = array();

    /**
     * Protected internal member variable that specifies whether or not this object is Restored from the database.
     * Used by Save() to determine if Save() should perform a db UPDATE or INSERT.
     * @var bool|null __blnRestored;
     */
    protected ?bool $__blnRestored = null;

    /**
     * Protected internal array that records which fields are dirty.
     * Used by Save() to optimize the Update or Insert function.
     * @var bool[] __blnDirty;
     */
    private ?array $__blnDirty;

    /**
     * Protected internal array that records which fields are valid.
     * Used by getters to prevent accidentally reading data that was not taken from the database.
     * @var bool[] __blnDirty;
     */
    private ?array $__blnValid;