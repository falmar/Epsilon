<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 4:38 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon;

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Access\Access;
use Epsilon\Application\Application;
use Epsilon\Database\DatabaseHandler;
use Epsilon\Environment\Cookie;
use Epsilon\Environment\Session;
use Epsilon\Environment\URI;
use Epsilon\Language\Language;
use Epsilon\Logger\Logger;
use Epsilon\Router\Router;
use Epsilon\Template\Template;
use Epsilon\Document\Document;
use Epsilon\User\User;
use PDO;

/**
 * Class Factory
 *
 * @package Epsilon
 */
class Factory
{
    private static $Application;
    private static $Router;
    private static $Access;
    private static $Cookie;
    private static $Session;
    private static $DatabaseHandler;
    private static $EventManager;

    /**
     * @param $ApplicationID
     */
    public static function setApplication($ApplicationID)
    {
        if (!isset(self::$Application)) {
            self::$Application = Application::getInstance($ApplicationID);
        }
    }

    /**
     * @return Application
     */
    public static function getApplication()
    {
        if (!isset(self::$Application)) {
            Factory::getLogger()->emergency("Application haven't been set");
        }

        return self::$Application;
    }

    /**
     * @return Access
     */
    public static function getAccess()
    {
        if (!isset(self::$Access)) {
            self::$Access = new Access();
        }

        return self::$Access;
    }

    /**
     * @return User
     */
    public static function getUser()
    {
        return User::getInstance();
    }

    /**
     * @return Router
     */
    public static function getRouter()
    {
        if (!isset(self::$Router)) {
            self::$Router = Router::getInstance(self::getApplication()->getApplicationID());
        }

        return self::$Router;
    }

    /**
     * @return URI
     */
    public static function getURI()
    {
        return URI::getInstance();
    }

    /**
     * @return Cookie
     */
    public static function getCookie()
    {
        if (!self::$Cookie) {
            self::$Cookie = new Cookie([
                "Lifespan" => eConfig::SESSION_LIFESPAN,
                "Path"     => eConfig::COOKIE_PATH,
                "Domain"   => eConfig::COOKIE_DOMAIN
            ]);
        }

        return self::$Cookie;
    }

    /**
     * @return Session
     */
    public static function getSession()
    {
        if (!self::$Session) {
            self::$Session = new Session(self::getDBH(), eConfig::SESSION_TIMEOUT, eConfig::SESSION_LIFESPAN);
        }

        return self::$Session;
    }

    /**
     * @return DatabaseHandler
     */
    public static function getDBH()
    {
        if (!isset(self::$DatabaseHandler)) {
            $strDSN                = eConfig::DB_PROTOCOL . ":dbname=" . eConfig::DB_NAME . ";host=" . eConfig::DB_HOST . ";port=" . eConfig::DB_PORT;
            self::$DatabaseHandler = new DatabaseHandler($strDSN, eConfig::DB_USER, eConfig::DB_PASSWORD, [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
            ]);
            self::$DatabaseHandler->setDebug(eConfig::DB_DEBUG);
        }

        return self::$DatabaseHandler;
    }

    /**
     * @return Document
     */
    public static function getDocument()
    {
        return Document::getInstance();
    }

    /**
     * @return Template
     */
    public static function getTemplate()
    {
        return Template::getInstance();
    }

    /**
     * @return Language
     */
    public static function getLanguage()
    {
        return Language::getInstance();
    }

    /**
     * @return Logger
     */
    public static function getLogger()
    {
        return Logger::getInstance();
    }

    /**
     * @return Event\Manager
     */
    public static function getEventManager()
    {
        if (!isset(self::$EventManager)) {
            self::$EventManager = new Event\Manager();
        }

        return self::$EventManager;
    }
}