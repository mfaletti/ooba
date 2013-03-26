<?php
/**
 * Ooba_Application_Resource_Memcache
 *
 * @category   Core
 * @package    Core_Resource
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Memcache extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'Memcache';
    
    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return void
     */
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);
        
        $memcachedServers = array();
        foreach (explode(' ', $options['hosts']) as $server) {
            $serverSpecs        = explode(':', $server);
            $memcachedServers[] = array(
                'host' => $serverSpecs[0],
                'port' => $serverSpecs[1]
            );
        }
        
        Ooba_Model_Storage_Adapter_Memcache::setDefaultHosts($memcachedServers);
      
        if (isset($options['default-lifetime']) === true) {
            Ooba_Model_Storage_Adapter_Memcache::setDefaultLifetime($options['default-lifetime']);
        }
    }
}
