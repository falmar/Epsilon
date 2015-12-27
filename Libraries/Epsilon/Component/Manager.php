<?php
/**
 * Project: Epsilon
 * Date: 6/18/15
 * Time: 10:48 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Component;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\Object\Object;
use Exception;
use PDO;
use PDOException;

/**
 * Class Manager
 *
 * @package Epsilon\Component
 */
class Manager extends Object
{

    protected $ApplicationID;
    protected $Component;

    /**
     * @param array $ApplicationID
     */
    public function __construct($ApplicationID)
    {
        parent::__construct(["ApplicationID" => $ApplicationID]);
    }

    /**
     * @return mixed
     */
    public function getComponent()
    {
        if (!isset($this->Component)) {
            $dbh = Factory::getDBH();

            $_Component  = Factory::getRouter()->getRoute('Component');
            $_Controller = Factory::getRouter()->getRoute('Controller');
            $Action      = Factory::getRouter()->getRoute('Action');
            $ID          = Factory::getRouter()->getRoute('ID');

            try {

                $stmt = $dbh->prepare("SELECT * FROM Component WHERE ApplicationID = :AppID AND blStatus = 1 AND Component = :Component;");

                try {
                    $stmt->bindValue(":AppID", $this->ApplicationID, PDO::PARAM_STR);
                    $stmt->bindValue(":Component", (string)ucfirst($_Component), PDO::PARAM_STR);
                    $stmt->execute();
                    $Component = new Object($stmt->fetch(PDO::FETCH_OBJ));
                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                    throw new Exception("EpsilonCMS Can't Load Component DB");
                }

                if ($Component->get("ComponentID")) {

                    $AccessLevels = Factory::getUser()->getAuthorizedLevels();

                    /** Verify if the current user has access to the component */
                    if (!in_array($Component->get("AccessLevelID"), $AccessLevels)) {
                        if (Factory::getUser()->isGuest()) {
                            Factory::getApplication()->redirectLogin();
                        } else {
                            Factory::getApplication()->redirectHome();
                        }
                    }

                    /** Creates the Class|Controller Namespace */
                    $Namespace = "\\Components\\" . $_Component . "\\Controllers\\";

                    /**
                     * If the route contains a controller use that controller name
                     * else
                     * use the component name as default controller
                     */
                    if ($_Controller) {
                        $Controller = $_Controller;
                    } else {
                        $Controller = $_Component;
                    }

                    $Class = $Namespace . $Controller;

                    $Component = new $Class($dbh, $Component);

                    /** Verify is the Method (Action) exist */
                    if (is_callable([
                        $Component,
                        $Action
                    ])) {
                        $Component->{$Action}($ID);
                    } else {
                        throw new \Exception("Controller method doesn't exist {$Controller}->{$Action}({$ID})");
                    }

                    $this->Component = $Component;

                } else {
                    throw new \Exception("Component {" . $_Component . "} doesn't exist in Database");
                }

            } catch (\Exception $e) {
                Factory::getLogger()->alert("ComponentManagerException: {Message} {File} {Line}", [
                    'Message' => $e->getMessage(),
                    'File'    => $e->getFile(),
                    'Line'    => $e->getLine()
                ]);
            }
        }

        return $this->Component;
    }

    /**
     * @return string
     */
    protected function getPath()
    {
        return COMPONENT_PATH;
    }

}