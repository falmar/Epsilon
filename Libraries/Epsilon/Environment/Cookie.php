<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:30 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Environment;

defined("EPSILON_EXEC") or die();

use Epsilon\Object\Object;

/**
 * Class Cookie
 *
 * @package Epsilon\Environment
 */
class Cookie extends Object
{
    private static $Instance;
    private        $Cookies;
    private        $newCookies;
    public         $Lifespan;
    public         $Path;
    public         $Domain;

    /**
     * Load the active cookies of the current session
     *
     * @param array $Options
     */
    public function __construct($Options = [])
    {
        parent::__construct($Options);
        if (!isset($this->Cookies)) {
            $this->Cookies = &$_COOKIE;
        }
        $this->newCookies = [];
    }

    /**
     * @return Cookie
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new Cookie();
        }

        return self::$Instance;
    }

    /**
     * Set a cookie
     *
     * @param string   $Key
     * @param mixed    $Value
     * @param int|NULL $Lifespan seconds
     */
    function set($Key, $Value, $Lifespan = null)
    {
        if ($Value === null) {
            $Lifespan = time() - 1;
        }

        $this->newCookies[$Key] = [
            "value"    => base64_encode($Value),
            "lifespan" => $Lifespan
        ];

        $Lifespan = time() + ((is_int($Lifespan)) ? $Lifespan : $this->Lifespan);
        setcookie($Key, base64_encode($Value), $Lifespan, $this->Path, $this->Domain);
    }

    /**
     * @param $Key
     * @return null|string
     */
    function get($Key)
    {
        if (array_key_exists($Key, $this->newCookies)) {
            $value = $this->newCookies[$Key]["value"];
        } elseif (array_key_exists($Key, $this->Cookies)) {
            $value = $this->Cookies[$Key];
        } else {
            $value = null;
        }

        return ($value) ? base64_decode($value) : null;
    }
}
