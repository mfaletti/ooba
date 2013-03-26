<?php
/**
 * Application
 * @category Ooba
 * @package Application
 * @author Michael Faletti <github.com/mfaletti>
 *
 * Ooba Application
 * This is where we bootstrap the App. Initialization of app wide resources and things like that.
 *
 */

class Ooba_Application
{	
	protected $_router;
	
	protected $_front;
	
	protected $_bootstrap;
	
	protected $options;
	
	public function __construct()
	{
		$this->_front = Ooba_Controller_Front::getInstance();
		$this->_bootstrap = new Ooba_Application_Bootstrap;
		return $this;
	}
	
    /**
     * Run the application
     *
     * Checks to see that we have a default controller directory. If not, an
     * exception is thrown.
     *
     * If so, it registers the bootstrap with the 'bootstrap' parameter of
     * the front controller, and dispatches the front controller.
     *
     * @return mixed
     * @throws Exception
     */
    public function run()
    {
        $this->initRouter();
		$this->_front->dispatch();
    }

	public function bootstrap($resource = null)
	{
		$this->_bootstrap->bootstrap($resource);
		return $this;
	}

	public function initRouter()
	{
		require APPLICATION_PATH . '/configs/routes.php';
		$router = Ooba_Controller_Router::getInstance();

		if (isset($routes)){
			$router->setRoutes($routes);
			if ($router->matchCurrentRequest() === false) {
				$this->_front->setControllerName('error');
				$this->_front->setActionName('error');
				$error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
				$error->type = Ooba_Controller_Front::EXCEPTION_NO_ROUTE;
				$this->_front->getRequest()->setParam('error_handler', $error);
			}
		}
		
		$this->_router = $router;
	}
}