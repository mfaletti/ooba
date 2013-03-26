<?php
/**
 * AssetUrl.php
 *
 * @category   Core
 * @package    Core_Resource
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Asseturl extends Ooba_Application_Resource_Abstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'Asseturl';
    
    /**
     * Defined by Ooba_Application_Resource_Abstract
     *
     * @return void
     */
    public function init()
    {
        $options = array_change_key_case($this->getOptions(), CASE_LOWER);
        
        if (count($options) > 0) {
            Ooba_View_Helper_AssetUrl::setOptions($options);
        }
    }
}
