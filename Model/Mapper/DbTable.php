<?php
/**
 * DbTable.php
 * Ooba_Model_Mapper_DbTable
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Mapper
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Model_Mapper_DbTable extends Ooba_Model_Mapper_DbTable_Abstract
{
    /**
     * This Object supports fetching multiple objects
     *
     * @var boolean
     */
    protected $_supportsMulti = true;
    
    /**
     * Populate a mappable with data
     *
     * @param  string|Core_Model_Abstract $mappable Model to populate
     * @param  array|object               $data     The data
     * @throws Core_Model_Mapper_Exception If data not array|object
     * @return Core_Model_Mappable
     */
    public function populate($mappable, $data)
    {
        if (is_object($mappable) === false) {
            $mappable = new $mappable;
        } else {
            $mappable  = get_class($mappable);
             $mappable = new $mappable;
        }
        
        if ((is_array($data) === false) and (is_object($data) === false)) {
            throw new Core_Model_Mapper_Exception(
             'Data for populating a mappable must be an array or object');
        }
        
        if (is_array($data) === true) {
             $data = (object) $data;
        }
        
        foreach ($mappable->getMap() as $mappableAttrib => $dbAttrib) {
            $method = 'set' . ucfirst($mappableAttrib);
            // When $dbAttrib is an array, it's a relationship, get those from the mappable
            if (($dbAttrib instanceof Core_Model_Relation_Abstract) === false) {
                if (isset($data->$dbAttrib) === true) {
                    $mappable->$mappableAttrib = ($data->$dbAttrib);
                }
            }
        }
        
        if (($data instanceof Zend_Db_Table_Row_Abstract) === true) {
            $mappable->getMapper('DbTable')->setCurrentRow($mappable, $data);
        }
        
        return $mappable;
    }
    
    /**
     * Save the mappable object
     *
     * @param  Core_Model_Abstract          $mappable Model to Save
     * @param  Core_Model_Relation_Abstract $relation (Optional) Relation to save by
     * @throws Core_Model_Mapper_Exception_InvalidMap  If map is invalid
     * @return void
     */
    public function save(Core_Model_Abstract $mappable, Core_Model_Relation_Abstract $relation = null)
    {
        $table = $this->getDbTableObj();
        $db    = $table->getAdapter();
        $db->forceAdapter(Core_Db_Adapter_Replicated::MASTER);
        $this->_validateMap();
        $this->_getCurrentRow($mappable);
        // Empty arrays for our data plus and any dependents or references
        $data       = array();
        $dependents = array();
        $joins      = array();
        $manyToMany = array();
        // These refs will be looped through to perfom cascading operations.
        $refsToUpdate = array();
        $map          = $mappable->getMap();
        // Walk over the maps and populate the arrays
        foreach ($map as $mappableAttrib => $dbAttrib) {
            if (is_object($dbAttrib) === true and isset($mappable->$mappableAttrib) === true) {
                $value = $mappable->$mappableAttrib;
                
                $relationObjectsToProcess[] = $dbAttrib;
                
                // This is a relation
                $relation = $dbAttrib;
                // Switch on the type of relation
                switch ($relation->getType()) {
                    case self::DEPENDENT:
                        $dependents = array_merge($dependents, $value);
                        break;
                    
                    case self::DEPENDENT_ONETOONE:
                        // Dependents need to be saved after us, so store it
                        $dependents[] = $value;
                        break;
                    
                    case self::REFERENCE:
                        $rData        = $this->_processReferences($mappable, $value, $relation, $mappableAttrib);
                        $refsToUpdate = array_merge($refsToUpdate, array(array('obj' => $value,
                                                                               'relation' => $relation)));
                        $data         = array_merge($data, $rData);
                        break;
                    
                    case self::MANYTOMANY:
                        $manyToMany[] = array('value'=> $value, 'relation' => $relation);
                        break;
                    
                    default:
                        // Do nothing with unknown relations
                        break;
                }
            } else if (is_object($dbAttrib) === false and isset($mappable->$mappableAttrib) === true) {
                $value = $mappable->$mappableAttrib;
                // This is just a data field, set the value in $data
                if (is_null($this->_getCurrentRow($mappable)) === false
                    and $this->_getCurrentRow($mappable)->$dbAttrib !== (string) $value) {
                    $this->_getCurrentRow($mappable)->$dbAttrib = $value;
                } else {
                    $data[$dbAttrib] = $value;
                }
            }
        }
        
        // Get our primary key so we can decide to insert or update
        if (is_null($mappable->getPrimaryKeyValue()) === true) {
            unset($data[$map[$mappable->getPrimaryKey()]]);
            $table = $this->getDbTableObj();
            $pkVal = $table->insert($data);
            $map   = $mappable->getMap();
            $pk    = $map[$mappable->getPrimaryKey()];
            $mappable->setPrimaryKeyValue($pkVal);
            foreach ($refsToUpdate as $ref) {
                 $this->_onInsertRelation($mappable, $ref['obj'], $ref['relation']);
            }
            
            unset($table);
        } else {
            $whereString = sprintf('%s = ?', $map[$mappable->getPrimaryKey()]);
            $rows        = $this->_getCurrentRow($mappable)->save();
        }
        
        foreach ($manyToMany as $join) {
            if ($join['relation']->cascadeSave === true) {
                $joinRows = $this->_processManyToMany($mappable, $join['value'], $join['relation']);
            }
        }

        // Re-populate the model with the data from the DB
        $mappable = $this->populate($mappable, $this->_getCurrentRow($mappable));
    }
    
    /**
     * Process references
     *
     * @param  Core_Model_Abstract          $mappable Mappable
     * @param  mixed                        $value    The object->property's value
     * @param  Core_Model_Relation_Abstract $relation The relation from the map
     * @throws Zend_Exception Only to be used wth Reference Relation
     * @return array
     */
    private function _processReferences(Core_Model_Abstract $mappable, $value,
                                        Core_Model_Relation_Abstract $relation)
    {
        if (($relation->getType() === self::REFERENCE) === false) {
            throw new Zend_Exception('Relation is not the type of' .  self::REFERENCE);
        }

        $return = array();
        // References need to be saved first, lets see how many we have
        if (is_array($value) === false) {
            // Only one, wrap it in an array so we don't have to duplicate logic later
            $value = array($value);
        }
        
        // Lets walk through them
        foreach ($value as $key => $obj) {
            // Get the pk from the object's mapper
            $objPk = $obj->getPrimaryKey();
            // If the relation table is an array then use the object to get the right one
            $relationTable = $relation->getTable($obj);
            $relationRule  = $relation->getRule($obj);
            
            // Load the class using autoloader to get around broken Zend_Db_Table
            if (class_exists($relationTable) === false) {
                Zend_Loader_Autoloader::autoload($relationTable);
            }
            
            // If pk is null then it needs to be saved first
            if (is_null($obj->$objPk) === true) {
                $obj->save();
            }
            
            // This is a reference relationship
            // Get the reference info from the dbTable
            $rInfo = (object) $this->getDbTableObj()->getReference($relationTable, $relationRule);
            
            $data = array();
            // Data for previous object  we need this so we can update related data with _onUpdateRecord
            $previous = array();
            
            // Using rInfo->refColumns and rInfo->columns populate data
            foreach ($rInfo->refColumns as $refColumnKey => $refColumn) {
                $columnKey     = $rInfo->columns[$refColumnKey];
                $modelProperty = $obj->getPropertyByColumn($refColumn);
                if (is_null($this->_getCurrentRow($mappable)) === false
                    and $this->_getCurrentRow($mappable)->$columnKey !== (string) $obj->$modelProperty) {
                    $previous[] = $this->_getCurrentRow($mappable)->$columnKey;
                }
                
                $data[$columnKey] = $obj->$modelProperty;
            }

            if (count($previous) > 0 === true) {
                $modelClass = get_class($obj);
                $prevObject = $this->findRelated($mappable, $relation);
                $this->_onUpdateRecord($obj, $relation, $prevObject);
            }
            
            $return[] = $data;
        }
        
        // If relation is Reference and count is 1, just return the 1
        if (($relation->getType() === self::REFERENCE) and (count($return) === 1)) {
            $previous = array();
            $return   = $return[0];
            foreach ($return as $key => $val) {
                if (is_null($this->_getCurrentRow($mappable)) === false
                    and $this->_getCurrentRow($mappable)->$key !== (string) $val) {
                    $previous[$key]                        = $this->_getCurrentRow($mappable)->$key;
                    $this->_getCurrentRow($mappable)->$key = $val;
                }
            }
        }
        
        return $return;

    }

    /**
     * Process references
     *
     * @param  Core_Model_Abstract          $mappable Mappable
     * @param  mixed                        $value    The object->property's value
     * @param  Core_Model_Relation_Abstract $relation The relation from the map
     * @return array
     */
    private function _processManyToMany(Core_Model_Abstract $mappable, $value,
                                        Core_Model_Relation_Abstract $relation)
    {
        $return       = array();
        $rowsByModel  = array();
        $joinDbTable  = $relation->getJoinDbTable();
        $joinDbTable  = new $joinDbTable;
        $localTable   = $this->getDbTable();
        $localRule    = $relation->getLocalRule();
        $localRInfo   = (object) $joinDbTable->getReference($localTable, $localRule);
        $remoteModels = $relation->getModel();
        if ( is_array($remoteModels) === false) {
            $remoteModels = array($remoteModels);
        }
        
        $rows = $this->_getCurrentRow($mappable)->findDependentRowset($joinDbTable, $localRule);
        foreach ($remoteModels as $remoteModel) {
            $obj           = new $remoteModel;
            $relationTable = $relation->getTable($obj);
            $relationRule  = $relation->getRule($obj);
            $rInfo         = (object) $joinDbTable->getReference($relationTable, $relationRule);
            $columns       = $rInfo->columns;
            
            // Loop through rows, See if they match our model
            foreach ($rows as $rowKey => $row) {
                $rowIsThisModel = true;
                $key            = $remoteModel;
                
                // Check that this row has all the required columns for this remoteModel
                foreach ($columns as $column) {
                    if (is_null($row->$column) === false) {
                        // Create key unique to this model type and row
                        $key .= '_' . $column . '_' . $row->$column;
                    } else {
                        // This row represents a different remoteModel
                        $rowIsThisModel = false;
                        // Stop looping through columns
                        break;
                    }
                }
                
                if ($rowIsThisModel === true) {
                    // Add key generated from above array.
                    $rowsByModel[$key] = array('model' => $remoteModel, 'row'=> $row,);
                    // We have determined the model type for this row, we don't need to analyze next loop
                    unset($rows[$rowKey]);
                }
            }
        }
        
        foreach ($value as $key => $obj) {
            $modelClass    = get_class($obj);
            $relationTable = $relation->getTable($obj);
            $relationRule  = $relation->getRule($obj);
            $objPk         = $obj->getPrimaryKey();
            $rInfo         = (object) $joinDbTable->getReference($relationTable, $relationRule);
            
            // If pk is null then it needs to be saved first
            if (is_null($obj->$objPk) === true) {
                 $obj->save();
            }
            
            $columns     = $rInfo->columns;
            $modelRowKey = $modelClass;
            
            foreach ($columns as $key => $column) {
                $columnModelProperty = $obj->getPropertyByColumn($rInfo->refColumns[$key]);
                $columnValue         = $obj->$columnModelProperty;

                $modelRowKey .= '_' . $column . '_' . $columnValue;
            }
            
            if (array_key_exists($modelRowKey, $rowsByModel) === true) {
                $rowsByModel[$modelRowKey] = null;
            } else {
                $data = array();
                
                // Using rInfo->refColumns and rInfo->columns populate data
                foreach ($rInfo->refColumns as $refColumnKey => $refColumn) {
                     $columnKey        = $rInfo->columns[$refColumnKey];
                     $modelProperty    = $obj->getPropertyByColumn($refColumn);
                     $data[$columnKey] = $obj->$modelProperty;
                }
                
                foreach ($localRInfo->refColumns as $refColumnKey => $refColumn) {
                         $columnKey        = $localRInfo->columns[$refColumnKey];
                         $getter           = 'get' . ucfirst(array_search($refColumn, $mappable->getMap()));
                         $data[$columnKey] = $mappable->$getter();
                }
                
                $joinDbTable->createRow($data)->save();
                $obj->clearCache();
                $this->_onInsertRelation($mappable, $obj, $relation);
            }
        }
        
        foreach ($rowsByModel as $item) {
            if (is_null($item['row']) === false) {
                $relatedObj    = $item['model'];
                $relationTable = $relation->getTable($relatedObj);
                $relatedObjRow = $item['row']->findParentRow($relationTable);
                $relatedObj    = new $obj;
                $relatedObj    = $this->populate($relatedObj, $relatedObjRow);
                $relatedObj->save();
                $this->_onDeleteRelation($mappable, $relatedObj, $relation);
                $item['row']->delete();
            }
        }

        return $return;
    }
    
    /**
     * Find a specific mappable object
     *
     * @param  Core_Model_Abstract $mappable Model to find
     * @param  mixed               $id       The primary key
     * @throws Core_Model_Mapper_Exception_InvalidMap If map is invalid
     * @return Core_Model_Mappable_DbTable|false
     */
    public function find(Core_Model_Abstract $mappable, $id)
    {
        $table  = $this->getDbTableObj();
        $result = $table->find($id);
        unset($table);
        $count = count($result);
        if ($count === 0) {
            return false;
        }
        
        if ($count > 1) {
            $return = array();
            foreach ($result as $row) {
                $return[] = $this->populate(get_class($mappable), $row);
            }
            
            return $return;
        }
        
        return $this->populate($mappable, $result->current());
    }
    
    /**
     * Find a specific mappable object
     *
     * @param  Core_Model_Abstract $mappable   Model to serach by
     * @param  array               $conditions The conditions for find
     * @throws Core_Model_Mapper_Exception If conditions are invalid
     * @return array
     */
    public function findBy(Core_Model_Abstract $mappable, array $conditions)
    {
        $table  = new $this->_dbTable;
        $select = $table->select();
        $map    = $mappable->getMap();
        foreach ($conditions as $key => $value) {
            if (isset($map[$key]) === false) {
                throw new Core_Model_Mapper_Exception(
                    "Core_Model_Mapper_DbTable::$key is not a valid model parameter");
            }
            
            if (is_object($map[$key]) === true) {
                throw new Core_Model_Mapper_Exception(
                    'Relations are not valid conditions for findBy');
            }
            
            if (($value === null)) {
                throw new Core_Model_Mapper_Exception(
                    'Core_Model_Mapper_DbTable:: condition value cannot be null');
            }
            
            $select = $select->where($map[$key] . ' = ?', $value);
        }
        
        $return = array();
        $models = $this->fetchAll($select);

        if (count($models) === 0) {
            return false;
        }
        
        foreach ($models as $model) {
            $return[] = $model;
        }
        
        return $return[0];
    }
    
    /**
     * Delete the object from the db
     *
     * @param  Core_Model_Abstract $mappable Model to Delete
     * @throws Core_Model_Mapper_Exception_InvalidMap If map is invalid
     * @return void
     */
    public function delete(Core_Model_Abstract $mappable)
    {
        $row = $this->_getCurrentRow($mappable);
        
        if (is_null($row) === true) {
            return;
        }

        $this->_onDeleteRecord($mappable);

        $row->delete();
    }

    /**
     * Get an array of all mappable objects in db
     *
     * @param  Zend_Db_Table_Select $select (Optional) Select to add more filters and Sorting
     * @throws Core_Model_Mapper_Exception_InvalidMap If map is invalid
     * @return array
     */
    public function fetchAll(Zend_Db_Table_Select $select = null)
    {
        $return  = array();
        $table   = $this->getDbTableObj();
        $results = $table->fetchAll($select);
        
        if (count($results) === 0) {
            return $return;
        }
        
        foreach ($results as $row) {
            $return[] = $this->populate(new $this->_mappable, $row);
        }
        
        return $return;
    }
    
    /**
     * Get an associative array of requested key value pairs
     * Good for select lists
     *
     * @param  string               $key    Field to be used for key     
     * @param  string               $value  Field to be stored in array
     * @param  Zend_Db_Table_Select $select (Optional) Select to add more filters and Sorting
     * @throws Core_Model_Mapper_Exception_InvalidMap If map is invalid
     * @return array
     */
    public function fetchArray($key, $value, Zend_Db_Table_Select $select = null)
    {
        $this->_validateMap();
        
        $return  = array();
        $results = $this->getDbTableObj()->fetchAll($select);
        if ($results->count() === 0) {
            return $return;
        }
        
        $mappable     = new $this->_mappable;
        $map          = $mappable->getMap();
        $dbKeyField   = $map[$key];
        $dbValueField = $map[$value];
        foreach ($results as $row) {
            $return[$row->$dbKeyField] = $row->$dbValueField;
        }
        
        unset($results);
        unset($mappable);
        unset($map);
        
        return $return;
    }
    
    /**
     * Remove Object Relation Row
     * 
     * @param  Core_Model_Abstract          $mappable Object  
     * @param  Core_Model_Abstract          $obj      Related Object 
     * @param  Core_Model_Relation_Abstract $relation Relation
     * @param  string                       $property Local Property to reset
     * @return boolean
     */
    protected function _hasRelatedObject(Core_Model_Abstract $mappable, Core_Model_Abstract $obj,
                                         Core_Model_Relation_Abstract $relation, $property)
    {
        $joinDbTable   = $relation->getJoinDbTable($obj);
        $joinDbTable   = new $joinDbTable;
        $localTable    = $this->getDbTable();
        $localRule     = $relation->getLocalRule();
        $localRInfo    = (object) $joinDbTable->getReference($localTable, $localRule);
        $remoteModels  = $relation->getModel();
        $relationTable = $relation->getTable($obj);
        $relationRule  = $relation->getRule($obj);
        $rInfo         = (object) $joinDbTable->getReference($relationTable, $relationRule);
        $columns       = $rInfo->columns;
        if (is_null($this->_getCurrentRow($mappable)) === true) {
            return false;
        }
        
        $select = $this->_getCurrentRow($mappable)->select();
        foreach ($rInfo->refColumns as $key => $column) {
            $objProp         = $obj->getPropertyByColumn($column);
            $joinTableColumn = $rInfo->columns[$key];
            $select          = $select->where("$joinTableColumn  = ?", $obj->$objProp);
        }

        switch ($relation->getType()) {
            case self::DEPENDENT:
                $rows = $this->_getCurrentRow($mappable)->findDependentRowset($relationTable, $select);
                break;
            
            case self::MANYTOMANY:
                $rows = $this->_getCurrentRow($mappable)->findManyToManyRowset($relationTable, $joinDbTable, $localRule,
                                                                        $relationRule, $select);
                break;

            default:
                // Do nothing with unknown relations
                break;
        }
        
        if (count($rows) > 0 ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove Object Relation Row
     * 
     * @param  Core_Model_Abstract          $obj        Object
     * @param  Core_Model_Abstract          $relatedObj Related Object 
     * @param  Core_Model_Relation_Abstract $relation   Relation
     * @param  string                       $property   Local Property to reset
     * @return boolean
     */
    protected function _removeRelatedObject(Core_Model_Abstract $obj,
                                            Core_Model_Abstract $relatedObj,
                                            Core_Model_Relation_Abstract $relation, $property)
    {
        $obj->{$property} = null;
        
        $joinDbTable   = $relation->getJoinDbTable($relatedObj);
        $joinDbTable   = new $joinDbTable;
        $localTable    = get_class($this->getDbTableObj());
        $localRule     = $relation->getLocalRule();
        $localRInfo    = (object) $joinDbTable->getReference($localTable, $localRule);
        $remoteModels  = $relation->getModel();
        $relationTable = $relation->getTable($relatedObj);
        $relationRule  = $relation->getRule($relatedObj);
        $rInfo         = (object) $joinDbTable->getReference($relationTable, $relationRule);
        $columns       = $rInfo->columns;
        
        if (is_null($this->_getCurrentRow($obj)) === true) {
            return false;
        }
        
        $select = $this->_getCurrentRow($obj)->select();
        foreach ($rInfo->refColumns as $key => $column) {
            $objProp         = $relatedObj->getPropertyByColumn($column);
            $joinTableColumn = $rInfo->columns[$key];
            $select          = $select->where("$joinTableColumn  = ?", $relatedObj->$objProp);
        }

        switch ($relation->getType()) {
            case self::DEPENDENT:
                $rows = $this->_getCurrentRow($obj)->findDependentRowset($relationTable, $select);
                break;
            
            case self::MANYTOMANY:
                $rows = $this->_getCurrentRow($obj)->findDependentRowset($joinDbTable, null, $select);
                break;

            default:
                // Do nothing with unknown relations
                break;
        }
        
        if (count($rows) > 0 ) {
            foreach ($rows as $row) {
                $row->delete();
            }
        }
        
        return true;
    }
    
    /**
     * Get a dependent object
     *
     * @param  Core_Model_Abstract           $mappable Model to search By 
     * @param  Core_Model_Relation_Dependent $relation The relation object
     * @param  Zend_Db_Select                $select   (Optional) Limit the result set
     * @return mixed
     */
    protected function _getDependentObjects(Core_Model_Abstract $mappable, Core_Model_Relation_Dependent $relation,
                                            Zend_Db_Select $select = null)
    {
        $return = array();
        
        // Get our model(s) from the relation
        $models = $relation->getModel();
        
        // If _currentRow isn't populated return an empty array
        if (is_null($this->_getCurrentRow($mappable)) === true) {
            if (($relation->getType() === self::DEPENDENT_ONETOONE)
                and (is_array($models) === false)) {
                $return = new $models;
            }
            
            return $return;
        }
        
        if (is_null($select) === true) {
            $select = $this->_getCurrentRow($mappable)->select();
        } else {
            $select->setTable($this->_getCurrentRow($mappable)->getTable());
        }
        
        // Get the limit/offset from the select
        $limit  = $select->getPart('limitcount');
        $offset = $select->getPart('limitoffset');
        
        if (is_array($models) === true) {
            $totals = $this->_countDependent($mappable, $relation, false);
            
            foreach ($models as $model) {
                // Clone the select
                $currentSelect = clone $select;
                
                if (is_null($limit) === false) {
                    if ($offset === null) {
                        $offset = 0;
                    } else if (($offset >= $totals[$model]) === true) {
                        $offset = ($offset - $totals[$model]);
                        continue;
                    }
                    
                    $count = count($return);
                    if ($count === $limit) {
                        // We already reached our limit in a previous iteration
                        break;
                    } else {
                        $limit           = ($limit - $count);
                        $previouslyFound = array_sum($found);
                        $offset          = ($offset - $previouslyFound);
                        // Don't let offset go below 0
                        if ($offset < 0) {
                            $offset = 0;
                        }
                    }
                    
                    $currentSelect->limit($limit, $offset);
                }
                
                $modelObj = new $model;
                $rows     = $this->_getDependentRows($mappable, $relation, $currentSelect, $model);
                foreach ($rows as $row) {
                    $return[] = $this->populate($model, $row);
                }
                
                if (is_null($limit) === false) {
                    $found[$model] = $rows->count();
                }
            }
        } else {
            $model = $models;
            
            // Clone the select
            $currentSelect = clone $select;
            $rows          = $this->_getDependentRows($mappable, $relation, $currentSelect, $model);

            if ($relation->getType() === self::DEPENDENT_ONETOONE) {
                if (count($rows) === 0) {
                    return false;
                }
                
                $row    = $rows->current();
                $return = $this->populate($model, $row);
            } else {
                foreach ($rows as $row) {
                    $return[] = $this->populate($model, $row);
                }
            }
        }
        
        return $return;
    }
    
    /**
     * Count dependent rows
     *
     * @param  Core_Model_Abstract           $mappable  The Object to search by
     * @param  Core_Model_Relation_Dependent $relation  The relation object
     * @param  boolean                       $aggregate (Optional) If false, return an array 
     *                                                  of counts by model
     * @param  Zend_Db_Select                $select    (Optional) Select to add additional filtering 
     * @return mixed
     */
    protected function _countDependent(Core_Model_Abstract $mappable,
                                       Core_Model_Relation_Dependent $relation, $aggregate = true,
                                       Zend_Db_Select $select = null)
    {
        $table = $relation->getTable();
        $rule  = $relation->getRule();
        if (is_array($table) === true) {
            $table = $table[0];
            $rule  = $rule[0];
        }
        
        $tableObj = new $table;
        $rInfo    = (object) $tableObj->getReference($this->getDbTable(), $rule);
        $groupBy  = implode($rInfo->columns);
        
        if (is_null($select) === true) {
            $select = $this->getDbTableObj()->select();
        } else {
            $select = clone $select;
        }
        
        try {
            $info = $tableObj->info();
            $name = $info[Zend_Db_Table_Abstract::NAME];
            $select->from('', array('autocount' => 'COUNT(*)'), $name)->limit(1);
        } catch (Zend_Db_Select_Exception $e) {
            $select->from($tableObj, array('autocount' => 'COUNT(*)'))->limit(1);
        }
        
        $select->group($groupBy);
        
        $count = 0;
        
        $rows = $this->_getDependentRows($mappable, $relation, $select);
        if (is_array($rows) === true) {
            if ($aggregate === false) {
                $models = $relation->getModel();
                $count  = array();
                foreach ($models as $key => $model) {
                    $row           = $rows[$key];
                    $count[$model] = $row->current()->autocount;
                }
            } else {
                foreach ($rows as $row) {
                    $count += $row->autocount;
                }
            }
        } else if (is_null($rows) === false) {
            $count = $rows->current()->autocount;
        }
        
        return $count;
    }
    
    /**
     * Get rows for a dependent relationship
     *
     * @param  Core_Model_Abstract           $mappable The Object to search by
     * @param  Core_Model_Relation_Dependent $relation The relation object
     * @param  Zend_Db_Select                $select   (Optional) Limit the result set
     * @param  string                        $modelKey (Optional) Limit to one model
     * @return mixed
     */
    protected function _getDependentRows(Core_Model_Abstract $mappable, Core_Model_Relation_Dependent $relation,
                                          Zend_Db_Select $select, $modelKey = null)
    {
        $rows = array();
        if (is_null($this->_getCurrentRow($mappable)) === true) {
            return null;
        }
        
        // Get our model from the relation
        if ($modelKey === null) {
            $models = $relation->getModel();
        } else {
            $models = $modelKey;
        }
        
        if (is_array($models) === true) {
            foreach ($models as $model) {
                $modelObj = new $model;
                
                $localSelect = clone $select;
                
                // Get the Dbtable row
                $relationTable = $relation->getTable($model);
                $relationRule  = $relation->getRule($model);
                $rows[]        = $this->_getCurrentRow($mappable)->findDependentRowset(new $relationTable,
                                                                        $relationRule,
                                                                        $localSelect);
            }
        } else {
            $model    = $models;
            $modelObj = new $model;
            
            $localSelect = clone $select;
            
            // Get the Dbtable row
            $relationTable = $relation->getTable($model);
            $relationRule  = $relation->getRule($model);
            $rows          = $this->_getCurrentRow($mappable)->findDependentRowset(new $relationTable,
                                                                    $relationRule,
                                                                    $localSelect);
        }
        
        return $rows;
    }
    
    /**
     * Get reference objects
     *
     * @param  Core_Model_Abstract           $mappable The Model bject searching by
     * @param  Core_Model_Relation_Reference $relation The relation object
     * @return mixed
     */
    protected function _getReferenceObjects(Core_Model_Abstract $mappable, Core_Model_Relation_Reference $relation)
    {
        // Get our model from the relation
        $modelClass = $relation->getModel();
        $return     = null;
        
        if (is_null($this->_getCurrentRow($mappable)) === true) {
            if (is_array($modelClass) === false) {
                return $return;
            } else {
                return null;
            }
        }
        
        if (is_array($modelClass) === false) {
            $modelClass = array($modelClass);
        }
        
        foreach ($modelClass as $model) {
            // Get the Dbtable row
            $relationTable = $relation->getTable($model);
            $relationRule  = $relation->getRule($model);
            $rInfo         = (object) $this->getDbTableObj()->getReference($relationTable, $relationRule);
            $columns       = $rInfo->columns;
            foreach ($columns as $column) {
                if (is_null($this->_getCurrentRow($mappable)->$column) === true) {
                    // This model is not referenced by this row
                    continue 2;
                }
            }
            
            $row = $this->_getCurrentRow($mappable)->findParentRow($relationTable, $relationRule);
            
            if (is_null($row) === false) {
                $model = $this->populate($model, $row);
                return $model;
            }
        }
        
        return new Core_Model_Empty();
    }
    
    /**
     * Get Many to many objects
     *
     * @param  Core_Model_Abstract            $mappable The Model object searching by
     * @param  Core_Model_Relation_ManyToMany $relation The relation object
     * @param  Zend_Db_Select                 $select   (Optional) Limit the result set
     * @return mixed
     */
    protected function _getManyToManyObjects(Core_Model_Abstract $mappable, Core_Model_Relation_ManyToMany $relation,
                                             Zend_Db_Select $select = null)
    {
        $return = array();
        $found  = array();
        
        // If _currentRow isn't populated return an empty array
        if (is_null($this->_getCurrentRow($mappable)) === true) {
            return $return;
        }
        
        if (is_null($select) === true) {
            $select = $this->_getCurrentRow($mappable)->select();
        } else {
            $select->setTable($this->_getCurrentRow($mappable)->getTable());
        }
        
        // Get the limit/offset from the select
        $limit  = $select->getPart('limitcount');
        $offset = $select->getPart('limitoffset');
        
        $models = $relation->getModel();

        if (is_array($models) === true) {
            $totals = $this->_countManyToMany($mappable, $relation, false, $select);
            
            foreach ($models as $model) {
                // Clone the select
                $currentSelect = clone $select;
                
                if (is_null($limit) === false) {
                    if ($offset === null) {
                        $offset = 0;
                    } else if (($offset >= $totals[$model]) === true) {
                        $offset = ($offset - $totals[$model]);
                        continue;
                    }
                    
                    $count = count($return);
                    if ($count === $limit) {
                        // We already reached our limit in a previous iteration
                        break;
                    } else {
                        $limit           = ($limit - $count);
                        $previouslyFound = array_sum($found);
                        $offset          = ($offset - $previouslyFound);
                        // Don't let offset go below 0
                        if ($offset < 0) {
                            $offset = 0;
                        }
                    }
                    
                    $currentSelect->limit($limit, $offset);
                }
                
                $modelObj = new $model;
                
                $rows = $this->_getManyToManyRows($mappable, $relation, $currentSelect, $model);

                foreach ($rows as $row) {
                    $return[] = $this->populate($model, $row);
                }
                
                if (is_null($limit) === false) {
                    $found[$model] = $rows->count();
                }
            }
        } else {
            $model = $models;
            
            // Clone the select
            $currentSelect = clone $select;
            
            $rows = $this->_getManyToManyRows($mappable, $relation, $currentSelect, $model);
            foreach ($rows as $row) {
                $return[] = $this->populate($model, $row);
            }
        }
        
        return $return;
    }
    
    /**
     * Count many to many rows
     *
     * @param  Core_Model_Abstract            $mappable  Mappable
     * @param  Core_Model_Relation_ManyToMany $relation  The relation object
     * @param  boolean                        $aggregate (Optional) If false, return 
     *                                                   an array of counts by model
     * @param  Zend_Db_Table_Select           $select    (Optional) Select
     * @return mixed
     */
    protected function _countManyToMany(Core_Model_Abstract $mappable,
                                        Core_Model_Relation_ManyToMany $relation, $aggregate = true,
                                        Zend_Db_Table_Select $select = null)
    {
        $joinDbTable = $relation->getJoinDbTable();
        
        $joinDbTable = new $joinDbTable;
        $lInfo       = (object) $joinDbTable->getReference($this->getDbTable(),
                                                  $relation->getLocalRule());
        $columns     = array();
        foreach ($lInfo->columns as $col) {
            $columns[] = "i.$col";
        }
        
        $groupBy = implode($columns);
        
        if (is_null($select) === true) {
            $select = $this->_getCurrentRow($mappable)->select();
        } else {
            $select = clone $select;
            $select->setTable($this->_getCurrentRow($mappable)->getTable());
        }
        
        $select->group($groupBy);
        $select->from('', array('autocount' => 'COUNT(*)'))->limit(1);
        
        $count = 0;
        
        $rows = $this->_getManyToManyRows($mappable, $relation, $select);
        if (is_array($rows) === true) {
            if ($aggregate === false) {
                $models = $relation->getModel();
                $count  = array();
                foreach ($models as $key => $model) {
                    $row        = $rows[$key];
                    $currentRow = $row->current();
                    if (is_null($currentRow) === false) {
                        $count[$model] = $currentRow->autocount;
                    } else {
                        $count[$model] = 0;
                    }
                }
            } else {
                foreach ($rows as $row) {
                    $currentRow = $row->current();
                    if (is_null($currentRow) === false) {
                        $count += $currentRow->autocount;
                    }
                }
            }
        } else if (count($rows) > 0 and $rows instanceof Zend_Db_Table_Rowset ) {
            $count = $rows->current()->autocount;
        }

        return $count;
    }
    
    /**
     * Get rows for a many to many relationship
     *
     * @param  Core_Model_Abstract            $mappable Mappable
     * @param  Core_Model_Relation_ManyToMany $relation The relation object
     * @param  Zend_Db_Select                 $select   (Optional) Limit the result set
     * @param  string                         $modelKey (Optional) Limit to one model
     * @return mixed
     */
    protected function _getManyToManyRows(Core_Model_Abstract $mappable, Core_Model_Relation_ManyToMany $relation,
                                          Zend_Db_Select $select, $modelKey = null)
    {
        $rows = array();
        // Get our model from the relation
        if ($modelKey === null) {
            $models = $relation->getModel();
        } else {
            $models = $modelKey;
        }
        
        $localRule   = $relation->getLocalRule();
        $joinDbTable = $relation->getJoinDbTable();
        
        if (is_array($models) === true) {
            foreach ($models as $model) {
                $modelObj = new $model;
                
                $localSelect = clone $select;
                // Get the Dbtable row
                $relationTable = $relation->getTable($model);
                $relationRule  = $relation->getRule($model);
                $rows[]        = $this->_getCurrentRow($mappable)->findManyToManyRowset(new $relationTable,
                                                                        $joinDbTable,
                                                                        $localRule,
                                                                        $relationRule,
                                                                        $localSelect);
            }
        } else {
            $model    = $models;
            $modelObj = new $model;
            
            // Get the Dbtable row
            $relationTable = $relation->getTable($model);
            $relationRule  = $relation->getRule($model);
            $rows          = $this->_getCurrentRow($mappable)->findManyToManyRowset(new $relationTable,
                                                                    $joinDbTable,
                                                                    $localRule,
                                                                    $relationRule,
                                                                    $select);
        }
        
        return $rows;
    }
    
    /**
     * Get relation objects based on the relation
     *
     * @param  Core_Model_Abstract          $mappable Model object you are search by
     * @param  Core_Model_Relation_Abstract $relation The relation object
     * @param  Zend_Db_Select               $select   (Optional) Limit the result set
     * @return mixed
     */
    protected function _getObjectsByRelation(Core_Model_Abstract $mappable, Core_Model_Relation_Abstract $relation,
                                             Zend_Db_Select $select = null)
    {
        if (is_object($mappable) === false) {
            $mappable = new $mappable;
        }
        
        switch ($relation->getType()) {
            case self::DEPENDENT:
                return $this->_getDependentObjects($mappable, $relation, $select);
                break;

            case self::DEPENDENT_ONETOONE:
                return $this->_getDependentObjects($mappable, $relation, $select);
                break;

            case self::REFERENCE:
                // Limit and offset aren't needed, ignore them
                $model = $this->_getReferenceObjects($mappable, $relation);
                return $model;
                break;

            case self::MANYTOMANY:
                return $this->_getManyToManyObjects($mappable, $relation, $select);
                break;

            default:
                // Do nothing with unknown relations
                break;
        }
    }
    
    /**
     * Count objects based on the relation
     *
     * @param  Core_Model_Abstract          $mappable  The Mappable we are searching by
     * @param  Core_Model_Relation_Abstract $relation  The relation object
     * @param  boolean                      $aggregate (Optional) If false, return
     *                                                 an array of counts by model
     * @param  Zend_Db_Table_Select         $select    (Optional) Select
     * @return mixed
     */
    protected function _countObjectsByRelation(Core_Model_Abstract $mappable,
                                               Core_Model_Relation_Abstract $relation, $aggregate = true,
                                               Zend_Db_Table_Select $select = null)
    {
        switch ($relation->getType()) {
            case self::DEPENDENT:
                return $this->_countDependent($mappable, $relation, $aggregate, $select);
                break;
            
            case self::DEPENDENT_ONETOONE:
                // Break intentionally omitted
            case self::REFERENCE:
                return 1;
                break;
            
            case self::MANYTOMANY:
                return $this->_countManyToMany($mappable, $relation, $aggregate, $select);
                break;
            
            default:
                // Do nothing with unknown relations
                break;
        }
    }
    
    /**
     * Return a Zend_Paginator object using methods
     *
     * @param  mixed                        $itemsMethod (Optional) Method used to get items
     * @param  mixed                        $countMethod (Optional) Method used to count items
     * @param  Core_Model_Abstract          $mappable    The Mappable we are searching by
     * @param  Core_Model_Relation_Abstract $relation    (Optional) The relation object
     * @param  Zend_Db_Select               $select      (Optional) Select object
     * @return Zend_Paginator
     */
    public function getPaginator($itemsMethod, $countMethod, Core_Model_Abstract $mappable,
                                 Core_Model_Relation_Abstract $relation = null,
                                 Zend_Db_Select $select = null)
    {
        return new Core_Paginator(
            new Core_Paginator_Adapter_MapperRelation(array($this, $itemsMethod),
                                                      array($this, $countMethod), $mappable, $relation, $select));
    }
    
    /**
     * Get a paginator for a relation
     *
     * @param  Core_Model_Abstract  $mappable  The Mappable we are searching by
     * @param  string               $property  The property name
     * @param  mixed                $relation  The relation object
     * @param  integer              $limit     The limit
     * @param  integer              $offset    The offset
     * @param  boolean              $paginator (Optional) Returning paginator or array
     * @param  Zend_Db_Table_Select $select    (Optional) Returning paginator or array
     * @return mixed
     */
    protected function _getPaginatorOrObjects(Core_Model_Abstract $mappable,
                                              $property,
                                              $relation,
                                              $limit,
                                              $offset,
                                              $paginator = false,
                                              Zend_Db_Table_Select $select = null)
    {
        $table = $this->getDbTableObj();
        
        if (is_null($select) === true) {
            $select = $table->select();
        } else {
            if ($select->getTable() !== $table) {
                // Need to throw exception if tables aren't the same
            }
        }
        
        if ($paginator === null) {
            $paginator = false;
        }
        
        unset($table);
        // References don't support paginators
        if (($paginator === false)
            or ($relation->getType() === self::REFERENCE)
            or ($relation->getType() === self::DEPENDENT_ONETOONE)) {
            if (is_null($limit) === false) {
                $select = $select->limit($limit, $offset);
            }
            
            return $this->_getObjectsByRelation($mappable, $relation, $select);
        }
        
        return $this->getPaginator('get' . ucfirst($property),
                                    'count' . ucfirst($property), $mappable,
                                    $relation, $select);
    }

    /**
     * Find Related Objects
     *
     * @param  Core_Model_Abstract          $mappable Mapabble to find related objects from
     * @param  Core_Model_Relation_Abstract $relation Relation to current model
     * @param  string                       $property (Optional) Property
     * @return mixed
     */
    public function findRelated(Core_Model_Abstract $mappable, Core_Model_Relation_Abstract $relation, $property = null)
    {
        $method = 'get' . ucfirst($property);
        if (method_exists($this, $method) === true) {
            return $this->$method($mappable);
        }
        
        $related = $this->_getObjectsByRelation($mappable, $relation);

         return $related;
    }
    
    /**
     * Magic __call
     *
     * @param  string $method    The method that was called
     * @param  array  $arguments The arguments passed to the method call
     * @throws Core_Model_Mapper_DbTable_Exception If unrecognized method
     * @throws Zend_Exception                      If Mapppable not passed
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        $recognized = null;
        // Recognize getRelationship(perPage, offset, paginator)
        if (preg_match('/^get(\w+)$/', $method, $matches) === 1) {
            $property   = strtolower(substr($matches[1], 0, 1)) . substr($matches[1], 1);
            $recognized = 'get';
        }
        
        // Recognize gettting a Paginator
        if (preg_match('/^get(\w+)Paginator$/', $method, $matches) === 1) {
            $property   = strtolower(substr($matches[1], 0, 1)) . substr($matches[1], 1);
            $recognized = 'getPaginator';
        }
        
        // Recognize gettting a Paginator
        if (preg_match('/^isIn(\w+)$/', $method, $matches) === 1) {
            $property   = strtolower(substr($matches[1], 0, 1)) . substr($matches[1], 1);
            $recognized = 'hasRelation';
        }
        
        // Recognize gettting a Paginator
        if (preg_match('/^remove(\w+)Relation$/', $method, $matches) === 1) {
            $property   = strtolower(substr($matches[1], 0, 1)) . substr($matches[1], 1);
            $recognized = 'removeRelation';
        }
        
        // Recognize countRelationship()
        if (preg_match('/^count(\w+)$/', $method, $matches) === 1) {
            $property   = strtolower(substr($matches[1], 0, 1)) . substr($matches[1], 1);
            $recognized = 'count';
        }
        
        if (isset($arguments[0]) === false) {
            throw new Zend_Exception('A Mappable must be passed to the function call __get(' . $method . ')'  );
        }
        
        // Check the relationship int the map
        @list($mappable, $limit, $offset, $paginator, $select) = $arguments;
        
        $map = $mappable->getMap();
        
        if (array_key_exists($property, $map) === true) {
            $relation = $map[$property];
            if (($relation instanceof Core_Model_Relation_Abstract) === true) {
                switch ($recognized) {
                    case 'get':
                        // Expected args are limit, offset, and paginator
                        @list($mappable, $limit, $offset, $paginator, $select) = $arguments;
                        return $this->_getPaginatorOrObjects($mappable, $property,
                                                    $relation,
                                                    $limit,
                                                    $offset,
                                                    $paginator, $select);
                        break;
                    
                    case 'hasRelation':
                        @list($obj, $relatedObj) = $arguments;
                        return $this->_hasRelatedObject($obj, $relatedObj, $relation, $property);
                        break;
                    
                    case 'removeRelation':
                        @list($obj,$relatedObj) = $arguments;
                        return $this->_removeRelatedObject($obj, $relatedObj, $relation, $property);
                        break;
                    
                    case 'getPaginator':
                        // Expected args are limit, offset, and paginator
                        @list($mappable, $limit, $offset, $select) = $arguments;
                        return $this->_getPaginatorOrObjects($mappable, $property,
                                                    $relation,
                                                    $limit,
                                                    $offset,
                                                    true, $select);
                        break;
                    
                    case 'count':
                        @list($mappable, $select) = $arguments;
                        return $this->_countObjectsByRelation($mappable, $relation, true, $select);
                        break;
                    
                    default:
                        // Do nothing here
                        break;
                }
            } else {
                // Item is a standard property
                switch ($recognized) {
                    case 'get':
                        $column = $relation;
                        return $this->_getColumnFromRow($column);
                        break;
                    
                    default:
                        return null;
                        break;
                }
            }
        }
        
        throw new Core_Model_Mapper_DbTable_Exception("Unrecognized method '$method()'");
    }
    
    /**
     * Check Current row and get column
     * 
     * @param  Zend_Db_Table_Row $row    Row
     * @param  string            $column Column 
     * @return mixed
     */
    private function _getColumnFromRow(Zend_Db_Table_Row $row, $column)
    {
        if (is_null($row) === false) {
            return $row->$column;
        } else {
            return null;
        }
    }
    
    /**
     * Get Table Column from map
     * 
     * @param  Core_Model_Abstract $mappable Key to lookup
     * @param  string              $key      Key to lookup
     * @throws Zend_Exception Thows exception if looking up a property that doesn't exist
     * @return string
     */
    public function getPropertyByColumn(Core_Model_Abstract $mappable, $key)
    {
        $this->_validateMap($mappable);
        $modelProperty = array_search($key, $mappable->getMap());
        if ($modelProperty !== true) {
            return $modelProperty;
        } else {
            throw new Zend_Exception('Map key does not exist');
        }
        
    }
    
    /**
     * Wake Up  reset current row after unserialization
     * 
     * @return void
     */
    public function __wakeup()
    {
        $this->_currentRow = null;
    }
}