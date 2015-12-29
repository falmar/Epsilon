<?php
/**
 * Project: Epsilon
 * Date: 7/27/15
 * Time: 12:58 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Components\Authentication\Controllers;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\User\SystemMessage;

/**
 * Class Authentication
 *
 * @package Components\Authentication\Controllers
 */
class Authentication extends \Components\Authentication\Authentication
{
    public function Login()
    {
        $Credentials = Input::getVar("Login", "GET");
        if (isset($Credentials["Email"])) {
            $Login = $Credentials["Email"];
        } else {
            $Login = null;
        }

        $this->setDocumentVariables();
        $this->setSubTitle("Authentication", false);
        SystemMessage::assignMessages('COM_AUTHENTICATION');

        $View = $this->getView("login", ['Login' => $Login]);

        $View->setDocumentPosition();
    }

    public function Authenticate()
    {
        $Credentials    = Input::getVar("Login", "POST");
        $Authentication = false;

        $this->setLanguageFile();

        if (count($Credentials) == 2 && isset($Credentials["Email"]) && isset($Credentials["Password"])) {

            if (Factory::getUser()->authenticate($Credentials["Email"], $Credentials["Password"], true)) {
                $Authentication = true;
            }
        }

        if ($Authentication) {
            Factory::getApplication()->redirectHome();
        } else {
            SystemMessage::addMessage("COM_AUTHENTICATION", SystemMessage::MSG_ERROR, "COM_LOGIN-FAILED");
            if (isset($Credentials["Email"])) {
                Factory::getApplication()->redirect("Authentication/Login/", ['Login[Email]' => $Credentials["Email"]]);
            } else {
                Factory::getApplication()->redirectLogin();
            }
        }
    }

    public function Logout()
    {
        Factory::getUser()->logOut();
        Factory::getApplication()->redirectLogin();
    }
}
