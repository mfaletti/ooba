<?php
/**
 * S3Url.php
 * Ooba_View_Helper_S3Url
 *
 * @category   Ooba
 * @package    Ooba_View
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 *
 */
class Ooba_View_Helper_S3Url extends Zend_View_Helper_Abstract
{
    /**
     * Default bucket to use
     *
     * @var string
     */
    static protected $_defaultBucket = 'default';

    /**
     * Get the S3 url with bucket
     *
     * @param  string $path   Path within S3
     * @param  string $bucket (Optional) Bucket to use
     * @return string
     */
    public function s3Url($path, $bucket = null)
    {
        if (is_null($bucket) === true) {
            $bucket = self::$_defaultBucket;
        }
        
        return "http://$bucket.s3.amazonaws.com/" . $path;
    }

    /**
     * Static function to set default bucket
     *
     * @param  string $defaultBucket The bucket to set as default
     * @return void
     */
    static public function setDefaultBucket($defaultBucket)
    {
        self::$_defaultBucket = strtolower($defaultBucket);
    }
}
