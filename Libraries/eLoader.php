<?php

/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 4:15 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

defined("EPSILON_EXEC") or die();

/**
 * PSR-4 Auto loader
 */

spl_autoload_register(function ($Class) {
    $slcPath = strtolower($Class);
    $Path    = explode("\\", $Class);
    $Class   = array_pop($Path);

    if (strpos($slcPath, "component") === 0) {
        array_shift($Path);
        $ClassPath = COMPONENT_PATH;
    } elseif (strpos($slcPath, "module") === 0) {
        array_shift($Path);
        $ClassPath = MODULE_PATH;
    } elseif (strpos($slcPath, "template") === 0) {
        array_shift($Path);
        $ClassPath = TEMPLATE_PATH;
    } elseif (strpos($slcPath, "app") === 0) {
        $ClassPath = ROOT_PATH;
    } else {
        $ClassPath = LIBRARY_PATH;
    }

    if ($Path) {
        $Path = $ClassPath . implode(DS, $Path) . DS . $Class . ".php";
    } else {
        $Path = $ClassPath . $Class . ".php";
    }

    if (is_readable($Path)) {
        require_once($Path);
    }
});