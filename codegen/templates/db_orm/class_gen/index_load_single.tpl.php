<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */
?>
<?php $objColumnArray = $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray); ?>
    /**
    * Load a single <?= $objTable->ClassName ?> object, by <?= $objCodeGen->ImplodeObjectArray(', ', '', '', 'PropertyName', $objCodeGen->GetColumnArray($objTable, $objIndex->ColumnNameArray)) ?> Index(es)
<?php foreach ($objColumnArray as $objColumn) { ?>
    * @param <?= $objColumn->VariableType ?> $<?= $objColumn->VariableName ?>

<?php } ?>
    * @param iClause[]|null $objOptionalClauses additional optional iClause objects for this query
    * @return <?= $objTable->ClassName ?>|null

    * @throws Caller
    * @throws InvalidCast
    */
    public static function loadBy<?= $objCodeGen->ImplodeObjectArray('', '', '', 'PropertyName', $objColumnArray); ?>(mixed <?= $objCodeGen->ParameterListFromColumnArray($objColumnArray); ?>, ?array $objOptionalClauses = null): ?<?= $objTable->ClassName ?>

    {
        return <?= $objTable->ClassName ?>::querySingle(
            QQ::AndCondition(
<?php foreach ($objColumnArray as $objColumn) { ?>
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objColumn->PropertyName ?>, $<?= $objColumn->VariableName ?>),
<?php } ?><?php GO_BACK(2); ?>

            ),
            $objOptionalClauses
        );
    }