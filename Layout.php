<?php
/**
 * Provide Layout support for MVC applications
 * @category Ooba
 * @package Layout
 * @author Michael Faletti <github.com/mfaletti>
 *
 *
 */
class Ooba_Layout
{
	/**
     * Placeholder container for layout variables
     */
    protected $_container = array();
	
	/**
     * Key used to store content from 'default' named response segment
     * @var string
     */
    protected $_contentKey = 'content';

	/**
     * Layout view
     * @var string
     */
    protected $_layout = 'layout';

    /**
     * Layout view script path
     * @var string
     */
    protected $_viewScriptPath = null;

    /**
     * @var Ooba_View
     */
    protected $_view;

    /**
     * View script suffix for layout script
     * @var string
     */
    protected $_viewSuffix = 'phtml';

    /**
     * @var Ooba_Layout
     */
    protected static $_instance;

	public function __construct($options = null)
	{
		if (null !== $options) {
            if (is_string($options)) {
                $this->setLayoutPath($options);
            } elseif (is_array($options)) {
                $this->setOptions($options);
            } else {
                throw new Ooba_Exception('Invalid option provided to Layout() constructor');
            }
        }
	}
	
	/**
     * Static method for initialization
     *
     * @param  string|array $options
     * @return Ooba_Layout
     */
    public static function startMvc($options = null)
    {
        if (null === self::$_instance) {
            self::$_instance = new self($options);
        }

        if (is_string($options)) {
            self::$_instance->setLayoutPath($options);
        } elseif (is_array($options)) {
            self::$_instance->setOptions($options);
        }

        return self::$_instance;
    }

   /**
     * Retrieve instance of Ooba_Layout object
     *
     * @return Ooba_Layout|null
     */
    public static function getInstance()
    {
        return self::$_instance;
    }

    /**
     * Set options en masse
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function setOptions($options)
    {
		if (!is_array($options)) {
            throw new Ooba_Exception('Layout setOptions() expects an array');
        }

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

	/**
     * Set layout script to use
     *
     * Note: enables layout by default, can be disabled
     *
     * @param  string $name
     * @param  boolean $enabled
     * @return Ooba_Layout
     */
    public function setLayout($name)
    {
        $this->_layout = (string) $name;
        return $this;
    }
    
	/**
     * Get current layout script
     *
     * @return string
     */
    public function getLayout()
    {
		if (null === $this->_layout) {
            $this->_layout = Ooba_Layout::getInstance();
            if (null === $this->_layout) {
                // Implicitly creates layout object
                $this->_layout = new Ooba_Layout();
            }
        }
    	return $this->_layout;
    }

    /**
     * Set layout script path
     *
     * @param  string $path
     * @return Ooba_Layout
     */
    public function setLayoutPath($path)
    {
        $this->_viewScriptPath = $path;
		return $this;
    }

    /**
     * Get current layout script path
     *
     * @return string
     */
    public function getLayoutPath()
    {
        return $this->_viewScriptPath;
    }

    /**
     * Set layout view script suffix
     *
     * @param  string $viewSuffix
     * @return Ooba_Layout
     */
    public function setViewSuffix($viewSuffix)
    {
        $this->_viewSuffix = (string) $viewSuffix;
        return $this;
    }

    /**
     * Retrieve layout view script suffix
     *
     * @return string
     */
    public function getViewSuffix()
    {
        return $this->_viewSuffix;
    }

    /**
     * Set view object
     *
     * @param  Ooba_View $view
     * @return Ooba_Layout
     */
    public function setView(Ooba_View $view)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * Get current view object
     *
     * If no view object currently set, retrieves it from the Action Controller.
     *
     * @return Ooba_View
     */
    public function getView()
    {
        return $this->_view;
    }

	/**
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
	/*public function render()
	{
		$this->_file = $this->getLayoutPath();
		$this->_file.= '/layout.phtml';
		
		ob_start();
		include $this->_file;
		return ob_get_clean();
	}*/
	
	/**
     * Render layout
     *
     * Sets internal script path as last path on script path stack, assigns
     * layout variables to view, determines layout name using inflector, and
     * renders layout view script.
     *
     * $name will be passed to the inflector as the key 'script'.
     *
     * @param  mixed $name
     * @return mixed
     */
    public function render($name = null)
    {
        if (null === $name) {
            $name = $this->getLayout();
        }

        $view = $this->getView();

        if (null !== ($path = $this->getViewScriptPath())) {
            if (method_exists($view, 'addScriptPath')) {
                $view->addScriptPath($path);
            } else {
                $view->setScriptPath($path);
            }
        } elseif (null !== ($path = $this->getViewBasePath())) {
            $view->addBasePath($path, $this->_viewBasePrefix);
        }

        return $view->render($name);
    }

    /**
     * Set layout variable
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_container[$key] = $value;
    }

    /**
     * Get layout variable
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_container[$key])) {
            return $this->_container[$key];
        }

        return null;
    }

    /**
     * Is a layout variable set?
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return (isset($this->_container[$key]));
    }

    /**
     * Unset a layout variable?
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        if (isset($this->_container[$key])) {
            unset($this->_container[$key]);
        }
    }
}
?>