<?php

/**
 * Project: Epsilon
 * Date: 12/19/15
 * Time: 12:53 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace App;

defined("EPSILON_EXEC") or die();

use Epsilon\Router\Router;

/**
 * Class SiteRouter
 */
class SiteRouter extends Router
{

    /**
     * @return array
     */
    protected function getDefaultRouteMap()
    {
        return ['<Component>/<Action>' => 'Site/Index'];
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return [
            'Site/<Year:\d{4}>/List'               => 'Site/Index',
            'Site/<Category:\w+>/List'             => 'Site/Index',
            'Site/<Day:\d{0-2}>/<Year:\d{3}>/List' => 'Site/Index',
        ];
    }


}