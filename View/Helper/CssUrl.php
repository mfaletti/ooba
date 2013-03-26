<?php
/**
 * Ooba_View_Helper_CssUrl.php
 *
 * @category   Ooba
 * @package    Ooba_View
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_View_Helper_CssUrl extends Ooba_View_Helper_AssetUrl
{
    /**
     * CSS directory
     *
     * @var string
     */
    public static $css = 'css';
    
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
     * Return the fully qualified url to the stylesheet
     *
     * @param  string $assetPath The path to the asset
     * @return string
     */
    public function cssUrl($assetPath)
    {
        $url = self::$domain . self::URI_SEPARATOR
             . self::$prefix . self::URI_SEPARATOR
             . self::$css . self::URI_SEPARATOR . $assetPath . self::$postfix;
        
        // Remove double slashes
        $url = preg_replace('#//+#', '/', $url);
        return self::$protocol . '://' . $url;
    }
}
