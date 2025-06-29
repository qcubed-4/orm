<?php
use QCubed\Codegen\CodegenBase;
use QCubed\Codegen\SqlTable;

/** @var SqlTable $objTable */

/** @var CodegenBase $objCodeGen */

$blnAutoInitialize = $objCodeGen->AutoInitialize;
if ($blnAutoInitialize) {
?>
    /**
     * Construct a new <?= $objTable->ClassName ?> object.
     * @param bool $blnInitialize
     */
    public function __construct(bool $blnInitialize = true)
    {
        if ($blnInitialize) {
            $this->Initialize();
        }
    }
<?php } ?>

    /**
     * Initialize each property with default values from the database definition
     */
    public function initialize(): void
    {
<?php foreach ($objTable->ColumnArray as $objColumn) { ?>
<?php 	if ($objColumn->Identity ||
				$objColumn->Timestamp) {
			// do not initialize with a default value
	 	}
	 	else { ?>
        $this-><?= $objColumn->VariableName ?> = <?php
        $defaultVarName = 'self::' . strtoupper($objColumn->Name) . '_DEFAULT';
        if ($objColumn->VariableType != \QCubed\Type::DATE_TIME)
            print ($defaultVarName);
        else
            print "(" . $defaultVarName . " === null)?null:new QDateTime(" . $defaultVarName . ")";
        ?>;
        $this->__blnValid[self::<?= strtoupper($objColumn->Name) ?>_FIELD] = true;
<?php 	} ?>
<?php } ?>
    }

    /**
    * Convert the object to its string representation.
    *
    * @return string The string representation of the object.
    */
    abstract function __toString(): string;