<?php
/**
 * Project: Epsilon
 * Date: 11/13/15
 * Time: 8:00 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Components\Site;

defined("EPSILON_EXEC") or die();

use Epsilon\Component\Component;

/**
 * Class Site
 *
 * @package Components\Site
 */
class Site extends Component
{

    /**
     * Define the properties for the Component
     *
     * @return array
     */
    protected function defineProperties()
    {
        return [
            'Controllers' => [
                ['Name' => 'Site', 'default' => 1]
            ]
        ];
    }
}