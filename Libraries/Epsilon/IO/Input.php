<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:40 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\IO;

defined("EPSILON_EXEC") or die();

/**
 * Class Input
 *
 * @package Epsilon\Input
 */
class Input
{
    const GET          = 1;
    const POST         = 2;
    const REQUEST      = 3;
    const HTML         = 1;
    const HTML_SLASHES = 2;
    const SLASHES      = 3;
    const FLOAT        = 4;
    const INT          = 5;
    const NONE         = 6;
    const DATE         = 7;
    const JSON         = 8;
    const BOOL         = 9;
    const UTF8         = 10;

    /**
     * Read Variables from $_POST, $_GET or $_REQUEST Global Variables
     * Clear the variables content to avoid injections or any other malicious input
     * If the Variable seems to be an array will clean all the values of the array as well
     *
     * @param string $key
     * @param int    $HTTP_Method
     * @param int    $ClearingMethod
     * @param array  $ClearArrayKeys
     * @return mixed|null
     */
    public static function getVar($key, $HTTP_Method = null, $ClearingMethod = null, $ClearArrayKeys = [])
    {
        switch ($HTTP_Method) {
            case self::GET:
                $RequestMethod = &$_GET;
                break;
            case self::POST:
                $RequestMethod = &$_POST;
                break;
            case self::REQUEST:
            default:
                $RequestMethod = &$_REQUEST;
                break;
        }

        if (isset($RequestMethod[$key])) {
            return self::cleanVar($RequestMethod[$key], $ClearingMethod, $ClearArrayKeys);
        } else {
            return null;
        }
    }

    /**
     * Clean an Variable or Array Values
     *
     * @param mixed $Var
     * @param int   $ClearingMethod
     * @param array $ClearArrayKeys
     * @return mixed
     */
    public static function cleanVar($Var, $ClearingMethod = null, $ClearArrayKeys = [])
    {
        if (is_array($Var) || is_object($Var)) {
            foreach ($Var as $k => $v) {
                if (array_key_exists($k, $ClearArrayKeys)) {
                    self::cleanValueByType($Var[$k], $ClearArrayKeys[$k]);
                } else {
                    self::cleanValueByType($Var[$k], $ClearingMethod);
                }
            }
        } else {
            self::cleanValueByType($Var, $ClearingMethod);
        }

        return $Var;
    }

    /**
     * cleanVar method alias
     *
     * @param mixed $Var
     * @param int   $ClearingMethod
     * @param array $ClearArrayKeys
     * @return mixed
     */
    public static function _($Var, $ClearingMethod = null, $ClearArrayKeys = [])
    {
        return self::cleanVar($Var, $ClearingMethod, $ClearArrayKeys);
    }

    /**
     * @param $Value
     * @param $ClearingMethod
     */
    private static function cleanValueByType(&$Value, $ClearingMethod)
    {
        switch ($ClearingMethod) {
            case self::HTML:
                $Value = htmlentities($Value, ENT_QUOTES);
                break;
            case self::HTML_SLASHES:
                $Value = htmlentities(addslashes($Value), ENT_QUOTES);
                break;
            case self::JSON:
                $Value = json_decode($Value);
                break;
            case self::UTF8:
                $Value = utf8_encode($Value);
                break;
            case self::DATE:
                $Value = date("Y-m-d h:i:s", strtotime($Value));
                break;
            case self::FLOAT:
                $Value = floatval($Value);
                break;
            case self::INT:
                $Value = intval($Value);
                break;
            case self::BOOL:
                $Value = boolval($Value);
                break;
            case self::NONE:
                break;
            default:
                $Value = preg_replace('/[^A-Za-z0-9_\!\?\=\* \&\@\:\(\)\\+\.\,\/\s\-]/', '', $Value);
                break;
        }
    }
}
