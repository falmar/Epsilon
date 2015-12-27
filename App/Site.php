<?php

/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 1:24 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace App;

defined("EPSILON_EXEC") or die();

use Epsilon\Application\Application;

/**
 * Class Site
 */
class Site extends Application
{

    public function redirectLogin()
    {
        $this->redirect('Authentication/Login/');
    }

    public function redirectHome()
    {
        $this->redirect('Home/Index/');
    }
}