<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:39 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Event;

defined("EPSILON_EXEC") or die();

/**
 * Class Manager
 *
 * @package Epsilon\Event
 */
class Manager
{
    protected $Events;

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->Events = [];
    }

    /**
     * @param string $Event
     * @param mixed  $Listener
     */
    public function addListener($Event, $Listener)
    {
        if (is_array($Listener)) {
            if (is_callable($Listener[0])) {
                $this->Events[$Event][] = $Listener;
            }
        }
    }

    /**
     * @param string $Event
     */
    public function dispatch($Event)
    {
        if (array_key_exists($Event, $this->Events)) {
            foreach ($this->Events[$Event] as $listener) {
                if (is_array($listener)) {
                    if (is_callable($listener[0])) {
                        call_user_func_array($listener[0], $listener[1]);
                    }
                }
            }
        }
    }
}
