<?php
/**
 * Project: Epsilon
 * Date: 6/20/15
 * Time: 9:27 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Access;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\Object\Object;
use PDO;
use PDOException;

/**
 * Class Access
 *
 * @package Epsilon\Access
 */
class Access extends Object
{

    protected static $Instance;
    protected        $arAccessLevels;
    protected        $arUserGroups;
    protected        $arUserGroupsMap;
    protected        $arUserGroupsPath;
    protected        $arGroupsByUser;
    protected        $assetRootID;
    protected        $arAssetRules;

    /**
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        parent::__construct($Options);
        $this->clearStatics();
    }

    /**
     * @return Access
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new Access();
        }

        return self::$Instance;
    }

    /**
     * Self explanatory
     */
    public function clearStatics()
    {
        $this->assetRootID     = null;
        $this->arAccessLevels  = [];
        $this->arAssetRules    = [];
        $this->arUserGroups    = [];
        $this->arUserGroupsMap = [];
        $this->arGroupsByUser  = [];
    }

    /**
     * @param string $UserID
     * @param string $Action
     * @param null   $AssetName
     * @return bool
     */
    public function authorized($UserID, $Action, $AssetName = null)
    {
        $UserID = (int)$UserID;

        $Action = strtolower(preg_replace('#[\s\-]+#', '.', trim($Action)));
        if (!is_int($AssetName)) {
            $AssetName = strtolower(preg_replace('#[\s\-]+#', '.', trim($AssetName)));
        }

        if (!$AssetName) {
            $AssetName = $this->getAssetRootID();
        }

        if (!isset($this->arAssetRules[$AssetName])) {
            $this->arAssetRules[$AssetName] = $this->getAssetRules($AssetName, true);
        }

        $Identities = $this->getGroupsByUser($UserID);
        array_unshift($Identities, $UserID * -1);


        /** @var Rules $Asset */
        $Asset = $this->arAssetRules[$AssetName];

        return $Asset->allowed($Action, $Identities);
    }

    /**
     * @return int
     */
    protected function getAssetRootID()
    {
        if (!$this->assetRootID) {
            $dbh  = Factory::getDBH();
            $stmt = $dbh->prepare("SELECT AssetID FROM Asset WHERE ApplicationID = :AppID AND NodeLevel = 0");
            $stmt->bindValue(":AppID", Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);
            $stmt->bindColumn("AssetID", $AssetID, PDO::PARAM_INT);
            try {
                $stmt->execute();
                $stmt->fetch();
                if ($AssetID) {
                    $this->assetRootID = $AssetID;
                }
            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
            }
        }

        return $this->assetRootID;
    }

    /**
     * @param string $Asset
     * @param bool   $Recursive
     * @return Rules
     */
    protected function getAssetRules($Asset, $Recursive = false)
    {
        $select = $Recursive ? "b.Rules" : "a.Rules";

        $group = $Recursive ? "GROUP BY b.AssetID, b.Rules, b.lft" : "a.AssetID, a.Rules, a.lft";

        $where = !is_string($Asset) ? "a.AssetID = :AssetID" : "a.Asset = :AssetID";

        $where .= " AND (a.ApplicationID = :AppID AND b.ApplicationID = :AppID)";

        if ($Recursive) {
            $join  = "LEFT JOIN Asset AS b ON b.lft <= a.lft AND b.rgt >= a.rgt";
            $order = "ORDER BY b.lft";
        } else {
            $join  = null;
            $order = null;
        }

        $dbh  = Factory::getDBH();
        $stmt = $dbh->prepare("SELECT $select FROM Asset as a $join WHERE $where $group $order");

        $stmt->bindValue(":AssetID", $Asset, PDO::PARAM_STR);
        $stmt->bindValue(":AppID", Factory::getApplication()->getApplicationID(), PDO::PARAM_STR);

        $arRules = [];

        try {

            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $Rule) {
                array_push($arRules, $Rule->Rules);
            }

        } catch (PDOException $e) {
            $dbh->catchException($e, $stmt->queryString);
        }

        return new Rules($arRules);
    }


    /**
     * @return array
     */
    public function getUserGroups()
    {
        if (!$this->arUserGroups) {
            $dbh  = Factory::getDBH();
            $stmt = $dbh->prepare("SELECT UserGroupID,ParentID,0 AS lft,0 AS rgt FROM UserGroup ORDER BY lft,ParentID");
            try {
                $stmt->execute();
                $this->arUserGroups = $stmt->fetchAll(PDO::FETCH_OBJ);
            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
                $this->arGroupsByUser = [];
            }
        }

        return $this->arUserGroups;
    }

    /**
     * @param $GroupID
     * @return array
     */
    protected function getUserGroupPath($GroupID)
    {
        if (!$this->arUserGroups) {
            $this->getUserGroups();
        }

        if (!array_key_exists($GroupID, $this->arGroupsByUser)) {
            return [];
        }

        $arUserGroups = [];
        foreach ($this->arUserGroups as $Group) {
            $arUserGroups[$Group->UserGroupID] = $Group;
        }

        if (!isset($this->arUserGroupsPath[$GroupID])) {
            $arPath = [];
            foreach ($this->arUserGroups as $Group) {
                if ($GroupID->lft <= $arUserGroups[$GroupID]->lft && $Group->rgt >= $arUserGroups[$GroupID]->rgt) {
                    array_push($arPath, $Group->UserGroupID);
                }
            }

            $this->arUserGroupsPath[$GroupID] = $arPath;
        }

        return $this->arUserGroupsPath[$GroupID];
    }

    /**
     * @param int  $UserID
     * @param bool $Recursive
     * @return array
     */
    public function getGroupsByUser($UserID, $Recursive = true)
    {
        $storedID = $UserID . ":" . (int)$Recursive;

        $GuestUserGroup = 1;

        if (!isset($this->arGroupsByUser[$storedID])) {
            if (!$UserID && !$Recursive) {
                $Groups = [$GuestUserGroup];
            } else {
                $dbh = Factory::getDBH();

                $select = $Recursive ? "b.UserGroupID" : "a.UserGroupID";

                if ($Recursive) {
                    $join = "LEFT JOIN UserGroup as b ON b.lft <= a.lft AND b.rgt >= a.rgt";
                } else {
                    $join = null;
                }

                if (!$UserID) {
                    $stmt = $dbh->prepare("SELECT $select FROM UserGroup AS a $join WHERE a.UserGroupID = :GroupID");
                    $stmt->bindValue(":GroupID", $GuestUserGroup, PDO::PARAM_INT);
                } else {
                    $stmt = $dbh->prepare("SELECT $select FROM UserGroupMap as map LEFT JOIN UserGroup as a ON a.UserGroupID = map.UserGroupID $join WHERE map.UserID = :UserID");
                    $stmt->bindValue(":UserID", $UserID, PDO::PARAM_INT);
                }

                try {
                    $stmt->execute();
                    $arGroups = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $Group) {
                        array_push($arGroups, $Group->UserGroupID);
                    }
                    $Groups = array_unique($arGroups);
                } catch (PDOException $e) {
                    $dbh->catchException($e, $stmt->queryString);
                    $Groups = [$GuestUserGroup];
                }
            }
            $this->arGroupsByUser[$storedID] = $Groups;
        }

        return $this->arGroupsByUser[$storedID];
    }

    /**
     * @return array
     */
    public function getAccessLevels()
    {
        if (!$this->arAccessLevels) {

            $dbh = Factory::getDBH();

            $stmt = $dbh->prepare("SELECT AccessLevelID, Rules FROM AccessLevel");

            try {
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $accl) {
                    $this->arAccessLevels[$accl->AccessLevelID] = (array)json_decode($accl->Rules);
                }
            } catch (PDOException $e) {
                $dbh->catchException($e, $stmt->queryString);
            }
        }

        return $this->arAccessLevels;
    }

    /**
     * @param int $UserID
     * @return array
     */
    public function getAuthorizedAccessLevel($UserID)
    {
        $UserGroups = $this->getGroupsByUser((int)$UserID);

        $arAuthorized = [1];

        foreach ($this->getAccessLevels() as $Level => $Rules) {
            foreach ($Rules as $GroupID) {
                if (in_array($GroupID, $UserGroups)) {
                    $arAuthorized[] = $Level;
                }
            }
        }

        return array_unique($arAuthorized);
    }
}