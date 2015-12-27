<?php
/**
 * Project: Epsilon
 * Date: 10/5/15
 * Time: 6:05 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\MVC;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\Object\ActiveRecord;
use Epsilon\Utility\Utility;
use Exception;

/**
 * Class Controller
 *
 * @package Epsilon\MVC
 */
abstract class Controller extends ActiveRecord
{

    const    COMPONENT_TYPE = "Component";
    const    MODULE_TYPE    = "Module";

    private   $ControllerType;
    private   $Models;
    private   $View;
    protected $Path;
    protected $RelativePath;
    protected $ViewPath;
    protected $LanguagePath;
    protected $ControllersPath;
    protected $ModelsPath;
    protected $blLanguageLoaded;
    protected $blCSSLoaded;
    protected $blJSLoaded;

    /**
     * Need to define if it is Component or Module
     *
     * @return string
     */
    abstract protected function defineControllerType();

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
        $this->ControllerType = $this->defineControllerType();
    }

    /**
     * Depend on the type of Controller get corresponding path
     * This is the path in disk /home/user/public_html/etc/etc
     *
     * @return string
     */
    protected function getPath()
    {
        if (!isset($this->Path)) {

            if ($this->ControllerType == self::COMPONENT_TYPE) {
                $Path = COMPONENT_PATH;
            } elseif ($this->ControllerType == self::MODULE_TYPE) {
                $Path = MODULE_PATH;
            } else {
                $Path = null;
            }

            $this->Path = $Path . $this->get($this->ControllerType) . DS;
        }

        return $this->Path;
    }

    /**
     * Depend on the path in disk get the relative path on server
     * e.g http://localhost.com/etc/etc
     *
     * @return string
     */
    protected function getRelativePath()
    {
        if (!isset($this->RelativePath)) {
            $this->RelativePath = Utility::getRelativePath($this->getPath());
        }

        return $this->RelativePath;
    }

    /**
     * get where the Views are located
     *
     * @return string
     */
    protected function getViewPath()
    {
        if (!isset($this->ViewPath)) {
            $this->ViewPath = $this->getPath() . "Views" . DS;
        }

        return $this->ViewPath;
    }

    /**
     * get where the language is located
     *
     * @return string
     */
    protected function getLanguagePath()
    {
        if (!$this->LanguagePath) {
            $this->LanguagePath = $this->getPath() . "Language" . DS;
        }

        return $this->LanguagePath;
    }

    /**
     * get where the other controllers are located
     *
     * @return string
     */
    protected function getControllerPath()
    {
        if (!isset($this->ControllersPath)) {
            $this->ControllersPath = $this->getPath() . "Controllers" . DS;
        }

        return $this->ControllersPath;
    }

    /**
     * get where the Model are located
     *
     * @return string
     */
    protected function getModelPath()
    {
        if (!isset($this->ModelsPath)) {
            $this->ModelsPath = $this->getPath() . "Models" . DS;
        }

        return $this->ModelsPath;
    }

    /**
     * set Params to the controller
     * need to execute ActiveRecord::Save() after if want to save them in the database as well
     *
     * @param array $Params
     */
    protected function setParams($Params)
    {
        if (is_object($Params)) {
            $Params = (array)$Params;
        }

        if (is_array($Params)) {
            $this->set("Params", json_encode($Params));
        }
    }

    /**
     * get the Controller params if there's no params set in the database it get the default params from the XML
     *
     * @return array|null
     */
    protected function getParams()
    {
        $Params = $this->get("Params");

        if (is_string($Params)) {
            $Params = (array)json_decode($Params);
        } elseif (!$Params) {
            $Params = $this->getDefaultParams();
        }

        return $Params;
    }

    /**
     * @param string $Key
     * @return mixed
     */
    public function getParam($Key)
    {
        $Params = $this->getParams();
        if (is_array($Params) && array_key_exists($Key, $Params)) {
            return $Params[$Key];
        } else {
            return null;
        }
    }

    /**
     * @param string $Key
     * @param mixed  $Value
     */
    public function setParam($Key, $Value)
    {
        $Params = $this->getParams();

        if (is_array($Params)) {
            $Params[$Key] = $Value;
        }

        $this->setParams($Params);
    }

    /**
     * Load the XML and return the default params of the controller
     *
     * @return array
     */
    protected function getDefaultParams()
    {
        $Params     = [];
        $Properties = $this->defineProperties();

        if (isset($Properties['Params']) && is_array($Properties['Params'])) {
            $Params = $Properties['Params'];
        }

        return $Params;
    }

    /**
     * get \Epsilon\MVC\View object corresponding to a template
     *
     * @param string $Template set the template name or location its CONTROLLER_NAME/Views/
     * @param string $Position set the position of Document
     * @param array  $Variables
     * @return View
     */
    protected function getView($Template = null, $Variables = [], $Position = null)
    {
        if (!$this->View) {
            if (is_null($Position)) {
                if (Factory::getApplication()->get("XHRequest")) {
                    $Position = "XHRequest";
                } else {
                    $Position = $this->get("Position");
                }
            }

            if (strpos($Template, ".php") === false) {
                $Template .= ".php";
            }

            $this->View = new View($this->getViewPath(), $Template, $Position, $Variables);
        }

        return $this->View;
    }


    /**
     * Set/Load the default variables for the View to be rendered
     * Also read the XML to set the Language files
     */
    protected function setDocumentVariables()
    {
        $this->setLanguageFile();
    }

    /**
     * @param null $File
     */
    protected function setLanguageFile($File = null)
    {

        $Files      = null;
        $eLang      = Factory::getLanguage();
        $Code       = $eLang->get("Code");
        $Language   = null;
        $Properties = $this->defineProperties();

        if (is_string($File) && !empty($File)) {
            $File = [$File];
        }

        if (isset($Properties['Languages']) && is_array($Properties['Languages'])) {

            foreach ($Properties['Languages'] as $lg) {

                if (isset($lg['default']) && (int)$lg['default'] === 1) {
                    $Default = $lg;
                } elseif (isset($lg['code']) && $lg['code'] == $Code) {
                    $Language = $lg;
                    break;
                }
            }

            if (!$Language && isset($Default)) {
                $Language = $Default;
            }

            if (isset($Language['files']) && is_array($Language['files'])) {
                $Files = $Language['files'];
            }
        }

        if ($Files) {
            foreach ($Files as $lang) {
                if (is_array($File)) {
                    foreach ($File as $f) {
                        if ($f == (string)$lang) {
                            $eLang->addFile((string)$lang, $this->getPath() . "Language" . DS);
                            break;
                        }
                    }
                } else {
                    $eLang->addFile((string)$lang, $this->getPath() . "Language" . DS);
                }
            }
        }

    }

    /**
     * @param array $FileNames
     */
    protected function setCSS($FileNames = [])
    {
        if (!is_array($FileNames)) {
            $FileNames = [$FileNames];
        }

        $Properties = $this->defineProperties();

        if ((isset($Properties['CSS']) && is_array($Properties['CSS']))) {

            foreach ($Properties['CSS'] as $CSS) {

                foreach ($FileNames as $File) {
                    if (!isset($CSS['src']) || !isset($CSS['name']) || (isset($CSS['name']) && $CSS['name'] != $File)) {
                        continue;
                    }

                    if (isset($CSS['external']) && (int)$CSS['external'] === 1) {
                        $Path = null;
                    } else {
                        $Path = $this->getRelativePath();
                    }

                    Factory::getDocument()->setStyleSheet($Path . (string)$CSS['src']);
                }
            }
        }
    }

    /**
     * @param array $FileNames
     */
    protected function setJS($FileNames = [])
    {

        if (!is_array($FileNames)) {
            $FileNames = [$FileNames];
        }

        $Properties = $this->defineProperties();

        if ((isset($Properties['JS']) && is_array($Properties['JS']))) {

            foreach ($Properties['JS'] as $JS) {

                foreach ($FileNames as $File) {
                    if (!isset($JS['src']) || !isset($JS['name']) || (isset($JS['name']) && $JS['name'] != $File)) {
                        continue;
                    }

                    if (isset($JS['external']) && (int)$JS['external'] === 1) {
                        $Path = null;
                    } else {
                        $Path = $this->getRelativePath();
                    }

                    Factory::getDocument()->setJavaScript($Path . (string)$JS['src']);
                }
            }
        }
    }

    /**
     * This is a shortcut to Factory::getDocument()->setSubTitle() method
     *
     * @param           $SubTitle
     * @param bool|TRUE $Language
     */
    protected function setSubTitle($SubTitle, $Language = true)
    {
        if ($Language) {
            $SubTitle = Factory::getLanguage()->_($SubTitle);
        }
        Factory::getDocument()->setSubTitle($SubTitle);
    }

    /**
     * @param      $Name
     * @param null $Component
     * @return object
     */
    protected function loadModel($Name, $Component = null)
    {
        if (!isset($this->Models[$Name])) {

            try {
                if ($Component != $this->get($this->ControllerType) && !is_null($Component)) {
                    $ClassName = $Name;
                } else {
                    $Component = $this->get($this->ControllerType);
                    $ClassName = null;

                    $Properties = $this->defineProperties();

                    if (isset($Properties['Models']) && is_array($Properties['Models'])) {

                        foreach ($Properties['Models'] as $Model) {
                            if ($Model == $Name) {
                                $ClassName = $Name;
                                break;
                            }
                        }
                    }

                    if ($ClassName === null) {
                        throw new Exception("Model {" . $Name . "} Doesn't exist in " . $this->ControllerType . " {" . $this->get($this->ControllerType) . "}");
                    }
                }

                $class = "\\" . $this->ControllerType . "s\\" . $Component . "\\Models\\" . $ClassName;

                $this->Models[$Name] = new $class;

            } catch (Exception $e) {
                Factory::getLogger()->alert("ComponentException: {Message}", ['Message' => $e->getMessage()]);
            }
        }

        return $this->Models[$Name];
    }
}