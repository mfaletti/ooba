<?php
/**
 * Ooba_Application_Resource_Model.php
 *
 * @category   Core
 * @package    Core_Resource
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 *
 */

class Ooba_Application_Resource_Model extends Ooba_Application_Resource_Abstract
{
    /**
     *
     * @return void
     */
    public function init()
    {
        $constantsKeeper = ConstantsKeeper::getInstance();
		Core_Model_Loader::setNameSpaces(array($constantsKeeper->modelNameSpaces));
		
		if (isset($constantsKeeper->storage) === true and empty($constantsKeeper->storage) === false) {
	    	Ooba_Model_Storage::setDefaultAdapters(explode(',', $constantsKeeper->storage));
	    }
	
		if (isset($constantsKeeper->writeTypes) === true and empty($constantsKeeper->writeTypes) === false) {
	    	Ooba_Model_Storage::setDefaultWriteTypes(explode(',', $constantsKeeper->writeTypes));
	    }
	
		if (isset($constantsKeeper->namespaces) === true and empty($constantsKeeper->namespaces) === false) {
	    	Ooba_Model_Storage::setDefaultNameSpaces(explode(',', $constantsKeeper->modelNameSpaces));
	    }
	
		if (isset($constantsKeeper->iteratorNamespaces) === true and empty($constantsKeeper->iteratorNamespaces) === false) {
			Ooba_Model_Iterator::setDefaultNamespaces(explode(',', $constantsKeeper->iteratorNamespaces));
	    }

		if (isset($constantsKeeper->defaultstorage) === true and $constantsKeeper->defaultstorage === true) {
	    	Ooba_Model_Abstract::setDefaultStorage(new Ooba_Model_Storage());
		}
	
		if (isset($constantsKeeper->namespaceprefix) === true) {
	    	Ooba_Model_Abstract::setDefaultNamespacePrefix($constantsKeeper->namespaceprefix);
	    }
    }
}

?>