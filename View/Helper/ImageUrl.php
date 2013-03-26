<?php
/**
 * Ooba_View_Helper_ImageUrl.php
 *
 * @category   Ooba
 * @package    Ooba_View
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_View_Helper_ImageUrl extends Ooba_View_Helper_AssetUrl
{
    /**
     * Image directory
     *
     * @var string
     */
    public static $image = 'images';
    
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
     * Return the fully qualified url to the image
     *
     * @param  string $assetPath The path to the asset
     * @return string
     */
    public function imageUrl($assetPath)
    {
        $url = self::$domain . self::URI_SEPARATOR
             . self::$prefix . self::URI_SEPARATOR
             . self::$image . self::URI_SEPARATOR . $assetPath;
        
        // Remove double slashes
        $url = preg_replace('#//+#', '/', $url);
        
        return self::$protocol . '://' . $url;
    }
}
