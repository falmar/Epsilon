<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:56 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\User;

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Factory;
use Epsilon\Object\ActiveRecord;
use Epsilon\Object\Object;
use Epsilon\Utility\Utility;
use PDO;
use PDOException;

/**
 * Class User
 *
 * @package Epsilon\User
 */
class User extends ActiveRecord
{

    private static $Instance;
    private        $blGuest;
    private        $authAccessLevels;
    private        $authUserGroups;
    protected      $arSystemMessages;
    protected      $arSystemMessagesElement;
    protected      $blAssignedMessages;

    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            "User",
            "u"
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            "UserID"   => "ID",
            "Name"     => "Name",
            "Email"    => "Email",
            "Username" => "Username",
            "blStatus" => "blStatus"
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [
            "PasswordSalt"     => "PasswordSalt",
            "Pwd"              => "Password",
            "RegisteredDate"   => "RegisteredDate",
            "LastLogin"        => "LastLogin",
            "ConfirmationCode" => "ConfirmationCode"
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
     * @param           $objPDO
     * @param null      $ID_Data
     * @param bool|true $ResultSet
     */
    public function __construct($objPDO, $ID_Data = null, $ResultSet = true)
    {
        $this->authAccessLevels        = [];
        $this->authUserGroups          = [];
        $this->arSystemMessages        = [];
        $this->arSystemMessagesElement = [];
        $this->blAssignedMessages      = false;
        parent::__construct($objPDO, $ID_Data, $ResultSet);
    }

    /**
     * @return User
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            if (Factory::getSession()->get("User")) {
                self::$Instance = new User(Factory::getDBH(), Factory::getSession()->get("User"));
            } else {
                self::$Instance = new User(Factory::getDBH());
                self::$Instance->setGuest(true);
            }
        }

        return self::$Instance;
    }

    /**
     * @param $Option
     */
    public function setGuest($Option)
    {
        $this->blGuest = (bool)$Option;
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return $this->blGuest;
    }

    /**
     * @param            $Username
     * @param            $Password
     * @param bool|false $SaveSession
     * @return bool
     */
    public function authenticate($Username, $Password, $SaveSession = false)
    {
        $dbh = $this->objPDO;

        $stmt = $dbh->prepare("SELECT UserID,Pwd FROM User WHERE (Email = :Username OR Username = :Username)");

        try {
            $stmt->bindValue(":Username", $Username, PDO::PARAM_STR);
            $stmt->bindColumn("Pwd", $Hash, PDO::PARAM_STR);
            $stmt->bindColumn("UserID", $UserID);
            $stmt->execute();
            $stmt->fetch();

            if (password_verify($Password, $Hash)) {
                if ($SaveSession) {
                    $this->logIn($UserID);
                }

                return true;
            }
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

        return false;
    }

    /**
     * @param $UserID
     * @return bool
     */
    private function logIn($UserID)
    {
        $this->ID = $UserID;
        if ($this->get("blStatus") == 1) {
            try {
                $this->set("LastLogin", Utility::getDateForDB());
                $this->save();
                Factory::getSession()->set("User", $UserID);

                return true;
            } catch (PDOException $e) {
                Factory::getDBH()->catchException($e);
            }
        }

        return false;
    }

    /**
     * destroy the current session
     */
    public function logOut()
    {
        Factory::getSession()->_session_destroy_method();
    }

    public function impress()
    {
        if (!$this->isGuest()) {
            $this->set("LastLogin", Utility::getDateForDB());
            $this->save();
        }
    }

    /**
     * Check if the user is authorized to perform an action
     *
     * @param      $Action
     * @param null $Asset
     * @return bool
     */
    public function authorized($Action, $Asset = null)
    {
        return Factory::getAccess()->authorized($this->get("ID"), $Action, $Asset);
    }

    /**
     * @return array
     */
    public function getAuthorizedLevels()
    {
        if (!$this->authAccessLevels) {
            $this->authAccessLevels = Factory::getAccess()->getAuthorizedAccessLevel($this->get("ID"));
        }

        return $this->authAccessLevels;
    }

    /**
     * @return array
     */
    public function getAuthorizedGroups()
    {
        if (!$this->authUserGroups) {
            $this->authUserGroups = Factory::getAccess()->getGroupsByUser($this->get("ID"));
        }

        return $this->authUserGroups;
    }

    /**
     * @param $actualPassword
     * @param $newPassword
     * @return bool
     */
    public function changePassword($actualPassword, $newPassword)
    {
        $dbh = Factory::getDBH();
        try {
            if ($this->authenticate($this->get('Email'), $actualPassword, false)) {
                $this->set('Password', password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => eConfig::PASSWORD_HAST_COST]));

                return $this->save();
            }
        } catch (PDOException $e) {
            $dbh->catchException($e);
        }

        return false;
    }

    /**
     * @param            $UserGroupID
     * @param bool|false $Main
     */
    public function addGroup($UserGroupID, $Main = false)
    {
        $dbh = Factory::getDBH();

        $stmt = $dbh->prepare("INSERT INTO UserGroupMap (UserID, UserGroupID, Main) VALUES (:UserID,:UserGroupID,:Main)");

        try {
            $stmt->bindValue(":UserID", $this->ID, PDO::PARAM_INT);
            $stmt->bindValue(":UserGroupID", $UserGroupID, PDO::PARAM_INT);
            $stmt->bindValue(":Main", $Main, PDO::PARAM_BOOL);
            $stmt->execute();
        } catch (PDOException $e) {
            $dbh->catchException($e, $stmt->queryString);
        }
    }

    public function deleteUserGroupMap()
    {
        $dbh = Factory::getDBH();

        $stmt = $dbh->prepare("DELETE FROM UserGroupMap WHERE UserID = :UserID");

        try {
            $stmt->bindValue(":UserID", $this->ID, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            $dbh->catchException($e, $stmt->queryString);
        }
    }

    /**
     * @return Object
     */
    public function getMainGroup()
    {

        $dbh = $this->objPDO;

        $stmt = $dbh->prepare("SELECT ug.UserGroupID,ug.Title FROM UserGroup ug INNER JOIN UserGroupMap ugm ON ugm.UserGroupID = ug.UserGroupID WHERE ugm.UserID = :UserID AND ugm.Main = 1");

        try {
            $stmt->bindValue("UserID", $this->ID, PDO::PARAM_INT);
            $stmt->execute();

            return new Object($stmt->fetch());
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e, $stmt->queryString);

            return new Object();
        }
    }

    public function __destruct()
    {
        if ($this->blForDeletion && $this->get("ID") == Factory::getUser()->get("ID")) {
            throw new PDOException("Can't Delete User if current session active");
        } elseif ($this->blForDeletion) {
            $this->deleteUserGroupMap();
        }

        parent::__destruct();
    }
}