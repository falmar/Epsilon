<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 4:38 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Object;

defined('EPSILON_EXEC') or die();

/**
 * Class Object
 *
 * @package Epsilon\Object
 */
class Object
{
    /**
     * @param array $Properties
     */
    public function __construct($Properties = [])
    {
        $this->setProperties($Properties);
    }

    /**
     * @param array $Properties
     */
    public function setProperties($Properties)
    {
        if (is_array($Properties) || is_object($Properties)) {
            foreach ($Properties as $Key => $Value) {
                $this->$Key = $Value;
            }
        }
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return get_class_vars($this);
    }

    /**
     * Use this method to avoid problems with setting properties with magic method $object->property = value;
     *
     * @param string $Key
     * @param mixed  $Value
     */
    public function set($Key, $Value)
    {
        $this->$Key = $Value;
    }

    /**
     * Use this method to avoid problems with accessing properties with magic method $object->property;
     *
     * @param $Key
     * @return mixed
     */
    public function get($Key)
    {
        return isset($this->$Key) ? $this->$Key : null;
    }

    /**
     * If for whatever reason this object is converted to string will print the Class Name instead of giving an error
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }
}
