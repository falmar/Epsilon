<?php
/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 12:58 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Factory;

require_once(LIBRARY_PATH . "eLoader.php");

date_default_timezone_set(eConfig::TIMEZONE);
ini_set("default_charset", eConfig::CHARSET);
ini_set("display_errors", eConfig::APP_DEBUG ? 1 : 0);
error_reporting(E_ALL);
register_shutdown_function("Epsilon\\Logger\\Logger::shutdown");
set_error_handler("Epsilon\\Logger\\Logger::addPHPError");
set_exception_handler("Epsilon\\Logger\\Logger::uncaughtException");

Factory::setApplication(eConfig::APPLICATION_ID);

//Factory::getLanguage()->addFile("Epsilon.xml");