<?php
/**
 * Ooba_View_Helper_ImageTag.php
 *
 * @category   Ooba
 * @package    Ooba_View
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_View_Helper_ImageTag extends Zend_View_Helper_HtmlElement
{
    /**
     * Generate an 'img' element
     *
     * @param  string $path 
     * @param  array  $attribs (Optional) Attributes for the element tag.
     * @return string The element XHTML.
     */
    public function imageTag($path, array $attribs = null)
    {
        $src = null;
        if (strtolower(substr($path, 0, 4)) === 'http') {
            $src = $path;
        } else {
            $plugin = new Ooba_View_Helper_ImageUrl();
            $src    = $plugin->imageUrl($path);
        }
        
        return '<img src="' . $this->view->escape($src) . '"'
               . $this->_htmlAttribs($attribs)
               . $this->getClosingBracket();
    }
}
