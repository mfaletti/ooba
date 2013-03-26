<?php
/**
 * Core_Model_Mappable_Interface
 *
 * @category   Ooba
 * @package    Model
 * @subpackage Mappable
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 * @version   1
 */
interface Ooba_Model_Mappable_Interface
{
    /**
     * Return an associative array of MapperTypes => MapperObjects
     *
     * @return array
     */
    public function getMappers();

    /**
     * Add a mapper
     *
     * @param  Core_Model_Mapper_Abstract $mapper The mapper object
     * @throws Core_Model_Exception_DuplicateMapper If duplicate type
     * @return Core_Model_Abstract
     */
    public function addMapper(Core_Model_Mapper_Abstract $mapper);

    /**
     * Return a specific mapper by type
     *
     * @param  mixed $type The type to find
     * @return Core_model_Mapper_Abstract
     **/
    public function getMapper($type);

    /**
     * Determine if model has mapper of given type
     *
     * @param  mixed $type The type to find
     * @return boolean
     **/
    public function hasMapper($type);

    /**
     * Get Map from mappable
     *
     * @return array
     **/
    public function getMap();
}
?>