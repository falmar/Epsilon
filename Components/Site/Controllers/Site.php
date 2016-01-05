<?php
/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 1:47 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Components\Site\Controllers;

use App\Config;

defined("EPSILON_EXEC") or die();

/**
 * Class Site
 *
 * @package Components\Site\Controllers
 */
class Site extends \Components\Site\Site
{
    public function Index()
    {
        $this->setSubTitle('Home', false);
        $this->getView('Home', [
            'SiteName' => Config::SITE_NAME
        ])->setDocumentPosition();
    }
}
