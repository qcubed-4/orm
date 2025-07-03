<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */
?>
    /**
    * Static Qcubed Query method to query for a single <?= $objTable->ClassName ?> object.
    * Offloads work to QModelTrait.trait.php
    * @param iCondition $objConditions any conditions on the query, itself
    * @param iClause|null $objOptionalClauses additional optional iClause objects for this query
    * @param array|null $mixParameterArray an array of name-value pairs to perform PrepareStatement with
    * @return <?= $objTable->ClassName ?>|null the queried object
    * @throws Caller
    */
    public static function querySingle(iCondition $objConditions, ?iClause $objOptionalClauses = null, ?array $mixParameterArray = null): ?<?= $objTable->ClassName ?>
    {
        return static::_QuerySingle($objConditions, $objOptionalClauses, $mixParameterArray);
    }

    /**
    * Static Qcubed Query method to query for an array of <?= $objTable->ClassName ?> objects.
    * Offloads work to QModelTrait.trait.php
    * @param iCondition $objConditions any conditions on the query, itself
    * @param iClause[] $objOptionalClauses additional optional iClause objects for this query
    * @param array|null $mixParameterArray an array of name-value pairs to perform PrepareStatement with
    * @return <?= $objTable->ClassName ?>[] the queried objects as an array
    * @throws Caller
    */
    public static function queryArray(iCondition $objConditions, mixed $objOptionalClauses = null, ?array $mixParameterArray = null): array
    {
        return static::_QueryArray($objConditions, $objOptionalClauses, $mixParameterArray);
    }

<?php if (count($objTable->PrimaryKeyColumnArray) == 1) { ?>
    /**
    * Query and retrieve primary key values from the <?= $objTable->ClassName ?> table based on the given conditions.
    * @param iCondition|null $objConditions Optional conditions to filter the query. Defaults to fetching all records.
    * @return int[] An array of primary key values corresponding to the matched <?= $objTable->ClassName ?> objects.
    * @throws Caller
    */
    public static function queryPrimaryKeys(?iCondition $objConditions = null): array
    {
        if ($objConditions === null) {
            $objConditions = QQ::All();
        }
        $clauses[] = QQ::Select(QQN::<?= $objTable->ClassName ?>()-><?= $objTable->PrimaryKeyColumnArray[0]->PropertyName ?>);
        $obj<?= $objTable->ClassNamePlural ?> = self::QueryArray($objConditions, $clauses);
        $pks = [];
        foreach ($obj<?= $objTable->ClassNamePlural ?> as $obj<?= $objTable->ClassName ?>) {
            $pks[] = $obj<?= $objTable->ClassName ?>-><?= $objTable->PrimaryKeyColumnArray[0]->VariableName ?>;
        }
        return $pks;
    }
<?php } ?>

    // See QModelTrait.trait.php for the following
    // protected static function buildQueryStatement(?Builder &$objQueryBuilder, iCondition $objConditions, mixed $objOptionalClauses, mixed $mixParameterArray, bool $blnCountOnly): string {
    // public static function queryCursor(iCondition $objConditions,?array $objOptionalClauses = null, ?array $mixParameterArray = null): ResultBase {
    // public static function queryCount(iCondition $objConditions, mixed $objOptionalClauses = null, mixed $mixParameterArray = null): int {

    /**
    * Retrieve an entity from the cache.
    *
    * @param mixed $key The key used to identify the cached entity.
    * @return <?= $objTable->ClassName ?>|null The entity retrieved from the cache, which may be a <?= $objTable->ClassName ?>| instance or implement the ModelTrait.
    */
    public static function getFromCache(mixed $key): ?<?= $objTable->ClassName ?>

    {
        return static::_GetFromCache($key);
    }