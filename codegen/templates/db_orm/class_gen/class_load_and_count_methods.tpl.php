<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */
?>
    ////////////////////////////////////
    // CLASS-WIDE LOAD AND COUNT METHODS
    ////////////////////////////////////

    /**
    * Retrieves the database connection associated with the class.
    *
    * @return DatabaseBase The database connection instance.
    */
    public static function getDatabase(): DatabaseBase
    {
        return Service::getDatabase(self::getDatabaseIndex());
    }

    /**
    * Loads a <?= $objTable->ClassName ?> object based on the given ID.
    * Optionally retrieves the object from cache if no clauses are provided.
    *
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php if ($objColumn->PrimaryKey) { ?>
    * @param int|null <?= $objCodeGen->ParameterListFromColumnArray($objTable->PrimaryKeyColumnArray); ?> The ID of the $<?= $objColumn->VariableName ?> to load.
<?php } ?>
<?php } ?>
    * @param iClause|null $objOptionalClauses Additional optional iClause objects for this query.
    * @return <?= $objTable->ClassName ?>|null The loaded <?= $objTable->ClassName ?> object or null if not found.
    * @throws Caller
    * @throws InvalidCast
    */
    public static function load(?int <?= $objCodeGen->ParameterListFromColumnArray($objTable->PrimaryKeyColumnArray); ?>, ?iClause $objOptionalClauses = null): ?<?= $objTable->ClassName ?>

    {
        if (!$objOptionalClauses) {
<?php if (count ($objTable->PrimaryKeyColumnArray) == 1) { ?>
            $objCachedObject = static::getFromCache($<?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>);
<?php } else {
$aItems = array();
foreach ($objTable->PrimaryKeyColumnArray as $objColumn) {
    $aItems[] = '$' . $objColumn->VariableName;
}
?>
            $strCacheKey = static::makeMultiKey (array(<?= implode (', ', $aItems) ?>));
            $objCachedObject = static::getFromCache ($strCacheKey);
<?php } ?>
            if ($objCachedObject) return $objCachedObject;
        }

        // Use QuerySingle to Perform the Query
        return <?= $objTable->ClassName ?>::querySingle(
            QQ::AndCondition(
<?php foreach ($objTable->PrimaryKeyColumnArray as $objColumn) { ?>
                QQ::Equal(QQN::<?= $objTable->ClassName ?>()-><?= $objColumn->PropertyName ?>, $<?= $objColumn->VariableName ?>),
<?php } ?><?php GO_BACK(2); ?>

            ),
            $objOptionalClauses
        );
    }

    /**
    * Loads all <?= $objTable->ClassName ?> objects as an array.
    * @param mixed $objOptionalClauses Optional query clauses to customize the query.
    * @return array An array of <?= $objTable->ClassName ?> objects.
    * @throws Caller If more than one argument is passed or another error occurs.
    */
    public static function loadAll(mixed $objOptionalClauses = null): array
    {
        if (func_num_args() > 1) {
            throw new Caller("LoadAll must be called with an array of optional clauses as a single argument");
        }
        // Call <?= $objTable->ClassName ?>::queryArray to perform the LoadAll query
        try {
            return <?= $objTable->ClassName; ?>::queryArray(QQ::All(), $objOptionalClauses);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
    * Counts all records in the <?= $objTable->ClassName ?> table.
    * @return int The total count of all <?= $objTable->ClassName ?> records.
    * @throws Caller
    */
    public static function countAll(): int
    {
        // Call <?= $objTable->ClassName ?>::queryCount to perform the CountAll query
        return <?= $objTable->ClassName ?>::queryCount(QQ::All());
    }