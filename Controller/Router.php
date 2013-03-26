<?
/** 
 * Router
 * @category Ooba
 * @package Controller
 * @author Michael Faletti <github.com/mfaletti>
 *
 * Simple Rails inspired Routing class to match request URL's against given routes and map them to a controller action.
 * RESTful, Can handle different HTTP methods.  
 * inspired by https://github.com/dannyvankooten/PHP-Router
 */

class Ooba_Controller_Router 
{
	/**
    * Array that holds all Route objects
    * @var array
    */ 
    protected $_routes = array();

    /**
     * Array to store named routes in, used for reverse routing.
     * @var array 
     */
    protected $namedRoutes = array();

    /**
     * The base REQUEST_URI. Gets prepended to all route url's.
     * @var string
     */
    protected $basePath = '';

	/**
     * Singleton instance of the Router
     *
     * @var Router
     */
    protected static $_instance = null;
    
	/**
    * Constructor
    *
    * marked protected to ensure only only one instance of the router is available in application
    * Instantiate using {@link getInstance()}; front controller is a singleton
    * object.
    *
    * @return void
    */
	protected function __construct(){}
	
    /**
     * Provides access to the singleton instance
     *
     * @return Router
     */
	public static function getInstance()
    {
    	if (null === self::$_instance) {
        	self::$_instance = new self();
        }
           
        return self::$_instance;
    }
	
    /**
     * Set the base url - gets prepended to all route url's.
     * @param string $base_url 
     */
    public function setBasePath($basePath) 
	{
        $this->basePath = (string) $basePath;
    }

    /**
    * Route factory method
    *
    * Maps the given URL to the given target.
    * @param string $routeUrl string
    * @param mixed $target The target of this route. Can be anything. Should probably turn this into a filename, 
	* controller / action pair, etc..
    * @param array $args Array of optional arguments.
    */
    public function map($routeUrl, $target = '', array $args = array()) 
	{
        $route = new Ooba_Controller_Route;

        $route->setUrl($this->basePath . $routeUrl);

        $route->setTarget($target);

        if(isset($args['methods'])) {
            $methods = explode(',', $args['methods']);
            $route->setMethods($methods);
        }

        if(isset($args['filters'])) {
            $route->setFilters($args['filters']);
        }

        if(isset($args['name'])) {
            $route->setName($args['name']);
            if (!isset($this->namedRoutes[$args['name']])) {
                $this->namedRoutes[$args['name']] = $route;
            }
        }

        $this->_routes[] = $route;
    }

    /**
    * Matches the current request against mapped routes
    */
    public function matchCurrentRequest() 
	{
        $requestMethod = (isset($_POST['_method']) && ($_method = strtoupper($_POST['_method'])) 
			&& in_array($_method,array('PUT','DELETE'))) ? $_method : $_SERVER['REQUEST_METHOD'];
        $requestUrl = $_SERVER['REQUEST_URI'];

        // strip GET variables from URL
        if(($pos = strpos($requestUrl, '?')) !== false) {
            $requestUrl =  substr($requestUrl, 0, $pos);
        }

        return $this->match($requestUrl, $requestMethod);
    }

    /**
    * Match given request url and request method and see if a route has been defined for it
    * If so, return route's target
    * If called multiple times
    */
    public function match($requestUrl, $requestMethod = 'GET')
	{
        foreach($this->_routes as $route) {
            // compare server request method with route's allowed http methods
            if(!in_array($requestMethod, $route->getMethods())) continue;

            // check if request url matches route regex. if not, return false.
            if (!preg_match("@^".$route->getRegex()."*$@i", rawUrlDecode($requestUrl), $matches)) continue;
            $params = array();

            if (preg_match_all("/:([\w-]+)/", $route->getUrl(), $argument_keys)) {

                // grab array with matches
                $argument_keys = $argument_keys[1];

                // loop trough parameter names, store matching value in $params array
                foreach ($argument_keys as $key => $name) {
                    if (isset($matches[$key + 1]))
                        $params[$name] = $matches[$key + 1];
                }
            }

            $route->setParameters($params);

			// set the controller and action parameters in the Front controller before dispatch occurs
			$this->_setRequestParams($route->getTarget(), $params);
            return $route;
            
        }
        return false;
    }

	protected function _setRequestParams($params, $requestParams)
    {
        $front = Ooba_Controller_Front::getInstance();
		$p = array_merge($params, $requestParams);
		
		foreach ($p as $param => $value) {
			if ($param === $front->getControllerKey()) {
                $front->setControllerName($value);
            }
            if ($param === $front->getActionKey()) {
                $front->setActionName($value);
            }

			if ($param === $front->getModuleKey()) {
                $front->setModuleName($value);
            }
		}
		
		// set params for request object
		$request = $front->getRequest();
		$request->setParams($requestParams);
    }

	/**
     * Retrieve an array of routes added to the route chain
     *
     * @return array all of the defined routes
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

	/**
	 * set routes
	 * @param $routes: array of custom defined routes
	 * @return void
	 */
	public function setRoutes($routes)
	{
		$routes = (array) $routes;
		
		foreach($routes as $key=>$route){
			call_user_func_array(array($this, "map"), $route);
		}
	}

	/**
     * Reverse route a named route
     * 
     * @param string $route_name The name of the route to reverse route.
     * @param array $params Optional array of parameters to use in URL
     * @return string The url to the route
     */
    public function generate($routeName, array $params = array()) 
	{
        // Check if route exists
        if (!isset($this->namedRoutes[$routeName]))
            throw new Ooba_Controller_Exception("No route with the name $routeName has been found.");

        $route = $this->namedRoutes[$routeName];
        $url = $route->getUrl();

        // replace route url with given parameters
        if ($params && preg_match_all("/:(\w+)/", $url, $param_keys)) {

            // grab array with matches
            $param_keys = $param_keys[1];

            // loop trough parameter names, store matching value in $params array
            foreach ($param_keys as $i => $key) {
                if (isset($params[$key]))
                    $url = preg_replace("/:(\w+)/", $params[$key], $url, 1);
            }
        }

        return $url;
    }
}
