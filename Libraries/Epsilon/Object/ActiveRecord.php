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

defined("EPSILON_EXEC") or die();

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
     * which are "read only" by ActiveRecord
     *
     * @var array $arRelationMap
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
        $this->blIsLoaded         = false;
        $this->blIsLazyLoaded     = false;
        $this->blIsRelationLoaded = false;
        $this->blForDeletion      = false;
        $this->blForceDeletion    = false;
        $this->arModifiedFields   = [];

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
    protected function load($map = "arTableMap")
    {

        if (isset($this->ID)) {

            switch ($map) {
                case "arTableMap":
                    $loaded = &$this->blIsLoaded;
                    break;
                case "arLazyTableMap":
                    $loaded = &$this->blIsLazyLoaded;
                    break;
                case "arRelationMap":
                    $loaded = &$this->blIsRelationLoaded;
                    break;
                default:
                    return false;
            }

            if ($loaded) {
                return true;
            }

            $ssql = "SELECT ";

            foreach ($this->$map as $Key => $Value) {
                if (is_array($Value) && count($Value) > 3) {
                    $tableAlias = $Value[2];
                    $arMap      = $Value[4];
                    foreach ($arMap as $k => $v) {
                        $ssql .= $tableAlias . "." . $k . " as $v,";
                    }
                } else {
                    $ssql .= $this->getTableNameAlias() . "." . $Key . ",";
                }
            }

            $ssql = substr($ssql, 0, strlen($ssql) - 1);

            $ssql .= " FROM " . $this->getTableName() . " " . $this->getTableNameAlias() . " ";

            foreach ($this->$map as $Key => $Value) {
                if (is_array($Value) && count($Value)) {

                    $tableName  = $Value[1];
                    $tableAlias = $Value[2];
                    $joinType   = strtoupper($Value[0]);
                    $references = $Value[3];

                    $ssql .= $joinType . " JOIN " . $tableName . " " . $tableAlias . " ON ";
                    if (is_array($references)) {
                        $ssql .= $tableAlias . "." . $references[0] . " = " . $this->getTableNameAlias() . "." . $references[1] . " ";
                    } else {
                        $ssql .= $tableAlias . "." . $references . " = " . $this->getTableNameAlias() . "." . $references . " ";
                    }
                }
            }

            $ssql .= "WHERE " . $this->getTableNameAlias() . "." . $this->fieldKey("ID", $this->arTableMap) . " = :id";

            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":id", $this->ID, $this->getPDOParamType($this->ID));
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

        $ssqlValues = "";
        $ID         = null;

        $tableName = $this->getTableName();

        if (isset($this->ID) && !$blNotAIColumn) {
            $ssql = "UPDATE $tableName SET ";
        } else {
            $ssql = "INSERT INTO $tableName (";
        }

        $maps = [
            "arTableMap",
            "arLazyTableMap"
        ];
        foreach ($maps as $map) {
            foreach ($this->$map as $key => $value) {
                if ($value == "ID" && !$blNotAIColumn) {
                    $ID = $key;
                } elseif ($value == "ID" && $blNotAIColumn) {
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
            "arTableMap",
            "arLazyTableMap"
        ];

        foreach ($maps as $map) {
            foreach ($this->$map as $key => $value) {
                if (isset($this->$value) && $this->modifiedProperty($value)) {
                    $stmt->bindValue(":$key", $this->$value, $this->getPDOParamType($this->$value));
                }
            }
        }

        if (isset($this->ID) || $blNotAIColumn == true) {
            $stmt->bindValue(":$ID", $this->get("ID"), $this->getPDOParamType($this->get("ID")));
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

    /**
     * @param $Data
     * @param $blResultSet
     */
    public function setProperties($Data, $blResultSet)
    {
        foreach ($Data as $key => $value) {

            $maps = [
                "arTableMap",
                "arLazyTableMap",
                "arRelationMap"
            ];

            foreach ($maps as $map) {

                $actualKey = null;
                $actualMap = $this->$map;

                if ($map == "arRelationMap") {
                    $this->setProperty($this->getRelationKeys(), $key, $value, $blResultSet);
                } else {
                    $this->setProperty($actualMap, $key, $value, $blResultSet);
                }
            }
        }
    }

    /**
     * @param $actualMap
     * @param $key
     * @param $value
     * @param $blResultSet
     */
    private function setProperty($actualMap, $key, $value, $blResultSet)
    {

        $actualKey = null;
        if (property_exists($this, $key)) {
            $actualKey = $key;
        } elseif (array_key_exists($key, $actualMap)) {
            $actualKey = $actualMap[$key];
        } elseif (in_array($key, $actualMap)) {
            $actualKey = $key;
        }

        /** If the key exist set the value */
        if ($actualKey) {

            if ($blResultSet && !$this->modifiedProperty($actualKey)) {
                $this->set($actualKey, $value, $blResultSet);
            } elseif (!$blResultSet) {
                $this->set($actualKey, $value, $blResultSet);
            }
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
            "arTableMap",
            "arLazyTableMap"
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
        $map = $this->propertyExist($Key);
        if ($map) {
            $this->$Key = $Value;
            if ($map != "arRelationMap" && $Key != "ID" && !$ResultSet) {
                $this->arModifiedFields[$Key] = true;
            }
        } elseif (property_exists($this, $Key)) {
            $this->$Key = $Value;
        }
    }

    /**
     * @param $Key
     * @return mixed
     */
    public function get($Key)
    {
        if (property_exists($this, $Key)) {
            return $this->$Key;
        } elseif ($this->load($this->propertyExist($Key))) {
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
        return array_key_exists($key, $this->arModifiedFields);
    }

    /**
     * @param $key
     * @return false|string
     */
    private function propertyExist($key)
    {
        foreach ([
            'arTableMap',
            'arLazyTableMap',
            'arRelationMap'
        ] as $map) {
            if ($this->checkPropertiesMap($key, $map)) {
                return $map;
            }
        }

        return false;
    }

    /**
     * @param string $Property
     * @param string $map
     * @return bool
     */
    private function checkPropertiesMap($Property, $map)
    {
        if ($map == "arTableMap" || $map == "arLazyTableMap") {
            if (in_array($Property, $this->$map)) {
                return true;
            }
        } elseif ($map == "arRelationMap") {
            if (in_array($Property, $this->arRelationKeys)) {
                return true;
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

        $this->set("blForDeletion", 1);
        $this->set("blForceDeletion", $ForceDeletion);

        if ($ForceDeletion) {
            $this->__destruct();
        }
    }

    public function __destruct()
    {
        if ($this->blForDeletion) {
            $this->blForDeletion = false;

            $table_name = $this->getTableName();
            $id         = $this->fieldKey("ID", $this->arTableMap);
            $ssql       = "DELETE FROM $table_name WHERE $id = :id";

            $stmt = $this->objPDO->prepare($ssql);
            $stmt->bindValue(":id", $this->ID, $this->getPDOParamType($this->ID));

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