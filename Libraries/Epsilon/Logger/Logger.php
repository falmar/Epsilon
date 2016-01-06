<?php
/**
 * Project: Epsilon
 * Date: 11/6/15
 * Time: 12:59 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Logger;

defined("EPSILON_EXEC") or die();

use App\Config;
use Epsilon\Factory;
use Epsilon\Utility\Utility;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Class Logger
 *
 * @package Epsilon\Logger
 */
class Logger extends AbstractLogger
{

    private static $instance;
    private        $logs = [];

    /**
     * @return Logger
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {

        $message = $this->interpolateMessage($message, $context);

        if (Config::APP_DEBUG) {
            echo $message, PHP_EOL;
        }

        if (($level == LogLevel::EMERGENCY || $level == LogLevel::ALERT) || !Factory::getDBH()) {
            if (is_writeable('error_log')) {
                $handle = fopen('error_log', 'a');
                fwrite($handle, $message . PHP_EOL);
                fclose($handle);
            }
            exit();
        } else {
            array_push($this->logs, [
                'UserID'         => Factory::getUser()->get('ID'),
                'SessionID'      => Factory::getSession()->getPHP_SessionID(),
                'ApplicationID'  => Factory::getApplication()->getApplicationID(),
                'Level'          => $level,
                'ErrorString'    => $message,
                'RegisteredDate' => Utility::getDateForDB()
            ]);
        }
    }

    function writeLogs()
    {
        foreach ($this->logs as $log) {
            $Log = new Log(Factory::getDBH(), $log, false);
            $Log->save();
        }
    }

    /**
     * @param int    $ErrorNo
     * @param string $Message
     * @param string $ErrorFile
     * @param string $ErrorLine
     */
    public static function addPHPError($ErrorNo, $Message, $ErrorFile, $ErrorLine)
    {
        self::getErrorTypeString($ErrorNo, $ErrorNoString, $LogLevel);
        self::getInstance()->log($LogLevel, "{ErrorString} ({ErrorNo}): {Message}.\nFile: {File}\nLine: {Line}", [
            'ErrorString' => $ErrorNoString,
            'ErrorNo'     => $ErrorNo,
            'Message'     => $Message,
            'File'        => $ErrorFile,
            'Line'        => $ErrorLine
        ]);
    }

    /**
     * @param $message
     * @param $context
     * @return string
     */
    private function interpolateMessage($message, $context)
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param \Exception $Exception
     */
    public static function uncaughtException($Exception)
    {
        self::getInstance()->log(LogLevel::EMERGENCY, "Uncaught Exception: {Message}\nFile: {File}\nLine: {Line}", [
            'Message' => $Exception->getMessage(),
            'File'    => $Exception->getFile(),
            'Line'    => $Exception->getLine()
        ]);
    }

    public static function shutdown()
    {
        if ($Error = error_get_last()) {
            self::addPHPError($Error["type"], $Error["message"], $Error["file"], $Error["line"]);
        }
    }

    /**
     * @param $ErrorNo
     * @param $ErrorNoString
     * @param $LogLevel
     * @return string
     */
    private static function getErrorTypeString($ErrorNo, &$ErrorNoString, &$LogLevel)
    {
        if ($ErrorNo === E_ERROR) {
            $ErrorNoString = "Fatal Error";
            $LogLevel      = LogLevel::EMERGENCY;
        } elseif ($ErrorNo === E_WARNING) {
            $ErrorNoString = "Warning";
            $LogLevel      = LogLevel::WARNING;
        } elseif ($ErrorNo === E_PARSE) {
            $ErrorNoString = "Parse Error";
            $LogLevel      = LogLevel::ERROR;
        } elseif ($ErrorNo === E_NOTICE) {
            $ErrorNoString = "Notice";
            $LogLevel      = LogLevel::NOTICE;
        } elseif ($ErrorNo === E_CORE_ERROR) {
            $ErrorNoString = "Core Error";
            $LogLevel      = LogLevel::EMERGENCY;
        } elseif ($ErrorNo === E_CORE_WARNING) {
            $ErrorNoString = "Core Warning";
            $LogLevel      = LogLevel::WARNING;
        } elseif ($ErrorNo === E_COMPILE_ERROR) {
            $ErrorNoString = "Compile Error";
            $LogLevel      = LogLevel::EMERGENCY;
        } elseif ($ErrorNo === E_COMPILE_WARNING) {
            $ErrorNoString = "Compile Warning";
            $LogLevel      = LogLevel::WARNING;
        } elseif ($ErrorNo === E_USER_ERROR) {
            $ErrorNoString = "User Error";
            $LogLevel      = LogLevel::EMERGENCY;
        } elseif ($ErrorNo === E_USER_WARNING) {
            $ErrorNoString = "User Warning";
            $LogLevel      = LogLevel::WARNING;
        } elseif ($ErrorNo === E_USER_NOTICE) {
            $ErrorNoString = "User Notice";
            $LogLevel      = LogLevel::NOTICE;
        } elseif ($ErrorNo === E_STRICT) {
            $ErrorNoString = "Strict Notice";
            $LogLevel      = LogLevel::NOTICE;
        } elseif ($ErrorNo === E_RECOVERABLE_ERROR) {
            $ErrorNoString = "Recoverable Error";
            $LogLevel      = LogLevel::ALERT;
        } else {
            $ErrorNoString = "Unknown error ($ErrorNo)";
            $LogLevel      = LogLevel::CRITICAL;
        }
    }

    public function __destruct()
    {
        $this->writeLogs();
    }
}
