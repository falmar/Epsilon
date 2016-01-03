<?php
/**
 * Project: Epsilon
 * User: dlavieri
 * Date: 7/14/15
 * Time: 11:34 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Module;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\Object\Object;
use PDO;
use PDOException;

defined("EPSILON_EXEC") or die();


/**
 * Class Manager
 *
 * @package Epsilon\Module
 */
class Manager extends Object
{

    protected $ApplicationID;
    protected $Modules;
    protected $MainMenuID;
    protected $AvailableMenuIDs;

    /**
     * @param array $ApplicationID
     */
    public function __construct($ApplicationID)
    {
        parent::__construct(["ApplicationID" => $ApplicationID]);
        $this->Modules;
        $this->AvailableMenuIDs = [];
    }

    /**
     * @return array
     */
    public function getModules()
    {
        if (!isset($this->Modules)) {
            $AccessLevels = implode(",", Factory::getUser()->getAuthorizedLevels());
            $dbh          = Factory::getDBH();

            $CurrentMenuID = Factory::getRouter()->getCurrentMenuID();

            if ($CurrentMenuID) {
                $MenuIDs = implode(",", $this->getAvailableMenuIDs($CurrentMenuID));
            } else {
                $MenuIDs = (int)$this->getMainMenuID();
            }

            $stmt = $dbh->prepare("SELECT mdl.ModuleID,mdl.Module FROM Module mdl
							INNER JOIN ModuleMenu mdlm ON mdlm.ModuleID = mdl.ModuleID
							WHERE mdl.blStatus = 1 AND mdl.ApplicationID = :AppID AND mdl.AccessLevelID IN ($AccessLevels) AND (mdlm.MenuID IN ($MenuIDs) OR mdlm.MenuID = 0) AND mdlm.Visible != 0 GROUP BY mdl.ModuleID ORDER BY Ordering");

            try {
                $stmt->bindValue(":AppID", Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);
                $stmt->execute();
                $arModules = [];

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $Module) {
                    $Class = "\\Modules\\" . $Module["Module"] . "\\" . $Module["Module"];

                    /** @var Module $Object */
                    $Object = new $Class($dbh, $Module);
                    array_push($arModules, $Object);
                    $Object->initialize();
                }

                $this->Modules = $arModules;
            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
            }
        }

        return $this->Modules;
    }

    /**
     * @param $CurrentMenuID
     * @return array AvailableMenuIDs
     */
    protected function getAvailableMenuIDs($CurrentMenuID)
    {
        if (!$this->AvailableMenuIDs) {
            $dbh = Factory::getDBH();
            do {
                $Parent = false;
                $stmt   = $dbh->prepare("SELECT m.MenuID,m.ParentID,m.lft,m.rgt FROM Menu m WHERE m.MenuID = :CurrentMenuID");
                try {
                    $stmt->bindValue(":CurrentMenuID", $CurrentMenuID, PDO::PARAM_INT);
                    $stmt->execute();
                    $Menu = $stmt->fetch(PDO::FETCH_OBJ);

                    $stmt = $dbh->prepare("SELECT m.MenuID,m.ParentID FROM Menu m WHERE lft <= :lft AND rgt >= :rgt");
                    $stmt->bindValue(":lft", $Menu->lft, PDO::PARAM_INT);
                    $stmt->bindValue(":rgt", $Menu->rgt, PDO::PARAM_INT);
                    $stmt->execute();

                    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $Menu) {
                        array_push($this->AvailableMenuIDs, $Menu->MenuID);
                    }

                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                }
            } while ($Parent == true);
        }

        return $this->AvailableMenuIDs;
    }

    /**
     * @return int MainMenuID
     */
    protected function getMainMenuID()
    {
        if (!$this->MainMenuID) {
            $dbh = Factory::getDBH();

            $stmt = $dbh->prepare("SELECT m.MenuID AS MenuID FROM Menu m
					INNER JOIN MenuBundle mb ON mb.MenuBundleID = m.MenuBundleID
					WHERE mb.ApplicationID = :AppID AND m.Root = 1 AND blStatus = 1");
            try {
                $stmt->bindValue(":AppID", Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);
                $stmt->bindColumn("MenuID", $MenuID, PDO::PARAM_INT);
                $stmt->execute();
                $stmt->fetch();
                $this->MainMenuID = $MenuID;
            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
            }
        }

        return $this->MainMenuID;
    }

}
