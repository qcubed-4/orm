<?php
foreach ($objTable->ColumnArray as $objColumn) {
	$strVarNamePlural = strtolower($objTable->ClassNamePlural);
	$strVarName = strtolower($objTable->ClassName);
	if ($objColumn->Identity || $objColumn->Unique) {
?>

    /**
    * Key an array of <?= strtolower($strVarNamePlural) ?> by their unique identifiers.
    *
    * @param array $<?= $strVarNamePlural ?> An array of objects to be keyed by their IDs.
    * @return array An associative array where the keys are the IDs and the values are the corresponding objects.
    */
    public static function key<?= $objTable->ClassNamePlural ?>By<?= $objColumn->PropertyName ?>(array $<?= $strVarNamePlural ?>): array
    {
        if (empty($<?= $strVarNamePlural ?>)) {
            return $<?= $strVarNamePlural ?>;
        }
        $ret = [];
        foreach ($<?= $strVarNamePlural ?> as $<?= $strVarName ?>) {
            $ret[$<?= $strVarName ?>-><?= $objColumn->VariableName ?>] = $<?= $strVarName ?>;
        }
        return $ret;
    }
<?php
	}
}
