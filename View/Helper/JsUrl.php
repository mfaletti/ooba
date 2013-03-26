<?php
/**
 * Ooba_View_Helper_JsUrl
 *
 * @category   Ooba
 * @package    View
 * @subpackage Helper
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 *
 */
class Ooba_View_Helper_JsUrl extends Ooba_View_Helper_AssetUrl
{
    /**
     * Js directory
     *
     * @var string
     */
    public static $js = 'js';
    
    /**
     * Construct
     *
     * @param Zend_Config|array $options (Optional) Options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
    }
    
    /**
     * Return the fully qualified url to the Javascript file
     *
     * @param  string $assetPath The path to the asset
     * @return string
     */
    public function jsUrl($assetPath)
    {
        $url = self::$domain . self::URI_SEPARATOR
             . self::$prefix . self::URI_SEPARATOR
             . self::$js . self::URI_SEPARATOR . $assetPath;
        
        // Remove double slashes
        $url = preg_replace('#//+#', '/', $url);
        
        return self::$protocol . '://' . $url;
    }
}