<?php
/**
 * Project: Epsilon
 * Date: 6/13/15
 * Time: 10:40 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Object;

defined('EPSILON_EXEC') or die();

use Epsilon\Database\Debug;
use Epsilon\User\SystemMessage;
use PDO;
use PDOException;

/**
 * Class DataBoundObject
 *
 * @package Epsilon\Base
 */
abstract class ActiveRecord
{

    protected $ID;

    /** @var  PDO */
    protected $objPDO;

    /**
     * $arTableName = ['Table_Name','Alias'];
     * it is mandatory to use alias for your database in order to avoid problems doing CRUD
     *
     * @var  array $arTableName
     */
    protected $arTableName;

    /**
     * arTableMap and arLazyTableMap contain the database fields bound to a Class_Key
     * Structure : ['Database_Key' => 'Class_Key'];
     * This will become: $this->$Class_Key = $Database_Value;
     * so now you can use $this->get('Class_Key') and return the value if key is not set will load form Database
     * In case there as typo on Class_Key $this->get() will return null.
     * As long your Database field names changes you don't need to refactor everything in your application for that field name you changed
     * instead you only need to change the ['Database_Key'] e.g:
     * $arTableMap = ['Password' => 'Password'];
     * then you realize that 'Password' may be a keyword for MySQL so you rename it to 'Pwd' you change the following:
     * $arTableMap = ['Pwd' => 'Password']
     * Your application will still use $this->get('Password') or $this->set('Password',$hast) run without any problems
     * or the need refactor the database field name you changed
     *
     * @var array $arTableMap
     * @var array $arLazyTableMap
     */
    protected $arTableMap;
    protected $arLazyTableMap;

    /**
     * arRelationMap contain the properties|fields of the database table directly related by ForeignKey
     * which are 'read only' by ActiveRecord
     *
*@var array $arRelationMap
     */
    protected $arRelationMap;

    /**
     * $arRelationKeys contains the all the Keys from the $arRelationMap in order to not iterate
     * for each $arRelationMap array checking if the key exist or not
     *
     * @var array $arRelationKeys
     */
    protected $arRelationKeys;

    /**
     * true or false if inner properties have been set
     *
     * @var bool
     */
    protected $blKeysSet;
    protected $arKeys;

    /**
     * @var bool $blIsLoaded         true or false if $arTableMap as been loaded or not
     * @var bool $blIsLazyLoaded     true or false if $arTableMap as been loaded or not
     * @var bool $blIsRelationLoaded true or false if $arLazyTableMap as been loaded or not
     * @var bool $blForceDeletion    true or false if wanna delete the object before __destroy() is called however __destroy() will throw PDOException
     * @var bool $blForDeletion      if set to true the table row will be deleted method called at __destroy()
     */
    protected $blIsLoaded;
    protected $blIsLazyLoaded;
    protected $blIsRelationLoaded;
    protected $blForceDeletion;
    protected $blForDeletion;

    /**
     * @var array $arModifiedFields contain all properties|field that are going to be modified on method Save()
     */
    protected $arModifiedFields;

    /** Rules */

    protected $arRules;

    const INT    = 1;
    const FLOAT  = 2;
    const STRING = 3;
    const BOOL   = 4;

    /** @return array */
    abstract protected function defineTableName();

    /** @return array */
    abstract protected function defineTableMap();

    /** @return array */
    abstract protected function defineLazyTableMap();

    /** @return array */
    abstract protected function defineRelationMap();

    /** @return array */
    abstract protected function defineRules();

    /**
     * @param           $objPDO
     * @param null      $ID_Data
     * @param bool|true $blResultSet
     */
    public function __construct($objPDO, $ID_Data = null, $blResultSet = true)
    {
        $this->objPDO             = $objPDO;
        $this->arTableName        = $this->defineTableName();
        $this->arTableMap         = $this->defineTableMap();
        $this->arLazyTableMap     = $this->defineLazyTableMap();
        $this->arRelationMap      = $this->defineRelationMap();
        $this->arRules            = $this->defineRules();
        $this->blIsLoaded         = false;
        $this->blIsLazyLoaded     = false;
        $this->blIsRelationLoaded = false;
        $this->blForDeletion      = false;
        $this->blForceDeletion    = false;
        $this->arModifiedFields   = [];
        $this->setInnerKeys();

        /**
         * IF $ID_Data is a numeric | string variable value set as DataBoundObject ID
         * else IF $ID_Data is an array or object set as Properties of DataBoundObject
         */
        if (is_numeric($ID_Data) || is_string($ID_Data)) {
            $this->ID = $ID_Data;
        } elseif (is_array($ID_Data) || is_object($ID_Data)) {
            $this->setProperties($ID_Data, $blResultSet);
        }
    }

    /**
     * @param string $map
     * @return bool
     */
    protected function load($map = 'arTableMap')
    {
        if (isset($this->ID)) {

            if ($map === 'arTableMap') {
                $loaded = &$this->blIsLoaded;
            } elseif ($map === 'arLazyTableMap') {
                $loaded = &$this->blIsLazyLoaded;
            } elseif ($map === 'arRelationMap') {
                $loaded = &$this->blIsRelationLoaded;
            } else {
                return false;
            }

            if ($loaded) {
                return true;
            }

            $ssql = 'SELECT ';

            foreach ($this->$map as $Key => $Value) {
                if (is_array($Value) && count($Value) > 3) {
                    $tableAlias = $Value[2];
                    $arMap      = $Value[4];
                    foreach ($arMap as $k => $v) {
                        $ssql .= $tableAlias . '.' . $k . ' as $v,';
                    }
                } else {
                    $ssql .= $this->getTableNameAlias() . '.' . $Key . ',';
                }
            }

            $ssql = substr($ssql, 0, strlen($ssql) - 1);

            $ssql .= ' FROM ' . $this->getTableName() . ' ' . $this->getTableNameAlias() . ' ';

            foreach ($this->$map as $Key => $Value) {
                if (is_array($Value) && count($Value)) {

                    $tableName  = $Value[1];
                    $tableAlias = $Value[2];
                    $joinType   = strtoupper($Value[0]);
                    $references = $Value[3];

                    $ssql .= $joinType . ' JOIN ' . $tableName . ' ' . $tableAlias . ' ON ';
                    if (is_array($references)) {
                        $ssql .= $tableAlias . '.' . $references[0] . ' = ' . $this->getTableNameAlias() . '.' . $references[1] . ' ';
                    } else {
                        $ssql .= $tableAlias . '.' . $references . ' = ' . $this->getTableNameAlias() . '.' . $references . ' ';
                    }
                }
            }

            $ssql .= 'WHERE ' . $this->getTableNameAlias() . '.' . $this->fieldKey('ID', $this->arTableMap) . ' = :id';

            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(':id', $this->ID, $this->getPDOParamType($this->ID));
            $stmt->execute();

            $ResultSet = $stmt->fetch(PDO::FETCH_OBJ);

            if (!is_object($ResultSet)) {
                $ResultSet = [];
            }

            $this->setProperties($ResultSet, true);

            if ($ResultSet) {
                $loaded = true;
            }

            return $loaded;
        } else {
            return false;
        }
    }

    /**
     * @param bool|false $blNotAIColumn
     * @return bool
     */
    public function save($blNotAIColumn = false)
    {
        if (count($this->arModifiedFields) <= 0) {
            return false;
        }

        $this->checkNotNulls();

        $ssqlValues = '';
        $ID         = null;

        $tableName = $this->getTableName();

        if (isset($this->ID) && !$blNotAIColumn) {
            $ssql = "UPDATE $tableName SET ";
        } else {
            $ssql = "INSERT INTO $tableName (";
        }

        $maps = [
            'arTableMap',
            'arLazyTableMap'
        ];
        foreach ($maps as $map) {
            foreach ($this->$map as $key => $value) {
                if ($value == 'ID' && !$blNotAIColumn) {
                    $ID = $key;
                } elseif ($value == 'ID' && $blNotAIColumn) {
                    $ID = $key;
                    $ssql .= "$key,";
                    $ssqlValues .= ":$key,";
                } elseif (isset($this->$value) && $this->modifiedProperty($value)) {
                    if (isset($this->ID) && !$blNotAIColumn) {
                        $ssql .= "$key = :$key, ";
                    } else {
                        $ssql .= "$key,";
                        $ssqlValues .= ":$key,";
                    }
                }
            }
        }

        if (isset($this->ID) && !$blNotAIColumn) {
            $ssql = substr($ssql, 0, strlen($ssql) - 2);
            $ssql .= " WHERE $ID = :$ID";
        } else {
            $ssql       = substr($ssql, 0, strlen($ssql) - 1);
            $ssqlValues = substr($ssqlValues, 0, strlen($ssqlValues) - 1);
            $ssql .= ") VALUES ($ssqlValues)";
        }

        $stmt = $this->objPDO->prepare($ssql);

        $maps = [
            'arTableMap',
            'arLazyTableMap'
        ];

        foreach ($maps as $map) {
            foreach ($this->$map as $key => $value) {
                if (isset($this->$value) && $this->modifiedProperty($value)) {
                    $CleanValue = $this->getByRule($key, $this->$value);
                    $stmt->bindValue(":$key", $CleanValue, $this->getPDOParamType($CleanValue));
                }
            }
        }

        if (isset($this->ID) || $blNotAIColumn == true) {
            $stmt->bindValue(":$ID", $this->get('ID'), $this->getPDOParamType($this->get('ID')));
        }

        $Result = $stmt->execute();

        if ($Result) {
            $this->arModifiedFields = [];
            if (!isset($this->ID)) {
                $this->ID = $this->objPDO->lastInsertId();
            }
        }

        return $Result;
    }

    private function setInnerKeys()
    {
        if (!$this->blKeysSet) {
            $this->arKeys = [];
            $maps         = [
                'arTableMap',
                'arLazyTableMap',
                'arRelationKeys'
            ];

            foreach ($maps as $map) {
                if ($map === 'arRelationKeys') {
                    $actualMap = $this->getRelationKeys();
                } else {
                    $actualMap = $this->$map;
                }

                foreach ($actualMap as $k => $v) {
                    $this->arKeys[$k] = $v;
                }
            }
        }
    }

    /**
     * @param $Data
     * @param $blResultSet
     */
    public function setProperties($Data, $blResultSet)
    {
        foreach ($Data as $key => $value) {
            $this->set($key, $value, $blResultSet);
        }
    }

    /**
     * TODO: Use Reflection to return only public properties
     *
     * @param bool|false $withRelation
     * @return Object
     */
    public function getProperties($withRelation = false)
    {

        $Object = [];

        $maps = [
            'arTableMap',
            'arLazyTableMap'
        ];

        foreach ($maps as $map) {
            foreach ($this->$map as $k => $v) {
                $Object[$v] = $this->get($v);
            }
        }

        if ($withRelation) {
            foreach ($this->getRelationKeys() as $k => $v) {
                $Object[$v] = $this->get($v);
            }
        }

        return new Object($Object);
    }

    /**
     * @param string $Key
     * @param mixed  $Value
     * @param bool   $ResultSet
     */
    public function set($Key, $Value, $ResultSet = false)
    {
        if (isset($this->arKeys[$Key])) {
            $Key = $this->arKeys[$Key];
        }

        if (in_array($Key, $this->arKeys)) {
            if (!$ResultSet || ($ResultSet && !isset($this->arModifiedFields[$Key]))) {
                $this->$Key = ($Value !== '') ? $Value : null;
                if ($Key != 'ID' && !$ResultSet && !in_array($Key, $this->getRelationKeys())) {
                    $this->arModifiedFields[$Key] = true;
                }
            }
        }
    }

    /**
     * Eval the Value
     *
     * @param $Key
     * @param $Value
     * @return mixed
     */
    private function getByRule($Key, $Value)
    {
        $Rule = isset($this->arRules[$Key]) ? ((is_array($this->arRules[$Key])) ? $this->arRules[$Key][0] : $this->arRules[$Key]) : null;

        if ($Rule === self::INT) {
            $Value = intval($Value);
        } elseif ($Rule === self::FLOAT) {
            $Value = floatval($Value);
        } elseif ($Rule === self::BOOL) {
            $Value = boolval($Value);
        } elseif ($Rule === self::STRING) {
            $Value = strval($Value);
        }

        return $Value;
    }

    /**
     * @param $Key
     * @return mixed
     */
    public function get($Key)
    {
        if (property_exists($this, $Key)) {
            return $this->$Key;
        } elseif ($this->load($this->getPropertyMap($Key))) {
            return $this->$Key;
        } else {
            return null;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    private function modifiedProperty($key)
    {
        return isset($this->arModifiedFields[$key]);
    }

    /**
     * @param string $Property
     * @return bool
     * @internal param string $map
     */
    private function getPropertyMap($Property)
    {
        foreach (['arTableMap', 'arLazyTableMap', 'arRelationMap'] as $map) {
            if ($map === 'arTableMap' || $map === 'arLazyTableMap') {
                if (in_array($Property, $this->$map)) {
                    return $map;
                }
            } elseif ($map === 'arRelationMap') {
                if (in_array($Property, $this->getRelationKeys())) {
                    return $map;
                }
            }
        }

        return false;
    }

    /**
     * @return array
     */
    private function getRelationKeys()
    {
        if (!isset($this->arRelationKeys)) {

            $this->arRelationKeys = [];

            foreach ($this->arRelationMap as $Map) {
                if (is_array($Map[4])) {
                    foreach ($Map[4] as $k => $v) {
                        array_push($this->arRelationKeys, $v);
                    }
                }
            }
        }

        return $this->arRelationKeys;
    }

    private function checkNotNulls()
    {
        if ($this->objPDO->getAttribute(PDO::ATTR_ERRMODE) != PDO::ERRMODE_EXCEPTION) {
            foreach ($this->arModifiedFields as $Key => $V) {
                $NotNull = (isset($this->arRules[$Key]) && is_array($this->arRules[$Key])) ? $this->arRules[$Key][1] : false;
                if ($NotNull && is_null($this->$Key)) {
                    $Message = "Field '{$Key}' can not be null";
                    if (!Debug::inDebug()) {
                        SystemMessage::addMessage('_system', SystemMessage::MSG_WARNING, $Message, false);
                    }
                    throw new PDOException($Message);
                }
            }
        }
    }

    /**
     * @param string $key
     * @param array  $map
     * @return mixed
     */
    private function fieldKey($key, $map)
    {
        return array_search($key, $map);
    }

    private function getTableName()
    {
        return $this->arTableName[0];
    }

    private function getTableNameAlias()
    {
        return $this->arTableName[1];
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function getPDOParamType($value)
    {
        if (is_array($value) || is_object($value)) {
            return PDO::PARAM_LOB;
        } elseif (is_integer($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    /**
     * @param bool|false $ForceDeletion
     */
    public function markForDeletion($ForceDeletion = false)
    {

        $this->set('blForDeletion', 1);
        $this->set('blForceDeletion', $ForceDeletion);

        if ($ForceDeletion) {
            $this->__destruct();
        }
    }

    public function __destruct()
    {
        if ($this->blForDeletion) {
            $this->blForDeletion = false;

            $table_name = $this->getTableName();
            $id         = $this->fieldKey('ID', $this->arTableMap);
            $ssql       = "DELETE FROM $table_name WHERE $id = :id";

            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(':id', $this->ID, $this->getPDOParamType($this->ID));

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                if ($this->blForceDeletion) {
                    throw $e;
                }
            }
        }
    }
}
