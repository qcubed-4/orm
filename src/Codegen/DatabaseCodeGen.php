<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Codegen;

use QCubed\Database;
use QCubed\Exception\Caller;
use Exception;
use QCubed\Exception\InvalidCast;
use QCubed\Type;
use QCubed\Database\ForeignKey;
use QCubed\project\Codegen\CodegenBase as QCodegen;
use SimpleXmlElement;

/**
 * @property array $TableArray Array of ORM tables
 * @property array $TypeTableArray Array of Type tables
 */

class DatabaseCodeGen extends QCodegen
{
    public SimpleXmlElement $objSettingsXml;    // Make public so templates can use it directly.

    // Objects
    /** @var array|SqlTable[] Array of tables in the database */
    protected array $objTableArray = [];
    /** @var array|SqlTable[] Array of tables in the database */
    protected  array $strExcludedTableArray = [];
    /** @var array|SqlTable[] Array of tables in the database */
    protected array $objTypeTableArray = [];
    /** @var array|SqlTable[] Array of tables in the database */
    protected array $strAssociationTableNameArray = [];

    /** @var Database\DatabaseBase The database we are dealing with */
    protected Database\DatabaseBase $objDb;

    protected int $intDatabaseIndex;
    /** @var string The delimiter to be used for parsing comments on the DB tables for being used as the name of ModelConnector's Label */
    protected string $strCommentConnectorLabelDelimiter;
    protected string $strErrors = '';

    // Table Suffixes
    protected array $strTypeTableSuffixArray;
    protected array $intTypeTableSuffixLengthArray;
    protected string $strAssociationTableSuffix;
    protected int $intAssociationTableSuffixLength;

    // Table Prefix
    protected mixed $strStripTablePrefix;
    protected int $intStripTablePrefixLength;

    // Exclude Patterns & Lists
    protected mixed $strExcludePattern;
    protected array $strExcludeListArray;

    // Include Patterns & Lists
    protected mixed $strIncludePattern;
    protected array $strIncludeListArray;

    // Uniquely Associated Objects
    protected string $strAssociatedObjectPrefix;
    protected string $strAssociatedObjectSuffix;

    // Relationship Scripts
    protected mixed $strRelationships;
    protected bool $blnRelationshipsIgnoreCase;

    protected mixed $strRelationshipsScriptPath;
    protected mixed $strRelationshipsScriptFormat;
    protected bool $blnRelationshipsScriptIgnoreCase;

    protected array $strRelationshipLinesQcubed = array();
    protected array $strRelationshipLinesSql = array();

    // Type Table Items, Table Name and Column Name RegExp Patterns
    protected string $strPatternTableName = '[[:alpha:]_][[:alnum:]_]*';
    protected string $strPatternColumnName = '[[:alpha:]_][[:alnum:]_]*';
    protected string $strPatternKeyName = '[[:alpha:]_][[:alnum:]_]*';

    protected mixed $blnGenerateControlId;
    protected OptionFile $objModelConnectorOptions;
    protected mixed $blnAutoInitialize;
    protected mixed $blnPrivateColumnVars;


    /**
     * Retrieves the specified table by its name.
     *
     * @param string $strTableName The name of the table to retrieve.
     * @return SqlTable|TypeTable The table object if it exists in the available table arrays.
     * @throws Caller If the table does not exist or cannot be processed.
     */
    public function getTable(string $strTableName): SqlTable|TypeTable
    {
        $strTableName = strtolower($strTableName);
        if (array_key_exists($strTableName, $this->objTableArray)) {
            return $this->objTableArray[$strTableName];
        }
        if (array_key_exists($strTableName, $this->objTypeTableArray)) {
            return $this->objTypeTableArray[$strTableName];
        }
        throw new Caller(sprintf('The Table does not exist or could not be processed: %s. %s', $strTableName,
            $this->strErrors));
    }

    /**
     * Retrieves the specified column from the given table.
     *
     * @param string $strTableName The name of the table from which the column is to be retrieved.
     * @param string $strColumnName The name of the column to retrieve.
     * @return mixed The column object if it exists in the specified table.
     * @throws Caller If the table does not exist, or the column does not exist within the table.
     */
    public function getColumn(string $strTableName, string $strColumnName): mixed
    {
        try {
            $objTable = $this->getTable($strTableName);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
        $strColumnName = strtolower($strColumnName);
        if (array_key_exists($strColumnName, $objTable->ColumnArray)) {
            return $objTable->ColumnArray[$strColumnName];
        }
        throw new Caller(sprintf('Column does not exist in %s: %s', $strTableName, $strColumnName));
    }

    /**
     * Given a CASE-INSENSITIVE table and column name, it will return TRUE if the Table/Column
     * exists ANYWHERE in the already analyzed database
     *
     * @param string $strTableName
     * @param string $strColumnName
     * @return boolean true if it is found/validated
     */
    public function validateTableColumn(string $strTableName, string $strColumnName): bool
    {
        $strTableName = trim(strtolower($strTableName));
        $strColumnName = trim(strtolower($strColumnName));

        if (array_key_exists($strTableName, $this->objTableArray)) {
            $strTableName = $this->objTableArray[$strTableName]->Name;
        } else {
            if (array_key_exists($strTableName, $this->objTypeTableArray)) {
                $strTableName = $this->objTypeTableArray[$strTableName]->Name;
            } else {
                if (array_key_exists($strTableName, $this->strAssociationTableNameArray)) {
                    $strTableName = $this->strAssociationTableNameArray[$strTableName];
                } else {
                    return false;
                }
            }
        }

        $objFieldArray = $this->objDb->getFieldsForTable($strTableName);

        foreach ($objFieldArray as $objField) {
            if (trim(strtolower($objField->Name)) == $strColumnName) {
                return true;
            }
        }

        return false;
    }

    public function getTitle(): string
    {
        if (!Database\Service::isInitialized()) {
            return '';
        }

        $objDatabase = Database\Service::getDatabase($this->intDatabaseIndex);

        if ($objDatabase) {
            return sprintf('Database Index #%s (%s / %s / %s)', $this->intDatabaseIndex, $objDatabase->Adapter,
                $objDatabase->Server, $objDatabase->Database);
        } else {
            return sprintf('Database Index #%s (N/A)', $this->intDatabaseIndex);
        }
    }

    public function getConfigXml(): string
    {
        $strCrLf = "\r\n";
        $strToReturn = sprintf('		<database index="%s">%s', $this->intDatabaseIndex, $strCrLf);
        $strToReturn .= sprintf('			<className prefix="%s" suffix="%s"/>%s', $this->strClassPrefix,
            $this->strClassSuffix, $strCrLf);
        $strToReturn .= sprintf('			<associatedObjectName prefix="%s" suffix="%s"/>%s',
            $this->strAssociatedObjectPrefix, $this->strAssociatedObjectSuffix, $strCrLf);
        $strToReturn .= sprintf('			<typeTableIdentifier suffix="%s"/>%s',
            implode(',', $this->strTypeTableSuffixArray), $strCrLf);
        $strToReturn .= sprintf('			<associationTableIdentifier suffix="%s"/>%s',
            $this->strAssociationTableSuffix, $strCrLf);
        $strToReturn .= sprintf('			<stripFromTableName prefix="%s"/>%s', $this->strStripTablePrefix,
            $strCrLf);
        $strToReturn .= sprintf('			<excludeTables pattern="%s" list="%s"/>%s', $this->strExcludePattern,
            implode(',', $this->strExcludeListArray), $strCrLf);
        $strToReturn .= sprintf('			<includeTables pattern="%s" list="%s"/>%s', $this->strIncludePattern,
            implode(',', $this->strIncludeListArray), $strCrLf);
        $strToReturn .= sprintf('			<relationships>%s', $strCrLf);
        if ($this->strRelationships) {
            $strToReturn .= sprintf('			%s%s', $this->strRelationships, $strCrLf);
        }
        $strToReturn .= sprintf('			</relationships>%s', $strCrLf);
        $strToReturn .= sprintf('			<relationshipsScript filepath="%s" format="%s"/>%s',
            $this->strRelationshipsScriptPath, $this->strRelationshipsScriptFormat, $strCrLf);
        $strToReturn .= sprintf('		</database>%s', $strCrLf);
        return $strToReturn;
    }

    public function getReportLabel(): string
    {
        // Setup Report Label
        $intTotalTableCount = count($this->objTableArray) + count($this->objTypeTableArray);
        if ($intTotalTableCount == 0) {
            $strReportLabel = 'There were no tables available to attempt code generation.';
        } else {
            if ($intTotalTableCount == 1) {
                $strReportLabel = 'There was 1 table available to attempt code generation:';
            } else {
                $strReportLabel = 'There were ' . $intTotalTableCount . ' tables available to attempt code generation:';
            }
        }

        return $strReportLabel;
    }

    /**
     * @throws Caller
     * @throws InvalidCast
     */
    public function generateAll(): string
    {
        $strReport = '';

        require_once(__DIR__ . '/template_utils.php');

        // Iterate through all the tables, generating one class at a time
        if ($this->objTableArray) {
            foreach ($this->objTableArray as $objTable) {
                if ($this->generateTable($objTable)) {
                    $intCount = $objTable->ReferenceCount;
                    if ($intCount == 0) {
                        $strCount = '(with no relationships)';
                    } else {
                        if ($intCount == 1) {
                            $strCount = '(with 1 relationship)';
                        } else {
                            $strCount = sprintf('(with %s relationships)', $intCount);
                        }
                    }
                    $strReport .= sprintf("A successfully generated DB ORM Class:   %s %s\r\n", $objTable->ClassName,
                        $strCount);
                } else {
                    $strReport .= sprintf("FAILED to generate a DB ORM Class:       %s\r\n", $objTable->ClassName);
                }
            }
        }

        // Iterate through all the TYPE tables, generating one TYPE class at a time
        if ($this->objTypeTableArray) {
            foreach ($this->objTypeTableArray as $objTypeTable) {
                if ($this->generateTypeTable($objTypeTable)) {
                    $strReport .= sprintf("A successfully generated DB Type Class:  %s\n", $objTypeTable->ClassName);
                } else {
                    $strReport .= sprintf("FAILED to generate DB Type class:      %s\n", $objTypeTable->ClassName);
                }
            }
        }

        return $strReport;
    }

    /**
     * @param DatabaseCodeGen[] $objCodeGenArray
     * @return array
     * @throws Caller
     * @throws InvalidCast
     */
    public static function generateAggregateHelper(array $objCodeGenArray): array
    {
        $strToReturn = array();

        if (count($objCodeGenArray)) {
            // Standard ORM Tables
            $objTableArray = array();
            foreach ($objCodeGenArray as $objCodeGen) {
                $objCurrentTableArray = $objCodeGen->TableArray;
                foreach ($objCurrentTableArray as $objTable) {
                    $objTableArray[$objTable->ClassName] = $objTable;
                }
            }

            $mixArgumentArray = array('objTableArray' => $objTableArray);
            if ($objCodeGenArray[0]->generateFiles('aggregate_db_orm', $mixArgumentArray)) {
                $strToReturn[] = 'Successfully generated Aggregate DB ORM file(s)';
            } else {
                $strToReturn[] = 'FAILED to generate Aggregate DB ORM file(s)';
            }

            // Type Tables
            $objTableArray = array();
            foreach ($objCodeGenArray as $objCodeGen) {
                $objCurrentTableArray = $objCodeGen->TypeTableArray;
                foreach ($objCurrentTableArray as $objTable) {
                    $objTableArray[$objTable->ClassName] = $objTable;
                }
            }

            $mixArgumentArray = array('objTableArray' => $objTableArray);
            if ($objCodeGenArray[0]->generateFiles('aggregate_db_type', $mixArgumentArray)) {
                $strToReturn[] = 'Successfully generated Aggregate DB Type file(s)';
            } else {
                $strToReturn[] = 'FAILED to generate Aggregate DB Type file(s)';
            }
        }

        return $strToReturn;
    }

    /**
     * Constructor for initializing the CodeGen settings and processing related configurations.
     *
     * @param mixed $objSettingsXml The XML settings object that contains configuration details for code generation.
     * @return void
     * @throws Exception If there are critical errors in the provided settings, such as invalid or missing required information.
     */
    public function __construct(object $objSettingsXml)
    {
        parent::__construct($objSettingsXml);
        // Make a settings file accessible to templates
        //$this->objSettingsXml = $objSettingsXml;

        // Setup Local Arrays
        $this->strAssociationTableNameArray = array();
        $this->objTableArray = array();
        $this->objTypeTableArray = array();
        $this->strExcludedTableArray = array();

        // Set the DatabaseIndex
        $this->intDatabaseIndex = static::lookupSetting($objSettingsXml, '', 'index', Type::INTEGER);

        // Append Suffix/Prefixes
        $this->strClassPrefix = static::lookupSetting($objSettingsXml, 'className', 'prefix');
        $this->strClassSuffix = static::lookupSetting($objSettingsXml, 'className', 'suffix');
        $this->strAssociatedObjectPrefix = static::lookupSetting($objSettingsXml, 'associatedObjectName', 'prefix');
        $this->strAssociatedObjectSuffix = static::lookupSetting($objSettingsXml, 'associatedObjectName', 'suffix');

        // Table Type Identifiers
        $strTypeTableSuffixList = static::lookupSetting($objSettingsXml, 'typeTableIdentifier', 'suffix');
        $strTypeTableSuffixArray = explode(',', $strTypeTableSuffixList);
        foreach ($strTypeTableSuffixArray as $strTypeTableSuffix) {
            $this->strTypeTableSuffixArray[] = trim($strTypeTableSuffix);
            $this->intTypeTableSuffixLengthArray[] = strlen(trim($strTypeTableSuffix));
        }
        $this->strAssociationTableSuffix = static::lookupSetting($objSettingsXml, 'associationTableIdentifier',
            'suffix');
        $this->intAssociationTableSuffixLength = strlen($this->strAssociationTableSuffix);

        // Stripping TablePrefixes
        $this->strStripTablePrefix = static::lookupSetting($objSettingsXml, 'stripFromTableName', 'prefix');
        $this->intStripTablePrefixLength = strlen($this->strStripTablePrefix);

        // Exclude/Include Tables
        $this->strExcludePattern = static::lookupSetting($objSettingsXml, 'excludeTables', 'pattern');
        $strExcludeList = static::lookupSetting($objSettingsXml, 'excludeTables', 'list');
        $this->strExcludeListArray = explode(',', $strExcludeList);
        array_walk($this->strExcludeListArray, 'QCubed\Codegen\array_trim');

        // Include Patterns
        $this->strIncludePattern = static::lookupSetting($objSettingsXml, 'includeTables', 'pattern');
        $strIncludeList = static::lookupSetting($objSettingsXml, 'includeTables', 'list');
        $this->strIncludeListArray = explode(',', $strIncludeList);
        array_walk($this->strIncludeListArray, 'QCubed\Codegen\array_trim');

        // Relationship Scripts
        $this->strRelationships = static::lookupSetting($objSettingsXml, 'relationships');
        $this->strRelationshipsScriptPath = static::lookupSetting($objSettingsXml, 'relationshipsScript', 'filepath');
        $this->strRelationshipsScriptFormat = static::lookupSetting($objSettingsXml, 'relationshipsScript', 'format');

        // Column Comment for ModelConnectorLabel setting.
        $this->strCommentConnectorLabelDelimiter = static::lookupSetting($objSettingsXml,
            'columnCommentForModelConnector', 'delimiter');

        // Check to make sure things that are required are there
        if (!$this->intDatabaseIndex) {
            $this->strErrors .= "CodeGen Settings XML Fatal Error: databaseIndex was invalid or not set\r\n";
        }

        // Aggregate RelationshipLinesQcubed and RelationshipLinesSql arrays
        if ($this->strRelationships) {
            $strLines = explode("\n", strtolower($this->strRelationships));
            if ($strLines) {
                foreach ($strLines as $strLine) {
                    $strLine = trim($strLine);

                    if (($strLine) &&
                        (strlen($strLine) > 2) &&
                        (!str_starts_with($strLine, '//')) &&
                        (!str_starts_with($strLine, '--')) &&
                        (!str_starts_with($strLine, '#'))
                    ) {
                        $this->strRelationshipLinesQcubed[$strLine] = $strLine;
                    }
                }
            }
        }

        if ($this->strRelationshipsScriptPath) {
            if (!file_exists($this->strRelationshipsScriptPath)) {
                $this->strErrors .= sprintf("CodeGen Settings XML Fatal Error: relationshipsScript filepath \"%s\" does not exist\r\n",
                    $this->strRelationshipsScriptPath);
            } else {
                $strScript = strtolower(trim(file_get_contents($this->strRelationshipsScriptPath)));
                switch (strtolower($this->strRelationshipsScriptFormat)) {
                    case 'qcubed':
                        $strLines = explode("\n", $strScript);
                        if ($strLines) {
                            foreach ($strLines as $strLine) {
                                $strLine = trim($strLine);

                                if (($strLine) &&
                                    (strlen($strLine) > 2) &&
                                    (!str_starts_with($strLine, '//')) &&
                                    (!str_starts_with($strLine, '--')) &&
                                    (!str_starts_with($strLine, '#'))
                                ) {
                                    $this->strRelationshipLinesQcubed[$strLine] = $strLine;
                                }
                            }
                        }
                        break;

                    case 'sql':
                        // Separate all commands in the script (separated by ";")
                        $strCommands = explode(';', $strScript);
                        if ($strCommands) {
                            foreach ($strCommands as $strCommand) {
                                $strCommand = trim($strCommand);

                                if ($strCommand) {
                                    // Take out all comment lines in the script
                                    $strLines = explode("\n", $strCommand);
                                    $strCommand = '';
                                    foreach ($strLines as $strLine) {
                                        $strLine = trim($strLine);
                                        if (($strLine) &&
                                            (!str_starts_with($strLine, '//')) &&
                                            (!str_starts_with($strLine, '--')) &&
                                            (!str_starts_with($strLine, '#'))
                                        ) {
                                            $strLine = str_replace('	', ' ', $strLine);
                                            $strLine = str_replace('        ', ' ', $strLine);
                                            $strLine = str_replace('       ', ' ', $strLine);
                                            $strLine = str_replace('      ', ' ', $strLine);
                                            $strLine = str_replace('     ', ' ', $strLine);
                                            $strLine = str_replace('    ', ' ', $strLine);
                                            $strLine = str_replace('   ', ' ', $strLine);
                                            $strLine = str_replace('  ', ' ', $strLine);
                                            $strLine = str_replace('  ', ' ', $strLine);
                                            $strLine = str_replace('  ', ' ', $strLine);
                                            $strLine = str_replace('  ', ' ', $strLine);
                                            $strLine = str_replace('  ', ' ', $strLine);

                                            $strCommand .= $strLine . ' ';
                                        }
                                    }

                                    $strCommand = trim($strCommand);
                                    if ((str_starts_with($strCommand, 'alter table')) &&
                                        (str_contains($strCommand, 'foreign key'))
                                    ) {
                                        $this->strRelationshipLinesSql[$strCommand] = $strCommand;
                                    }
                                }
                            }
                        }
                        break;

                    default:
                        $this->strErrors .= sprintf("CodeGen Settings XML Fatal Error: relationshipsScript format \"%s\" is invalid (must be either \"qcubed\" or \"sql\")\r\n",
                            $this->strRelationshipsScriptFormat);
                        break;
                }
            }
        }

        $this->blnGenerateControlId = static::lookupSetting($objSettingsXml, 'generateControlId', 'support',
            Type::BOOLEAN);
        $this->objModelConnectorOptions = new OptionFile();

        $this->blnAutoInitialize = static::lookupSetting($objSettingsXml, 'createOptions', 'autoInitialize',
            Type::BOOLEAN);
        $this->blnPrivateColumnVars = static::lookupSetting($objSettingsXml, 'createOptions', 'privateColumnVars',
            Type::BOOLEAN);

        if ($this->strErrors) {
            return;
        }

        $this->analyzeDatabase();
    }

    /**
     * Analyzes the database configuration, tables, and relationships.
     *
     * This method retrieves the database configuration and ensures it is valid. It categorizes tables
     * into type tables, association tables, and regular tables based on specific naming conventions
     * and suffixes. Each category of tables is processed accordingly to extract relevant metadata.
     * Type tables and relationships between tables are analyzed to identify invalid configurations
     * or issues in the database schema. Warnings are generated for invalid foreign keys or
     * non-compliant relationships.
     *
     * @return void
     * @throws Caller
     */
    protected function analyzeDatabase(): void
    {
        if (!Database\Service::count()) {
            $this->strErrors = 'FATAL ERROR: No databases are listed in the configuration file. Edit the /project/includes/configuration/active/databases.cfg.php file';
            return;
        }

        // Set aside the Database object
        $this->objDb = Database\Service::getDatabase($this->intDatabaseIndex);

        // Ensure the DB Exists
        if (!isset($this->objDb)) {
            $this->strErrors = 'FATAL ERROR: No database configured at index ' . $this->intDatabaseIndex . '. Check your configuration file.';
            return;
        }

        // Ensure a DB Profiling is DISABLED on this DB
        if ($this->objDb->EnableProfiling) {
            $this->strErrors = 'FATAL ERROR: Code generator cannot analyze the database at index ' . $this->intDatabaseIndex . ' while n DB Profiling is enabled.';
            return;
        }

        // Get the list of Tables as a string[]
        $strTableArray = $this->objDb->getTables();


        // ITERATION 1: Simply create the Table and TypeTable Arrays
        if ($strTableArray) {
            foreach ($strTableArray as $strTableName) {

                // Do we Exclude this Table Name? (given includeTables and excludeTables)
                // First check the lists of Excludes and the Exclude Patterns
                if (in_array($strTableName, $this->strExcludeListArray) ||
                    (strlen($this->strExcludePattern) > 0 && preg_match(":" . $this->strExcludePattern . ":i", $strTableName))
                ) {
                    // So we THINK we may be excluding this table
                    // But check against the explicit INCLUDE list and patterns
                    if (!in_array($strTableName, $this->strIncludeListArray) && (strlen($this->strIncludePattern) <= 0 ||
                            !preg_match(":" . $this->strIncludePattern . ":i", $strTableName))
                    ) {
                        // If we're here, then we want to exclude this table
                        $this->strExcludedTableArray[strtolower($strTableName)] = true;

                        // Exit this iteration of the foreach loop
                    }
                    continue;
                }

                // Check to see if this table name exists anywhere else yet and warn if it is
                foreach (static::$CodeGenArray as $objCodeGen) {
                    if ($objCodeGen instanceof DatabaseCodeGen) {
                        foreach ($objCodeGen->objTableArray as $objPossibleDuplicate) {
                            if (strtolower($objPossibleDuplicate->Name) == strtolower($strTableName)) {
                                $this->strErrors .= 'Duplicate Table Name Used: ' . $strTableName . "\r\n";
                            }
                        }
                    }
                }

                // Perform different tasks based on whether it's an Association table,
                // a Type table, or just a regular table
                $blnIsTypeTable = false;
                foreach ($this->intTypeTableSuffixLengthArray as $intIndex => $intTypeTableSuffixLength) {
                    if (($intTypeTableSuffixLength) &&
                        (strlen($strTableName) > $intTypeTableSuffixLength) &&
                        (substr($strTableName,
                                strlen($strTableName) - $intTypeTableSuffixLength) == $this->strTypeTableSuffixArray[$intIndex])
                    ) {
                        // Let's mark that we have a type table
                        $blnIsTypeTable = true;
                        // Create a TYPE Table and add it to the array
                        $objTypeTable = new TypeTable($strTableName);
                        $this->objTypeTableArray[strtolower($strTableName)] = $objTypeTable;
                        // If we found type table, there is no point of iterating for other type table suffixes
                        break;
//						_p("TYPE Table: $strTableName<br />", false);
                    }
                }
                if (!$blnIsTypeTable) {
                    // If the current table wasn't a type table, let's look for other table types
                    if (($this->intAssociationTableSuffixLength) &&
                        (strlen($strTableName) > $this->intAssociationTableSuffixLength) &&
                        (substr($strTableName,
                                strlen($strTableName) - $this->intAssociationTableSuffixLength) == $this->strAssociationTableSuffix)
                    ) {
                        // Add this ASSOCIATION Table Name to the array
                        $this->strAssociationTableNameArray[strtolower($strTableName)] = $strTableName;
//						_p("ASSN Table: $strTableName<br />", false);

                    } else {
                        // Create a Regular Table and add it to the array
                        $objTable = new SqlTable($strTableName);
                        $this->objTableArray[strtolower($strTableName)] = $objTable;
//						_p("Table: $strTableName<br />", false);
                    }
                }
            }
        }


        // Analyze All the Type Tables
        if ($this->objTypeTableArray) {
            foreach ($this->objTypeTableArray as $objTypeTable) {
                $this->analyzeTypeTable($objTypeTable);
            }
        }

        // Analyze All the Regular Tables
        if ($this->objTableArray) {
            foreach ($this->objTableArray as $objTable) {
                $this->analyzeTable($objTable);
            }
        }

        // Analyze All the Association Tables
        if ($this->strAssociationTableNameArray) {
            foreach ($this->strAssociationTableNameArray as $strAssociationTableName) {
                $this->analyzeAssociationTable($strAssociationTableName);
            }
        }

        // Finally, for each Relationship in all Tables, Warn on Non-Single Column PK-based FK:
        if ($this->objTableArray) {
            foreach ($this->objTableArray as $objTable) {
                if ($objTable->ColumnArray) {
                    foreach ($objTable->ColumnArray as $objColumn) {
                        if ($objColumn->Reference && !$objColumn->Reference->IsType) {
                            $objReference = $objColumn->Reference;
//							$objReferencedTable = $this->objTableArray[strtolower($objReference->Table)];
                            $objReferencedTable = $this->getTable($objReference->Table);
                            $objReferencedColumn = $objReferencedTable->ColumnArray[strtolower($objReference->Column)];


                            if (!$objReferencedColumn->PrimaryKey) {
                                $this->strErrors .= sprintf("Warning: Invalid Relationship created in %s class (for foreign key \"%s\") -- column \"%s\" is not the single-column primary key for the referenced \"%s\" table\r\n",
                                    $objReferencedTable->ClassName, $objReference->KeyName, $objReferencedColumn->Name,
                                    $objReferencedTable->Name);
                            } else {
                                if (count($objReferencedTable->PrimaryKeyColumnArray) != 1) {
                                    $this->strErrors .= sprintf("Warning: Invalid Relationship created in %s class (for foreign key \"%s\") -- column \"%s\" is not the single-column primary key for the referenced \"%s\" table\r\n",
                                        $objReferencedTable->ClassName, $objReference->KeyName,
                                        $objReferencedColumn->Name, $objReferencedTable->Name);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieves a comma-separated string of column names from the given table.
     *
     * @param SqlTable $objTable The table object from which to extract the list of column names.
     * @return string A comma-separated string containing the names of all columns in the specified table.
     */
    protected function listOfColumnsFromTable(SqlTable $objTable): string
    {
        $strArray = array();
        $objColumnArray = $objTable->ColumnArray;
        if ($objColumnArray) {
            foreach ($objColumnArray as $objColumn) {
                $strArray[] = $objColumn->Name;
            }
        }
        return implode(', ', $strArray);
    }

    /**
     * Retrieves an array of specified columns from the given table.
     *
     * @param SqlTable $objTable The table object from which the columns are to be retrieved.
     * @param array|null $strColumnNameArray An array of column names to retrieve. If null, no columns are retrieved.
     * @return array An array containing the requested columns from the table.
     */
    protected function getColumnArray(SqlTable $objTable, ?array $strColumnNameArray): array
    {
        $objToReturn = array();

        if ($strColumnNameArray) {
            foreach ($strColumnNameArray as $strColumnName) {
                $objToReturn[] = $objTable->ColumnArray[strtolower($strColumnName)];
            }
        }

        return $objToReturn;
    }

    /**
     * Generates the necessary files for the specified SQL table.
     *
     * @param SqlTable $objTable The SQL table for which the files are to be generated.
     * @return bool The result of the file generation process.
     * @throws Caller
     * @throws InvalidCast
     */
    public function generateTable(SqlTable $objTable): bool
    {
        // Create Argument Array
        $mixArgumentArray = array('objTable' => $objTable);
        return $this->generateFiles('db_orm', $mixArgumentArray);
    }

    /**
     * Generates files for the specified type table.
     *
     * @param TypeTable $objTypeTable The type table instance for which files are to be generated.
     * @return bool The result of the file generation process.
     * @throws Caller
     * @throws InvalidCast
     */
    public function generateTypeTable(TypeTable $objTypeTable): bool
    {
        // Create Argument Array
        $mixArgumentArray = array('objTypeTable' => $objTypeTable);
        return $this->generateFiles('db_type', $mixArgumentArray);
    }

    /**
     * Analyzes an association table to verify its structure, foreign keys, and many-to-many relationships.
     *
     * @param string $strTableName The name of the table being analyzed for association relationships.
     * @return void
     * @throws Caller
     * @throws Exception
     */
    protected function analyzeAssociationTable(string $strTableName): void
    {
        $objFieldArray = $this->objDb->getFieldsForTable($strTableName);

        // Association tables must have 2 fields
        if (count($objFieldArray) != 2) {
            $this->strErrors .= sprintf("AssociationTable %s does not have exactly 2 columns.\n",
                $strTableName);
            return;
        }

        if ((!$objFieldArray[0]->NotNull) ||
            (!$objFieldArray[1]->NotNull)
        ) {
            $this->strErrors .= sprintf("AssociationTable %s's two columns must both be not null",
                $strTableName);
            return;
        }

        if (((!$objFieldArray[0]->PrimaryKey) &&
                ($objFieldArray[1]->PrimaryKey)) ||
            (($objFieldArray[0]->PrimaryKey) &&
                (!$objFieldArray[1]->PrimaryKey))
        ) {
            $this->strErrors .= sprintf("AssociationTable %s only supports two-column composite Primary Keys.\n",
                $strTableName);
            return;
        }

        $objForeignKeyArray = $this->objDb->getForeignKeysForTable($strTableName);

        // Adds to it, the list of Foreign Keys from any Relationships Script
        $objForeignKeyArray = $this->getForeignKeysFromRelationshipsScript($strTableName, $objForeignKeyArray);

        if (count($objForeignKeyArray) != 2) {
            $this->strErrors .= sprintf("AssociationTable %s does not have exactly 2 foreign keys.  Code Gen analysis found %s.\n",
                $strTableName, count($objForeignKeyArray));
            return;
        }

        // Setup two new ManyToManyReference objects
        $objManyToManyReferenceArray[0] = new ManyToManyReference();
        $objManyToManyReferenceArray[1] = new ManyToManyReference();

        // Ensure that the linked tables are both not excluded
        if (array_key_exists($objForeignKeyArray[0]->ReferenceTableName, $this->strExcludedTableArray) ||
            array_key_exists($objForeignKeyArray[1]->ReferenceTableName, $this->strExcludedTableArray)
        ) {
            return;
        }

        // Setup GraphPrefixArray (if applicable)
        if ($objForeignKeyArray[0]->ReferenceTableName == $objForeignKeyArray[1]->ReferenceTableName) {
            // We are analyzing a graph association
            $strGraphPrefixArray = $this->calculateGraphPrefixArray($objForeignKeyArray);
        } else {
            $strGraphPrefixArray = array('', '');
        }

        // Go through each FK and set up each ManyToManyReference object
        for ($intIndex = 0; $intIndex < 2; $intIndex++) {
            $objManyToManyReference = $objManyToManyReferenceArray[$intIndex];

            $objForeignKey = $objForeignKeyArray[$intIndex];
            $objOppositeForeignKey = $objForeignKeyArray[($intIndex == 0) ? 1 : 0];

            // Make sure the FK is a single-column FK
            if (count($objForeignKey->ColumnNameArray) != 1) {
                $this->strErrors .= sprintf("AssoiationTable %s has multi-column foreign keys.\n",
                    $strTableName);
                return;
            }

            $objManyToManyReference->KeyName = $objForeignKey->KeyName;
            $objManyToManyReference->Table = $strTableName;
            $objManyToManyReference->Column = $objForeignKey->ColumnNameArray[0];
            $objManyToManyReference->PropertyName = $this->modelColumnPropertyName($objManyToManyReference->Column);
            $objManyToManyReference->OppositeColumn = $objOppositeForeignKey->ColumnNameArray[0];
            $objManyToManyReference->AssociatedTable = $objOppositeForeignKey->ReferenceTableName;

            // Calculate OppositeColumnVariableName
            // Do this by first making a fake column which is the PK column of the AssociatedTable,
            // but whose column name is ManyToManyReference->Column
//				$objOppositeColumn = clone($this->objTableArray[strtolower($objManyToManyReference->AssociatedTable)]->PrimaryKeyColumnArray[0]);

            $objTable = $this->getTable($objManyToManyReference->AssociatedTable);
            $objOppositeColumn = clone($objTable->PrimaryKeyColumnArray[0]);
            $objOppositeColumn->Name = $objManyToManyReference->OppositeColumn;
            $objManyToManyReference->OppositeVariableName = $this->modelColumnVariableName($objOppositeColumn);
            $objManyToManyReference->OppositePropertyName = $this->modelColumnPropertyName($objOppositeColumn->Name);
            $objManyToManyReference->OppositeVariableType = $objOppositeColumn->VariableType;
            $objManyToManyReference->OppositeDbType = $objOppositeColumn->DbType;

            $objManyToManyReference->VariableName = $this->modelReverseReferenceVariableName($objOppositeForeignKey->ReferenceTableName);
            $objManyToManyReference->VariableType = $this->modelReverseReferenceVariableType($objOppositeForeignKey->ReferenceTableName);

            $objManyToManyReference->ObjectDescription = $strGraphPrefixArray[$intIndex] . $this->calculateObjectDescriptionForAssociation($strTableName,
                    $objForeignKey->ReferenceTableName, $objOppositeForeignKey->ReferenceTableName, false);
            $objManyToManyReference->ObjectDescriptionPlural = $strGraphPrefixArray[$intIndex] . $this->calculateObjectDescriptionForAssociation($strTableName,
                    $objForeignKey->ReferenceTableName, $objOppositeForeignKey->ReferenceTableName, true);

            $objManyToManyReference->OppositeObjectDescription = $strGraphPrefixArray[($intIndex == 0) ? 1 : 0] . $this->calculateObjectDescriptionForAssociation($strTableName,
                    $objOppositeForeignKey->ReferenceTableName, $objForeignKey->ReferenceTableName, false);
            $objManyToManyReference->IsTypeAssociation = ($objTable instanceof TypeTable);
            $objManyToManyReference->Options = $this->objModelConnectorOptions->getOptions($this->modelClassName($objForeignKey->ReferenceTableName),
                $objManyToManyReference->ObjectDescription);

        }


        // Iterate through the list of Columns to create objColumnArray
        $objColumnArray = array();
        foreach ($objFieldArray as $objField) {
            if (($objField->Name != $objManyToManyReferenceArray[0]->Column) &&
                ($objField->Name != $objManyToManyReferenceArray[1]->Column)
            ) {
                $objColumn = $this->analyzeTableColumn($objField, null);
                if ($objColumn) {
                    $objColumnArray[strtolower($objColumn->Name)] = $objColumn;
                }
            }
        }

        // Make sure lone primary key columns are marked as unique
        $objKeyColumn = null;
        foreach ($objColumnArray as $objColumn) {
            if ($objColumn->PrimaryKey) {
                if ($objKeyColumn === null) {
                    $objKeyColumn = $objColumn;
                } else {
                    $objKeyColumn = false; // multiple key columns
                }
            }
        }
        if ($objKeyColumn) {
            $objKeyColumn->Unique = true;
        }

        $objManyToManyReferenceArray[0]->ColumnArray = $objColumnArray;
        $objManyToManyReferenceArray[1]->ColumnArray = $objColumnArray;

        // Push the ManyToManyReference Objects to the tables
        for ($intIndex = 0; $intIndex < 2; $intIndex++) {
            $objManyToManyReference = $objManyToManyReferenceArray[$intIndex];
            $strTableWithReference = $objManyToManyReferenceArray[($intIndex == 0) ? 1 : 0]->AssociatedTable;

            $objTable = $this->getTable($strTableWithReference);
            $objArray = $objTable->ManyToManyReferenceArray;
            $objArray[] = $objManyToManyReference;
            $objTable->ManyToManyReferenceArray = $objArray;
        }

    }

    /**
     * Analyzes the structure and data of a type table to validate its format and extract its metadata.
     *
     * @param TypeTable $objTypeTable The type table object to analyze, which includes properties for its structure and metadata.
     * @return void This method does not return a value; it modifies the provided type table object directly and updates its metadata.
     * @throws Caller
     * @throws InvalidCast
     * @throws Exception
     */
    protected function analyzeTypeTable(TypeTable $objTypeTable): void
    {
        // Set up the Array of Reserved Words
        $strReservedWords = explode(',', static::PHP_RESERVED_WORDS);
        for ($intIndex = 0; $intIndex < count($strReservedWords); $intIndex++) {
            $strReservedWords[$intIndex] = strtolower(trim($strReservedWords[$intIndex]));
        }

        // Set up a type table object
        $strTableName = $objTypeTable->Name;
        $objTypeTable->ClassName = $this->modelClassName($strTableName);

        // Ensure that there are only 2 fields, an integer PK field (can be named anything) and a unique varchar field
        $objFieldArray = $this->objDb->getFieldsForTable($strTableName);

        if (($objFieldArray[0]->Type != Database\FieldType::INTEGER) ||
            (!$objFieldArray[0]->PrimaryKey)
        ) {
            $this->strErrors .= sprintf("TypeTable %s's first column is not a PK integer.\n",
                $strTableName);
            return;
        }

        if (($objFieldArray[1]->Type != Database\FieldType::VAR_CHAR) ||
            (!$objFieldArray[1]->Unique)
        ) {
            $this->strErrors .= sprintf("TypeTable %s's second column is not a unique VARCHAR.\n",
                $strTableName);
            return;
        }

        // Get the rows
        $objResult = $this->objDb->query(sprintf('SELECT * FROM %s', $strTableName));
        $strNameArray = array();
        $strTokenArray = array();
        $strExtraPropertyArray = array();
        $extraFields = array();
        $intRowWidth = count($objFieldArray);
        while ($objDbRow = $objResult->getNextRow()) {
            $strRowArray = $objDbRow->getColumnNameArray();
            $id = $strRowArray[0];
            $name = $strRowArray[1];

            $strNameArray[$id] = str_replace("'", "\\'", str_replace('\\', '\\\\', $name));
            $strTokenArray[$id] = $this->typeTokenFromTypeName($name);
            if ($intRowWidth > 2) { // there are extra columns to the process
                $strExtraPropertyArray[$id] = array();
                for ($i = 2; $i < $intRowWidth; $i++) {
                    $strFieldName = static::typeColumnPropertyName($objFieldArray[$i]->Name);
                    $extraFields[$i - 2]['name'] = $strFieldName;
                    $extraFields[$i - 2]['type'] = $this->variableTypeFromDbType($objFieldArray[$i]->Type);
                    $extraFields[$i - 2]['nullAllowed'] = !$objFieldArray[$i]->NotNull;

                    // Get and resolve type-based value
                    $value = $objDbRow->getColumn($objFieldArray[$i]->Name, $objFieldArray[$i]->Type);
                    $strExtraPropertyArray[$id][$strFieldName] = $value;
                }
            }

            foreach ($strReservedWords as $strReservedWord) {
                if (trim(strtolower($strTokenArray[$id])) == $strReservedWord) {
                    $this->strErrors .= sprintf("Warning: TypeTable %s contains a type name, which is a reserved word: %s. Appended _ to the beginning of it.\r\n",
                        $strTableName, $strReservedWord);
                    $strTokenArray[$id] = '_' . $strTokenArray[$id];
                }
            }
            if (strlen($strTokenArray[$id]) == 0) {
                $this->strErrors .= sprintf("Warning: TypeTable %s contains an invalid type name: %s\r\n",
                    $strTableName, stripslashes($strNameArray[$id]));
                return;
            }
        }

        ksort($strNameArray);
        ksort($strTokenArray);

        $objTypeTable->NameArray = $strNameArray;
        $objTypeTable->TokenArray = $strTokenArray;
        $objTypeTable->ExtraFieldsArray = $extraFields;
        $objTypeTable->ExtraPropertyArray = $strExtraPropertyArray;
        $objColumn = $this->analyzeTableColumn($objFieldArray[0], $objTypeTable);
        $objColumn->Unique = true;
        $objTypeTable->KeyColumn = $objColumn;
    }

    /**
     * Analyzes the structure of a given SQL table and populates its metadata, including columns, indexes, and foreign keys.
     *
     * @param SqlTable $objTable The table objects to be analyzed and populated with metadata, including its columns, indexes, and relationships.
     * @return void
     * @throws Caller
     * @throws Exception
     */
    protected function analyzeTable(SqlTable $objTable): void
    {
        // Set up the Table Object
        $objTable->OwnerDbIndex = $this->intDatabaseIndex;
        $strTableName = $objTable->Name;
        $objTable->ClassName = $this->modelClassName($strTableName);
        $objTable->ClassNamePlural = $this->pluralize($objTable->ClassName);

        $objTable->Options = $this->objModelConnectorOptions->getOptions($objTable->ClassName,
            OptionFile::TABLE_OPTIONS_FIELD_NAME);

        // Get the List of Columns
        $objFieldArray = $this->objDb->getFieldsForTable($strTableName);

        // Iterate through the list of Columns to create objColumnArray
        $objColumnArray = array();
        if ($objFieldArray) {
            foreach ($objFieldArray as $objField) {
                $objColumn = $this->analyzeTableColumn($objField, $objTable);
                if ($objColumn) {
                    $objColumnArray[strtolower($objColumn->Name)] = $objColumn;
                }
            }
        }
        $objTable->ColumnArray = $objColumnArray;

        // Make sure lone primary key columns are marked as unique
        $objKeyColumn = null;
        foreach ($objColumnArray as $objColumn) {
            if ($objColumn->PrimaryKey) {
                if ($objKeyColumn === null) {
                    $objKeyColumn = $objColumn;
                } else {
                    $objKeyColumn = false; // multiple key columns
                }
            }
        }
        if ($objKeyColumn) {
            $objKeyColumn->Unique = true;
        }


        // Get the List of Indexes
        $objTable->IndexArray = $this->objDb->getIndexesForTable($objTable->Name);

        // Create an Index array
        $objIndexArray = array();
        // Create our Index for Primary Key (if applicable)
        $strPrimaryKeyArray = array();
        foreach ($objColumnArray as $objColumn) {
            if ($objColumn->PrimaryKey) {
                $objPkColumn = $objColumn;
                $strPrimaryKeyArray[] = $objColumn->Name;
            }
        }
        if (!empty($objPkColumn)) {
            $objIndex = new Index();
            $objIndex->KeyName = 'pk_' . $strTableName;
            $objIndex->PrimaryKey = true;
            $objIndex->Unique = true;
            $objIndex->ColumnNameArray = $strPrimaryKeyArray;
            $objIndexArray[] = $objIndex;

            if (count($strPrimaryKeyArray) == 1) {
                $objPkColumn->Unique = true;
                $objPkColumn->Indexed = true;
            }
        }

        // Iterate though each Index that exists in this table set any Column's "Index" property
        // to TRUE if they are a single-column index
        if ($objTable->IndexArray) {
            foreach ($objArray = $objTable->IndexArray as $objDatabaseIndex) {
                // Make sure the columns are defined
                if (count($objDatabaseIndex->ColumnNameArray) == 0) {
                    $this->strErrors .= sprintf("Index %s in table %s indexes on no columns.\n",
                        $objDatabaseIndex->KeyName, $strTableName);
                } else {
                    // Ensure every column exist in the DbIndex's ColumnNameArray
                    $blnFailed = false;
                    foreach ($objArray = $objDatabaseIndex->ColumnNameArray as $strColumnName) {
                        if (array_key_exists(strtolower($strColumnName), $objTable->ColumnArray) &&
                            ($objTable->ColumnArray[strtolower($strColumnName)])
                        ) {
                            // The condition is true - we continue with the next iteration.
                            continue;
                        } else {
                            // When we get here, we'll add an appropriate warning.
                            $this->strErrors .= sprintf(
                                "Index %s in a table %s indexes on the column %s, which does not appear to exist.\n",
                                $objDatabaseIndex->KeyName,
                                $strTableName,
                                $strColumnName
                            );
                            $blnFailed = true;
                        }
                    }

                    if (!$blnFailed) {
                        // Let's make sure if this is a single-column index, we haven't already created a single-column index for this column
                        $blnAlreadyCreated = false;
                        foreach ($objIndexArray as $objIndex) {
                            if (count($objIndex->ColumnNameArray) == count($objDatabaseIndex->ColumnNameArray)) {
                                if (implode(',', $objIndex->ColumnNameArray) == implode(',',
                                        $objDatabaseIndex->ColumnNameArray)
                                ) {
                                    $blnAlreadyCreated = true;
                                }
                            }
                        }

                        if (!$blnAlreadyCreated) {
                            // Create the Index Object
                            $objIndex = new Index();
                            $objIndex->KeyName = $objDatabaseIndex->KeyName;
                            $objIndex->PrimaryKey = $objDatabaseIndex->PrimaryKey;
                            $objIndex->Unique = $objDatabaseIndex->Unique;
                            if ($objDatabaseIndex->PrimaryKey) {
                                $objIndex->Unique = true;
                            }
                            $objIndex->ColumnNameArray = $objDatabaseIndex->ColumnNameArray;

                            // Add the new index object to the index array
                            $objIndexArray[] = $objIndex;

                            // Lastly, if it's a single-column index, update the Column in the table to reflect this
                            if (count($objDatabaseIndex->ColumnNameArray) == 1) {
                                $strColumnName = $objDatabaseIndex->ColumnNameArray[0];
                                $objColumn = $objTable->ColumnArray[strtolower($strColumnName)];
                                $objColumn->Indexed = true;

                                if ($objIndex->Unique) {
                                    $objColumn->Unique = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Add the IndexArray to the table
        $objTable->IndexArray = $objIndexArray;


        // Get the List of Foreign Keys from the database
        $objForeignKeys = $this->objDb->getForeignKeysForTable($objTable->Name);

        // Adds to it, the list of Foreign Keys from any Relationships Script
        $objForeignKeys = $this->getForeignKeysFromRelationshipsScript($strTableName, $objForeignKeys);

        // Iterate through each foreign key that exists in this table
        if ($objForeignKeys) {
            foreach ($objForeignKeys as $objForeignKey) {

                // Make sure it's a single-column FK
                if (count($objForeignKey->ColumnNameArray) != 1) {
                    $this->strErrors .= sprintf("Foreign Key %s in a table %s keys on multiple columns.  Multiple-columned FKs are not supported by the code generator.\n",
                        $objForeignKey->KeyName, $strTableName);
                } else {
                    // Make sure the column in the FK definition actually exists in this table
                    $strColumnName = $objForeignKey->ColumnNameArray[0];

                    if (array_key_exists(strtolower($strColumnName), $objTable->ColumnArray) &&
                        ($objColumn = $objTable->ColumnArray[strtolower($strColumnName)])
                    ) {

                        // Now, we make sure there is a single-column index for this FK that exists
                        $blnFound = false;
                        if ($objIndexArray = $objTable->IndexArray) {
                            foreach ($objIndexArray as $objIndex) {
                                if ((count($objIndex->ColumnNameArray) == 1) &&
                                    (strtolower($objIndex->ColumnNameArray[0]) == strtolower($strColumnName))
                                ) {
                                    $blnFound = true;
                                }
                            }
                        }

                        if (!$blnFound) {
                            // Single Column Index for this FK does not exist.  Let's create a virtual one and warn
                            $objIndex = new Index();
                            $objIndex->KeyName = sprintf('virtualix_%s_%s', $objTable->Name, $objColumn->Name);
                            $objIndex->Unique = $objColumn->Unique;
                            $objIndex->ColumnNameArray = array($objColumn->Name);

                            $objIndexArray = $objTable->IndexArray;
                            $objIndexArray[] = $objIndex;
                            $objTable->IndexArray = $objIndexArray;

                            if ($objIndex->Unique) {
                                $this->strWarnings .= sprintf("Notice: It is recommended that you add a single-column UNIQUE index on \"%s.%s\" for the Foreign Key %s\r\n",
                                    $strTableName, $strColumnName, $objForeignKey->KeyName);
                            } else {
                                $this->strWarnings .= sprintf("Notice: It is recommended that you add a single-column index on \"%s.%s\" for the Foreign Key %s\r\n",
                                    $strTableName, $strColumnName, $objForeignKey->KeyName);
                            }
                        }

                        // Make sure the table being referenced actually exists
                        if ((array_key_exists(strtolower($objForeignKey->ReferenceTableName), $this->objTableArray)) ||
                            (array_key_exists(strtolower($objForeignKey->ReferenceTableName), $this->objTypeTableArray))
                        ) {

                            // STEP 1: Create the New Reference
                            $objReference = new Reference();

                            // Retrieve the Column object
                            $objColumn = $objTable->ColumnArray[strtolower($strColumnName)];

                            // Set up Key Name
                            $objReference->KeyName = $objForeignKey->KeyName;

                            $strReferencedTableName = $objForeignKey->ReferenceTableName;

                            // Setup IsType flag
                            if (array_key_exists(strtolower($strReferencedTableName), $this->objTypeTableArray)) {
                                $objReference->IsType = true;
                            } else {
                                $objReference->IsType = false;
                            }

                            // Setup Table and Column names
                            $objReference->Table = $strReferencedTableName;
                            $objReference->Column = $objForeignKey->ReferenceColumnNameArray[0];

                            // Setup VariableType
                            $objReference->VariableType = $this->modelClassName($strReferencedTableName);

                            // Set up PropertyName and VariableName
                            $objReference->PropertyName = $this->modelReferencePropertyName($objColumn->Name);
                            $objReference->VariableName = $this->modelReferenceVariableName($objColumn->Name);
                            $objReference->Name = $this->modelReferenceColumnName($objColumn->Name);

                            // Add this reference to the column
                            $objColumn->Reference = $objReference;

                            // References will not have been correctly read earlier, so try again with the reference name
                            $objColumn->Options = $this->objModelConnectorOptions->getOptions($objTable->ClassName,
                                    $objReference->PropertyName) + $objColumn->Options;


                            // STEP 2: Set up the REVERSE Reference for Non Type-based References
                            if (!$objReference->IsType) {
                                // Retrieve the ReferencedTable object
//								$objReferencedTable = $this->objTableArray[strtolower($objReference->Table)];
                                $objReferencedTable = $this->getTable($objReference->Table);
                                $objReverseReference = new ReverseReference();
                                $objReverseReference->Reference = $objReference;
                                $objReverseReference->KeyName = $objReference->KeyName;
                                $objReverseReference->Table = $strTableName;
                                $objReverseReference->Column = $strColumnName;
                                $objReverseReference->NotNull = $objColumn->NotNull;
                                $objReverseReference->Unique = $objColumn->Unique;
                                $objReverseReference->PropertyName = $this->modelColumnPropertyName($strColumnName);

                                $objReverseReference->ObjectDescription = $this->calculateObjectDescription($strTableName,
                                    $strColumnName, $strReferencedTableName, false);
                                $objReverseReference->ObjectDescriptionPlural = $this->calculateObjectDescription($strTableName,
                                    $strColumnName, $strReferencedTableName, true);
                                $objReverseReference->VariableName = $this->modelReverseReferenceVariableName($objTable->Name);
                                $objReverseReference->VariableType = $this->modelReverseReferenceVariableType($objTable->Name);

                                // For Special Case ReverseReferences, calculate Associated MemberVariableName and PropertyName...

                                // See if ReverseReference is due to an ORM-based Class Inheritance Chain
                                if ((count($objTable->PrimaryKeyColumnArray) == 1) && ($objColumn->PrimaryKey)) {
                                    $objReverseReference->ObjectMemberVariable = static::prefixFromType(Type::OBJECT) . $objReverseReference->VariableType;
                                    $objReverseReference->ObjectPropertyName = $objReverseReference->VariableType;
                                    $objReverseReference->ObjectDescription = $objReverseReference->VariableType;
                                    $objReverseReference->ObjectDescriptionPlural = $this->pluralize($objReverseReference->VariableType);
                                    $objReverseReference->Options = $this->objModelConnectorOptions->getOptions($objReference->VariableType,
                                        $objReverseReference->ObjectDescription);

                                    // Otherwise, see if it's just plain ol' unique
                                } else {
                                    if ($objColumn->Unique) {
                                        $objReverseReference->ObjectMemberVariable = $this->calculateObjectMemberVariable($strTableName,
                                            $strColumnName, $strReferencedTableName);
                                        $objReverseReference->ObjectPropertyName = $this->calculateObjectPropertyName($strTableName,
                                            $strColumnName, $strReferencedTableName);
                                        // get override options for codegen
                                        $objReverseReference->Options = $this->objModelConnectorOptions->getOptions($objReference->VariableType,
                                            $objReverseReference->ObjectDescription);
                                    }
                                }

                                $objReference->ReverseReference = $objReverseReference;     // Let forward reference also see things from the other side looking back

                                // Add this ReverseReference to the referenced table's ReverseReferenceArray
                                $objArray = $objReferencedTable->ReverseReferenceArray;
                                $objArray[] = $objReverseReference;
                                $objReferencedTable->ReverseReferenceArray = $objArray;
                            }
                        } else {
                            $this->strErrors .= sprintf("Foreign Key %s in a table %s references a table %s that do not appear to exist.\n",
                                $objForeignKey->KeyName, $strTableName, $objForeignKey->ReferenceTableName);
                        }
                    } else {
                        $this->strErrors .= sprintf("Foreign Key %s in a table %s indexes on a column that does not appear to exist.\n",
                            $objForeignKey->KeyName, $strTableName);
                    }
                }
            }
        }

        // Verify: Table Name is valid (alphanumeric + "_" characters only, must not start with a number)
        // and NOT a PHP Reserved Word
        $strMatches = array();
        preg_match('/' . $this->strPatternTableName . '/', $strTableName, $strMatches);
        if (count($strMatches) && ($strMatches[0] == $strTableName) && ($strTableName != '_')) {
            // Set up Reserved Words
            $strReservedWords = explode(',', static::PHP_RESERVED_WORDS);
            for ($intIndex = 0; $intIndex < count($strReservedWords); $intIndex++) {
                $strReservedWords[$intIndex] = strtolower(trim($strReservedWords[$intIndex]));
            }

            $strTableNameToTest = trim(strtolower($strTableName));
            foreach ($strReservedWords as $strReservedWord) {
                if ($strTableNameToTest == $strReservedWord) {
                    $this->strErrors .= sprintf("Table '%s' has a table name which is a PHP reserved word.\r\n",
                        $strTableName);
                    unset($this->objTableArray[strtolower($strTableName)]);
                    return;
                }
            }
        } else {
            $this->strErrors .= sprintf("Table '%s' can only contain characters that are alphanumeric or _, and must not begin with a number.\r\n",
                $strTableName);
            unset($this->objTableArray[strtolower($strTableName)]);
            return;
        }

        // Verify: Column Names are all valid names
        $objColumnArray = $objTable->ColumnArray;
        foreach ($objColumnArray as $objColumn) {
            $strColumnName = $objColumn->Name;
            $strMatches = array();
            preg_match('/' . $this->strPatternColumnName . '/', $strColumnName, $strMatches);

            if (count($strMatches) && ($strMatches[0] == $strColumnName) && ($strColumnName != '_')) {
                // If the column name is valid, we just continue with the next iteration.
                continue;
            } else {
                // If the column name is invalid, we add an appropriate warning and remove the table.
                $this->strErrors .= sprintf(
                    "Table '%s' has an invalid column name: '%s'\r\n",
                    $strTableName,
                    $strColumnName
                );
                unset($this->objTableArray[strtolower($strTableName)]);
                return;
            }
        }

        // Verify: Table has at least one PK
        $blnFoundPk = false;
        $objColumnArray = $objTable->ColumnArray;
        foreach ($objColumnArray as $objColumn) {
            if ($objColumn->PrimaryKey) {
                $blnFoundPk = true;
            }
        }
        if (!$blnFoundPk) {
            $this->strErrors .= sprintf("Table %s does not have any defined primary keys.\n", $strTableName);
            unset($this->objTableArray[strtolower($strTableName)]);
        }
    }

    /**
     * Analyzes a database table column and constructs a SqlColumn object representing its properties.
     *
     * @param Database\FieldBase $objField The database field object containing metadata about the column.
     * @param mixed $objTable The table object the column belongs to.
     * @return SqlColumn|null A SqlColumn object representing the analyzed column, or null if the column name is invalid.
     * @throws InvalidCast
     */
    protected function analyzeTableColumn(Database\FieldBase $objField, mixed $objTable): ?SqlColumn
    {
        $objColumn = new SqlColumn();
        $objColumn->Name = $objField->Name;
        $objColumn->OwnerTable = $objTable;
        if (substr_count($objField->Name, "-")) {
            $tableName = $objTable ? " in table " . $objTable->Name : "";
            $this->strErrors .= "Invalid column name" . $tableName . ": " . $objField->Name . ". Dashes are not allowed.";
            return null;
        }

        $objColumn->DbType = $objField->Type;

        $objColumn->VariableType = $this->variableTypeFromDbType($objColumn->DbType);
        $objColumn->VariableTypeAsConstant = Type::constant($objColumn->VariableType);

        $objColumn->Length = $objField->MaxLength;
        $objColumn->Default = $objField->Default;

        $objColumn->PrimaryKey = $objField->PrimaryKey;
        $objColumn->NotNull = $objField->NotNull;
        $objColumn->Identity = $objField->Identity;
        $objColumn->Unique = $objField->Unique;


        $objColumn->Timestamp = $objField->Timestamp;

        $objColumn->VariableName = $this->modelColumnVariableName($objColumn);
        $objColumn->PropertyName = $this->modelColumnPropertyName($objColumn->Name);

        // separate overrides embedded in the comment

        // extract options embedded in the comment field
        if (($strComment = $objField->Comment) &&
            ($pos1 = strpos($strComment, '{')) !== false &&
            ($pos2 = strrpos($strComment, '}', $pos1))
        ) {

            $strJson = substr($strComment, $pos1, $pos2 - $pos1 + 1);
            $a = json_decode($strJson, true);

            if ($a) {
                $objColumn->Options = $a;
                $objColumn->Comment = substr($strComment, 0, $pos1) . substr($strComment,
                        $pos2 + 1); // return comment without options
                if (!empty ($a['Timestamp'])) {
                    $objColumn->Timestamp = true;    // alternate way to specify that a column is a self-updating timestamp
                }
                if ($objColumn->Timestamp && !empty($a['AutoUpdate'])) {
                    $objColumn->AutoUpdate = true;
                }
            } else {
                $objColumn->Comment = $strComment;
            }
        }

        // Combine with options found in the design editor, allowing the editor to take precedence
        $objColumn->Options = $this->objModelConnectorOptions->getOptions($objTable->ClassName,
                $objColumn->PropertyName) + $objColumn->Options;

        return $objColumn;
    }

    /**
     * Removes the predefined prefix from the specified table name if the prefix exists and matches the conditions.
     *
     * @param string $strTableName The name of the table from which the prefix should be stripped.
     * @return string|null The table name with the prefix removed, or the original table name if no applicable prefix is found.
     */
    protected function stripPrefixFromTable(string $strTableName): ?string
    {
        // If applicable, strip any StripTablePrefix from the table name
        if ($this->intStripTablePrefixLength &&
            (strlen($strTableName) > $this->intStripTablePrefixLength) &&
            (substr($strTableName, 0,
                    $this->intStripTablePrefixLength - strlen($strTableName)) == $this->strStripTablePrefix)
        ) {
            return substr($strTableName, $this->intStripTablePrefixLength);
        }

        return $strTableName;
    }

    /**
     * Processes a relationship definition line for a QCubed script and extracts or creates a foreign key definition
     * based on its table and column references.
     *
     * @param string $strTableName The name of the table for which the foreign key is being processed.
     * @param string $strLine A line from the relationship definition script specifying source and destination table. Column mappings.
     * @return ForeignKey|null The generated or resolved foreign key definition if valid, otherwise null.
     * @throws Exception If errors occur during foreign key processing.
     */
    protected function getForeignKeyForQcubedRelationshipDefinition(string $strTableName, string $strLine): ?Database\ForeignKey
    {
        $strTokens = explode('=>', $strLine);
        if (count($strTokens) != 2) {
            $this->strErrors .= sprintf("Could not parse Relationships Script reference: %s (Incorrect Format)\r\n",
                $strLine);
            $this->strRelationshipLinesQcubed[$strLine] = null;
            return null;
        }

        $strSourceTokens = explode('.', $strTokens[0]);
        $strDestinationTokens = explode('.', $strTokens[1]);

        if ((count($strSourceTokens) != 2) ||
            (count($strDestinationTokens) != 2)
        ) {
            $this->strErrors .= sprintf("Could not parse Relationships Script reference: %s (Incorrect Table.Column Format)\r\n",
                $strLine);
            $this->strRelationshipLinesQcubed[$strLine] = null;
            return null;
        }

        $strColumnName = trim($strSourceTokens[1]);
        $strReferenceTableName = trim($strDestinationTokens[0]);
        $strReferenceColumnName = trim($strDestinationTokens[1]);
        $strFkName = sprintf('virtualfk_%s_%s', $strTableName, $strColumnName);

        if (strtolower($strTableName) == trim($strSourceTokens[0])) {
            $this->strRelationshipLinesQcubed[$strLine] = null;
            return $this->getForeignKeyHelper($strLine, $strFkName, $strTableName, $strColumnName,
                $strReferenceTableName, $strReferenceColumnName);
        }

        return null;
    }

    /**
     * Extracts and processes foreign key information from an SQL relationship definition line.
     *
     * @param string $strTableName The name of the table for which the foreign key relationship is being parsed.
     * @param string $strLine The line containing the SQL statement that defines the foreign key relationship.
     * @return ForeignKey|null A foreign key helper object representing the parsed foreign key relationship, or null if the line is invalid or contains unsupported formats or multiple columns.
     */
    protected function getForeignKeyForSqlRelationshipDefinition(string $strTableName, string $strLine): ?ForeignKey
    {
        $strMatches = array();

        // Start
        $strPattern = '/alter[\s]+table[\s]+';
        // Table Name
        $strPattern .= '[\[\`\'\"]?(' . $this->strPatternTableName . ')[\]\`\'\"]?[\s]+';

        // Add Constraint
        $strPattern .= '(add[\s]+)?(constraint[\s]+';
        $strPattern .= '[\[\`\'\"]?(' . $this->strPatternKeyName . ')[\]\`\'\"]?[\s]+)?[\s]*';
        // Foreign Key
        $strPattern .= 'foreign[\s]+key[\s]*(' . $this->strPatternKeyName . ')[\s]*\(';
        $strPattern .= '([^)]+)\)[\s]*';
        // References
        $strPattern .= 'references[\s]+';
        $strPattern .= '[\[\`\'\"]?(' . $this->strPatternTableName . ')[\]\`\'\"]?[\s]*\(';
        $strPattern .= '([^)]+)\)[\s]*';
        // End
        $strPattern .= '/';

        // Perform the RegExp
        preg_match($strPattern, $strLine, $strMatches);

        if (count($strMatches) == 9) {
            $strColumnName = trim($strMatches[6]);
            $strReferenceTableName = trim($strMatches[7]);
            $strReferenceColumnName = trim($strMatches[8]);
            $strFkName = $strMatches[5];
            if (!$strFkName) {
                $strFkName = sprintf('virtualfk_%s_%s', $strTableName, $strColumnName);
            }

            if ((str_contains($strColumnName, ',')) ||
                (str_contains($strReferenceColumnName, ','))
            ) {
                $this->strErrors .= sprintf("Relationship Script has a foreign key definition with multiple columns: %s (Multiple-columned FKs are not supported by the code generator)\r\n",
                    $strLine);
                $this->strRelationshipLinesSql[$strLine] = null;
                return null;
            }

            // Cleanup strColumnName nad preferenceColumnName
            $strColumnName = str_replace("'", '', $strColumnName);
            $strColumnName = str_replace('"', '', $strColumnName);
            $strColumnName = str_replace('[', '', $strColumnName);
            $strColumnName = str_replace(']', '', $strColumnName);
            $strColumnName = str_replace('`', '', $strColumnName);
            $strColumnName = str_replace('	', '', $strColumnName);
            $strColumnName = str_replace(' ', '', $strColumnName);
            $strColumnName = str_replace("\r", '', $strColumnName);
            $strColumnName = str_replace("\n", '', $strColumnName);
            $strReferenceColumnName = str_replace("'", '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace('"', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace('[', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace(']', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace('`', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace('	', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace(' ', '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace("\r", '', $strReferenceColumnName);
            $strReferenceColumnName = str_replace("\n", '', $strReferenceColumnName);

            if (strtolower($strTableName) == trim($strMatches[1])) {
                $this->strRelationshipLinesSql[$strLine] = null;
                return $this->getForeignKeyHelper($strLine, $strFkName, $strTableName, $strColumnName,
                    $strReferenceTableName, $strReferenceColumnName);
            }

        } else {
            $this->strErrors .= sprintf("Could not parse Relationships Script reference: %s (Not in ANSI SQL Format)\r\n",
                $strLine);
            $this->strRelationshipLinesSql[$strLine] = null;
        }
        return null;
    }

    /**
     * Creates a ForeignKey object after validating the existence of the specified tables and columns.
     *
     * @param string $strLine The line of input data being processed, typically from a script or configuration file.
     * @param string $strFkName The name of the foreign key being created.
     * @param string $strTableName The name of the table containing the foreign key column.
     * @param string $strColumnName The name of the column in the table that is part of the foreign key.
     * @param string $strReferencedTable The name of the table being referenced by the foreign key.
     * @param string $strReferencedColumn The name of the column in the referenced table.
     * @return Database\ForeignKey|null The ForeignKey object if validation succeeds, or null if any table or column validation fails.
     */
    protected function getForeignKeyHelper(
        string $strLine,
        string $strFkName,
        string $strTableName,
        string $strColumnName,
        string $strReferencedTable,
        string $strReferencedColumn
    ): ?Database\ForeignKey
    {
        // Make Sure Tables/Columns Exist or display error otherwise
        if (!$this->validateTableColumn($strTableName, $strColumnName)) {
            $this->strErrors .= sprintf("Could not parse Relationships Script reference: \"%s\" (\"%s.%s\" does not exist)\r\n",
                $strLine, $strTableName, $strColumnName);
            return null;
        }

        if (!$this->validateTableColumn($strReferencedTable, $strReferencedColumn)) {
            $this->strErrors .= sprintf("Could not parse Relationships Script reference: \"%s\" (\"%s.%s\" does not exist)\r\n",
                $strLine, $strReferencedTable, $strReferencedColumn);
            return null;
        }

        return new Database\ForeignKey($strFkName, array($strColumnName), $strReferencedTable,
            array($strReferencedColumn));
    }

    /**
     * This will go through the various Relationships Script lines (if applicable) as setup during
     * the __constructor() through the <relationships> and <relationshipsScript> tags in the
     * configuration settings.
     *
     * If no Relationships are defined, this method will simply exit making no changes.
     *
     * @param string $strTableName The name of the table for which to determine foreign keys.
     * @param array $objForeignKeyArray The array to which the identified foreign keys will be appended.
     * @return array The updated array containing all identified foreign keys for the specified table.
     * @throws Exception
     */
    protected function getForeignKeysFromRelationshipsScript(string $strTableName, array $objForeignKeyArray): array
    {
        foreach ($this->strRelationshipLinesQcubed as $strLine) {
            if ($strLine) {
                $objForeignKey = $this->getForeignKeyForQcubedRelationshipDefinition($strTableName, $strLine);

                if ($objForeignKey) {
                    $objForeignKeyArray[] = $objForeignKey;
                    $this->strRelationshipLinesQcubed[$strLine] = null;
                }
            }
        }

        foreach ($this->strRelationshipLinesSql as $strLine) {
            if ($strLine) {
                $objForeignKey = $this->getForeignKeyForSqlRelationshipDefinition($strTableName, $strLine);

                if ($objForeignKey) {
                    $objForeignKeyArray[] = $objForeignKey;
                    $this->strRelationshipLinesSql[$strLine] = null;
                }
            }
        }

        return $objForeignKeyArray;
    }

    /**
     * Generates a control ID based on the provided table and column.
     *
     * @param object $objTable The table object used in generating the control ID.
     * @param ColumnInterface $objColumn The column object whose properties may influence the control ID.
     * @return string|null The generated control ID if successful, or null if no ID is generated.
     * @throws Caller
     * @throws InvalidCast
     */
    public function generateControlId(object $objTable, ColumnInterface $objColumn): ?string
    {
        $strControlId = null;
        if (isset($objColumn->Options['ControlId'])) {
            $strControlId = $objColumn->Options['ControlId'];
        } elseif ($this->blnGenerateControlId) {
            //$strObjectName = $this->modelVariableName($objTable->Name);
            $strClassName = $objTable->ClassName;
            $strControlVarName = $this->modelConnectorVariableName($objColumn);
            //$strLabelName = static::modelConnectorControlName($objColumn);

            $strControlId = $strControlVarName . $strClassName;

        }
        return $strControlId;
    }

    /**
     * Returns a string that will cast a variable coming from the database into a PHP type.
     * Doing this in the template saves significant amounts of time over using Type::cast() or GetColumn.
     *
     * @param SqlColumn $objColumn The database column for which a casting string is generated.
     * @return string The string representing the casting operation.
     * @throws Exception If the column has an unsupported or invalid database field type.
     */
    public function getCastString(SqlColumn $objColumn): string
    {


        return match ($objColumn->DbType) {
            Database\FieldType::BIT => ('$mixVal = (bool)$mixVal;'),
            Database\FieldType::BLOB, Database\FieldType::CHAR, Database\FieldType::VAR_CHAR, Database\FieldType::JSON => '',
            Database\FieldType::DATE => ('$mixVal = new QDateTime($mixVal, null, QDateTime::DATE_ONLY_TYPE);'),
            Database\FieldType::DATE_TIME => ('$mixVal = new QDateTime($mixVal);'),
            Database\FieldType::TIME => ('$mixVal = new QDateTime($mixVal, null, QDateTime::TIME_ONLY_TYPE);'),
            Database\FieldType::FLOAT, Database\FieldType::INTEGER => ('$mixVal = (' . $objColumn->VariableType . ')$mixVal;'),
            default => throw new Exception ('Invalid database field type'),
        };
    }

    ////////////////////
    // Public Overriders
    ////////////////////

    /**
     * Magic method to retrieve properties of the class based on the provided name.
     *
     * @param string $strName The name of the property to retrieve.
     * @return mixed The value of the requested property. May return various types depending on the property.
     * @throws Caller If the property is deprecated or does not exist in the current class or parent class.
     */
    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'TableArray':
                return $this->objTableArray;
            case 'TypeTableArray':
                return $this->objTypeTableArray;
            case 'DatabaseIndex':
                return $this->intDatabaseIndex;
            case 'CommentConnectorLabelDelimiter':
                return $this->strCommentConnectorLabelDelimiter;
            case 'AutoInitialize':
                return $this->blnAutoInitialize;
            case 'PrivateColumnVars':
                return $this->blnPrivateColumnVars;
            case 'objSettingsXml':
                throw new Caller('The field objSettingsXml is deprecated');
            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }

    /**
     * Sets the value of a property dynamically.
     *
     * @param string $strName The name of the property to set.
     * @param mixed $mixValue The value to assign to the specified property.
     * @return void
     * @throws Exception
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            parent::__set($strName, $mixValue);
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
        }
    }
}

/**
 * Trims whitespace or other predefined characters from the beginning and end of a string variable.
 *
 * @param string &$strValue The string variable to be trimmed. The value is passed by reference and modified in place.
 * @return void
 */
function array_trim(string &$strValue): void
{
    $strValue = trim($strValue);
}