<?php
/**
 * Abstract controller
 * @category Ooba
 * @package Controller
 * @author Michael Faletti <github.com/mfaletti>
 *
 * Base Action Controller
 *
 */

abstract class Ooba_Controller_Action
{
	/**
     * View
     * @var Zend_View_Interface
     */
    public $view;

	protected $_name;

	/**
     * @var front controller
     */
	protected $_frontController;
	
    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $request = null;

    /**
     * Ooba_Controller_Response
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response = null;
	
	/**
     * View script suffix; defaults to 'phtml'
     * @see {render()}
     * @var string
     */
    public $viewSuffix = 'phtml';
	
	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
	{
		$this->setRequest($request)
             ->setResponse($response);
	}
	
	/**
     * Initialize object
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init(){}
	
	/**
	 * Pre-dispatch hook. 
	 * called before controller's action method gets called
	 * 
	 * return void
	 */
	public function preDispatch(){}

	/**
	 * Post-dispatch hook. 
	 * called after controller's action method executes
	 * 
	 * return void
	 */
	public function postDispatch(){}
	
	/**
	 * Set the controller name. probably not necessary
	 *
	 */	
	public function setName($name)
	{
		$this->_name = $name;
	}
	
	/**
	 * Dispatch a request action
	 *
	 * TODO: add more type/error checking
	 */
	public function dispatch($action)
	{
		$this->preDispatch();
		
		$actionMethod = $action . 'Action';
		
		if (!method_exists($this, $actionMethod)) {
			// route to error page
			$module = $this->getFrontController()->getDefaultModule();
			$error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
			$error->type = Ooba_Controller_Front::EXCEPTION_NO_ACTION;
			$this->getRequest()->setParam('error_handler', $error);
			$this->forward('error', 'error', $module);
			return;
		}
		
		$this->initView();
		$this->init();
		$this->$actionMethod();
		$this->render($action);
		$this->postDispatch();
	}

	public function initView()
	{
		$dirs   = $this->getFrontController()->getControllerDirectory();
		$module = $this->getFrontController()->getModuleName();
		
		if (empty($module) || !isset($dirs[$module])) {
            $module = $this->getFrontController()->getDefaultModule();
        }
		
		$baseDir = dirname($dirs[$module]) . DIRECTORY_SEPARATOR . 'views/scripts';
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
			throw new Ooba_Controller_Exception('Missing base view directory ("' . $baseDir . '")');
		}

		if (Zend_Registry::isRegistered('view')) {
			$this->view = Zend_Registry::get('view');
			$this->view->addScriptPath($baseDir);
		} else {//bootstrap the view
			// $this->view = new Zend_View(array('basePath' => $baseDir));
			// 			$this->view->addScriptPath(APPLICATION_PATH . '/views/scripts');
			// 			$this->view->addHelperPath('Ooba/View/Helper', 'Ooba_View_Helper');
			//  			$this->view->addHelperPath(APPLICATION_PATH . '/views/helpers', 'Ndj_View_Helper');
			// 			Zend_Registry::set('view', $this->view);
		}

		return $this->view;
	}
	
	/** 
	 * Render page layout
	 * 
	 * @var $action string: The action whose view we want to render
	 */
	public function render($action = null)
	{
		if (null === $this->view) {
			$this->initView();
		}
		
		if ($this->_request->isXmlHttpRequest()) {
			$this->viewSuffix = 'json.phtml';
			$this->view->layout()->disableLayout();
			$this->getResponse()->setHeader('Content-Type', 'application/json');
		}
		
		$script = $this->getViewScript($action);
		$layout = Zend_Layout::getMvcInstance();
		
		if (!$layout->isEnabled()) {
			$body = $this->view->render($script);
		} else {
			$layout->setView($this->view);
			$layout->content = $this->view->render($script);
			$body = $layout->render();
		}
		
		$this->_response->clearBody();
		$this->_response->appendBody($body);
		
		$channel = Zend_Wildfire_Channel_HttpHeaders::getInstance()->flush();
		$this->_response->sendResponse();
	}
	
	/** 
	 * Get a view script for an action
	 * 
	 * @var $action string
	 */
	public function getViewScript($action = null)
	{
		$front = $this->getFrontController();
		if (null === $action) {
			$action = $front->getActionName();
		} elseif(!is_string($action)) {
			throw new Ooba_Controller_Exception('Invalid action specified for view render');
		}
		
		$script = $action . '.' . $this->viewSuffix; 

        $controller = $front->getControllerName();
        $script = $controller . DIRECTORY_SEPARATOR . $script;
        return $script;
	}
	
	/**
	 * get the front controller
	 *
	 * @return Ooba_Controller_Front
	 */
	public function getFrontController()
	{
		if (null !== $this->_frontController) {
			return $this->_frontController;
		}
		
		$this->_frontController = Ooba_Controller_Front::getInstance();
		return $this->_frontController;
	}
	
	/**
     * Set the Request object
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return Ooba_Controller_Action
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }
	
	/**
     * Return the Request object
     *
     * @return Request
     */
    public function getRequest()
    {
		return $this->_request;
    }

    /**
     * Return the Response object
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Set the Response object
     *
     * @param Zend_Controller_Response_Abstract $response
     * @return Ooba_ontroller_Action
     */
    public function setResponse(Zend_Controller_Response_Abstract $response)
    {
        $this->_response = $response;
        return $this;
    }

	/**
     * Redirect to another URL
     
     * @param string $url
     * @return void
     */
    public function _redirect($url)
    {
        if ($url !== null and empty($url) === false and
            (bool) preg_match('/^https?:\/\//', $url) === false) {
			// prevent header injections
		    $url = str_replace(array("\n", "\r"), '', $url);
			header("Location: $url");
		}
    }

    /**
     * Forward to another controller/action.
     *
     * It is important to supply the unformatted names, i.e. "article"
     * rather than "ArticleController".  The dispatcher will do the
     * appropriate formatting when the request is received.
     *
     * If only an action name is provided, forwards to that action in this
     * controller.
     *
     * If an action and controller are specified, forwards to that action and
     * controller in this module.
     *
     * Specifying an action, controller, and module is the most specific way to
     * forward.
     *
     * A fourth argument, $params, will be used to set the request parameters.
     * If either the controller or module are unnecessary for forwarding,
     * simply pass null values for them before specifying the parameters.
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @return void
     */
    final public function forward($action, $controller = null, $module = null, array $params = null)
    {
        $request = $this->getRequest();
		$front = $this->getFrontController();

        if (null !== $params) {
            $request->setParams($params);
        }

        if (null !== $controller) {
            $front->setControllerName($controller);
		}

        // Module should only be reset if controller has been specified
		if (null !== $module) {
			$front->setModuleName($module);
		}

        $front->setActionName($action)
              ->dispatch();
    }


    /**
     * Gets a parameter from the Request object.  If the
     * parameter does not exist, NULL will be returned.
     *
     * If the parameter does not exist and $default is set, then
     * $default will be returned instead of NULL.
     *
     * @param string $paramName
     * @param mixed $default
     * @return mixed
     */
    public function getParam($paramName, $default = null)
    {
        $value = $this->getRequest()->getParam($paramName);
         if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a parameter in the {@link $_request Request object}.
     *
     * @param string $paramName
     * @param mixed $value
     * @return Ooba_Controller_Action
     */
    public function setParam($paramName, $value)
    {
        $this->getRequest()->setParam($paramName, $value);

        return $this;
    }
}