<?php
    /**
     *
     * Part of the QCubed PHP framework.
     *
     * @license MIT
     *
     */

    namespace QCubed\Codegen;

    use QCubed\Codegen\Generator\Label;
    use QCubed\Exception\UndefinedProperty;
    use QCubed\Project\Codegen\CodegenBase as Codegen;
    use QCubed\Error\Handler;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use Exception;
    use QCubed\Folder;
    use QCubed\ModelConnector\Options;
    use QCubed\ObjectBase;
    use QCubed\QString;
    use QCubed\Database;
    use QCubed\Database\Service;
    use QCubed\Type;
    use SimpleXmlElement;

    /**
     * Handles and processes errors encountered during the code generation process,
     * specifically parsing errors related to SimpleXML operations.
     *
     * @param int $__exc_errno The error number associated with the error.
     * @param string $__exc_errstr The error message describing the nature of the error.
     * @param string $__exc_errfile The file where the error occurred, typically the script being processed.
     * @param int $__exc_errline The line number in the file where the error occurred.
     * @return void
     */
    function qcubedHandleCodeGenParseError(int $__exc_errno, string $__exc_errstr, string $__exc_errfile, int $__exc_errline): void
    {
        $strErrorString = str_replace("SimpleXMLElement::__construct() [<a href='function.SimpleXMLElement---construct'>function.SimpleXMLElement---construct</a>]: ",
            '', $__exc_errstr);
        Codegen::$RootErrors .= sprintf("%s\r\n", $strErrorString);
    }

    /**
     * Checks if a string begins with a specified substring.
     *
     * @param string $str The string to be checked.
     * @param string $sub The substring to check for at the beginning of the string.
     * @return bool Returns true if the string begins with the specified substring, otherwise false.
     */
    function beginsWith(string $str, string $sub): bool
    {
        return (str_starts_with($str, $sub));
    }

    /**
     * Checks if a given string ends with the specified substring.
     *
     * @param string $str The full string to check.
     * @param string $sub The substring to look for at the end of the given string.
     * @return bool Returns true if the string ends with the specified substring, otherwise false.
     */
    function endsWith(string $str, string $sub): bool
    {
        return (str_ends_with($str, $sub));
    }

    /**
     * Trims the specified number of characters or substring from the beginning of the input string.
     *
     * @param int|string $off The number of characters to trim if passed as an integer
     *                        or the substring to remove from the start if passed as a string.
     * @param string $str The input string from which the trimming will be performed.
     * @return string The trimmed string after removing the specified characters or substring.
     */
    function trimOffFront(int|string $off, string $str): string
    {
        if (is_numeric($off)) {
            return substr($str, $off);
        } else {
            return substr($str, strlen($off));
        }
    }

    /**
     * Trims a specified number of characters or a specific substring from the end of a given string.
     *
     * @param mixed $off The number of characters to trim off or the substring to remove from the end.
     *                   If numeric, it represents the count of characters to remove from the end.
     *                   If a string, it removes the specified substring from the end.
     * @param string $str The input string from which characters or a substring will be trimmed.
     *
     * @return string The resulting string after trimming the specified characters or substring.
     */
    function trimOffEnd(mixed $off, string $str): string
    {
        if (is_numeric($off)) {
            return substr($str, 0, strlen($str) - $off);
        } else {
            return substr($str, 0, strlen($str) - strlen($off));
        }
    }

    /**
     * This is the CodeGen class that performs the code generation
     * for both the Object-Relational Model (e.g., Data Objects) and
     * the draft Forms, which make up simple HTML/PHP scripts to perform
     * basic CRUD functionality on each object.
     * @package Codegen
     * @property string $Errors List of errors
     * @property string $Warnings List of warnings
     */
    abstract class CodegenBase extends ObjectBase
    {
        public static array $CodeGenArray = [];

        /** @var string Class Prefix, as specified in the CodegenBase_settings.xml file */
        protected string $strClassPrefix;

        /** @var string Class suffix, as specified in the CodegenBase_settings.xml file */
        protected string $strClassSuffix;

        /** @var SimpleXmlElement Configuration or XML object holder */
        protected SimpleXmlElement $objSettingsXml;


        /** @var string Errors and Warnings collected during the process of CodegenBase * */
        protected string $strErrors = '';

        /** @var string|null Warnings collected during the CodegenBase process. */
        protected ?string $strWarnings = null;

        protected int $intDatabaseIndex;
        protected string $strAssociationTableSuffix;
        protected string $strAssociatedObjectPrefix;
        protected string $strAssociatedObjectSuffix;

        /**
         * PHP Reserved Words.  They make up:
         * Invalid Type names -- these are reserved words that cannot be Type names in any user type table
         * Invalid Table names -- these are reserved words that cannot be used as any table name
         * Please refer to: http://php.net/manual/en/reserved.php
         */
        const string PHP_RESERVED_WORDS = 'new, null, break, return, switch, self, case, const, clone, continue, declare, default, echo, else, elseif, empty, exit, eval, if, try, throw, catch, public, private, protected, function, extends, foreach, for, while, do, var, class, static, abstract, isset, unset, implements, interface, instanceof, include, include_once, require, require_once, abstract, and, or, xor, array, list, false, true, global, parent, print, exception, namespace, goto, final, endif, endswitch, enddeclare, endwhile, use, as, endfor, endforeach, this';

        /**
         * @var array The list of template base paths to search, in order, when looking for a particular template. Set this
         * To insert new template paths. If not set, the default will be the project template path, followed by the qcubed core path.
         */
        public static array $TemplatePaths = [];

        /**
         * DebugMode -- for Template Developers
         * This will output the current evaluated template/statement to the screen
         * On "eval" errors, you can click on the "View Rendered Page" to see what currently
         * is being evaluated, which should hopefully aid in template debugging.
         */
        const false DEBUG_MODE = false;

        /**
         * Represents the base array used for code generation purposes.
         */
        public static ?array $CodegenBaseArray = [];

        /**
         * This is the array representation of the parsed SettingsXml
         * for report back purposes.
         *
         * @var string[] array of config settings
         */
        protected static array $SettingsXmlArray;

        /**
         * This is the SimpleXML representation of the Settings XML file
         *
         * @var SimpleXmlElement|null the XML representation
         */
        protected static ?SimpleXmlElement $SettingsXml = null;

        /**
         * Stores the file path for the settings configuration.
         */
        public static string $SettingsFilePath = '';

        //public static string $GenerateControlId = '';

        /**
         * Application Name (from CodegenBaseBase Settings)
         *
         * @var string $ApplicationName
         */
        public static string $ApplicationName = '';

        /**
         * Preferred Render Method (from CodegenBaseBase Settings)
         *
         * @var string $PreferredRenderMethod
         */
        public static string $PreferredRenderMethod = '';

        /**
         * Create Method (from CodegenBaseBase Settings)
         *
         * @var string $CreateMethod
         */
        public static string $CreateMethod = '';

        /**
         * Default Button Class (from CodegenBaseBase Settings)
         *
         * @var string $DefaultButtonClass
         */
        public static string $DefaultButtonClass = '';

        /**
         * @var string $RootErrors
         */
        public static string $RootErrors = '';

        /**
         * @var string[] array of directories to be excluded in CodegenBaseBase (lower cased)
         * @access protected
         */
        protected static array $DirectoriesToExcludeArray = array('.', '..', '.svn', 'svn', 'cvs', '.git');

        /**
         * Constructs a new instance of the class with the provided settings XML object.
         *
         * @param SimpleXmlElement $objSettingsXml An object representing the settings in XML format
         */
        public function __construct(SimpleXmlElement $objSettingsXml)
        {
            $this->objSettingsXml = $objSettingsXml;
            $this->strErrors = '';
        }

        /**
         * Determines the prefix based on the provided type.
         *
         * @param string $strType The type for which the prefix is to be determined. Typically one of the defined constants in the `Type` class.
         * @return string A string prefix corresponding to the provided type. Returns an empty string if the type is not recognized.
         */
        public static function prefixFromType(string $strType): string
        {
            switch ($strType) {
                case Type::OBJECT:
                case Type::ARRAY_TYPE:
                    return "obj";
                case Type::BOOLEAN:
                    return "bln";
                case Type::DATE_TIME:
                    return "dtt";
                case Type::FLOAT:
                    return "flt";
                case Type::INTEGER:
                    return "int";
                case Type::STRING:
                    return "str";
            }
            // Suppressing the IDE warning about no value being return
            return "";
        }

        /**
         * Retrieves the paths to installed templates by scanning a specific directory for template configuration files.
         *
         * The method checks a designated directory for files ending with '.inc.php'. It then includes these files and
         * merges any valid arrays returned from them into a single array of template paths.
         *
         * @return array An array containing the paths to the installed templates. If no valid templates are found, returns an empty array.
         */
        public function getInstalledTemplatePaths(): array
        {
            $dir = QCUBED_CONFIG_DIR . '/templates';

            $paths = [];

            if (is_dir($dir)) {   // does the active directory exist?
                foreach (scandir($dir) as $strFileName) {
                    if (str_ends_with($strFileName, '.inc.php')) {
                        $paths2 = include($dir . '/' . $strFileName);
                        if ($paths2 && is_array($paths2)) {
                            $paths = array_merge($paths, $paths2);
                        }
                    }
                }
            }

            return $paths;
        }

        /**
         * Generates the XML representation of the code generation settings.
         *
         * @return string The XML string containing the configured settings, including application name,
         *                preferred render method, and data sources from the code generation array.
         */
        public static function getSettingsXml(): string
        {
            $strCrLf = "\r\n";

            $strToReturn = sprintf('<codegen>%s', $strCrLf);
            $strToReturn .= sprintf('	<name application="%s"/>%s', Codegen::$ApplicationName, $strCrLf);
            $strToReturn .= sprintf('	<render preferredRenderMethod="%s"/>%s', Codegen::$PreferredRenderMethod,
                $strCrLf);
            $strToReturn .= sprintf('	<dataSources>%s', $strCrLf);
            foreach (Codegen::$CodeGenArray as $objCodeGen) {
                $strToReturn .= $strCrLf . $objCodeGen->getConfigXml();
            }
            $strToReturn .= sprintf('%s	</dataSources>%s', $strCrLf, $strCrLf);
            $strToReturn .= '</codegen>';

            return $strToReturn;
        }

        /**
         * Executes the code generation process using a specified XML settings file.
         *
         * @param string $strSettingsXmlFilePath The file path to the CodeGen settings XML file.
         * @return void This method does not return any value. It sets relevant CodeGen properties and creates code generation instances based on the XML configuration.
         *              If errors occur during the process, they are stored in `Codegen::$RootErrors`.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function run(string $strSettingsXmlFilePath): void
        {
            if (!defined('QCUBED_CODE_GENERATING')) {
                define('QCUBED_CODE_GENERATING', true);
            }

            Codegen::$CodeGenArray = array();
            Codegen::$SettingsFilePath = $strSettingsXmlFilePath;

            if (!file_exists($strSettingsXmlFilePath)) {
                Codegen::$RootErrors = 'FATAL ERROR: CodeGen Settings XML File (' . $strSettingsXmlFilePath . ') was not found.';
                return;
            }

            if (!is_file($strSettingsXmlFilePath)) {
                Codegen::$RootErrors = 'FATAL ERROR: CodeGen Settings XML File (' . $strSettingsXmlFilePath . ') was not found.';
                return;
            }

            // Try Parsing the XML Settings File
            try {
                $errorHandler = new Handler('\\QCubed\\Codegen\\QcubedHandleCodeGenParseError', E_ALL);
                Codegen::$SettingsXml = new SimpleXMLElement(file_get_contents($strSettingsXmlFilePath));
                $errorHandler->restore();
            } catch (Exception $objExc) {
                Codegen::$RootErrors .= 'FATAL ERROR: Unable to parse CodeGenSettings XML File: ' . $strSettingsXmlFilePath;
                Codegen::$RootErrors .= "\r\n";
                Codegen::$RootErrors .= $objExc->getMessage();
                return;
            }

            // Application Name
            Codegen::$ApplicationName = Codegen::lookupSetting(Codegen::$SettingsXml, 'name', 'application');

            // Codegen Defaults
            Codegen::$PreferredRenderMethod = Codegen::lookupSetting(Codegen::$SettingsXml, 'formgen',
                'preferredRenderMethod');
            Codegen::$CreateMethod = Codegen::lookupSetting(Codegen::$SettingsXml, 'formgen', 'createMethod');
            Codegen::$DefaultButtonClass = Codegen::lookupSetting(Codegen::$SettingsXml, 'formgen', 'buttonClass');

            if (!Codegen::$DefaultButtonClass) {
                Codegen::$RootErrors .= "CodeGen Settings XML Fatal Error: buttonClass was not defined\r\n";
                return;
            }

            // Iterate Through DataSources
            if (Codegen::$SettingsXml->dataSources->asXML()) {
                foreach (Codegen::$SettingsXml->dataSources->children() as $objChildNode) {
                    switch (dom_import_simplexml($objChildNode)->nodeName) {
                        case 'database':
                            Codegen::$CodeGenArray[] = new DatabaseCodeGen($objChildNode);
                            break;
                        default:
                            Codegen::$RootErrors .= sprintf("Invalid Data Source Type in CodeGen Settings XML File (%s): %s\r\n",
                                $strSettingsXmlFilePath, dom_import_simplexml($objChildNode)->nodeName);
                            break;
                    }
                }
            }
        }

        /**
         * Retrieves a setting value from a node based on the provided tag name, attribute name, and expected type.
         *
         * @param mixed $objNode The node containing the setting. Can be an object or array-like structure.
         * @param string $strTagName The tag name to look for within the node. If provided, navigates to the specified tag within the node.
         * @param string|null $strAttributeName The attribute name whose value needs to be retrieved. If null, the method retrieves the value of the entire node or tag.
         * @param string $strType The expected data type of the value. Defaults to `Type::STRING`. Supported types include `Type::STRING`, `Type::INTEGER`, and `Type::BOOLEAN`.
         * @return mixed The value of the specified setting, cast to the expected type if applicable. Returns null if the type casting fails or the attribute is not found.
         * @throws Caller
         * @throws InvalidCast
         */
        static public function lookupSetting(mixed $objNode, string $strTagName, ?string $strAttributeName = null, string $strType = Type::STRING): mixed
        {
            if ($strTagName) {
                $objNode = $objNode->$strTagName;
            }

            if ($strAttributeName) {
                switch ($strType) {
                    case Type::INTEGER:
                        try {
                            return Type::cast($objNode[$strAttributeName], Type::INTEGER);
                        } catch (Exception $objExc) {
                            return null;
                        }
                    case Type::BOOLEAN:
                        try {
                            return Type::cast($objNode[$strAttributeName], Type::BOOLEAN);
                        } catch (Exception $objExc) {
                            return null;
                        }
                    default:
                        return trim(Type::cast($objNode[$strAttributeName], Type::STRING));
                }
            } else {
                return trim(Type::cast($objNode, Type::STRING));
            }
        }

        /**
         * Aggregates and processes code generation objects based on type and generates a consolidated output.
         *
         * This method categorizes code generation instances into database-specific and REST service-specific groups.
         * It then invokes helper methods to generate aggregated data for these categories.
         *
         * @return array An array containing the aggregated results from the processed code generation objects.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function generateAggregate(): array
        {
            $objDbOrmCodeGen = array();
            $objRestServiceCodeGen = array();

            foreach (Codegen::$CodeGenArray as $objCodeGen) {
                if ($objCodeGen instanceof DatabaseCodeGen) {
                    $objDbOrmCodeGen[] = $objCodeGen;
                }
            }

            $strToReturn = array();
            array_merge($strToReturn, DatabaseCodeGen::generateAggregateHelper($objDbOrmCodeGen));

            return $strToReturn;
        }

        /**
         * Given a template prefix (e.g., db_orm_, db_type_, rest_, soap_, etc.), pull
         * all the _*.tpl templates from any subfolders of the template prefix
         * in Codegen::TemplatesPath and Codegen::TemplatesPathCustom,
         * and call generateFile() on each one.  If there are any template files that reside
         * in BOTH TemplatesPath AND TemplatesPathCustom, then only use the TemplatesPathCustom one (which
         * in essence overrides the one in TemplatesPath)
         *
         * @param string $strTemplatePrefix The prefix used to locate the templates within the template paths.
         * @param mixed $mixArgumentArray The arguments used for template generation, typically containing data needed for file processing and generation.
         * @return bool Returns true if all files were successfully generated, or false if any file generation failed.
         * @throws Caller
         * @throws InvalidCast
         */
        public function generateFiles(string $strTemplatePrefix, mixed $mixArgumentArray): bool
        {
            // If you are editing core templates and getting EOF errors only on the travis build, this may be your problem. Scan your files and remove short tags.
            if (Codegen::DEBUG_MODE && ini_get('short_open_tag')) {
                _p("Warning: PHP directive short_open_tag is on. Using short tags will cause unexpected EOF on travis build.\n",
                    false);
            }

            // validate the template paths
            foreach (static::$TemplatePaths as $strPath) {
                if (!is_dir($strPath)) {
                    throw new Exception(sprintf("Template path: %s does not appear to be a valid directory.", $strPath));
                }
            }

            // Create an array of arrays of standard templates and custom (override) templates to process
            // Index by [module_name][filename] => true/false where
            // module name (e.g. "class_gen", "form_delegates) is a name of folder within the prefix (e.g. "db_orm")
            // filename is the template filename itself (in a _*.tpl format)
            // true = override (use custom) and false = do not override (use standard)
            $strTemplateArray = array();

            // Go through standard templates first, then override in order
            foreach (static::$TemplatePaths as $strPath) {
                $this->buildTemplateArray($strPath . $strTemplatePrefix, $strTemplateArray);
            }

            // Finally, iterate through all the TemplateFiles and call GenerateFile to Evaluate/Generate/Save them
            $blnSuccess = true;
            foreach ($strTemplateArray as $strModuleName => $strFileArray) {
                foreach ($strFileArray as $strFilename => $strPath) {
                    if (!$this->generateFile($strTemplatePrefix . '/' . $strModuleName, $strPath, $mixArgumentArray)) {
                        $blnSuccess = false;
                    }
                }
            }

            return $blnSuccess;
        }

        /**
         * Builds an array of templates based on the provided file path.
         *
         * @param string $strTemplateFilePath The file path to the templates' directory. If not a directory or invalid, the method will return without modifying the array.
         * @param array &$strTemplateArray A reference to the array where the templates will be stored. The array will be categorized by module names.
         *                                  Each template file is stored with its full path.
         * @return void This method does not return a value. The $strTemplateArray parameter is modified directly.
         */
        protected function buildTemplateArray(string $strTemplateFilePath, array &$strTemplateArray): void
        {
            if (!$strTemplateFilePath) {
                return;
            }
            if (!str_ends_with($strTemplateFilePath, '/')) {
                $strTemplateFilePath .= '/';
            }
            if (is_dir($strTemplateFilePath)) {
                $objDirectory = opendir($strTemplateFilePath);
                while ($strModuleName = readdir($objDirectory)) {
                    if (!in_array(strtolower($strModuleName), Codegen::$DirectoriesToExcludeArray) &&
                        is_dir($strTemplateFilePath . $strModuleName)
                    ) {
                        $objModuleDirectory = opendir($strTemplateFilePath . $strModuleName);
                        while ($strFilename = readdir($objModuleDirectory)) {
                            if ((QString::firstCharacter($strFilename) == '_') &&
                                (str_ends_with($strFilename, '.tpl.php'))
                            ) {
                                $strTemplateArray[$strModuleName][$strFilename] = $strTemplateFilePath . $strModuleName . '/' . $strFilename;
                            }
                        }
                    }
                }
            }
        }

        /**
         * Parses the first line of a template file to extract template settings as XML.
         * If the first line does not conform to the expected format, an exception will be thrown.
         *
         * @param string $strTemplateFilePath The path to the template file to be processed.
         * @param string|null $strTemplate A reference to the template content. If null, the method reads and populates the content from the file.
         * @return SimpleXMLElement An instance of SimpleXMLElement representing the extracted template settings.
         * @throws Exception If the first line of the template file does not conform to the expected XML format.
         */
        protected function getTemplateSettings(string $strTemplateFilePath, ?string &$strTemplate = null): SimpleXmlElement
        {
            if ($strTemplate === null) {
                $strTemplate = file_get_contents($strTemplateFilePath);
            }
            $strError = 'Template\'s first line must be <template OverwriteFlag="boolean" TargetDirectory="string" DirectorySuffix="string" TargetFileName="string"/>: ' . $strTemplateFilePath;
            // Parse out the first line (which contains a path and overwriting information)
            $intPosition = strpos($strTemplate, "\n");
            if ($intPosition === false) {
                throw new Exception($strError);
            }

            $strFirstLine = trim(substr($strTemplate, 0, $intPosition));

            $objTemplateXml = null;
            // Attempt to Parse the First Line as XML
            try {
                @$objTemplateXml = new SimpleXMLElement($strFirstLine);
            } catch (Exception $objExc) {
            }

            if (!($objTemplateXml instanceof SimpleXMLElement)) {
                throw new Exception($strError);
            }
            $strTemplate = substr($strTemplate, $intPosition + 1);
            return $objTemplateXml;
        }

        /**
         * Generates a file based on a given template and provided arguments. Can save the generated content to disk
         * or return it as a string depending on the configuration.
         *
         * @param string $strModuleSubPath The subpath within the module for finding related template files.
         * @param string $strTemplateFilePath The file path to the PHP template file to be evaluated.
         * @param mixed $mixArgumentArray An array of arguments to be passed into the template for evaluation.
         * @param bool $blnSave Optional. Whether to save the generated content to disk. Defaults to true.
         * @return string|bool Returns true or boolean success if the file is saved or not overwritten.
         *               Returns the evaluated template content as a string if saving is disabled.
         * @throws Caller If the template file is not found or the include path cannot be overridden.
         * @throws Exception If required template settings are null, or if the target directory cannot be created.
         */
        public function generateFile(string $strModuleSubPath, string $strTemplateFilePath, mixed $mixArgumentArray, bool $blnSave = true): string|bool
        {
            // Setup Debug/Exception Message
            if (Codegen::DEBUG_MODE) {
                echo("Evaluating $strTemplateFilePath<br/>");
            }

            // Check to see if the template file exists, and if it does, Load It
            if (!file_exists($strTemplateFilePath)) {
                throw new Caller('Template File Not Found: ' . $strTemplateFilePath);
            }

            // Evaluate the Template
            // make sure paths are set up to pick up included files from the various directories.
            // Must be the reverse of the buildTemplateArray order
            $a = array();
            foreach (static::$TemplatePaths as $strTemplatePath) {
                array_unshift($a, $strTemplatePath . $strModuleSubPath);
            }
            $strSearchPath = implode(PATH_SEPARATOR, $a) . PATH_SEPARATOR . get_include_path();
            $strOldIncludePath = set_include_path($strSearchPath);
            if ($strSearchPath != get_include_path()) {
                throw new Caller ('Can\'t override includes path. Make sure your apache or server settings allow including paths to be overridden. ');
            }

            $strTemplate = $this->evaluatePHP($strTemplateFilePath, $mixArgumentArray, $templateSettings);
            set_include_path($strOldIncludePath);

            $blnOverwriteFlag = Type::cast($templateSettings['OverwriteFlag'], Type::BOOLEAN);
            $strTargetDirectory = Type::cast($templateSettings['TargetDirectory'], Type::STRING);
            $strDirectorySuffix = Type::cast($templateSettings['DirectorySuffix'], Type::STRING);
            $strTargetFileName = Type::cast($templateSettings['TargetFileName'], Type::STRING);

            if (is_null($blnOverwriteFlag) || is_null($strTargetFileName) || is_null($strTargetDirectory) || is_null($strDirectorySuffix)) {
                throw new Exception('the template settings cannot be null');
            }

            if ($blnSave && $strTargetDirectory) {
                // Figure out the REAL target directory
                $strTargetDirectory = $strTargetDirectory . $strDirectorySuffix;

                // Create a Directory (if needed)
                if (!is_dir($strTargetDirectory)) {
                    if (!Folder::makeDirectory($strTargetDirectory, 0777)) {
                        throw new Exception('Unable to mkdir ' . $strTargetDirectory);
                    }
                }

                // Save to Disk
                $strFilePath = sprintf('%s/%s', $strTargetDirectory, $strTargetFileName);
                if ($blnOverwriteFlag || (!file_exists($strFilePath))) {

                    // Validate target directory writability (and existence)
                    if (!is_dir($strTargetDirectory) || !is_writable($strTargetDirectory)) {
                        $this->reportError(sprintf(
                            'Permission denied: target directory is not writable by the web server: %s',
                            $strTargetDirectory
                        ));
                        return false;
                    }

                    // If overwriting an existing file, also validate file writability
                    if (file_exists($strFilePath) && !is_writable($strFilePath)) {
                        $this->reportError(sprintf(
                            'Permission denied: target file is not writable by the web server: %s',
                            $strFilePath
                        ));
                        return false;
                    }

                    $intBytesSaved = file_put_contents($strFilePath, $strTemplate);
                    if ($intBytesSaved === false) {
                        $this->reportError(sprintf('Failed to write file: %s', $strFilePath));
                        return false;
                    }

                    $this->setGeneratedFilePermissions($strFilePath);
                    return ($intBytesSaved == strlen($strTemplate));

                } else // Because we are not supposed to overwrite, we should return "true" by default
                {
                    return true;
                }
            }

            // Why Did We Not Save?
            if ($blnSave) {
                // We WANT to Save, but QCubed Configuration says that this functionality/feature should no longer be generated
                // By definition, we should return "true"
                return true;
            }
            // Running generateFile() specifically asking it not to save -- so return the evaluated template instead
            return $strTemplate;
        }

        /**
         * Sets the permissions for the generated file to allow full read and write access.
         * This method modifies the file permissions only on non-Windows operating systems.
         *
         * @param string $strFilePath The path of the file whose permissions are to be set.
         * @return void This method does not return a value.
         */
        protected function setGeneratedFilePermissions(string $strFilePath): void
        {
            // CHMOD to full read/write permissions (applicable only to windows)
            // Need to ignore error handling for this call just in case
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $e = new Handler();
                chmod($strFilePath, 0666);
            }
        }

        /**
         * Evaluates the specified PHP template file with the provided arguments and template settings.
         *
         * @param string $strFilename The filename of the PHP template to be evaluated.
         * @param array $mixArgumentArray An associative array of arguments to be extracted and made available within the template.
         * @param array|null $templateSettings A variable to capture template-specific settings (passed by reference), updated during the evaluation.
         * @return string They evaluated template content as a string, with all processed data and logic applied.
         */
        protected function evaluatePHP(string $strFilename, array $mixArgumentArray, ?array &$templateSettings = null): string
        {
            // Get all the arguments and set them locally
            if ($mixArgumentArray) {
                foreach ($mixArgumentArray as $strName => $mixValue) {
                    $$strName = $mixValue;
                }
            }
            global $_TEMPLATE_SETTINGS;
            unset($_TEMPLATE_SETTINGS);
            $_TEMPLATE_SETTINGS = null;

            // Of course, we also need to locally allow "objCodeGen"
            $objCodeGen = $this;

            // Get Database Escape Identifiers
            $strEscapeIdentifierBegin = Service::getDatabase($this->intDatabaseIndex)->EscapeIdentifierBegin;
            $strEscapeIdentifierEnd = Service::getDatabase($this->intDatabaseIndex)->EscapeIdentifierEnd;

            // Store the Output Buffer locally
            $strAlreadyRendered = ob_get_contents();

            if (ob_get_level()) {
                ob_clean();
            }
            ob_start();
            include($strFilename);
            $strTemplate = ob_get_contents();
            ob_end_clean();

            $templateSettings = $_TEMPLATE_SETTINGS;
            unset($_TEMPLATE_SETTINGS);

            // Restore the output buffer and return the evaluated template
            print($strAlreadyRendered);

            // Remove all \r from the template (for Win/*nix compatibility)
            return str_replace("\r", '', $strTemplate);
        }

        ///////////////////////
        // COMMONLY OVERRIDDEN CONVERSION FUNCTIONS
        ///////////////////////

        /**
         * Removes a predefined prefix from the beginning of the given table name, if applicable.
         *
         * @param string $strTableName The name of the table from which the prefix may be removed.
         * @return string|null The table name without the prefix, or null if no prefix could be removed or the input is invalid.
         */
        protected function stripPrefixFromTable(string $strTableName): ?string
        {
            return null;
        }

        /**
         * Constructs the model class name based on the provided table name.
         *
         * @param string $strTableName The table name, potentially including a prefix, to convert into a model class name.
         * @return string The constructed model class name, which includes the class prefix, camel-cased table name, and class suffix.
         */
        protected function modelClassName(string $strTableName): string
        {
            $strTableName = $this->stripPrefixFromTable($strTableName);
            return sprintf('%s%s%s',
                $this->strClassPrefix,
                QString::camelCaseFromUnderscore($strTableName),
                $this->strClassSuffix);
        }

        /**
         * Generates a model variable name based on the provided table name.
         *
         * @param string $strTableName The name of the table, typically in underscore format, from which the model variable name is derived.
         * @return string A camelCase formatted model variable name, prefixed according to the object type.
         */
        public function modelVariableName(string $strTableName): string
        {
            $strTableName = $this->stripPrefixFromTable($strTableName);
            return Codegen::prefixFromType(Type::OBJECT) .
                QString::camelCaseFromUnderscore($strTableName);
        }

        /**
         * Generates a reverse reference variable name for a given table by stripping its prefix
         * and converting it to a model variable format.
         *
         * @param string $strTableName The table name for which the reverse reference variable name is to be generated.
         * @return string A formatted string representing the reverse reference variable name for the specified table.
         */
        protected function modelReverseReferenceVariableName(string $strTableName): string
        {
            $strTableName = $this->stripPrefixFromTable($strTableName);
            return $this->modelVariableName($strTableName);
        }

        /**
         * Resolves the variable type for a reverse reference based on the given table name.
         *
         * @param string $strTableName The name of the database table, which may include a prefix that needs to be stripped.
         * @return string The generated model class name corresponding to the processed table name.
         */
        protected function modelReverseReferenceVariableType(string $strTableName): string
        {
            $strTableName = $this->stripPrefixFromTable($strTableName);
            return $this->modelClassName($strTableName);
        }

        /**
         * Generates the variable name for a model column based on its type and name.
         *
         * @param SqlColumn $objColumn The column object containing metadata such as the variable type and name.
         * @return string The generated variable name, combining a type-based prefix and a camel-cased version of the column name.
         */
        protected function modelColumnVariableName(SqlColumn $objColumn): string
        {
            return Codegen::prefixFromType($objColumn->VariableType) .
                QString::camelCaseFromUnderscore($objColumn->Name);
        }

        /**
         * Converts a column name from underscore notation to camel case property name.
         *
         * @param string $strColumnName The column name in underscore notation.
         * @return string The converted property name in camel case format.
         */
        protected function modelColumnPropertyName(string $strColumnName): string
        {
            return QString::camelCaseFromUnderscore($strColumnName);
        }

        /**
         * Converts a column name from underscore format to camel case format for use as a property name.
         *
         * @param string $strColumnName The column name in underscore format that needs to be converted.
         * @return string The column name converted to camel case format.
         */
        protected function typeColumnPropertyName(string $strColumnName): string
        {
            return QString::camelCaseFromUnderscore($strColumnName);
        }

        /**
         * Generates a normalized column name for a reference column in the model.
         *
         * @param string $strColumnName The original column name from the database schema.
         * @return string The transformed column name. If the original name ends with "_id", the suffix is removed.
         *                Otherwise, "_object" is appended to make the variable name distinct.
         */
        protected function modelReferenceColumnName(string $strColumnName): string
        {
            $intNameLength = strlen($strColumnName);

            // Does the column name for this reference column end in "_id"?
            if (($intNameLength > 3) && (substr($strColumnName, $intNameLength - 3) == "_id")) {
                // It ends in "_id", but we don't want to include the "Id" suffix
                // in the Variable Name.  So remove it.
                $strColumnName = substr($strColumnName, 0, $intNameLength - 3);
            } else {
                // Otherwise, let's add "_object" so that we don't confuse this variable name
                // from the variable that was mapped from the physical database
                // E.g., if it's a numeric FK, and the column is defined as "person INT",
                // there will end up being two variables, one for the Person id integer, and
                // one for the Person object itself.  We'll add Object to the name of the Person object
                // to make this delineation.
                $strColumnName = sprintf("%s_object", $strColumnName);
            }

            return $strColumnName;
        }

        /**
         * Generates a variable name for a model reference based on the provided column name.
         *
         * @param string $strColumnName The column name used to derive the model reference variable name.
         * @return string A formatted variable name, prefixed and transformed from the column name.
         */
        protected function modelReferenceVariableName(string $strColumnName): string
        {
            $strColumnName = $this->modelReferenceColumnName($strColumnName);
            return Codegen::prefixFromType(Type::OBJECT) .
                QString::camelCaseFromUnderscore($strColumnName);
        }

        /**
         * Converts a column name into a camel-cased property name suitable for referencing a model.
         *
         * @param string $strColumnName The column name to convert into a property name.
         * @return string The camel-cased property name derived from the provided column name.
         */
        protected function modelReferencePropertyName(string $strColumnName): string
        {
            $strColumnName = $this->modelReferenceColumnName($strColumnName);
            return QString::camelCaseFromUnderscore($strColumnName);
        }

        /**
         * Generates the SQL variable assignment string for a given column, optionally including an equality clause.
         *
         * @param SqlColumn $objColumn The column object used to generate the variable assignment string.
         * @param bool $blnIncludeEquality Determines whether the equality clause should be included in the variable assignment.
         * @return string A formatted string representing the SQL variable assignment for the column.
         */
        protected function parameterCleanupFromColumn(SqlColumn $objColumn, ?bool $blnIncludeEquality = false): string
        {
            if ($blnIncludeEquality) {
                return sprintf('$%s = $objDatabase->sqlVariable($%s, true);',
                    $objColumn->VariableName, $objColumn->VariableName);
            } else {
                return sprintf('$%s = $objDatabase->sqlVariable($%s);',
                    $objColumn->VariableName, $objColumn->VariableName);
            }
        }

        /**
         * Generates a parameter list string from an array of column objects.
         *
         * @param array $objColumnArray An array of column objects from which the parameter list is derived. Each object is expected to have a property used for naming variables.
         * @return string A string representation of the parameter list, with entries concatenated and formatted according to specified rules.
         */
        protected function parameterListFromColumnArray(array $objColumnArray): string
        {
            return $this->implodeObjectArray(', ', '$', '', 'VariableName', $objColumnArray);
        }

        /**
         * Generates a string by concatenating an array of objects' properties with a specified glue, prefix, and suffix.
         *
         * @param string $strGlue The string used to join the elements of the resulting array.
         * @param string $strPrefix A string to prefix each object's property value in the resulting array.
         * @param string $strSuffix A string to suffix each object's property value in the resulting array.
         * @param string $strProperty The name of the property to be accessed within each object in the array.
         * @param array $objArrayToImplode An array of objects to be processed.
         * @return string A single string obtained by concatenating processed property values from the objects, separated by the specified glue.
         */
        protected function implodeObjectArray(string $strGlue, string $strPrefix, string $strSuffix, string $strProperty, array $objArrayToImplode): string
        {
            $strArrayToReturn = array();
            if ($objArrayToImplode) {
                foreach ($objArrayToImplode as $objObject) {
                    $strArrayToReturn[] = sprintf('%s%s%s', $strPrefix, $objObject->$strProperty, $strSuffix);
                }
            }

            return implode($strGlue, $strArrayToReturn);
        }

        /**
         * Converts a type name into a sanitized token suitable for use as an identifier.
         *
         * @param string $strName The input type name to be processed into a valid token. It may contain alphanumeric characters, underscores, or other symbols.
         * @return string A sanitized string token that contains only alphanumeric characters and underscores. If the token starts with a numeric character, an underscore is prepended.
         */
        protected function typeTokenFromTypeName(string $strName): string
        {
            $strToReturn = '';
            for ($intIndex = 0; $intIndex < strlen($strName); $intIndex++) {
                if (((ord($strName[$intIndex]) >= ord('a')) &&
                        (ord($strName[$intIndex]) <= ord('z'))) ||
                    ((ord($strName[$intIndex]) >= ord('A')) &&
                        (ord($strName[$intIndex]) <= ord('Z'))) ||
                    ((ord($strName[$intIndex]) >= ord('0')) &&
                        (ord($strName[$intIndex]) <= ord('9'))) ||
                    ($strName[$intIndex] == '_')
                ) {
                    $strToReturn .= $strName[$intIndex];
                }
            }

            if (is_numeric(QString::firstCharacter($strToReturn))) {
                $strToReturn = '_' . $strToReturn;
            }
            return $strToReturn;
        }

        /**
         * Generates a control name for a model connector based on a column's properties.
         *
         * @param ColumnInterface $objColumn The column object containing metadata and options used to determine the control name.
         * @return string The generated control name. If a name is explicitly defined in the column's options, it returns that name; otherwise, a name derived from the column's property is returned.
         * @throws Exception
         */
        public static function modelConnectorControlName(ColumnInterface $objColumn): string
        {
            if (($o = $objColumn->Options) && isset ($o['Name'])) { // Did developer default?
                return $o['Name'];
            }
            return QString::wordsFromCamelCase(Codegen::modelConnectorPropertyName($objColumn));
        }

        /**
         * Retrieves the property name or description for a given column based on its type.
         *
         * @param ColumnInterface $objColumn The column for which the property name or description is to be determined. This can be an instance of `SqlColumn`, `ReverseReference`, or `ManyToManyReference`.
         * @return string The property name or description derived from the provided column.
         * @throws Exception If the column type is unknown.
         */
        public static function modelConnectorPropertyName(ColumnInterface $objColumn): string
        {
            if ($objColumn instanceof SqlColumn) {
                if ($objColumn->Reference) {
                    return $objColumn->Reference->PropertyName;
                } else {
                    return $objColumn->PropertyName;
                }
            } elseif ($objColumn instanceof ReverseReference) {
                if ($objColumn->Unique) {
                    return ($objColumn->ObjectDescription);
                } else {
                    return ($objColumn->ObjectDescriptionPlural);
                }
            } elseif ($objColumn instanceof ManyToManyReference) {
                return $objColumn->ObjectDescriptionPlural;
            } else {
                throw new Exception ('Unknown column type.');
            }
        }

        /**
         * Retrieves the variable name for a model connector associated with the given column.
         *
         * @param ColumnInterface $objColumn The column for which the model connector variable name is to be retrieved.
         * @return string The variable name derived from the column's property name and control helper.
         * @throws Caller
         * @throws InvalidCast
         */
        public function modelConnectorVariableName(ColumnInterface $objColumn): string
        {
            $strPropName = static::modelConnectorPropertyName($objColumn);
            $objControlHelper = $this->getControlCodeGenerator($objColumn);
            return $objControlHelper->varName($strPropName);
        }

        /**
         * Generates the variable name for a model connector label associated with the given column.
         *
         * @param ColumnInterface $objColumn The column interface instance for which the label variable name is to be generated.
         * @return string The variable name corresponding to the model connector label for the provided column.
         * @throws InvalidCast
         */
        public function modelConnectorLabelVariableName(ColumnInterface $objColumn): string
        {
            $strPropName = static::modelConnectorPropertyName($objColumn);
            return Label::instance()->varName($strPropName);
        }

        /**
         * Determines the appropriate control class for a given column.
         *
         * @param ColumnInterface $objColumn The column for which the control class is to be determined. Can be a SQL column, reverse reference, or many-to-many reference.
         * @return string The fully qualified class name of the control that corresponds to the given column type.
         * @throws Exception If the column type is unknown or cannot be resolved to a control class.
         */
        protected function modelConnectorControlClass(ColumnInterface $objColumn): string
        {
            // Is the class specified by the developer?
            if ($o = $objColumn->Options) {
                if (isset ($o['FormGen']) && $o['FormGen'] == Options::FORMGEN_LABEL_ONLY) {
                    return '\\QCubed\\Control\\Label';
                }
                if (isset($o['ControlClass'])) {
                    return $o['ControlClass'];
                }
            }

            // otherwise, return the default class based on the column
            if ($objColumn instanceof SqlColumn) {
                if ($objColumn->Identity) {
                    return '\\QCubed\\Control\\Label';
                }

                if ($objColumn->Timestamp) {
                    return '\\QCubed\\Control\\Label';
                }

                if ($objColumn->Reference) {
                    return '\\QCubed\\Project\\Control\\ListBox';
                }

                return match ($objColumn->VariableType) {
                    Type::BOOLEAN => '\\QCubed\\Project\\Control\\Checkbox',
                    Type::DATE_TIME => '\\QCubed\\Control\\DateTimePicker',
                    Type::INTEGER => '\\QCubed\\Control\\IntegerTextBox',
                    Type::FLOAT => '\\QCubed\\Control\\FloatTextBox',
                    default => '\\QCubed\\Project\\Control\\TextBox',
                };
            } elseif ($objColumn instanceof ReverseReference) {
                if ($objColumn->Unique) {
                    return '\\QCubed\\Project\\Control\\ListBox';
                } else {
                    return '\\QCubed\\Control\\CheckboxList';    // for multi-selection
                }
            } elseif ($objColumn instanceof ManyToManyReference) {
                return '\\QCubed\\Control\\CheckboxList';    // for multi-selection
            }
            throw new Exception('Unknown column type.');
        }

        /**
         * Retrieves the control class associated with a given SQL table.
         * Returns a developer-specified control class if defined, otherwise a default is provided.
         *
         * @param SqlTable $objTable The SQL table object containing metadata and options.
         * @return string The fully qualified name of the control class, either specified in the table options or a default value.
         */
        public static function dataListControlClass(SqlTable $objTable): string
        {
            // Is the class specified by the developer?
            if ($o = $objTable->Options) {
                if (isset($o['ControlClass'])) {
                    return $o['ControlClass'];
                }
            }

            // Otherwise, return a default
            return '\\QCubed\\Project\\Control\\DataGrid';
        }

        /**
         * Determines the name for a data list control based on the provided table's options or class name.
         *
         * @param SqlTable $objTable The table object containing metadata such as options and class name.
         * @return string The name for the data list control. Returns the value from the table's options if specified, otherwise generates a name from the plural class name.
         */
        public static function dataListControlName(SqlTable $objTable): string
        {
            if (($o = $objTable->Options) && isset ($o['Name'])) { // Did developer default?
                return $o['Name'];
            }
            return QString::wordsFromCamelCase($objTable->ClassNamePlural);
        }

        /**
         * Retrieves the name of a data list item based on the provided table.
         *
         * @param SqlTable $objTable The table object from which the item name is derived. It may contain an overridden name in its options.
         * @return string The resolved item name. If an override exists in the table options, it returns that value; otherwise, it converts the table class name from camel case format.
         */
        public static function dataListItemName(SqlTable $objTable): string
        {
            if (($o = $objTable->Options) && isset ($o['ItemName'])) { // Did developer override?
                return $o['ItemName'];
            }
            return QString::wordsFromCamelCase($objTable->ClassName);
        }

        /**
         * Generates the variable name for a data list based on the provided SQL table.
         *
         * @param SqlTable $objTable The SQL table object used to determine the data list's a variable name.
         * @return string The variable name for the data list associated with the specified SQL table.
         * @throws Caller
         */
        public function dataListVarName(SqlTable $objTable): string
        {
            $strPropName = self::dataListPropertyNamePlural($objTable);
            $objControlHelper = $this->getDataListCodeGenerator($objTable);
            return $objControlHelper->varName($strPropName);
        }

        /**
         * Retrieves the data list property name associated with the provided SQL table.
         *
         * @param SqlTable $objTable The SQL table object for which the property name is to be determined.
         * @return string The class name of the provided SQL table object.
         */
        public static function dataListPropertyName(SqlTable $objTable): string
        {
            return $objTable->ClassName;
        }

        /**
         * Retrieves the pluralized property name of the class associated with the provided SQL table.
         *
         * @param SqlTable $objTable The SQL table object from which to retrieve the pluralized class name property.
         * @return string The pluralized property name of the class associated with the given SQL table.
         */
        public static function dataListPropertyNamePlural(SqlTable $objTable): string
        {
            return $objTable->ClassNamePlural;
        }

        /**
         * Retrieves the code generator for the specified column's control class.
         *
         * @param mixed $objColumn The column object for which the control's code generator is to be retrieved.
         * @return mixed An instance of the code generator associated with the control class of the specified column.
         * @throws Caller If the control class does not implement the 'getCodeGenerator' method.
         */
        public function getControlCodeGenerator(mixed $objColumn): mixed
        {
            $strControlClass = $this->modelConnectorControlClass($objColumn);

            if (method_exists($strControlClass, 'getCodeGenerator')) {
                return $strControlClass::getCodeGenerator();
            } else {
                throw new Caller("Class " . $strControlClass . " must implement getCodeGenerator()");
            }
        }

        /**
         * Retrieves the code generator for the data list control class associated with the provided table.
         *
         * @param mixed $objTable The table object from which the data list control class is determined.
         * @return mixed The result of the `getCodeGenerator` method implemented by the data list control class.
         * @throws Caller If the determined data list control class does not implement the `getCodeGenerator` method.
         */
        public function getDataListCodeGenerator(mixed $objTable): mixed
        {
            $strControlClass = $this->dataListControlClass($objTable);

            if (method_exists($strControlClass, 'getCodeGenerator')) {
                return $strControlClass::getCodeGenerator();
            } else {
                throw new Caller("Class " . $strControlClass . " must implement getCodeGenerator()");
            }
        }

        /**
         * Generates and returns a string representing the member variable name for an object.
         *
         * @param string $strTableName The name of the database table.
         * @param string $strColumnName The name of the database column.
         * @param string $strReferencedTableName The name of the referenced table in the object relationship.
         * @return string The constructed member variable name based on the provided table and column details.
         */
        protected function calculateObjectMemberVariable(string $strTableName, string $strColumnName, string $strReferencedTableName): string
        {
            return sprintf('%s%s%s%s',
                Codegen::prefixFromType(Type::OBJECT),
                $this->strAssociatedObjectPrefix,
                $this->calculateObjectDescription($strTableName, $strColumnName, $strReferencedTableName, false),
                $this->strAssociatedObjectSuffix);
        }

        /**
         * Calculates the property name of an object based on the provided table name, column name,
         * and referenced table name, combined with the associated object prefix and suffix.
         *
         * @param string $strTableName The name of the table being processed.
         * @param string $strColumnName The column name from the table relevant to the property.
         * @param string $strReferencedTableName The name of the referenced table used in the calculation.
         * @return string A string representing the object property name, constructed using the prefix,
         *                suffix, and a description derived from the given parameters.
         */
        protected function calculateObjectPropertyName(string $strTableName, string $strColumnName, string $strReferencedTableName): string
        {
            return sprintf('%s%s%s',
                $this->strAssociatedObjectPrefix,
                $this->calculateObjectDescription($strTableName, $strColumnName, $strReferencedTableName, false),
                $this->strAssociatedObjectSuffix);
        }

        // TODO: These functions need to be documented heavily with information from "lexical analysis on fk names.txt"

        /**
         * Calculates an object description based on table names, column names, and other parameters.
         *
         * @param string $strTableName The name of the table for which the description is being calculated.
         * @param string $strColumnName The column name to be used in determining the description.
         * @param string $strReferencedTableName The name of the referenced table in the relationship.
         * @param bool $blnPluralize Indicates whether the description should use a pluralized form of the table name.
         * @return string A formatted string that describes the object based on the provided parameters.
         */
        protected function calculateObjectDescription(string $strTableName, string $strColumnName, string $strReferencedTableName, bool $blnPluralize): string
        {
            // Strip Prefixes (if applicable)
            $strTableName = $this->stripPrefixFromTable($strTableName);
            $strReferencedTableName = $this->stripPrefixFromTable($strReferencedTableName);

            // Starting Point
            $strToReturn = QString::camelCaseFromUnderscore($strTableName);

            if ($blnPluralize) {
                $strToReturn = $this->pluralize($strToReturn);
            }

            if ($strTableName == $strReferencedTableName) {
                // Self-referencing Reference to Describe

                // If Column Name is only the name of the referenced table, or the name of the referenced table with "_id",
                // then the object description is simply based off the table name.
                if (($strColumnName == $strReferencedTableName) ||
                    ($strColumnName == $strReferencedTableName . '_id')
                ) {
                    return sprintf('Child%s', $strToReturn);
                }

                // Rip out trailing "_id" if applicable
                $intLength = strlen($strColumnName);
                if (($intLength > 3) && (substr($strColumnName, $intLength - 3) == "_id")) {
                    $strColumnName = substr($strColumnName, 0, $intLength - 3);
                }

                // Rip out the referenced table name from the column name
                $strColumnName = str_replace($strReferencedTableName, "", $strColumnName);

                // Change any double "_" to single "_"
                $strColumnName = str_replace("__", "_", $strColumnName);
                $strColumnName = str_replace("__", "_", $strColumnName);

                $strColumnName = QString::camelCaseFromUnderscore($strColumnName);

                // Special case for Parent/Child
                if ($strColumnName == 'Parent') {
                    return sprintf('Child%s', $strToReturn);
                }

                return sprintf("%sAs%s",
                    $strToReturn, $strColumnName);

            } else {
                // If Column Name is only the name of the referenced table, or the name of the referenced table with "_id",
                // then the object description is simply based off the table name.
                if (($strColumnName == $strReferencedTableName) ||
                    ($strColumnName == $strReferencedTableName . '_id')
                ) {
                    return $strToReturn;
                }

                // Rip out trailing "_id" if applicable
                $intLength = strlen($strColumnName);
                if (($intLength > 3) && (substr($strColumnName, $intLength - 3) == "_id")) {
                    $strColumnName = substr($strColumnName, 0, $intLength - 3);
                }

                // Rip out the referenced table name from the column name
                $strColumnName = str_replace($strReferencedTableName, "", $strColumnName);

                // Change any double "_" to single "_"
                $strColumnName = str_replace("__", "_", $strColumnName);
                $strColumnName = str_replace("__", "_", $strColumnName);

                return sprintf("%sAs%s",
                    $strToReturn,
                    QString::camelCaseFromUnderscore($strColumnName));
            }
        }

        /**
         * Calculates the description of an object for an association, based on a table and reference table names.
         *
         * @param string $strAssociationTableName The association table name used to determine the object description.
         * @param string $strTableName The primary table name involved in the association.
         * @param string $strReferencedTableName The referenced table name in the association.
         * @param bool $blnPluralize Whether the description should be pluralized.
         * @return string The calculated object description reflecting the association details and naming conventions.
         */
        protected function calculateObjectDescriptionForAssociation(
            string $strAssociationTableName,
            string $strTableName,
            string $strReferencedTableName,
            bool $blnPluralize
        ): string
        {
            // Strip Prefixes (if applicable)
            $strTableName = $this->stripPrefixFromTable($strTableName);
            $strAssociationTableName = $this->stripPrefixFromTable($strAssociationTableName);
            $strReferencedTableName = $this->stripPrefixFromTable($strReferencedTableName);

            // Starting Point
            $strToReturn = QString::camelCaseFromUnderscore($strReferencedTableName);

            if ($blnPluralize) {
                $strToReturn = $this->pluralize($strToReturn);
            }

            // Let's start with strAssociationTableName

            // Rip out trailing "_assn" if applicable
            $strAssociationTableName = str_replace($this->strAssociationTableSuffix, '', $strAssociationTableName);

            // remove instances of the table names in the association table name
            $strTableName2 = str_replace('_', '', $strTableName); // remove underscores if they are there
            $strReferencedTableName2 = str_replace('_', '',
                $strReferencedTableName); // remove underscores if they are there

            if (beginsWith($strAssociationTableName, $strTableName . '_')) {
                $strAssociationTableName = trimOffFront($strTableName . '_', $strAssociationTableName);
            } elseif (beginsWith($strAssociationTableName, $strTableName2 . '_')) {
                $strAssociationTableName = trimOffFront($strTableName2 . '_', $strAssociationTableName);
            } elseif (beginsWith($strAssociationTableName, $strReferencedTableName . '_')) {
                $strAssociationTableName = trimOffFront($strReferencedTableName . '_', $strAssociationTableName);
            } elseif (beginsWith($strAssociationTableName, $strReferencedTableName2 . '_')) {
                $strAssociationTableName = trimOffFront($strReferencedTableName2 . '_', $strAssociationTableName);
            } elseif ($strAssociationTableName == $strTableName ||
                $strAssociationTableName == $strTableName2 ||
                $strAssociationTableName == $strReferencedTableName ||
                $strAssociationTableName == $strReferencedTableName2
            ) {
                $strAssociationTableName = "";
            }

            if (endsWith($strAssociationTableName, '_' . $strTableName)) {
                $strAssociationTableName = trimOffEnd('_' . $strTableName, $strAssociationTableName);
            } elseif (endsWith($strAssociationTableName, '_' . $strTableName2)) {
                $strAssociationTableName = trimOffEnd('_' . $strTableName2, $strAssociationTableName);
            } elseif (endsWith($strAssociationTableName, '_' . $strReferencedTableName)) {
                $strAssociationTableName = trimOffEnd('_' . $strReferencedTableName, $strAssociationTableName);
            } elseif (endsWith($strAssociationTableName, '_' . $strReferencedTableName2)) {
                $strAssociationTableName = trimOffEnd('_' . $strReferencedTableName2, $strAssociationTableName);
            } elseif ($strAssociationTableName == $strTableName ||
                $strAssociationTableName == $strTableName2 ||
                $strAssociationTableName == $strReferencedTableName ||
                $strAssociationTableName == $strReferencedTableName2
            ) {
                $strAssociationTableName = "";
            }

            // Change any double "__" to single "_"
            $strAssociationTableName = str_replace("__", "_", $strAssociationTableName);
            $strAssociationTableName = str_replace("__", "_", $strAssociationTableName);
            $strAssociationTableName = str_replace("__", "_", $strAssociationTableName);

            // If we have nothing left or just a single "_" in AssociationTableName, return "Starting Point"
            if (($strAssociationTableName == "_") || ($strAssociationTableName == "")) {
                return sprintf("%s%s%s",
                    $this->strAssociatedObjectPrefix,
                    $strToReturn,
                    $this->strAssociatedObjectSuffix);
            }

            // Otherwise, add "As" and the predicate
            return sprintf("%s%sAs%s%s",
                $this->strAssociatedObjectPrefix,
                $strToReturn,
                QString::camelCaseFromUnderscore($strAssociationTableName),
                $this->strAssociatedObjectSuffix);
        }

        // This is called by AnalyzeAssociationTable to calculate the GraphPrefixArray for a self-referencing association table (e.g., directed graph)

        /**
         * Determines the graph prefix array based on the provided foreign key array.
         *
         * @param array $objForeignKeyArray An array of objects containing column name arrays which are analyzed to determine the graph prefix.
         * @return array An array containing the calculated graph prefixes. The positions in the array correspond to the input foreign key structure.
         */
        protected function calculateGraphPrefixArray(array $objForeignKeyArray): array
        {
            // Analyze Column Names to determine GraphPrefixArray
            if ((str_contains(strtolower($objForeignKeyArray[0]->ColumnNameArray[0]), 'parent')) ||
                (str_contains(strtolower($objForeignKeyArray[1]->ColumnNameArray[0]), 'child'))
            ) {
                $strGraphPrefixArray[0] = '';
                $strGraphPrefixArray[1] = 'Parent';
            } else {
                $strGraphPrefixArray[0] = 'Parent';
                $strGraphPrefixArray[1] = '';
            }

            return $strGraphPrefixArray;
        }

        /**
         * Maps a database field type to the corresponding variable type.
         *
         * @param mixed $strDbType The database field type to be converted. Typically, a value from the `Database\FieldType` class.
         * @return string The corresponding variable type. Throws an exception if the database field type is not recognized.
         * @throws InvalidCast
         */
        protected function variableTypeFromDbType(mixed $strDbType): string
        {
            return match ($strDbType) {
                Database\FieldType::BIT => Type::BOOLEAN,
                Database\FieldType::CHAR, Database\FieldType::VAR_CHAR, Database\FieldType::JSON, Database\FieldType::BLOB => Type::STRING,
                Database\FieldType::DATE_TIME, Database\FieldType::TIME, Database\FieldType::DATE => Type::DATE_TIME,
                Database\FieldType::FLOAT => Type::FLOAT,
                Database\FieldType::INTEGER => Type::INTEGER,
                default => throw new InvalidCast("Invalid Db Type to Convert: $strDbType"),
            };
        }

        /**
         * Converts a singular noun to its plural form based on certain linguistic rules.
         *
         * @param string $strName The singular noun to be pluralized.
         * @return string The plural form of the given noun. Applies specific rules for cases such as words ending in 'y', 's', 'x', 'z', 'sh', and 'ch'.
         */
        protected function pluralize(string $strName): string
        {
            // Special Rules go Here
            if (((strtolower($strName) == 'play'))) {
                return $strName . 's';
            }

            $intLength = strlen($strName);
            if (substr($strName, $intLength - 1) == "y") {
                return substr($strName, 0, $intLength - 1) . "ies";
            }
            if (substr($strName, $intLength - 1) == "s") {
                return $strName . "es";
            }
            if (substr($strName, $intLength - 1) == "x") {
                return $strName . "es";
            }
            if (substr($strName, $intLength - 1) == "z") {
                return $strName . "zes";
            }
            if (substr($strName, $intLength - 2) == "sh") {
                return $strName . "es";
            }
            if (substr($strName, $intLength - 2) == "ch") {
                return $strName . "es";
            }

            return $strName . "s";
        }

        /**
         * Appends an error message to the list of errors.
         *
         * @param string $strError The error message to be recorded.
         * @return void
         */
        public function reportError(string $strError): void
        {
            $this->strErrors .= $strError . "\r\n";
        }

        ////////////////////
        // Public Overriders
        ////////////////////

        /**
         * Retrieves the value of a property based on its name.
         *
         * @param string $strName The name of the property to retrieve.
         * @return mixed The value of the requested property. If the property does not exist, attempts to retrieve it using the parent class or throws an exception if not found.
         * @throws Caller
         * @throws UndefinedProperty
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case 'Errors':
                    return $this->strErrors;
                case 'Warnings':
                    return $this->strWarnings;
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
         * Magic method to set the value of a property dynamically.
         *
         * @param string $strName The name of the property to be set.
         * @param mixed $mixValue The value to assign to the specified property. The value will be cast to the appropriate type if applicable.
         * @return void This method does not return a value.
         * @throws Exception
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            try {
                switch ($strName) {
                    case 'Errors':
                        ($this->strErrors = Type::cast($mixValue, Type::STRING));
                        break;

                    case 'Warnings':
                        ($this->strWarnings = Type::cast($mixValue, Type::STRING));
                        break;

                    default:
                        parent::__set($strName, $mixValue);
                }
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
            }
        }
    }