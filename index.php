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

function getTime()
{
    list($o_sec, $sec) = explode(" ", microtime());

    return ((float)$o_sec + (float)$sec);
}

$InitTime = getTime();

define("EPSILON_EXEC", 1);

define("DS", DIRECTORY_SEPARATOR);

define("EPSILON_PATH", __DIR__ . DS);

require_once("App" . DS . "DefinePath.php");

require_once("App" . DS . "DefineVariables.php");

use Epsilon\Factory;

$App = Factory::getApplication();

$App->initialize();

$App->render();

if (!$App->isXHRequest()) {
    echo "<p style='color: black;'>";
    echo "&nbsp; Memory Usage: " . sprintf("%0.4f MB", memory_get_peak_usage() / 1048576);
    $EndTime = getTime();
    $Time    = round($EndTime - $InitTime, 6);
    echo "<br>&nbsp; This site has loaded in $Time seconds.";
    echo "</p>";
}