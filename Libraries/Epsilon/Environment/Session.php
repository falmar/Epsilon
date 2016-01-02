<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:31 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Environment;


use Epsilon\Factory;
use PDO;
use PDOException;

/**
 * Class Session
 *
 * @package Epsilon\Environment
 */
class Session
{
    /** @var PDO $objPDO */
    private $objPDO;
    private $SessionID;
    private $PHP_SessionID;
    private $SessionTimeout;
    private $SessionLifespan;
    private $CookieToken;
    private $RemoteIPv4;
    private $RemoteIPv6;
    private $UserAgent;
    private $SessionVariables;
    private $newSessionVariables;
    private $globalSessionVariables;
    private $blWritten;

    /**
     * @param PDO $objPDO
     * @param int $Timeout
     * @param int $Lifespan
     */
    public function __construct($objPDO, $Timeout, $Lifespan)
    {
        $this->objPDO          = $objPDO;
        $this->SessionTimeout  = $Timeout;
        $this->SessionLifespan = $Lifespan;
        $this->CookieToken     = null;

        if (isset($_SERVER["REMOTE_ADDR"])) {
            if (strlen($_SERVER["REMOTE_ADDR"]) > 15) {
                $this->RemoteIPv6 = $_SERVER["REMOTE_ADDR"];
            } else {
                $this->RemoteIPv4 = $_SERVER["REMOTE_ADDR"];
            }
        }

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->UserAgent = "PHP_ENGINE";
        } else {
            $this->UserAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        $this->SessionVariables       = [];
        $this->newSessionVariables    = [];
        $this->globalSessionVariables = [];

        session_set_save_handler([
            & $this,
            '_session_open_method'
        ], [
            & $this,
            '_session_close_method'
        ], [
            & $this,
            '_session_read_method'
        ], [
            & $this,
            '_session_write_method'
        ], [
            & $this,
            '_session_destroy_method'
        ], [
            & $this,
            '_session_gc_method'
        ]);

        if (isset($_COOKIE["PHPSESSID"])) {

            $this->PHP_SessionID = $_COOKIE["PHPSESSID"];

            $ssql = "SELECT SessionID FROM Session
                    WHERE AsciiSessionID = :Ascii_ID
                    AND UserAgent = :UserAgent
                    AND (TIMESTAMPDIFF(SECOND,RegisteredDate,:now) < :Lifespan)
                    AND ((TIMESTAMPDIFF(SECOND,LastImpress,:now) < :Timeout) OR LastImpress IS NULL)";
            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":Ascii_ID", $this->PHP_SessionID, PDO::PARAM_STR);
            $stmt->bindValue(":UserAgent", $this->UserAgent, PDO::PARAM_STR);
            $stmt->bindValue(":now", $this->getDateNOW(), PDO::PARAM_STR);
            $stmt->bindValue(":Lifespan", $this->SessionLifespan . " seconds", PDO::PARAM_STR);
            $stmt->bindValue(":Timeout", $this->SessionTimeout . " seconds", PDO::PARAM_STR);
            $stmt->bindColumn("SessionID", $SessionID, PDO::PARAM_INT);

            try {
                $stmt->execute();
                $stmt->fetch(PDO::FETCH_OBJ);
            } catch (PDOException $e) {

            }

            if (!($SessionID > 0)) {
                $this->destroySession();
            }
        }

        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * @return string
     */
    public function getPHP_SessionID()
    {
        return $this->PHP_SessionID;
    }

    private function impress()
    {
        try {
            $stmt = $this->objPDO->prepare("UPDATE Session SET LastImpress = :now WHERE AsciiSessionID = :ascii_id");
            $stmt->bindValue(":now", $this->getDateNOW(), PDO::PARAM_STR);
            $stmt->bindValue(":ascii_id", $this->PHP_SessionID, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }
    }

    /**
     * @param string $PHP_SessionID
     */
    private function readSession($PHP_SessionID)
    {

        try {
            $ssql = "SELECT SessionID
                    FROM Session
                    WHERE AsciiSessionID = :Ascii_ID AND (RemoteIPv4 = :RemoteIPv4 OR RemoteIPv6 = :RemoteIPv6)";

            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":Ascii_ID", $this->PHP_SessionID, PDO::PARAM_STR);
            $stmt->bindValue(":RemoteIPv4", $this->RemoteIPv4, PDO::PARAM_STR);
            $stmt->bindValue(":RemoteIPv6", $this->RemoteIPv6, PDO::PARAM_STR);
            $stmt->bindColumn("SessionID", $this->SessionID, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->fetch();

            if (!$this->SessionID && !Factory::getApplication()->isCLI()) {
                $ssql = "INSERT INTO Session (AsciiSessionID, LastImpress, RegisteredDate, UserAgent, RemoteIPv4,RemoteIPv6, CookieToken)
						VALUES (:Ascii_ID,:now,:now,:UserAgent,:RemoteIPv4,:RemoteIPv6,:CookieToken)";
                $stmt = $this->objPDO->prepare($ssql);
                $stmt->bindValue(":Ascii_ID", $PHP_SessionID, PDO::PARAM_STR);
                $stmt->bindValue(":now", $this->getDateNOW(), PDO::PARAM_STR);
                $stmt->bindValue(":UserAgent", $this->UserAgent, PDO::PARAM_STR);
                $stmt->bindValue(":RemoteIPv4", $this->RemoteIPv4, PDO::PARAM_STR);
                $stmt->bindValue(":RemoteIPv6", $this->RemoteIPv6, PDO::PARAM_STR);
                $stmt->bindValue(":CookieToken", $this->CookieToken, PDO::PARAM_STR);
                $stmt->execute();
                $this->SessionID     = $this->objPDO->lastInsertId();
            }

            $this->PHP_SessionID = $PHP_SessionID;

            $this->timeOutVariables();
            $this->impress();
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

    }

    /**
     * @return bool
     */
    private function destroySession()
    {
        try {
            $ssql = "DELETE FROM SessionVariable WHERE AsciiSessionID = :Ascii_ID;
                     DELETE FROM Session WHERE AsciiSessionID = :Ascii_ID;";
            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":Ascii_ID", $this->PHP_SessionID, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);

            return false;
        }
    }

    /**
     * @return bool
     */
    private function timeOutVariables()
    {
        try {
            $ssql = "DELETE FROM SessionVariable WHERE AsciiSessionID = :Ascii_ID AND TIMESTAMPDIFF(SECOND,Lifespan,:Now)>0";
            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":Ascii_ID", $this->PHP_SessionID, PDO::PARAM_STR);
            $stmt->bindValue(":Now", $this->getDateNOW(), PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

        return false;
    }

    /**
     * @param string $Key
     * @param bool   $Global
     * @return mixed|null
     */
    private function readVariable($Key, $Global = false)
    {
        try {
            if ($Global) {
                $PHP_SessionID   = "_system";
                $SessionVariable = &$this->globalSessionVariables;
                if (array_key_exists($Key, $SessionVariable)) {
                    return $this->getGlobalVariable($Key);
                }
            } else {
                $PHP_SessionID   = $this->PHP_SessionID;
                $SessionVariable = &$this->SessionVariables;
                if (array_key_exists($Key, $SessionVariable)) {
                    return $this->get($Key);
                }
            }

            $ssql = "SELECT VariableValue,Lifespan FROM SessionVariable WHERE AsciiSessionID = :Ascii_ID AND VariableName = :VariableName";
            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":Ascii_ID", $PHP_SessionID, PDO::PARAM_STR);
            $stmt->bindValue(":VariableName", $Key, PDO::PARAM_STR);
            $stmt->bindColumn("VariableValue", $VariableValue, PDO::PARAM_LOB);
            $stmt->bindColumn("Lifespan", $Lifespan, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->fetch();

            if ($VariableValue) {
                $SessionVariable[$Key] = [
                    "Value"         => $VariableValue,
                    "Lifespan"      => $Lifespan,
                    "PHP_SessionID" => $PHP_SessionID,
                    "Written"       => false
                ];

                if ($Global) {
                    return $this->getGlobalVariable($Key);
                } else {
                    return $this->get($Key);
                }
            }

        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function writeVariables()
    {
        if ($this->blWritten || Factory::getApplication()->isCLI()) {
            return false;
        }

        try {

            if (is_array($this->newSessionVariables)) {
                foreach ($this->newSessionVariables as $k => $v) {
                    try {
                        if (is_null(unserialize($v["Value"]))) {
                            $stmt = $this->objPDO->prepare("DELETE FROM SessionVariable WHERE AsciiSessionID = :Ascii_ID AND VariableName = :VariableName");
                        } else {
                            if ($this->checkVar($k, $v["PHP_SessionID"])) {
                                $stmt = $this->objPDO->prepare("INSERT INTO SessionVariable (AsciiSessionID, VariableName, VariableValue, Lifespan) VALUES (:Ascii_ID,:VariableName,:VariableValue,IF(:Lifespan>0,DATE_ADD(:now, INTERVAL :Lifespan SECOND),NULL))");
                            } else {
                                $stmt = $this->objPDO->prepare("UPDATE SessionVariable SET VariableValue = :VariableValue, Lifespan = IF(:Lifespan>0,DATE_ADD(:now,INTERVAL :Lifespan SECOND),NULL) WHERE VariableName = :VariableName AND AsciiSessionID = :Ascii_ID");
                            }
                            $stmt->bindValue(":VariableValue", $v["Value"], PDO::PARAM_LOB);
                            $stmt->bindValue(":now", $this->getDateNOW(), PDO::PARAM_STR);
                            $stmt->bindValue(":Lifespan", $v["Lifespan"], PDO::PARAM_INT);
                        }

                        $stmt->bindValue(":VariableName", $k, PDO::PARAM_STR);
                        $stmt->bindValue(":Ascii_ID", $v["PHP_SessionID"]);
                        $stmt->execute();
                        $v["Written"] = true;
                    } catch (PDOException $e) {
                    }
                }
                $this->blWritten = true;

                return true;
            }

        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

        return false;
    }

    /**
     * @param string   $Key
     * @param mixed    $Value
     * @param int|null $Lifespan
     */
    public function set($Key, $Value, $Lifespan = null)
    {
        if (!is_integer($Lifespan)) {
            $Lifespan = null;
        }

        $this->newSessionVariables[$Key] = [
            "Value"         => serialize($Value),
            "Lifespan"      => $Lifespan,
            "PHP_SessionID" => $this->PHP_SessionID,
            "Written"       => false
        ];
    }

    /**
     * @param $Key
     * @return mixed|null
     */
    public function get($Key)
    {
        if (array_key_exists($Key, $this->newSessionVariables)) {
            $Variable = $this->newSessionVariables[$Key];
        } elseif (array_key_exists($Key, $this->SessionVariables)) {
            $Variable = $this->SessionVariables[$Key];
        } else {
            return $this->readVariable($Key);
        }

        return ($Variable["Value"]) ? unserialize($Variable["Value"]) : null;
    }

    /**
     * @param string   $Key
     * @param string   $Value
     * @param int|null $Lifespan
     */
    public function setGlobalVariable($Key, $Value, $Lifespan = null)
    {
        if (!is_integer($Lifespan)) {
            $Lifespan = null;
        }

        $this->newSessionVariables[$Key] = [
            "Value"         => serialize($Value),
            "Lifespan"      => $Lifespan,
            "PHP_SessionID" => "_system",
            "Written"       => false
        ];
    }

    /**
     * @param $Key
     * @return mixed|null
     */
    public function getGlobalVariable($Key)
    {
        if (array_key_exists($Key, $this->globalSessionVariables)) {
            $Variable = $this->globalSessionVariables[$Key];
        } elseif (array_key_exists($Key, $this->newSessionVariables[$Key])) {
            $Variable = $this->newSessionVariables[$Key];
        } else {
            return $this->readVariable($Key, true);
        }

        return ($Variable["Value"]) ? unserialize($Variable["Value"]) : null;
    }

    /**
     * @param string $key
     * @param string $PHP_SessionID
     * @return bool
     */
    private function checkVar($key, $PHP_SessionID)
    {
        if ($PHP_SessionID == "_system") {
            $Global = true;
        } else {
            $Global = false;
        }

        $this->readVariable($key, $Global);

        if (array_key_exists($key, $this->SessionVariables) || array_key_exists($key, $this->globalSessionVariables)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return bool|string
     */
    public function getDateNOW()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     *
     */
    public function __destruct()
    {
        return true;
    }

    /**
     * Session Handler Functions
     */

    /**
     * @param $save_path
     * @param $session_name
     * @return bool
     */
    public function _session_open_method($save_path, $session_name) { return true; }


    /**
     * @return bool
     */
    public function _session_close_method()
    {
        return $this->writeVariables();
    }

    /**
     * @param string $id
     * @return string
     */
    public function _session_read_method($id)
    {
        $this->readSession($id);

        return "";
    }

    /**
     * @return bool
     */
    public function _session_write_method() { return true; }

    /**
     * @return bool
     */
    public function _session_destroy_method()
    {
        return $this->destroySession();
    }

    /**
     * @param int $maxLifeTime
     * @return bool
     */
    public function _session_gc_method($maxLifeTime) { return true; }
}
