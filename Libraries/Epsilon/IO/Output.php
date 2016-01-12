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

defined('EPSILON_EXEC') or die();

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
                if (isset($ClearArrayKeys[$k])) {
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
        if ($ClearingMethod === self::HTML) {
            $Value = html_entity_decode($Value, ENT_QUOTES);
        } elseif ($ClearingMethod === self::HTML_SLASHES) {
            $Value = html_entity_decode(addslashes($Value), ENT_QUOTES);
        } elseif ($ClearingMethod === self::JSON) {
            $Value = json_encode($Value);
        } elseif ($ClearingMethod === self::UTF8) {
            $Value = utf8_decode($Value);
        } else {
            $Value = Input::cleanVar($Value, $ClearingMethod);
        }
    }
}
