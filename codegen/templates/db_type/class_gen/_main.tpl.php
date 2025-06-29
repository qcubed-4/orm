<?php
	/** @var \QCubed\Codegen\TypeTable $objTypeTable */
	/** @var \QCubed\Codegen\DatabaseCodeGen $objCodeGen */
	global $_TEMPLATE_SETTINGS;
	$_TEMPLATE_SETTINGS = array(
		'OverwriteFlag' => true,
		'DirectorySuffix' => '',
		'TargetDirectory' => QCUBED_PROJECT_MODEL_GEN_DIR,
		'TargetFileName' => $objTypeTable->ClassName . 'Gen.php'
	);
?>
<?php print("<?php\n"); ?>
/**
 * <?= $objTypeTable->ClassName ?> file
 */

use QCubed\Database\FieldType;
use QCubed\Database\RowBase;
use QCubed\ObjectBase;
use QCubed\Query\Node;
use QCubed\Exception\Caller;

/**
 * Class <?= $objTypeTable->ClassName ?>
 *
 * The <?= $objTypeTable->ClassName ?> class defined here contains
 * code for the <?= $objTypeTable->ClassName ?> enumerated type.  It represents
 * the enumerated values found in the "<?= $objTypeTable->Name ?>" Table
 * in the database.
 *
 * To use, you should use the <?= $objTypeTable->ClassName ?> subclass which
 * extends this <?= $objTypeTable->ClassName ?>Gen class.
 *
 * Because subsequent re-code generations will overwrite any changes to this
 * file, you should leave this file unaltered to prevent yourself from losing
 * any information or code changes.  All customizations should be done by
 * overriding existing or implementing new methods, properties and variables
 * in the <?= $objTypeTable->ClassName ?> class.
 *
 * @package <?= \QCubed\Project\Codegen\CodegenBase::$ApplicationName; ?>

 * @subpackage Model
 */
abstract class <?= $objTypeTable->ClassName ?>Gen extends ObjectBase
{
<?= ($intKey = 0) == 1; ?><?php foreach ($objTypeTable->TokenArray as $intKey=>$strValue) { ?>
    const <?= $strValue ?> = <?= $intKey ?>;
<?php } ?>

    const MAX_ID = <?= $intKey ?>;

    public static function nameArray(): array
    {
        return [
<?php if (count($objTypeTable->NameArray)) { ?>
<?php   foreach ($objTypeTable->NameArray as $intKey=>$strValue) { ?>
            <?= $intKey ?> => t('<?= $strValue ?>'),
<?php   } ?><?php GO_BACK(2); ?>
<?php }?>

        ];
    }

    public static function tokenArray(): array
    {
        return [
<?php if (count($objTypeTable->TokenArray)) { ?>
<?php   foreach ($objTypeTable->TokenArray as $intKey=>$strValue) { ?>
            <?= $intKey ?> => '<?= $strValue ?>',
<?php   } ?><?php GO_BACK(2); ?>
<?php }?>

        ];
    }

<?php if (count($objTypeTable->ExtraFieldsArray)) { ?>
    public static function extraColumnNamesArray(): array
    {
        return [
<?php foreach ($objTypeTable->ExtraFieldsArray as $colData) { ?>
            '<?= $colData['name'] ?>',
<?php } ?><?php GO_BACK(2); ?>

        ];
    }

    public static function extraColumnValuesArray(): array
    {
        return array(
<?php foreach ($objTypeTable->ExtraPropertyArray as $intKey=>$arrColumns) { ?>
            <?= $intKey ?> => array (
<?php 	foreach ($arrColumns as $strColName=>$mixColValue) { ?>
                '<?= $strColName ?>' => <?= \QCubed\Codegen\TypeTable::literal($mixColValue) ?>,
<?php 	} ?><?php GO_BACK(2); ?>

            ),
<?php } ?><?php GO_BACK(2); ?>

        );
    }

<?php if (count($objTypeTable->ExtraFieldsArray)) { ?>
<?php   foreach ($objTypeTable->ExtraFieldsArray as $colData) { ?>
    public static function <?= lcfirst($colData['name']) ?>Array(): array
    {
        return array(
<?php       foreach ($objTypeTable->ExtraPropertyArray as $intKey=>$arrColumns) { ?>
            '<?= $intKey ?>' => <?= \QCubed\Codegen\TypeTable::literal($arrColumns[$colData['name']]) ?>,
<?php       }     ?><?php GO_BACK(2); ?>

        );
    }

<?php   } ?>
<?php } ?>
<?php }?>
    /**
     * Returns the string corresponding to the given id.
     *
     * @param integer $int<?= $objTypeTable->ClassName ?>Id
     * @return string
     * @throws Caller
     */
    public static function toString(int $int<?= $objTypeTable->ClassName ?>Id): string
    {
        return match ($int<?= $objTypeTable->ClassName ?>Id) {
    <?php foreach ($objTypeTable->TokenArray as $intKey=>$strValue) { ?>
        <?= $intKey ?> => t('<?= $strValue ?>'),
    <?php } ?>

            default => throw new Caller(sprintf('Invalid intProjectStatusTypeId: %s', $int<?= $objTypeTable->ClassName ?>Id))
        };
    }

    /**
    * Returns the string corresponding to the given id.
    *
    * @param integer $int<?= $objTypeTable->ClassName ?>Id
    * @return string
    * @throws Caller
    */
    public static function toToken(int $int<?= $objTypeTable->ClassName ?>Id): string
    {
        return match ($int<?= $objTypeTable->ClassName ?>Id) {
    <?php foreach ($objTypeTable->TokenArray as $intKey=>$strValue) { ?>
        <?= $intKey ?> => t('<?= $strValue ?>'),
    <?php } ?>

            default => throw new Caller(sprintf('Invalid intProjectStatusTypeId: %s', $int<?= $objTypeTable->ClassName ?>Id))
        };
    }

<?php foreach ($objTypeTable->ExtraFieldsArray as $colData) { ?>
    /**
    * Get the associated <?php echo $colData['name'] ?> value by an id.
    *
    * @param int $int<?= $objTypeTable->ClassName ?>Id
    * @return string
    * @throws Caller
    */
    public static function to<?php echo $colData['name'] ?>(int $int<?php echo $objTypeTable->ClassName ?>Id): string
    {
        return match ($int<?php echo $objTypeTable->ClassName ?>Id) {
    <?php
    $valueMap = [];
    foreach ($objTypeTable->ExtraPropertyArray as $intKey => $arrColumns) {
        $val = \QCubed\Codegen\TypeTable::literal($arrColumns[$colData['name']]);
        $valueMap[$val][] = $intKey;
    }
    foreach ($valueMap as $val => $keys) {if (count($keys) === 1) { ?>
        <?= $keys[0] ?> => <?= $val ?>,
    <?php  } else { ?>
        <?= implode(', ', $keys) ?>  => <?= $val ?>,
    <?php  }
    } ?>

            default => throw new Caller(sprintf('Invalid int<?php echo $objTypeTable->ClassName ?>Id: %s', $int<?php echo $objTypeTable->ClassName ?>Id))
        };
    }

<?php } ?>
    ///////////////////////////////
    // INSTANTIATION-RELATED METHODS
    ///////////////////////////////

    /**
    * Instantiate a ProjectStatusType from a Database Row.
    * Simply return the integer id corresponding to this item.
    * Take in an optional strAliasPrefix, used in case another Object::InstantiateDbRow
    * is calling this ProjectStatusType::InstantiateDbRow in order to perform
    * early binding on referenced objects.
    * @param RowBase $objDbRow
    * @param string|null $strAliasPrefix
    * @param string|null $strExpandAsArrayNodes
    * @param array|null $arrPreviousItems
    * @param string[] $strColumnAliasArray
    * @return int|null
    */
    public static function instantiateDbRow(RowBase $objDbRow, ?string $strAliasPrefix = null, mixed $strExpandAsArrayNodes = null, ?array $arrPreviousItems = null, array $strColumnAliasArray = array()): ?int
    {
        $strAlias = $strAliasPrefix . 'id';
        $strAliasName = array_key_exists($strAlias, $strColumnAliasArray) ? $strColumnAliasArray[$strAlias] : $strAlias;

        return $objDbRow->GetColumn($strAliasName, FieldType::INTEGER);
    }
}

/**
 * @property-read Node\Column $Id
 * @property-read Node\Column $_PrimaryKeyNode
 */
class Node<?= $objTypeTable->ClassName ?> extends Node\Table {
    protected ?string $strTableName = '<?= $objTypeTable->Name ?>';
    protected ?string $strPrimaryKey = 'id';
    protected ?string $strClassName = '<?= $objTypeTable->ClassName ?>';
    protected ?bool $blnIsType = true;

    /**
    * Returns the names of the available fields to query against this node.
    * @returns string[]
    */
    public function fields(): array
    {
        return ["id", "name"];
    }

    /**
    * Returns the field names of the primary key field(s)
    * @returns string[]
    */
    public function primaryKeyFields(): array
    {
        return ["id"];
    }

    /**
    * Retrieves the value of a property based on its name.
    *
    * @param string $strName The name of the property to retrieve.
    * @return mixed The value of the requested property. If the property does not exist, attempts to retrieve it using the parent class or throws an exception if not found.
    * @throws Caller
    */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'Id':
            case '_PrimaryKeyNode':
                return new Node\Column('id', 'Id', 'Integer', $this);

            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }
}
