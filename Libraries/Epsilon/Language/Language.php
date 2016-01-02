<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:41 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Language;

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Database\DatabaseHandler;
use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\IO\Output;
use Epsilon\Object\ActiveRecord;
use PDO;
use PDOException;

/**
 * Class Language
 *
 * @package Epsilon\Language
 */
class Language extends ActiveRecord
{
    const HTML_ENT    = 1;
    const UTF8_DECODE = 2;

    private static $Instance;
    protected      $arImportedFiles;
    protected      $arStrings;
    private        $Path;

    /**
     * Language constructor
     *
     * @param DatabaseHandler $objPDO
     * @param mixed           $ID_Data
     * @param bool|true       $ResultSet
     */
    public function __construct($objPDO, $ID_Data, $ResultSet = true)
    {
        parent::__construct($objPDO, $ID_Data, $ResultSet);
        $this->arImportedFiles = [];
        $this->arStrings       = [];
    }

    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            "Language",
            "lang"
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            "LanguageID"    => "ID",
            "ApplicationID" => "ApplicationID",
            "Title"         => "Title",
            "Code"          => "Code"
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [
            "Root" => "Root"
        ];
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
     * @return Language
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {

            $dbh           = Factory::getDBH();
            $ApplicationID = Factory::getApplication()->getApplicationID();
            $Session       = Factory::getSession();
            $LanguageID    = null;

            if (Input::getVar("LanguageID", "REQUEST")) {
                $LanguageID = Input::getVar("LanguageID", "REQUEST");
            } elseif ($Session->get("LanguageID")) {
                $LanguageID = $Session->get("LanguageID");
            }

            if ($LanguageID) {
                $stmt = $dbh->prepare("SELECT * FROM Language WHERE ApplicationID = :AppID AND LanguageID = :LangID");

                try {
                    $stmt->bindValue(":AppID", $ApplicationID, PDO::PARAM_STR);
                    $stmt->bindValue(":LangID", $LanguageID, PDO::PARAM_INT);
                    $stmt->execute();
                    $rst = $stmt->fetch(PDO::FETCH_OBJ);
                    if (is_object($rst)) {
                        self::$Instance = new Language($dbh, $rst);
                    } else {
                        unset($rst);
                    }
                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                }
            }

            if (!self::$Instance instanceof Language) {
                $stmt = $dbh->prepare("SELECT * FROM Language WHERE ApplicationID = :AppID AND Root = 1");
                try {
                    $stmt->bindValue(":AppID", $ApplicationID, PDO::PARAM_STR);
                    $stmt->execute();
                    $rst = $stmt->fetch(PDO::FETCH_OBJ);
                    if (is_object($rst)) {
                        self::$Instance = new Language($dbh, $rst);
                    } else {
                        Factory::getLogger()->emergency("No Language found in Database exiting...");
                    }
                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                }
            }

            if (self::$Instance instanceof Language && !$Session->get("LanguageID") || self::$Instance->get("ID") != $Session->get("LanguageID")) {
                $Session->set("LanguageID", self::$Instance->get("ID"));
                Factory::getSession()->set("Language", null);
            } else {
                $Language = Factory::getSession()->get("Language");
                if (is_array($Language)) {
                    if (isset($Language["arImportedFiles"])) {
                        self::$Instance->set("arImportedFiles", $Language["arImportedFiles"]);
                    }

                    if (isset($Language["arStrings"])) {
                        self::$Instance->set("arStrings", $Language["arStrings"]);
                    }
                }
            }
        }

        return self::$Instance;
    }

    /**
     * @param      $Key
     * @param int  $ClearMethod
     * @param bool $Output
     * @return bool|string
     */
    public function _($Key, $ClearMethod = Input::HTML, $Output = false)
    {
        if (array_key_exists($Key, $this->arStrings)) {
            if ($Output) {
                $String = Output::_($this->arStrings[$Key], $ClearMethod);
            } else {
                $String = Input::_($this->arStrings[$Key], $ClearMethod);
            }

            return $String;
        } else {
            return null;
        }
    }


    /**
     * @param mixed  $FileName
     * @param string $Path
     * @param string $DefaultCode
     * @return bool
     */
    public function addFile($FileName, $Path = null, $DefaultCode = null)
    {
        if (!array_key_exists($Path . $FileName, $this->arImportedFiles)) {

            if (!$DefaultCode) {
                $DefaultCode = $this->get("Code");
            }

            if (!$Path) {
                $Path = $this->getPath();
            }

            $File = $Path . $DefaultCode . DS . $DefaultCode . "." . $FileName;

            if (is_readable($File)) {
                switch (strtolower(pathinfo($FileName, PATHINFO_EXTENSION))) {
                    case "xml":
                        $XML_Language = simplexml_load_file($File);

                        if (!isset($XML_Language->Strings)) {
                            break;
                        }

                        $this->arImportedFiles[$Path . $FileName] = 1;

                        foreach ($XML_Language->Strings->String as $String) {
                            $this->arStrings[(string)$String["key"]] = (string)$String;
                        }
                        break;
                }

                if (isset($this->arImportedFiles[$Path . $FileName])) {
                    $this->setInSession();
                }

            } else {
                Factory::getLogger()->warning("LanguageException: Can't read Language: {File} ", ['File' => $File]);
            }
        }

        if (isset($this->arImportedFiles[$Path . $FileName])) {
            return true;
        } else {
            return false;
        }
    }

    protected function setInSession()
    {
        if (!eConfig::APP_DEBUG) {
            $Language                    = [];
            $Language["arImportedFiles"] = $this->arImportedFiles;
            $Language["arStrings"]       = $this->arStrings;
            Factory::getSession()->set("Language", $Language);
        }
    }

    /**
     * @return string
     */
    protected function getPath()
    {
        if (!isset($this->Path)) {
            $this->Path = LANGUAGE_PATH;
        }

        return $this->Path;
    }
}
