<?php
/**
 * Ooba_Model_Storage_Adapter_ArrayAccess
 *
 * @category   Ooba
 * @package    Model
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Storage_Adapter_ArrayAccess
{
    /**
     * Push a value on a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to add
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function push($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null);
    
    /**
     * Push many values 
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Values to add as individual array elements
     * @param  array|null         $options   (Optional) Options used for running commands
     * @throws Ooba_Model_Storage_Adpapter_Exception_InvalidData If you try to incemrent multiple model
     * @return boolean
     */
    public function pushAll($nameSpace, Ooba_Storage_Query $query, $field, array $values, $options = null);
    
    /**
     * Pull a value from an array
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to remove
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function pull($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null);
    
    /**
     * Pull many values from an array
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  array              $values    Value to add
     * @param  array|null         $options   (Optional) Options used for running commands
     * @throws Ooba_Model_Storage_Adpapter_Exception_InvalidData If you try to incemrent multiple model
     * @return boolean
     */
    public function pullAll($nameSpace, Ooba_Storage_Query $query, $field, array $values, $options = null);
    
    /**
     * Pop off a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to update
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value 1 to remove last element  -1 to remove first element
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function pop($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null);
    
    /**
     * Add values if they don't exist
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to add
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function addToSet($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null);
}
