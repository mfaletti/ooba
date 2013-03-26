<?php
/**
 * Ooba_Application_Resource_Solr
 *
 * @category   Ooba
 * @package    Application_Resource
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Solr extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'Solr';

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return void
     */
    public function init()
    {
        $options = $this->getOptions();
        if (isset($options['queryHost']) === true) {
            Ooba_Storage_Solr::setDefaultQueryEndpoint($options['queryHost']);
        }

        if (isset($options['indexHosts']) === true) {
            Ooba_Storage_Solr::setDefaultIndexEndpoints(explode(',', $options['indexHosts']));
        }

        if (isset($options['vertical']) === true) {
            Ooba_Storage_Solr::setDefaultContext($options['vertical']);
        }
    }
}
