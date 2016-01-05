<?php
/**
 * Project: Epsilon
 * Date: 1/5/16
 * Time: 5:33 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2016 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;

if (Factory::getDocument()->countByPosition("XHRequest")) {
    foreach (Factory::getDocument()->getByPosition("XHRequest") as $Content) {
        echo $Content;
    }
}
