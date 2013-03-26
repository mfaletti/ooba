<?php
/**
 * AssetUrl.php
 * Ooba_View_Helper_AssetUrl
 *
 * @category   Ooba
 * @package    View
 * @subpackage Helper
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 *
 */
class Ooba_View_Helper_AssetUrl extends Zend_View_Helper_Abstract
{
    /**
     * Uri Separator
     *
     * @const string
     */
    const URI_SEPARATOR = '/';
    
    /**
     * Domain
     *
     * @var string
     */
    public static $domain;
    
    /**
     * Path prefix
     *
     * @var string
     */
    public static $prefix = '';
    
    /**
     * Path postfix
     *
     * @var string
     */
    public static $postfix = '';
    
    /**
     * Protocol
     *
     * @var string
     */
    public static $protocol = 'http';
    
    /**
     * Construct
     *
     * @param Zend_Config|array $options (Optional) Options
     */
    public function __construct($options = array())
    {
        self::setOptions($options);
        if (empty(self::$domain) === true) {
            self::$domain = ConstantsKeeper::getInstance()->constAsseturlDomain;

            if (self::$domain === '') {
                self::$domain = $_SERVER['SERVER_NAME'];
            }
        }
        
        if (empty(self::$postfix) === true) {
            self::$postfix = ConstantsKeeper::getInstance()->constAsseturlPostfix;
        }
    }
    
    /**
     * Return the fully qualified url to the asset
     *
     * @param  string $assetPath The path to the asset
     * @return string
     */
    public function assetUrl($assetPath = '')
    {
        if (func_num_args() === 0) {
            return $this;
        }

		$url = self::$domain . self::URI_SEPARATOR
             . self::$prefix . self::URI_SEPARATOR
             . $assetPath . self::$postfix;
        
        // Remove double slashes
        $url = preg_replace('#//+#', '/', $url);
        
        return self::$protocol . '://' . $url;
    }

	/**
     * Return the full domain path (without the postfix)
     * 
     * @return string
     */
    public function domainPath()
    {
        $url = self::$domain . self::URI_SEPARATOR
             . self::$prefix . self::URI_SEPARATOR;
        
        // Remove double slashes
        $url = preg_replace('#//+#', '/', $url);
        
        return self::$protocol . '://' . $url;
    }
    
    /**
     * Set the AssetUrl options
     *
     * @param  array $options Array of options for setup
     * @return void
     */
    public static function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            self::$$key = $value;
        }
    }
}
