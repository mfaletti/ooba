<?php
/**
 * Ooba_Application_Resource_Id
 *
 * @category   Core
 * @package    Core_Resource
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Id extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'Id';
    
    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return void
     */
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);
        
        if (isset($options['prefix']) === true) {
            Ooba_Id::setPrefix($options['prefix']);
        }
    }
}
