<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:46 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Router;

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\Object\Object;
use PDO;
use PDOException;

/**
 * Class Router
 *
 * @package Epsilon\Router
 */
abstract class Router extends Object
{
    protected static $Instance;
    protected        $ApplicationID;
    protected        $defaultRouteMap;
    protected        $arRouteMaps;
    protected        $Route;
    protected        $CurrentMenuID;
    protected        $strRoute;

    /**
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        parent::__construct($Options);
        $this->arRouteMaps     = $this->getRouteMaps();
        $this->defaultRouteMap = $this->getDefaultRouteMap();
    }

    /**
     * @param string $ApplicationID
     */
    public static function getInstance($ApplicationID)
    {
        if (!isset(self::$Instance)) {
            $Class = "\\App\\{$ApplicationID}Router";
            if (!class_exists($Class)) {
                Factory::getLogger()->emergency("RouterException: Can't read Router: {Router}", [
                    'Router' => $ApplicationID . 'Router'
                ]);
            }

            self::$Instance = new $Class([
                'ApplicationID' => $ApplicationID
            ]);
        }

        return self::$Instance;
    }

    /**
     * Define how the structure of the route is
     * e.g: URI: schema://domain/index.php/Component/Action
     * e.g: URI: schema://domain/index.php/Component/Action/ID
     * e.g: URI: schema://domain/index.php/Component/Controller/Action/ID
     * Component, Action and ID are mandatory
     *
     * @return array
     */
    public function getRouteMaps()
    {
        return [
            '<Component>/<Action>',
            '<Component>/<Action>/<ID>',
            '<Component>/<Controller>/<Action>/<ID>'
        ];
    }

    /**
     * @return array
     */
    abstract protected function getDefaultRouteMap();

    /**
     * @return array
     */
    abstract protected function getRules();

    /**
     * @param $Key
     * @return mixed
     */
    public function getRoute($Key = null)
    {
        if (!isset($this->Route)) {
            $this->route();
        }

        if (!is_null($Key)) {
            $Route = isset($this->Route[$Key]) ? $this->Route[$Key] : null;
        } else {
            $Route = $this->Route;
        }

        return $Route;
    }

    /**
     * @return string
     */
    public function getRouteString()
    {
        if (!isset($this->Route)) {
            if (Factory::getApplication()->isCLI()) {
                $strRoute = Factory::getApplication()->getCLIOption('route');
            } else {
                $strRoute = Factory::getURI()->getInversePath();
            }

            if (strpos($strRoute, '/') === 0) {
                $strRoute = substr($strRoute, 1);
            }
            $this->strRoute = $strRoute;
        }

        return $this->strRoute;
    }

    /**
     * Creates the route to according to the URI
     */
    public function route()
    {
        if (!isset($this->Route)) {

            $strRoute = $this->getRouteString();

            $Route  = $this->getDefaultRouteMap();
            $Params = [];

            if (strlen($strRoute) >= 3) {
                if ($Rules = $this->getRules()) {
                    foreach ($Rules as $rKey => $rV) {
                        if (preg_match($this->getRuleRegex($rKey), $strRoute)) {
                            $Params   = $this->getMapFromRule($rKey, $strRoute);
                            $strRoute = $rV;
                            break;
                        }
                    }
                }

                foreach ($this->getRouteMaps() as $Map) {
                    if (substr_count($Map, '/') === substr_count($strRoute, '/')) {
                        $Route = [$Map => $strRoute];
                    }
                }
            }

            $arMap  = explode('/', Input::cleanVar(array_keys($Route)[0]));
            $arPath = explode('/', Input::cleanVar(array_values($Route)[0]));
            $cMap   = count($arMap);

            for ($f = 0; $f < $cMap; $f++) {
                $this->Route[$arMap[$f]] = $arPath[$f];
            }

            foreach ($Params as $pKey => $pValue) {
                $this->Route[$pKey] = $pValue;
            }

            if (!$this->Route) {
                Factory::getLogger()->emergency("Can't Route Application exiting...");
            }
        }
    }

    /**
     * @param string $Rule
     * @return string
     */
    private function getRuleRegex($Rule)
    {
        $Rule = explode('/', $Rule);
        foreach ($Rule as &$item) {
            if (strpos($item, ':')) {
                $item = substr($item, strpos($item, ':') + 1, -1);
            }
        }
        unset($item);

        return '/' . str_replace('/', '\/', implode('/', $Rule)) . '/';
    }

    /**
     * @param $Rule
     * @param $Path
     * @return array
     */
    private function getMapFromRule($Rule, $Path)
    {
        $Params = [];
        $Rule   = explode('/', $Rule);
        $Path   = explode('/', $Path);

        for ($f = 0; $f < count($Rule); $f++) {
            if (strpos($Rule[$f], ':')) {
                $Params[(substr($Rule[$f], 1, strpos($Rule[$f], ':') - 1))] = $Path[$f];
            }
        }

        return $Params;
    }


    /**
     * @param null  $Route
     * @param array $Parameters
     * @param null  $Fragment
     * @return string
     */
    public function getURL($Route = null, $Parameters = [], $Fragment = null)
    {
        $eURI    = Factory::getURI();
        $Query   = null;
        $arQuery = $Parameters;

        if (($Rules = $this->getRules()) && is_array($Parameters)) {
            $rRoute       = $Route;
            $HighestMatch = 0;
            foreach ($Rules as $rKey => $rValue) {
                $Match = 0;
                if ($rValue == $rRoute) {
                    $replace = [];

                    foreach (explode('/', $rKey) as $item) {
                        if (strpos($item, ':')) {
                            $Key = substr($item, 1, strpos($item, ':') - 1);
                            if (array_key_exists($Key, $Parameters)) {
                                $replace[$item] = $Parameters[$Key];
                                unset($arQuery[$Key]);
                                $Match++;
                            }
                        }
                    }

                    $r = strtr($rKey, $replace);

                    if (preg_match($this->getRuleRegex($rKey), $r)) {
                        if ($Match >= $HighestMatch) {
                            $HighestMatch = $Match;
                            $Route        = $r;
                        }
                    }
                }
            }
        }

        if (!eConfig::PRETTY_URL && $Route) {
            $Route = "?r={$Route}";
        } elseif (strpos($Route, '/') !== 0 && $Route) {
            $Route = '/' . $Route;
        }

        foreach ($arQuery as $Key => $Value) {
            if (!$Query && eConfig::PRETTY_URL) {
                $Query = "?" . $Key . "=" . $Value;
            } else {
                $Query = "&" . $Key . "=" . $Value;
            }
        }

        if (is_string($Fragment)) {
            $Fragment = "#{$Fragment}";
        }

        return $eURI->getRelativePath() . ((eConfig::SHOW_SCRIPT) ? 'index.php' : null) . $Route . $Query . $Fragment;
    }

    /**
     * TODO: rewrite method
     *
     * @return mixed
     */
    public function getCurrentMenuID()
    {
        if (!isset($this->CurrentMenuID)) {
            $dbh           = Factory::getDBH();
            $App           = Factory::getApplication();
            $ComponentID   = $App->get("Component")->get("ID");
            $ApplicationID = $App->getApplicationID();
            $URL           = $this->getRouteString();

            $ssql = "SELECT m.MenuID AS MenuID FROM Menu m
					INNER JOIN MenuBundle mb ON mb.MenuBundleID = m.MenuBundleID
					WHERE mb.ApplicationID = :AppID AND m.ComponentID = :ComponentID AND m.URL LIKE :URL";

            $stmt = $dbh->prepare($ssql);

            try {
                $this->bindMenuValues($stmt, $ApplicationID, $ComponentID, $URL, $MenuID);
                $stmt->execute();
                $stmt->fetch();

                if (count(array_filter(explode("/", $URL))) == 5 && $stmt->rowCount() <= 0) {

                    $URL = explode("/", $URL);
                    array_pop($URL);
                    $URL = implode("/", $URL) . "/";

                    $stmt = $dbh->prepare($ssql);
                    $this->bindMenuValues($stmt, $ApplicationID, $ComponentID, $URL, $MenuID);
                    $stmt->execute();

                }

                if (count(array_filter(explode("/", $URL))) == 4 && $stmt->rowCount() <= 0) {

                    $URL = explode("/", $URL);
                    array_pop($URL);
                    $URL = implode("/", $URL) . "/";

                    $stmt = $dbh->prepare($ssql);
                    $this->bindMenuValues($stmt, $ApplicationID, $ComponentID, $URL, $MenuID);
                    $stmt->execute();
                }

                if ($stmt->rowCount() == 1) {
                    $stmt->fetch();
                    $this->CurrentMenuID = $MenuID;
                }

            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
            }
        }

        return $this->CurrentMenuID;
    }

    /**
     * @param \PDOStatement $stmt
     * @param               $AppID
     * @param               $ComponentID
     * @param               $URL
     * @param               $MenuID
     */
    public function bindMenuValues($stmt, $AppID, $ComponentID, $URL, & $MenuID)
    {
        $stmt->bindValue(":AppID", $AppID, PDO::PARAM_STR);
        $stmt->bindValue(":ComponentID", $ComponentID, PDO::PARAM_STR);
        $stmt->bindValue(":URL", "%$URL", PDO::PARAM_STR);
        $stmt->bindColumn("MenuID", $MenuID, PDO::PARAM_INT);
    }
}
