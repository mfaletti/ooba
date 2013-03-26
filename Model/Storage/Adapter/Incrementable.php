<?php
/**
 * Ooba_Model_Storage_Adapter_Incrementable
 *
 * @category   Ooba
 * @package    OobaModel
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Storage_Adapter_Incrementable
{
    /**
     * Increment a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  integer            $n         (Optional) Nnumber to increment by
     * @param  array|null         $options   (Optional) Options used for running commands
     * @return boolean
     */
    public function increment($nameSpace, Ooba_Storage_Query $query, $field, $n = 1, $options = null);
    
    /**
     * Decrement a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  integer            $n         (Optional) Nnumber to decrement by
     * @param  array|null         $options   (Optional) Options used for running commands
     * @throws Ooba_Model_Storage_Adpapter_Exception_InvalidData If you try to incemrent multiple model
     * @return boolean
     */
    public function decrement($nameSpace, Ooba_Storage_Query $query, $field, $n = 1, $options = null);
}
