<?php
/**
 * Project: Epsilon
 * Date: 7/27/15
 * Time: 2:28 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Components\Authentication\Models;

defined("EPSILON_EXEC") or die();

use Epsilon\MVC\Model;
use Epsilon\Factory;
use PDO;
use PDOException;

/**
 * Class AuthenticationM
 *
 * @package Components\Authentication\Models
 */
class AuthenticationM extends Model
{
    /**
     * @param $Email
     * @param $Password
     * @param $UserID
     * @return bool
     */
    public function Login($Email, $Password, &$UserID)
    {
        $dbh = Factory::getDBH();

        $stmt = $dbh->prepare("SELECT UserID FROM User WHERE (Email = :Email OR Username = :Email) AND Pwd = sha1(concat(PasswordSalt,:Pwd))");

        try {
            $stmt->bindValue(":Email", $Email, PDO::PARAM_STR);
            $stmt->bindValue(":Pwd", $Password, PDO::PARAM_STR);
            $stmt->bindColumn("UserID", $UserID);
            $stmt->execute();
            $stmt->fetch();

            return true;
        } catch (PDOException $e) {
            $dbh->catchException($e, $stmt->queryString);

            return false;
        }
    }

    /**
     * @param $Email
     * @return int
     */
    public function VerifyEmail($Email)
    {
        $dbh = Factory::getDBH();

        $stmt = $dbh->prepare("SELECT UserID FROM User WHERE Email = :Email");

        try {
            $stmt->bindColumn('UserID', $UserID, PDO::PARAM_INT);
            $stmt->bindValue(':Email', $Email, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->fetch();

            return $UserID;
        } catch (\PDOException $e) {
            Factory::getDBH()->catchException($e, $stmt->queryString);

            return 0;
        }
    }
}