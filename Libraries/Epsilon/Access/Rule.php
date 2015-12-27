<?php
/**
 * Project: Epsilon
 * Date: 6/20/15
 * Time: 11:05 PM
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
 * Class Rule
 *
 * @package Epsilon\Access
 */
class Rule extends Object
{

    protected $Identities;

    /**
     * @param array $Identities
     */
    public function __construct($Identities)
    {
        parent::__construct([]);
        $this->Identities = [];
        if (is_string($Identities)) {
            $Identities = json_decode($Identities, true);
        }
        $this->mergeIdentities($Identities);
    }

    /**
     * @param $Identities
     */
    public function mergeIdentities($Identities)
    {
        if ($Identities instanceof Rule) {
            $Identities = $Identities->getIdentities();
        }

        if (is_array($Identities)) {
            foreach ($Identities as $Identity => $Allow) {
                $this->mergeIdentity($Identity, $Allow);
            }
        }
    }

    /**
     * @param $Identity
     * @param $Allow
     */
    public function mergeIdentity($Identity, $Allow)
    {
        $Identity = (int)$Identity;
        $Allow    = (int)((boolean)$Allow);

        if (array_key_exists($Identity, $this->Identities)) {
            if ($this->Identities[$Identity] !== 0) {
                $this->Identities[$Identity] = $Allow;
            }
        } else {
            $this->Identities[$Identity] = $Allow;
        }
    }

    /**
     * @param $Identities
     * @return bool
     */
    public function allowed($Identities)
    {
        $result = false;

        if ($Identities) {
            if (!is_array($Identities)) {
                $Identities = [$Identities];
            }

            foreach ($Identities as $Identity) {
                $Identity = (int)$Identity;

                if (isset($this->Identities[$Identity])) {
                    $result = (bool)$this->Identities[$Identity];

                    // An explicit deny Win.
                    if ($result === false) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return $this->Identities;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->Identities);
    }

}