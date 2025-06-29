<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Codegen;

/**
 * Redefine __CODEGEN_OPTION_FILE__ if you want your file to be in a different location
 */
use QCubed\ObjectBase;

if (!defined("__CODEGEN_OPTION_FILE__")) {
    define("__CODEGEN_OPTION_FILE__", QCUBED_CONFIG_DIR . '/codegen_options.json');
}

/**
 * Class OptionFile
 * Interface to the option file that lets you specify various hand-edited and automated options
 * per a field. We currently use this for the ModelConnectorEditor, but it could potentially be
 * used for other things too.
 *
 * Regarding the choice of JSON file: we needed a file format that works good hand editing but also can
 * look good when the machine is generated. There are a few choices: XML is somewhat cumbersome and is not completely
 * straight forward when moving to PHP objects. YML would require people to learn YML; they have enough to do.
 * PHP objects can be output, but they don't look every good when output by machine. JSON seemed to be the one
 * that was easiest to implement with the necessary requirements.
 *
 * Note that this ties table and field names in the database to these options. If the table or field name
 * changes in the database, the options will be lost. We can try to guess as to whether changes were made based upon
 * the index of the changes in the field list, but not entirely easy to do. Best would be for a developer to hand-code
 * the changes in the JSON file in this case.
 *
 * This will be used by the designer to record the changes in preparation for codegen.
 * @package QCubed\Codegen
 */
class OptionFile extends ObjectBase
{
    protected array $options = array();
    protected ?bool $blnChanged = false;

    const TABLE_OPTIONS_FIELD_NAME = '*';

    public function __construct()
    {
        if (file_exists(__CODEGEN_OPTION_FILE__)) {
            $strContent = file_get_contents(__CODEGEN_OPTION_FILE__);

            if ($strContent) {
                $this->options = json_decode($strContent, true);
            }
        }

        // TODO: Analyze the result for changes and make a guess as to whether a table name or field name was changed
    }

    /**
     * Save the current configuration into the option file.
     */
    function save(): void
    {
        if (!$this->blnChanged) {
            return;
        }
        $flags = JSON_PRETTY_PRINT;
        $strContent = json_encode($this->options, $flags);

        file_put_contents(__CODEGEN_OPTION_FILE__, $strContent);
        $this->blnChanged = false;
    }

    /**
     * Makes sure save is the final step.
     */
    function __destruct()
    {
        $this->save();
    }

    /**
     * Set an option.
     *
     * @param string $strTableName The name of the table.
     * @param string $strFieldName The name of the field.
     * @param string $strOptionName The name of the option.
     * @param mixed $mixValue The value to be set for the option.
     * @return void
     */
    public function setOption(string $strTableName, string $strFieldName, string $strOptionName, mixed $mixValue): void
    {
        $this->options[$strTableName][$strFieldName][$strOptionName] = $mixValue;
        $this->blnChanged = true;
    }

    /**
     * Set options for a specific class and field.
     *
     * @param string $strClassName The name of the class.
     * @param string $strFieldName The name of the field.
     * @param mixed $mixValue The value to set for the specified field; if empty, the option is removed.
     * @return void
     */
    public function setOptions(string $strClassName, string $strFieldName, mixed $mixValue): void
    {
        if (empty ($mixValue)) {
            unset($this->options[$strClassName][$strFieldName]);
        } else {
            $this->options[$strClassName][$strFieldName] = $mixValue;
        }
        $this->blnChanged = true;
    }

    /**
     * Unset an option.
     *
     * @param string $strClassName The class name associated with the option.
     * @param string $strFieldName The field name associated with the option.
     * @param string $strOptionName The name of the option to be removed.
     * @return void
     */
    public function unsetOption(string $strClassName, string $strFieldName, string $strOptionName): void
    {
        unset ($this->options[$strClassName][$strFieldName][$strOptionName]);
        $this->blnChanged = true;
    }

    /**
     * Retrieves the value of a specific option based on the provided class name, field name, and option name.
     *
     * @param string $strClassName The name of the class to which the option belongs.
     * @param string $strFieldName The field name associated with the option.
     * @param string $strOptionName The name of the option to retrieve.
     * @return mixed Returns the value of the specified option if found, or null if the option does not exist.
     */
    public function getOption(string $strClassName, string $strFieldName, string $strOptionName): mixed
    {
        return $this->options[$strClassName][$strFieldName][$strOptionName] ?? null;
    }

    /**
     * Retrieves all options associated with a specific class and field name.
     *
     * @param string $strClassName The name of the class containing the options.
     * @param string $strFieldName The field name associated with the options.
     * @return array Returns an array of options if available, or an empty array if no options are found.
     */
    public function getOptions(string $strClassName, string $strFieldName): array
    {
        return $this->options[$strClassName][$strFieldName] ?? array();
    }
}