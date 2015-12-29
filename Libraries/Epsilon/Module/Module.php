<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:16 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Module;

defined("EPSILON_EXEC") or die();

use Epsilon\MVC\Controller;

/**
 * Class Module
 *
 * @package Epsilon\Module
 */
abstract class Module extends Controller
{

    /**
     * @return mixed
     */
    abstract public function initialize();

    /**
     * @return array
     */
    protected function defineTableName()
    {
        return [
            "Module",
            "mdl"
        ];
    }

    /**
     * @return array
     */
    protected function defineTableMap()
    {
        return [
            "ModuleID"       => "ID",
            "ApplicationID"  => "ApplicationID",
            "AssetID"        => "AssetID",
            "AccessLevelID"  => "AccessLevelID",
            "Module"         => "Module",
            "Title"          => "Title",
            "Position"       => "Position",
            "Ordering"       => "Ordering",
            "StartDate"      => "StartDate",
            "ExpirationDate" => "ExpirationDate",
            "blStatus"       => "blStatus"
        ];
    }

    /**
     * @return array
     */
    protected function defineLazyTableMap()
    {
        return [
            "Params" => "Params"
        ];
    }

    /**
     * @return array
     */
    protected function defineRelationMap()
    {
        return [];
    }

    /** @return array */
    protected function defineRules()
    {
        return [];
    }

    /** Need to define if it is Component or Module */
    protected function defineControllerType()
    {
        return self::MODULE_TYPE;
    }
}
