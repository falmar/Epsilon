<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 6:05 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\User;

defined('EPSILON_EXEC') or die();

use Epsilon\Factory;
use Epsilon\Object\ActiveRecord;
use Epsilon\Utility\Utility;
use PDO;
use PDOException;

/**
 * Class SystemMessage
 *
 * @package Epsilon\User
 */
class SystemMessage extends ActiveRecord
{
    const MSG_INFO    = 'primary';
    const MSG_SUCCESS = 'success';
    const MSG_WARNING = 'warning';
    const MSG_ERROR   = 'alert';
    protected static $arSystemMessages        = [];
    protected static $arSystemMessagesElement = [];
    protected static $blAssignedMessages      = false;

    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            'SystemMessage',
            'esm'
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            'SystemMessageID' => 'ID',
            'UserID'          => 'UserID',
            'SessionID'       => 'SessionID',
            'Type'            => 'Type',
            'Message'         => 'Message',
            'Element'         => 'Element',
            'Viewed'          => 'Viewed',
            'RegisteredDate'  => 'RegisteredDate'
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [];
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
     * @param $Bool
     */
    public function setViewed($Bool)
    {
        try {
            $this->set('Viewed', $Bool);
            $this->save();
        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }
    }

    /**
     * @param           $Element
     * @param           $Type
     * @param           $Message
     * @param bool|true $Language
     */
    public static function addMessage($Element, $Type, $Message, $Language = true)
    {

        if ($Language) {
            $Message = Factory::getLanguage()->_($Message);
        }

        try {

            $Message = new SystemMessage(Factory::getDBH(), [
                'UserID'         => Factory::getUser()->get('ID'),
                'SessionID'      => Factory::getSession()->getPHP_SessionID(),
                'Element'        => $Element,
                'Message'        => $Message,
                'Type'           => $Type,
                'Viewed'         => 0,
                'RegisteredDate' => Utility::getDateForDB()
            ], false);

            $Message->save();

        } catch (PDOException $e) {
            Factory::getDBH()->catchException($e);
        }

    }

    /**
     * @param $Element
     * @return bool
     */
    public static function assignMessages($Element)
    {
        if (!isset(self::$arSystemMessagesElement[$Element])) {
            $dbh = Factory::getDBH();

            $stmt = $dbh->prepare("SELECT SystemMessageID,Type,Message FROM SystemMessage WHERE (Element = :Element OR Element = '_system' OR Element = '_DBH') AND (UserID = :UserID OR SessionID = :SessionID) AND Viewed = 0");

            try {
                $stmt->bindValue(':Element', $Element, PDO::PARAM_STR);
                $stmt->bindValue(':UserID', Factory::getUser()->get('ID'), PDO::PARAM_INT);
                $stmt->bindValue(':SessionID', Factory::getSession()->getPHP_SessionID());
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $Message) {
                    array_push(self::$arSystemMessages, new SystemMessage($dbh, $Message));
                }
                self::$arSystemMessagesElement[$Element] = true;

                return true;
            } catch (PDOException $e) {
                Factory::getDBH()->catchException($e, $stmt->queryString);
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getMessages()
    {
        return self::$arSystemMessages;
    }
}
