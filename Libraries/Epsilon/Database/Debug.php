<?php
/**
 * Project: Epsilon
 * Date: 10/27/15
 * Time: 9:29 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Database;

defined('EPSILON_EXEC') or die();

use Epsilon\Factory;
use Epsilon\User\SystemMessage;
use PDOException;

/**
 * Class Debug
 *
 * @package Epsilon\Database
 */
class Debug
{
    private static $blDebug          = false;
    private static $currentSSQL      = '';
    private static $arDebugSSQL      = [];
    private static $arErrorDebugSSQL = [];

    /**
     * @param bool $bl
     */
    public static function setDebug($bl)
    {
        if (is_bool($bl)) {
            self::$blDebug = $bl;
        }
    }

    /**
     * @return bool
     */
    public static function inDebug()
    {
        return self::$blDebug;
    }

    /**
     * @param $SSQL
     */
    public static function addSSQL($SSQL)
    {
        self::$currentSSQL = $SSQL;
        array_push(self::$arDebugSSQL, $SSQL);
    }

    /**
     * @return array
     */
    public static function getDebugSSQL()
    {
        return self::$arDebugSSQL;
    }

    /**
     * @return array
     */
    public static function getErrorDebugSSQL()
    {
        return self::$arErrorDebugSSQL;
    }

    /**
     * @param PDOException $exception
     * @param null|string  $SSQL
     * @return bool
     */
    public static function catchException(PDOException $exception, $SSQL = null)
    {
        if (count($exception->errorInfo) == 3) {
            $DriverCode    = $exception->errorInfo[1];
            $DriverMessage = htmlentities($exception->errorInfo[2]);
        } else {
            $DriverCode    = 'PDO_ENGINE';
            $DriverMessage = htmlentities($exception->getMessage());
        }

        Factory::getLogger()->warning('DBH Exception: DriverCode: {DriverCode} Message: {Message} File: {File} ({Line})', [
            'DriverCode' => $DriverCode,
            'Message'    => $DriverMessage,
            'Line'       => $exception->getLine(),
            'File'       => $exception->getFile(),
        ]);

        if ($SSQL) {
            array_push(self::$arErrorDebugSSQL, $SSQL);
        }

        if (self::inDebug()) {
            SystemMessage::addMessage('_DBH', 'alert', $DriverMessage, false);
        }

        return true;
    }
}
