<?php
/**
 * Project: Epsilon
 * Date: 11/13/15
 * Time: 8:27 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Components\Authentication;

defined('EPSILON_EXEC') or die();

use Epsilon\Component\Component;

/**
 * Class Authentication
 *
 * @package Components\Authentication
 */
class Authentication extends Component
{

    /**
     * Define the properties for the Component
     *
     * @return array
     */
    protected function defineProperties()
    {
        return [
            'Languages'   => [
                [
                    'name'    => 'English US',
                    'default' => 1,
                    'code'    => 'en-US',
                    'files'   => ['com_authentication.xml']
                ]
            ],
            'Controllers' => [
                ['name' => 'Authentication', 'default' => 1],
            ]
        ];
    }
}
