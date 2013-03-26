<?php
/**
 * Ooba_Model_Storage_Adapter_Settable
 *
 * @category   Ooba
 * @package    Model_Storage
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
interface Ooba_Model_Storage_Adapter_Settable
{
    /**
     * Set a property of a model
     *
     * @param  string             $nameSpace Namespace
     * @param  Ooba_Storage_Query $query     Conditions of documents to udpate
     * @param  string             $field     Property of model to change
     * @param  mixed              $value     Value to set the field to
     * @param  array|null         $options   (Optional) Options to pass to command
     * @return boolean
     */
    public function set($nameSpace, Ooba_Storage_Query $query, $field, $value, $options = null);
}
