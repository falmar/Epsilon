<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:27 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Database;

defined("EPSILON_EXEC") or die();

use Epsilon\Factory;
use PDO;
use PDOException;

/**
 * Class DatabaseHandler
 *
 * @package Epsilon\Database
 */
class DatabaseHandler extends PDO
{
    public $PreparedQueries;

    /**
     * @param string $strDSN
     * @param string $User
     * @param string $Password
     * @param array  $Options
     */
    public function __construct($strDSN, $User, $Password, $Options = [])
    {
        $this->PreparedQueries = 0;
        try {
            parent::__construct($strDSN, $User, $Password, $Options);
        } catch (PDOException $e) {
            Factory::getLogger()->emergency("DatabaseHandler Can't Connect exiting...");
        }
    }

    /** User Modified Options */

    /**
     * Modified for Debugging SQL Strings
     *
     * @param string $ssql
     * @return \PDOStatement
     */
    public function query($ssql)
    {
        if ($this->inDebug()) {
            Debug::addSSQL($ssql);
            $this->PreparedQueries++;
        }

        return parent::query($ssql);
    }

    /**
     * Modified for Debugging SQL Strings
     *
     * @param string $ssql
     * @param array  $options
     * @return \PDOStatement
     */
    public function prepare($ssql, $options = [])
    {
        if ($this->inDebug()) {
            Debug::addSSQL($ssql);
            $this->PreparedQueries++;
        }

        return parent::prepare($ssql, $options);
    }

    /** User Defined Methods */

    /**
     * @param bool $bl
     */
    public function setDebug($bl)
    {
        Debug::setDebug($bl);
    }

    /**
     * @return bool
     */
    public function inDebug()
    {
        return Debug::inDebug();
    }

    /**
     * @param PDOException $exception
     * @param null|string  $SSQL
     * @return bool
     */
    public function catchException(PDOException $exception, $SSQL = null)
    {
        Debug::catchException($exception, $SSQL);
    }
}