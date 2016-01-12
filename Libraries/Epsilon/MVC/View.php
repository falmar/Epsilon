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

defined('EPSILON_EXEC') or die();

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
    protected $blBuffer;

    /**
     * @param string $TemplateDir
     * @param string $Template
     * @param string $Position
     * @param array  $Variables
     * @param bool   $Buffer
     */
    public function __construct($TemplateDir, $Template, $Position, $Variables = [], $Buffer = true)
    {
        parent::__construct([]);
        $this->TemplateDir    = $TemplateDir;
        $this->Template       = $Template;
        $this->Position       = $Position;
        $this->blBuffer       = $Buffer;
        $this->Variables      = [];
        $this->VariablesByRef = [];
        $this->assign($Variables);
    }

    /**
     * @param bool $Buffer
     * @return string
     * @throws \Exception
     */
    public function render($Buffer = false)
    {
        $this->checkExtension($this->Template);
        $File = $this->getPath() . $this->Template;

        if (!is_readable($File)) {
            throw new \Exception('ViewException: cannot read: ' . $File);
        }

        if ($this->blBuffer || $Buffer) {
            ob_start();
            require($File);

            return ob_get_clean();
        } else {
            require($File);

            return '';
        }
    }

    /**
     * @param $Template
     * @return string
     * @throws \Exception
     */
    protected function renderPartial($Template)
    {
        $this->checkExtension($Template);
        $File = $this->getPath() . $Template;
        if (!is_readable($File)) {
            throw new \Exception('ViewException: cannot read: ' . $File);
        }

        require($File);
    }

    /**
     * @param $Template
     */
    private function checkExtension(&$Template)
    {
        if (strpos($Template, '.php') === false) {
            $Template .= '.php';
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
            return $this->render();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
