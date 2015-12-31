<?php
/**
 * Project: Epsilon
 * Date: 12/28/15
 * Time: 9:45 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\IO;


class Output
{
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
     * Clean an Variable or Array Values
     *
     * @param mixed $Var
     * @param int   $ClearingMethod
     * @param array $ClearArrayKeys
     * @return mixed
     */
    public static function cleanVar($Var, $ClearingMethod = self::NONE, $ClearArrayKeys = [])
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
     * @param       $Value
     * @param int   $ClearingMethod
     * @param array $ClearArrayKeys
     * @return mixed
     */
    public static function _($Value, $ClearingMethod = self::NONE, $ClearArrayKeys = [])
    {
        return self::cleanVar($Value, $ClearingMethod, $ClearArrayKeys);
    }

    /**
     * @param mixed $Value
     * @param int   $ClearingMethod
     * @return mixed
     */
    private static function cleanValueByType(&$Value, $ClearingMethod)
    {
        switch ($ClearingMethod) {
            case self::HTML:
                $Value = html_entity_decode($Value, ENT_QUOTES);
                break;
            case self::HTML_SLASHES:
                $Value = html_entity_decode(stripslashes($Value), ENT_QUOTES);
                break;
            case self::JSON:
                $Value = json_encode($Value);
                break;
            case self::UTF8:
                $Value = utf8_decode($Value);
                break;
            case self::DATE:
            case self::FLOAT:
            case self::INT:
            case self::BOOL:
                $Value = Input::cleanVar($Value, $ClearingMethod);
                break;
            case self::NONE:
                break;
            default:
                $Value = preg_replace('/[^A-Za-z0-9_\!\?\=\* \&\@\:\(\)\\+\.\,\/\s\-]/', '', $Value);
                break;
        }
    }
}
