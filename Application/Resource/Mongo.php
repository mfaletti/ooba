<?
/**
 * Ooba_Application_Resource_Mongo
 *
 * @category   Ooba
 * @package    Application_Resources
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Mongo extends Ooba_Application_Resource_Abstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'Mongo';
    
    /**
     * Defined by Ooba_Application_Resource_Abstract
     *
     * @return void
     */
    public function init()
    {
        $constantsKeeper = ConstantsKeeper::getInstance();
        $options = array(
        	'hosts' => $constantsKeeper->mongoHost,
			'persist' => $constantsKeeper->mongoPersist,
        );

		if (isset($constantsKeeper->mongoReplicasetEnabled) and (bool) $constantsKeeper->mongoReplicasetEnabled === true) {
			$options['replset'] = $constantsKeeper->mongoReplicaset;
		}
		
		if (count($options) > 0) {
            Ooba_Storage_Mongo::setOptions($options);
        }
        
		if (isset($constantsKeeper->mongoDefaultDb)) {
			Ooba_Model_Storage_Adapter_Mongo::setDb($constantsKeeper->mongoDefaultDb);
		}
		
        if (isset($constantsKeeper->mongoProfilerEnabled) === true and
                  (bool) $constantsKeeper->mongoProfilerEnabled === true) {
            Ooba_Storage_Mongo::setProfilerEnabled(true);
        }
        
        if (isset($constantsKeeper->mongoProfilerDoStore) === true and
                  (bool) $constantsKeeper->mongoProfilerDoStore === true) {
            Ooba_Storage_Mongo::setProfilerDoStore(true);
        }
    }
}
