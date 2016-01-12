<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:47 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Template;

defined('EPSILON_EXEC') or die();

use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\MVC\View;
use Epsilon\Object\ActiveRecord;
use Epsilon\User\SystemMessage;
use Epsilon\Utility\Utility;
use PDO;
use PDOException;

/**
 * Class Template
 *
 * @package Epsilon\Template
 */
abstract class Template extends ActiveRecord
{

    protected static $Instance;

    protected $Path;
    protected $RelativePath;
    protected $Positions;
    protected $StyleSheets;
    protected $JavaScripts;
    protected $XML;
    protected $Rendered;
    protected $DefaultTemplate;
    protected $DefaultXHRTemplate;

    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            'Template',
            'tpl'
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            'TemplateID'    => 'ID',
            'ApplicationID' => 'ApplicationID',
            'Title'         => 'Title',
            'Template'      => 'Template',
            'Root'          => 'Root'
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [
            'Params' => 'Params'
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
     * @return string
     */
    abstract protected function defineDefaultTemplate();

    /**
     * @return string
     */
    abstract protected function defineDefaultXHRTemplate();

    /**
     * @return array
     */
    abstract protected function defineProperties();

    /**
     * @param           $objPDO
     * @param null      $ID_Data
     * @param bool|true $ResultSet
     */
    public function __construct($objPDO, $ID_Data, $ResultSet = true)
    {
        parent::__construct($objPDO, $ID_Data, $ResultSet);
        $this->StyleSheets        = [];
        $this->JavaScripts        = [];
        $this->Positions          = [];
        $this->DefaultTemplate    = $this->defineDefaultTemplate();
        $this->DefaultXHRTemplate = $this->defineDefaultXHRTemplate();
        $this->setLanguageFile();

    }

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {

            $dbh = Factory::getDBH();

            if (Input::getVar('TemplateID', 'REQUEST')) {
                $TemplateID = Input::getVar('TemplateID', 'REQUEST');
            } elseif (Factory::getCookie()->get('TemplateID')) {
                $TemplateID = Factory::getCookie()->get('TemplateID');
            } else {
                $TemplateID = null;
            }

            if ($TemplateID) {

                $stmt = $dbh->prepare('SELECT * FROM Template WHERE TemplateID = :TemplateID AND ApplicationID = :AppID');

                try {
                    $stmt->bindValue(':AppID', Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);
                    $stmt->bindValue(':TemplateID', $TemplateID, PDO::PARAM_INT);
                    $stmt->execute();
                    $rst = $stmt->fetch(PDO::FETCH_OBJ);
                    if (is_object($rst)) {
                        $Class          = $rst->Template;
                        self::$Instance = new $Class($dbh, $rst);
                    } else {
                        unset($rst);
                    }
                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                }
            }

            if (!self::$Instance instanceof Template) {
                $stmt = $dbh->prepare('SELECT * FROM Template WHERE ApplicationID = :AppID AND Root = 1');
                $stmt->bindValue(':AppID', Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);
                try {
                    $stmt->execute();
                    $rst = $stmt->fetch(PDO::FETCH_OBJ);
                    if (is_object($rst)) {
                        $Class          = "Templates\\{$rst->Template}\\{$rst->Template}";
                        self::$Instance = new $Class($dbh, $rst);
                    }

                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                }

                if (!self::$Instance) {
                    Factory::getLogger()->emergency('No Template found in Database exiting...');
                }
            }
        }

        return self::$Instance;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if (!isset($this->Path)) {
            $this->Path = TEMPLATE_PATH . $this->get('Template') . DS;
        }

        return $this->Path;
    }

    public function display()
    {
        if (!$this->Rendered) {
            $eApplication = Factory::getApplication();
            $ContentType  = $eApplication->get('ContentType');
            $XHRequest    = $eApplication->get('XHRequest');

            $ContentType = strtolower($ContentType);

            if ($ContentType === 'text/html') {
                header('Content-type: text/html;');
                if ($XHRequest) {
                    $View = $this->get('DefaultXHRTemplate');
                } else {
                    $View = $this->get('DefaultTemplate');
                }

                echo (new View($this->getViewPath(), $View, null, [
                    'SystemMessages' => SystemMessage::getMessages()
                ]));
            }

            $this->Rendered = true;
        }
    }

    /**
     * @return string
     */
    public function getRelativePath()
    {
        if (!isset($this->RelativePath)) {
            $this->RelativePath = Utility::getRelativePath($this->getPath());
        }

        return $this->RelativePath;
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->getPath() . 'Views' . DS;
    }

    /**
     * @return array
     */
    public function getPositions()
    {
        if (!$this->Positions) {
            $Properties = $this->defineProperties();

            if (isset($Properties['Positions']) && is_array($Properties['Positions'])) {
                foreach ($Properties['Positions'] as $Positions) {
                    array_push($this->Positions, (string)$Positions);
                }
            }
        }

        return $this->Positions;
    }

    /**
     * @return array
     */
    public function getStyleSheets()
    {
        if (!$this->StyleSheets) {
            $Properties = $this->defineProperties();

            if (isset($Properties['CSS']) && is_array($Properties['CSS'])) {

                foreach ($Properties['CSS'] as $CSS) {

                    if (!isset($CSS['src'])) {
                        continue;
                    }

                    if (isset($CSS['external']) && (bool)$CSS['external'] === true) {
                        $Path = null;
                    } else {
                        $Path = $this->getRelativePath();
                    }

                    array_push($this->StyleSheets, $Path . (string)$CSS['src']);
                }
            }
        }

        return $this->StyleSheets;
    }

    /**
     * @return array
     */
    public function getJavaScripts()
    {
        if (!$this->JavaScripts) {
            $Properties = $this->defineProperties();

            if (isset($Properties['JS']) && is_array($Properties['JS'])) {

                foreach ($Properties['JS'] as $JS) {

                    if (!isset($JS['src'])) {
                        continue;
                    }

                    if (isset($JS['external']) && (bool)$JS['external'] === true) {
                        $Path = null;
                    } else {
                        $Path = $this->getRelativePath();
                    }

                    array_push($this->JavaScripts, $Path . (string)$JS['src']);
                }
            }
        }

        return $this->JavaScripts;
    }

    /**
     * @param null $File
     */
    public function setLanguageFile($File = null)
    {
        $Files      = null;
        $Language   = null;
        $Properties = $this->defineProperties();
        $eLang      = Factory::getLanguage();
        $Code       = $eLang->get('Code');

        if (is_string($File) && !empty($File)) {
            $File = [$File];
        }

        if (isset($Properties['Languages']) && is_array($Properties['Languages'])) {
            foreach ($Properties['Languages'] as $lg) {

                if (isset($lg['default']) && (bool)$lg['default'] === true) {
                    $Default = $lg;
                } elseif (isset($lg['code']) && $lg['code'] == $Code) {
                    $Language = $lg;
                    break;
                }
            }

            if (!$Language && isset($Default)) {
                $Language = $Default;
                $Code     = (isset($Language['code'])) ? $Language['code'] : $Code;
            }

            if (isset($Language['files']) && is_array($Language['files'])) {
                $Files = $Language['files'];
            }
        }

        $isArray = is_array($File);

        if ($Files) {
            foreach ($Files as $lang) {
                if ($isArray) {
                    foreach ($File as $f) {
                        if ($f == (string)$lang) {
                            $eLang->addFile((string)$lang, $this->getPath() . 'Language' . DS, $Code);
                            break;
                        }
                    }
                } else {
                    $eLang->addFile((string)$lang, $this->getPath() . 'Language' . DS, $Code);
                }
            }
        }
    }
}
