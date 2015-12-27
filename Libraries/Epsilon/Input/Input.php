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

namespace Epsilon\Input;

defined("EPSILON_EXEC") or die();

/**
 * Class Input
 *
 * @package Epsilon\Input
 */
class Input
{
    const GET             = 1;
    const POST            = 2;
    const REQUEST         = 3;
    const HTML            = 1;
    const HTML_NO_SLASHES = 2;
    const SLASHES         = 3;
    const FLOAT           = 4;
    const INT             = 5;
    const NONE            = 6;
    const DATE            = 7;

    /**
     * Read Variables from $_POST, $_GET or $_REQUEST Global Variables
     * Clear the variables content to avoid injections or any other malicious input
     * If the Variable seems to be an array will clean all the values of the array as well
     *
     * @param string $key
     * @param string $HTTP_Method
     * @param string $ClearingMethod
     * @param        array
     * @return mixed|null
     */
    public static function getVar($key, $HTTP_Method = null, $ClearingMethod = null, $ClearArrayKeys = [])
    {
        switch (strtoupper($HTTP_Method)) {
            case self::GET:
            case "GET":
                $RequestMethod = &$_GET;
                break;
            case self::POST:
            case "POST":
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
     * @param       $Var
     * @param       $ClearingMethod
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
     * @param $Value
     * @param $ClearingMethod
     */
    public static function cleanValueByType(&$Value, $ClearingMethod)
    {
        switch (strtolower($ClearingMethod)) {
            case self::HTML:
            case 'html':
                $Value = htmlentities(addslashes($Value), ENT_QUOTES);
                break;
            case self::HTML_NO_SLASHES:
            case 'html_nosls':
                $Value = htmlentities($Value, ENT_QUOTES);
                break;
            case self::FLOAT:
            case 'float':
                $Value = floatval($Value);
                break;
            case self::INT:
            case 'int':
                $Value = intval($Value);
                break;
            case self::DATE:
            case "date":
                $Value = date("Y-m-d h:i:s", strtotime($Value));
                break;
            case self::NONE:
            case 'none':
                break;
            default:
                $Value = htmlentities(preg_replace('/[^A-Za-z0-9_\@\.\,\/\s\-\ñ\á\ó\é\ú\í]/', '', $Value), ENT_QUOTES);
                break;
        }
    }
}