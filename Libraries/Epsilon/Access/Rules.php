<?php
/**
 * Project: Epsilon
 * Date: 6/20/15
 * Time: 10:57 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Access;

defined("EPSILON_EXEC") or die();

use Epsilon\Object\Object;

/**
 * Class Rules
 *
 * @package Epsilon\Access
 */
class Rules extends Object
{

    protected $Actions;

    /**
     * @param mixed $Rules
     */
    public function __construct($Rules = null)
    {
        parent::__construct([]);
        $this->Actions = [];
        if (is_string($Rules)) {
            $this->merge(json_decode($Rules, true));
        } elseif (is_object($Rules) || is_array($Rules)) {
            $this->mergeCollection((array)$Rules);
        }
    }

    /**
     * @param $Action
     * @param $Identity
     * @return bool
     */
    public function allowed($Action, $Identity)
    {
        if (isset($this->Actions[$Action])) {
            /** @var Rule $Rule */
            $Rule = $this->Actions[$Action];

            return $Rule->allowed($Identity);
        }

        return false;
    }

    /**
     * @param $Action
     * @param $Identities
     */
    public function mergeAction($Action, $Identities)
    {
        if (isset($this->Actions[$Action])) {
            /** @var $Rule Rule */
            $Rule = $this->Actions[$Action];
            $Rule->mergeIdentities($Identities);
        } else {
            $this->Actions[$Action] = new Rule($Identities);
        }
    }

    /**
     * @param $Rules
     */
    public function mergeCollection($Rules)
    {
        if (is_array($Rules)) {
            foreach ($Rules as $Actions) {
                $this->merge(json_decode($Actions, true));
            }
        }
    }

    /**
     * @param Rules|array $Actions
     */
    public function merge($Actions)
    {
        if (is_array($Actions)) {
            foreach ($Actions as $Action => $Identities) {
                $this->mergeAction($Action, $Identities);
            }
        } elseif ($Actions instanceof Rules) {
            $Rules = $Actions->getActions();
            foreach ($Rules as $Action => $Identities) {
                $this->mergeAction($Action, $Identities);
            }
        }
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->Actions;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $arRules = [];

        foreach ($this->getActions() as $Action => $Rule) {
            // Convert the action to JSON, then back into an array otherwise
            // re-encoding will quote the JSON for the identities in the action.
            $arRules[$Action] = json_decode((string)$Rule);
        }

        return json_encode($arRules);
    }

}
