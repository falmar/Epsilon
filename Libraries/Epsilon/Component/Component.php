<?php
/**
 * Project: Epsilon
 * Date: 10/8/15
 * Time: 9:24 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Component;

defined('EPSILON_EXEC') or die();

use Epsilon\MVC\Controller;

/**
 * Class Component
 *
 * @package Epsilon\Component
 */
abstract class Component extends Controller
{
    /**
     * Look at ActiveRecord Object for an explanation of this method
     *
     * @return string
     */
    protected function defineTableName()
    {
        return [
            'Component',
            'com'
        ];
    }

    /**
     * Look at ActiveRecord Object for an explanation of this method
     *
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            'ComponentID'   => 'ID',
            'ApplicationID' => 'ApplicationID',
            'AccessLevelID' => 'AccessLevelID',
            'AssetID'       => 'AssetID',
            'Component'     => 'Component',
            'Position'      => 'Position',
            'blStatus'      => 'blStatus',
        ];
    }

    /**
     * Look at ActiveRecord Object for an explanation of this method
     *
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [
            'Title'     => 'Title',
            'Protected' => 'Protected',
            'Params'    => 'Params'
        ];
    }

    /**
     * Look at ActiveRecord Object for an explanation of this method
     *
     * @return array
     */
    protected function defineRelationMap()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function defineRules()
    {
        return [];
    }

    /**
     * Define the type of Controller in this case is 'Component'
     *
*@return string
     */
    protected function defineControllerType()
    {
        return self::COMPONENT_TYPE;
    }


}
