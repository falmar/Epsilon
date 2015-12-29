<?php
/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 12:55 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */


define("EPSILON_EXEC", 1);
define("DS", DIRECTORY_SEPARATOR);
define("EPSILON_PATH", __DIR__ . DS);

require_once("App" . DS . "DefinePath.php");
require_once("App" . DS . "DefineVariables.php");

use Epsilon\Factory;

$App = Factory::getApplication();
$App->initialize();
$App->render();