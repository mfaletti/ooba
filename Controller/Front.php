<?php
/**
 * Front.php
 * @category Ooba
 * @package Ooba_Controller
 * @author Michael Faletti <github.com/mfaletti>
 *
 * Front Controller - Dissect HTTP request into constituents and figure out what the client is asking for.
 * Implements the singleton pattern. 
 */

class Ooba_Controller_Front
{
    /**
     * Const - No controller exception; controller does not exist
     */
    const EXCEPTION_NO_CONTROLLER = 'EXCEPTION_NO_CONTROLLER'; 

	/**
     * Const - No action exception; controller exists, but action does not
     */
    const EXCEPTION_NO_ROUTE = 'EXCEPTION_NO_ROUTE';
	
	/**
     * Const - No action exception; controller exists, but action does not
     */
    const EXCEPTION_NO_ACTION = 'EXCEPTION_NO_ACTION';

	/**
     * Base URL
     * @var string
     */
    protected $_baseUrl = null;
 
     /**
     * Directory|ies where controllers are stored
     *
     * @var string|array
     */
    protected $_controllerDirectory = array();

	/**
     * Controller
     * @var string
     */
    protected $_controller = 'index';

	/**
     * Controller key for retrieving controller from params
     * @var string
     */
    protected $_controllerKey = 'controller';

	/**
     * Action
     * @var string
     */
    protected $_action = 'index';

	/**
     * Action key for retrieving action from params
     * @var string
     */
    protected $_actionKey = 'action';

	/**
     * Module
     * @var string
     */
    protected $_module = 'index';

	/**
     * Module key for retrieving module from params
     * @var string
     */
    protected $_moduleKey = 'module';
 
     /**
     * Request object
     * @var Request
     */
    protected $_request = null;

     /**
     * Response object
     * @var Request
     */
    protected $_response = null;
 
     /**
     * Response object
     * @var Response
     */
    //protected $_response = null;
 
    /**
     * Singleton instance of the front controller
     *
     * @var Front
     */
    protected static $_instance = null;
 
     /**
     * Default controller
     * @var string
     */
    protected $_defaultController = 'index';
 
     /**
     * Default action
     * @var string
     */
    protected $_defaultAction = 'index';

	/**
    * Default module;
    * @var string
    */
	protected $_defaultModule = 'index';

    /**
     * Router object
     * @var Router
     */
    protected $_router = null;

	/** request parameters **/
	protected $_params = array();
	
	/** options for front controller **/
	protected $_options = array();
	
	/** 
	 * Display Exceptions or not
	 * @var bool
	 */
	public $displayExceptions = false;
       
     /**
     * Constructor
     *
     * marked protected to ensure only only one instance of the front controller is available in application
     * Instantiate using {@link getInstance()}; front controller is a singleton
     * object.
     *
     * @return void
     */
     protected function __construct()
     {
		$router = Ooba_Controller_Router::getInstance();
		$request = new Zend_Controller_Request_Http;
		$response = new Zend_Controller_Response_Http;
		$this->setRequest($request)
			 ->setResponse($response)
			 ->setRouter($router);
	}
       
    /**
     * Provides access to the singleton instance
     *
     * @return Front
     */
	public static function getInstance()
    {
    	if (null === self::$_instance) {
        	self::$_instance = new self();
        }
           
        return self::$_instance;
    }
       
    /**
     * Add a single path to the controller directory stack
     *
     * @param string $path
     * @param string $module
     * @return Ooba_Controller_Front
     */
    public function addControllerDirectory($path, $module = null)
    {
        if (null == $module) {
            $module = $this->_defaultModule;
        }

        $module = (string) $module;
        $path   = rtrim((string) $path, '/\\');

        $this->_controllerDirectory[$module] = $path;
        return $this;
    }

    /**
     * Return the currently set controller directories
     *
     * If a module is specified, returns just that directory.
     *
     * @param  string $module Module name
     * @return array|string Returns array of all directories by default, single
     * module directory if module argument provided
     */
    public function getControllerDirectory($module = null)
    {
        if (null === $module) {
            return $this->_controllerDirectory;
        }

        $module = (string) $module;
        if (array_key_exists($module, $this->_controllerDirectory)) {
            return $this->_controllerDirectory[$module];
        }

        return null;
    }

    /**
     * Set controller directory
     *
     * @param array|string $directory
	 * @param  string $module Optional module name to use with string $directory
     * @return Front
     */
    public function setControllerDirectory($directory, $module = null)
    {
        $this->_controllerDirectory = array();

        if (is_string($directory)) {
            $this->addControllerDirectory($directory, $module);
        } elseif (is_array($directory)) {
            foreach ((array) $directory as $module => $path) {
                $this->addControllerDirectory($path, $module);
            }
        } else {
            throw new Ooba_Controller_Exception('Controller directory spec must be either a string or an array');
        }

        return $this;
    }

    /**
     * Remove a controller directory by module name
     *
     * @param  string $module
     * @return bool
     */
    public function removeControllerDirectory($module)
    {
        $module = (string) $module;
        if (array_key_exists($module, $this->_controllerDirectory)) {
            unset($this->_controllerDirectory[$module]);
            return true;
        }
        return false;
    }

	/**
     * Retrieve the controller key
     *
     * @return string
     */
    public function getControllerKey()
    {
        return $this->_controllerKey;
    }

    /**
     * Set the controller key
     *
     * @param string $key
     * @return Front
     */
    public function setControllerKey($key)
    {
        $this->_controllerKey = (string) $key;
        return $this;
    }

	/**
     * Retrieve the controller key
     *
     * @return string
     */
    public function getActionKey()
    {
        return $this->_actionKey;
    }

	/**
     * Set the controller key
     *
     * @param string $key
     * @return Front
     */
    public function setActionKey($key)
    {
        $this->_actionKey = (string) $key;
        return $this;
    }
	

	/**
     * Set the module key
     *
     * @param string $key
     * @return Front
     */
    public function setModuleKey($key)
    {
        $this->_moduleKey = (string) $key;
        return $this;
    }

	/**
     * Retrieve the controller key
     *
     * @return string
     */
    public function getModuleKey()
    {
        return $this->_moduleKey;
    }

	/**
     * Retrieve the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        if (null === $this->_module) {
            $this->_module = $this->_request->getParam($this->getModuleKey());
        }

        return $this->_module;
    }

	/**
     * Set the module name
     *
     * @param string $value
     * @return Front
     */
    public function setModuleName($value)
    {
        $this->_module = $value;
        return $this;
    }

	/**
     * Set the controller name to use
     *
     * @param string $value
     * @return Front
     */
    public function setControllerName($value)
    {
        $this->_controller = $value;
        return $this;
    }

	public function getControllerName()
    {
        if (null === $this->_controller) {
            $this->_controller = $this->_request->getParam($this->getControllerKey());
        }

        return $this->_controller;
    }

	/**
     * Set the action name
     *
     * @param string $value
     * @return Front
     */
    public function setActionName($value)
    {
        $this->_action = $value;
        return $this;
    }

	/**
     * Retrieve the action name
     *
     * @return string
     */
    public function getActionName()
    {
        if (null === $this->_action) {
            $this->_action = $this->_request->getParam($this->getActionKey());
        }

        return $this->_action;
    }

	public function getDefaultModule()
	{
		return $this->_defaultModule;
	}
	
	public function getDefaultController()
	{
		return $this->_defaultController;
	}
	
	public function getDefaultAction()
	{
		return $this->_defaultAction;
	}
	
	/**
	 * Get the request parameters
	 * 
	 */
	public function getParams()
	{
		if (empty($this->_params)) {
			$this->_params = array('module' => $this->getModuleName(),
				'controller' => $this->getControllerName(),
				'action' => $this->getActionName());
		}
			
		return $this->_params;
	}

	/**
     * Set request class/object
     *
     * Set the request object.  The request holds the request environment.
     *
     * If a class name is provided, it will instantiate it
     *
     * @param string|Request $request
     * @throws Exception if invalid request class
     * @return Front
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
		return $this;
    }

	/**
     * Return the request object.
     *
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set response class/object
     *
     * Set the response object.  The response is a container for action
     * responses and headers. Usage is optional.
     *
     * If a class name is provided, instantiates a response object.
     *
     * @param string|Zend_Controller_Response $response
     * @return Ooba_Controller_Front
     */
    public function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->_response = $response;
		return $this;
    }

    /**
     * Return the response object.
     *
     * @return null|Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->_response;
    }

	/**
     * Set router class/object
     *
     * Set the router object.  The router is responsible for mapping
     * the request to a controller and action.
     *
     * If a class name is provided, instantiates router with any parameters
     * registered via {@link setParam()} or {@link setParams()}.
     *
     * @param string|Router $router
     * @throws Exception if invalid router class
     * @return Front
     */
    public function setRouter($router)
    {
        if (is_string($router)) {
            if (class_exists($router)) {
                $router = new $router();
            }
        }

        if (!$router instanceof Ooba_Controller_Router) {
            throw new Ooba_Controller_Exception('Invalid router class');
        }

        $this->_router = $router;

        return $this;
    }

	/**
     * Return the router object.
     *
     * Instantiates a Router object if no router currently set.
     *
     * @return Router
     */
    public function getRouter()
    {
        if (null == $this->_router) {
            $this->setRouter(Ooba_Controller_Router::getInstance());
        }

        return $this->_router;
    }

     /**
     * Dispatch an HTTP request to a module/controller/action.
     * @return void
     */
     public function dispatch(Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null)
     {
		$this->setControllerDirectory(APPLICATION_PATH . '/modules/'. $this->_module . '/controllers');
		$file = APPLICATION_PATH . '/modules/'. $this->_module . '/controllers/' . ucfirst($this->_controller) . 'Controller.php';

		if (!is_file($file)) {
			// route to error
			$this->setControllerName('error');
			$this->setActionName('error');

			$error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
			$error->type = self::EXCEPTION_NO_CONTROLLER;
			$this->getRequest()->setParam('error_handler', $error);
			$this->dispatch();
			
	        return;
		}

		require_once $file;
		
		$class = ucfirst($this->_controller) . 'Controller';
		if ($this->_module !== $this->_defaultModule) {
			$class = $class = ucfirst($this->_module) . '_' . $class;
		}
		
		/**
         * Instantiate default request object (HTTP version) if none provided
         */
        if (null !== $request) {
            $this->setRequest($request);
        } elseif ((null === $request) && (null === ($request = $this->getRequest()))) {
            $request = new Zend_Controller_Request_Http;
            $this->setRequest($request);
        }

		/**
         * Instantiate default response object (HTTP version) if none provided
         */
        if (null !== $response) {
            $this->setResponse($response);
        } elseif ((null === $this->_response) && (null === ($this->_response = $this->getResponse()))) {
            $response = new Zend_Controller_Response_Http;
            $this->setResponse($response);
        }

		$controller = new $class($this->_request, $this->_response);
		if (!$controller instanceof Ooba_Controller_Action) {
			throw new Ooba_Controller_Exception('Controller "' . $class . '" is not an instance of Ooba_Controller_Action');
		}

		$controller->setName($this->_controller);
		
		if (is_null($this->_action)) {
			$this->_action = $this->_defaultAction;
		}
		
		$controller->dispatch($this->_action);
		$this->_request->setDispatched(true);
     }
}