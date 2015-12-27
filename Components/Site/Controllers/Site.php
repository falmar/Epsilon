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

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;

/**
 * Class Site
 *
 * @package Components\Site\Controllers
 */
class Site extends \Components\Site\Site
{
    public function Index($ID)
    {

        $this->setSubTitle('Home', false);

        $View = $this->getView('Home', [
            'Var1'       => 'This is',
            'Var2'       => 'a test.',
            'URL'        => Factory::getRouter()->getURL('Site/Index', ['title' => 'yolo']),
            'Year'       => Factory::getRouter()->getRoute('Year'),
            'CategoryID' => Factory::getRouter()->getRoute('CategoryID'),
            'ID'         => $ID
        ]);

        $View->setDocumentPosition();

    }
}