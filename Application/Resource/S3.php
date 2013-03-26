<?php
/**
 * S3.php
 *
 * @category   Core
 * @package    Core_Resource
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_S3 extends Ooba_Application_Resource_Abstract
{
    /**
     * Explicit type
     *
     * @var string
     */
    public $_explicitType = 'S3';

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return void
     */
    public function init()
    {
        $constantsKeeper = ConstantsKeeper::getInstance();
		if (isset($constantsKeeper->awsBucket)) {
			Ooba_View_Helper_S3Url::setDefaultBucket($constantsKeeper->awsBucket);
	        Ooba_Storage_S3::setDefaultBucket($constantsKeeper->awsBucket);
	        Zend_Registry::set('defaultS3Bucket', $constantsKeeper->awsBucket);
		}

		if (isset($constantsKeeper->awsAccountKey) and isset($constantsKeeper->awsSecretKey)) {
            $awsKey       = $constantsKeeper->awsAccountKey;
            $awsSecretKey = $constantsKeeper->awsSecretKey;
            Ooba_Storage_S3::setDefaultAwsKey($constantsKeeper->awsAccountKey);
            Ooba_Storage_S3::setDefaultAwsSecretKey($constantsKeeper->awsSecretKey);
        }

        
        if (isset($constantsKeeper->mediaFileS3Bucket)) {
            Zend_Registry::set('mediafileS3Bucket', $constantsKeeper->mediaFileS3Bucket);
        } else if (isset($constantKeeper->awsBucket) === true) {
            Zend_Registry::set('mediafileS3Bucket', $constantKeeper->awsBucket);
        }
    }
}
