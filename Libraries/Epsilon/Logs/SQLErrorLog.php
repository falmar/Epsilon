<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 6:25 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Logs;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\Object\ActiveRecord;

/**
 * Class SQLErrorLog
 *
 * @package Epsilon\Logs
 */
class SQLErrorLog extends ActiveRecord
{
    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            "SQL_ErrorLog",
            "sql_el"
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            "SQL_ErrorLogID" => "ID",
            "UserID"         => "UserID",
            "SessionID"      => "SessionID",
            "ApplicationID"  => "ApplicationID",
            "SQLSTATE_Code"  => "SQLSTATE_Code",
            "DriverCode"     => "DriverCode",
            "DriverMessage"  => "DriverMessage",
            "Backtrace"      => "Backtrace",
            "Impress"        => "Impress",
            "RegisteredDate" => "RegisteredDate"
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function defineRelationMap()
    {
        return [];
    }

    /** @return array */
    protected function defineRules()
    {
        return [];
    }

    /**
     * @param           $objPDO
     * @param null      $ID_Date
     * @param bool|true $ResultSet
     */
    public function __construct($objPDO, $ID_Date, $ResultSet = true)
    {
        parent::__construct($objPDO, $ID_Date, $ResultSet);
        $this->setProperties([
            "SessionID"     => Factory::getSession()->getPHP_SessionID(),
            "ApplicationID" => Factory::getApplication()->getApplicationID(),
            "UserID"        => Factory::getUser()->get("ID"),
            "Impress"       => 0
        ], false);
    }

    public function Impress()
    {
        $this->set("Impress", 1);
        $this->save();
    }
}