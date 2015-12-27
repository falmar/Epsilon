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
use Epsilon\Object\Object;

/**
 * Class View
 *
 * @package Epsilon\MVC
 */
class View extends Object
{
    protected $TemplateDir;
    protected $Template;
    protected $Position;
    protected $Variables;
    protected $VariablesByRef;
    protected $blPositionSet;

    /**
     * @param string      $TemplateDir
     * @param string      $Template
     * @param null|string $Position
     * @param null|mixed  $Variables
     */
    public function __construct($TemplateDir, $Template, $Position = null, $Variables = null)
    {
        parent::__construct([]);
        $this->TemplateDir    = $TemplateDir;
        $this->Template       = $Template;
        $this->Position       = $Position;
        $this->Variables      = [];
        $this->VariablesByRef = [];
        $this->assign($Variables);
    }

    public function render()
    {
        $this->checkExtension($this->Template);

        $File = $this->getPath() . $this->Template;

        if (!is_readable($File)) {
            throw new \Exception("ViewException: Can't read: " . $File);
        }

        require($File);
    }

    /**
     * @param $Template
     * @throws \Exception
     */
    public function renderPartial($Template)
    {
        $this->checkExtension($Template);

        $File = $this->getPath() . $Template;

        if (!is_readable($File)) {
            throw new \Exception("ViewException: Can't read: " . $File);
        }

        require($File);
    }

    /**
     * @param $Template
     */
    private function checkExtension(&$Template)
    {
        if (strpos($Template, ".php") === false) {
            $Template .= ".php";
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->TemplateDir;
    }

    /**
     * @param string|array $Key
     * @param null|mixed   $Value
     * @return $this
     */
    public function assign($Key, $Value = null)
    {
        if (is_array($Key)) {
            foreach ($Key as $k => $v) {
                $this->Variables[$k] = $v;
            }
        } elseif ($Key) {
            $this->Variables[$Key] = $Value;
        }

        return $this;
    }

    /**
     * Assign a referenced variable to the View
     *
     * @param $Key
     * @param $Value
     * @return $this
     */
    public function assignByRef($Key, &$Value)
    {
        $this->VariablesByRef[$Key] = &$Value;

        return $this;
    }

    /**
     * Set the position in document
     */
    public function setDocumentPosition()
    {
        if (!$this->blPositionSet && $this->Position) {
            Factory::getDocument()->setInPosition($this->Position, $this);
            $this->blPositionSet = true;
        }

        return $this;
    }

    /**
     * @param $Key
     * @return mixed|null
     */
    public function getVar($Key)
    {

        if (isset($this->Variables[$Key])) {
            $Value = $this->Variables[$Key];
        } elseif (isset($this->VariablesByRef[$Key])) {
            $Value = $this->VariablesByRef[$Key];
        } else {
            $Value = null;
        }

        return $Value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $this->render();

            return "";
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
}