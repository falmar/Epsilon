<?php
/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 2:02 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Templates\fSite;

defined("EPSILON_EXEC") or die();

use Epsilon\Template\Template;

/**
 * Class fSite
 *
 * @package Templates\fSite
 */
class fSite extends Template
{

    /**
     * @return string
     */
    protected function defineDefaultTemplate()
    {
        return "Site.php";
    }

    /**
     * @return string
     */
    protected function defineDefaultXHRTemplate()
    {
        return "AjaxResponse.php";
    }

    /**
     * @return array
     */
    protected function defineProperties()
    {
        return [
            'Template'    => 'fSite',
            'Date'        => '2015-12-17',
            'Author'      => 'David Lavieri',
            'AuthorEmail' => 'daviddlavier@gmail.com',
            'Positions'   => [
                'XHRequest',
                'MainMenu',
                'Access',
                'Component',
                'Footer',
            ],
            'Languages'   => [
                [
                    'default' => true,
                    'name'    => 'English US',
                    'code'    => 'en-US',
                    'files'   => []
                ]
            ],
            'JS'          => [
                ['src' => 'vendor/jquery/jquery.min.js'],
                ['src' => 'vendor/what-input/what-input.min.js'],
                ['src' => 'vendor/foundation-sites/foundation.min.js'],
                ['src' => 'vendor/foundation-sites/js/foundation.util.mediaQuery.js'],
                ['src' => 'js/app.js'],
            ],
            'CSS'         => [
                ['src' => 'css/app.css']
            ]
        ];
    }
}