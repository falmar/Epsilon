<?php
/**
 * Project: Epsilon
 * Date: 11/6/15
 * Time: 3:49 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Logger;

defined('EPSILON_EXEC') or die();

use Epsilon\Object\ActiveRecord;

/**
 * Class Log
 *
 * @package Epsilon\Logger
 */
class Log extends ActiveRecord
{
    protected $UserID;
    protected $SessionID;
    protected $ApplicationID;
    protected $ErrorString;
    protected $Level;
    protected $RegisteredDate;

    /** @return array */
    protected function defineTableName()
    {
        return [
            'Log',
            'l'
        ];
    }

    /** @return array */
    protected function defineTableMap()
    {
        return [
            'LogID'          => 'ID',
            'UserID'         => 'UserID',
            'SessionID'      => 'SessionID',
            'ApplicationID'  => 'ApplicationID',
            'ErrorString'    => 'ErrorString',
            'Level'          => 'Level',
            'RegisteredDate' => 'RegisteredDate',
        ];
    }

    /** @return array */
    protected function defineLazyTableMap()
    {
        return [];
    }

    /** @return array */
    protected function defineRelationMap()
    {
        return [];
    }

    /** @return array */
    protected function defineRules()
    {
        return [];
    }
}
