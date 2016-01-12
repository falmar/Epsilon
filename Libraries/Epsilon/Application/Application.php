<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 4:35 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Application;

defined('EPSILON_EXEC') or die();

use Epsilon\Factory;
use Epsilon\Object\Object;
use Epsilon\Component\Manager as ComponentManager;
use Epsilon\Module\Manager as ModuleManager;

/**
 * Class Application
 *
 * @package Epsilon\Application
 */
abstract class Application extends Object
{
    protected static $Instance;
    protected        $ApplicationID;
    protected        $ContentType;
    protected        $XHRequest;
    protected        $Component;
    protected        $Modules;
    protected        $ComponentManager;
    protected        $ModuleManager;
    protected        $CLIMode;
    /** @var \Epsilon\Object\Object $CLIOptions */
    protected $CLIOptions;

    /**
     * @return void
     */
    public abstract function redirectLogin();

    /**
     * @return void
     */
    public abstract function redirectHome();

    /**
     * Set the Property $this->Modules to an empty array()
     * Set the Content type to text/html
     * Verify if an XML HTTP Request have been sent (AJAX)
     *
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        parent::__construct($Options);
        $this->Modules     = [];
        $this->ContentType = 'text/html';

        if (php_sapi_name() == 'cli') {
            $this->CLIMode = true;
            $this->setCLIOptions();
        } else {
            $this->CLIMode = false;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && !$this->CLIMode) {
            $this->XHRequest = true;
        } else {
            $this->XHRequest = false;
        }
    }

    /**
     * Return an Application Object According to the ApplicationID located in 'App/' folder
     *
*@param string $ApplicationID
     * @return Application
     */
    public static function getInstance($ApplicationID)
    {
        if (!isset(self::$Instance)) {
            $Class = "\\App\\$ApplicationID";
            if (!class_exists($Class)) {
                Factory::getLogger()->emergency('ApplicationException: Can\'t read Application: {ApplicationID}', [
                    'ApplicationID' => $ApplicationID
                ]);
            }

            self::$Instance = new $Class([
                'ApplicationID' => $ApplicationID
            ]);
        }

        return self::$Instance;
    }

    /**
     * Initialize the Application
     * Initialize the Document
     * Load the Component and execute it
     * Load the Modules if there's not an XML HTTP Request
     */
    public function initialize()
    {
        Factory::getDocument()->initialize();

        if ($this->isCLI()) {
            if ($this->getCLIOption('user') && $this->getCLIOption('password')) {
                if (!Factory::getUser()->authenticate($this->getCLIOption('user'), $this->getCLIOption('password'), true)) {
                    Factory::getLogger()->emergency('Wrong username or password');
                }
            }
        }

        $this->Component = $this->getComponentManager()->getComponent();

        if (!$this->XHRequest) {
            $this->Modules = $this->getModuleManager()->getModules();
        }
    }

    /**
     * Call the method Document->render();
     */
    public function render()
    {
        Factory::getDocument()->render();
    }

    /**
     * Return the string of the Application ID
     *
     * @return string
     */
    public function getApplicationID()
    {
        return $this->ApplicationID;
    }

    /**
     * @return \Epsilon\Component\Manager
     */
    public function getComponentManager()
    {
        if (!isset($this->ComponentManager)) {
            $this->ComponentManager = new ComponentManager($this->getApplicationID());
        }

        return $this->ComponentManager;
    }

    /**
     * @return \Epsilon\Module\Manager
     */
    public function getModuleManager()
    {
        if (!isset($this->ModuleManager)) {
            $this->ModuleManager = new ModuleManager($this->getApplicationID());
        }

        return $this->ModuleManager;
    }

    /**
     * Redirect the Application
     * Calls the Router::getURL() method
     *
     * @param string $Route
     * @param array  $Parameters
     * @param null   $Fragment
     */
    public function redirect($Route, $Parameters = [], $Fragment = null)
    {
        Factory::getEventManager()->dispatch('Application.onRedirect');
        header('Location: ' . Factory::getRouter()->getURL($Route, $Parameters, $Fragment));
        exit();
    }

    /**
     * Returns TRUE|FALSE if is a XML HTTP Request
     *
     * @return bool
     */
    public function isXHRequest()
    {
        return $this->XHRequest;
    }

    /**
     * Returns true or false if script running through cli
     *
     * @return bool
     */
    public function isCLI()
    {
        return $this->CLIMode;
    }

    /**
     * Set all available option passed through cli to CLIOptions variable
     */
    private function setCLIOptions()
    {
        $args = [];
        if (isset($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $arg) {
                if (preg_match('/([^=]+):(.*)/', $arg, $reg)) {
                    $args[$reg[1]] = $reg[2];
                } elseif (preg_match('/-([a-zA-Z0-9])/', $arg, $reg)) {
                    $args[$reg[1]] = 'true';
                }
            }
        }

        $this->CLIOptions = new Object($args);
    }

    /**
     * return option passed through cli
     *
     * @param string $Key
     * @return string
     */
    public function getCLIOption($Key)
    {
        return $this->CLIOptions->get($Key);
    }
}
