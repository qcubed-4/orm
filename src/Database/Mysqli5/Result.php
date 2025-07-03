<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Database\Mysqli5;

use Exception;

/**
 * Class Result
 * @package QCubed\Database\Mysqli5
 */
class Result extends MysqliResult
{
    /**
     * Fetches all fields from the result set and returns them as an array of Field objects.
     *
     * @return Field[] Returns an array of Field objects representing all fields in the result set.
     * @throws Exception
     */
    public function fetchFields(): array
    {
        $objArrayToReturn = array();
        while ($objField = $this->objMySqliResult->fetch_field()) {
            $objArrayToReturn[] = new Field($objField, $this->objDb);
        }
        return $objArrayToReturn;
    }

    /**
     * Fetches the next field from the result set and returns it as a Field object.
     *
     * @return Field|null Returns an instance of the Field object if a field is successfully fetched, or null if no more fields are available.
     * @throws Exception
     */
    public function fetchField(): ?Field
    {
        if ($objField = $this->objMySqliResult->fetch_field()) {
            return new Field($objField, $this->objDb);
        }
        return null;
    }
}