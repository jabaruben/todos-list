<?php

namespace TodosList\Infraestructure;

/**
 * 
 * HELP FOR ROUTING 
 *
 * Route								Example Match			Variables
 * /contact/							/contact/	
 * /users/[i:id]/						/users/12/				$id: 12
 * /[a:c]/[a:a]?/[i:id]?				/controller/action/21	$c: "controller", $a: "action", $id: 21
 * /[:c]/[:a]?/[:id]?					/test/testing/12		$c: "test", $a: "testing", $id: 12
 * /[:controller]/[:action]?/[:id]?		/test/testing/12		$controller: "test", $action: "testing", $id: 12
 * 
 * *                    // Match all request URIs
 * [i]                  // Match an integer
 * [i:id]               // Match an integer as 'id'
 * [a:action]           // Match alphanumeric characters as 'action'
 * [h:key]              // Match hexadecimal characters as 'key'
 * [:action]            // Match anything up to the next / or end of the URI as 'action'
 * [create|edit:action] // Match either 'create' or 'edit' as 'action'
 * [*]                  // Catch all (lazy, stops at the next trailing slash)
 * [*:trailing]         // Catch all as 'trailing' (lazy)
 * [**:trailing]        // Catch all (possessive - will match the rest of the URI)
 * .[:format]?          // Match an optional parameter 'format' - a / or . before the block is also optional
 * 
 * 'i'  => '[0-9]++'
 * 'a'  => '[0-9A-Za-z]++'
 * 'h'  => '[0-9A-Fa-f]++'
 * '*'  => '.+?'
 * '**' => '.++'
 * ''   => '[^/\.]++'
 * 
 * The way to access to the variables generated into de url like [configuracion:controller]
 * is after the $match = $router->match(); will be stored into $match["params"]["controller"].
 * 
 * 						   METHOD        				ROUTE 									TARGET 						NAME				PATH TO FOLDER
 * Example: $router->map('GET|POST','/[configuracion:controller]/[preguntas:action]', 'configuracion.preguntas', 'configuracion.preguntas', 'sections/contratos');
 * 
 * ###$router->map('GET|POST','/[configuracion:controller]/[preguntas:action]', 'configuracion.preguntas', 'configuracion.preguntas');
 * 
 **/

/**
 * Hay que tener creado un .htaccess con este contenido:
 *
 * Options -MultiViews
 *
 * RewriteEngine On
 * RewriteCond %{REQUEST_FILENAME} !-f
 * # RewriteRule . index.php [QSA,L]
 * RewriteRule ^ index.php [QSA,L]
 *
 *
 * This is the map method
 * map($method, $route, $target, $name = null, $pathToFolder = null, $callback = null)
 * 
 * Priority defining routes:
 * - First: Controller and Method or other params defined inside $route using: 
 *     [preguntas:controller]/[listado:action]
 * - Second: Controller and Method defined inside $target like this:
 *     $target = "Preguntas:listado"
 * - Third: Have $pathToFolder param to define the path to your controller.
 * 
 **/
class Router
{

	/**
	 * @var array Array of all routes (incl. named routes).
	 */
	protected $routes = array();

	/**
	 * @var array Array of all private routes (incl. named routes).
	 */
	protected $privateRoutes = array();

	/**
	 * @var array Array of all named routes.
	 */
	protected $namedRoutes = array();

	/**
	 * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
	 */
	protected $basePath = '';

	/**
	 * @var array Array of default match types (regex helpers)
	 */
	protected $matchTypes = array(
		'i'  => '[0-9]++',
		'a'  => '[0-9A-Za-z]++',
		'h'  => '[0-9A-Fa-f]++',
		'*'  => '.+?',
		'**' => '.++',
		''   => '[^/\.]++'
	);

	/**
	 * Create router in one call from config.
	 *
	 * @param array $routes
	 * @param string $basePath
	 * @param array $matchTypes
	 */
	public function __construct($routes = array(), $basePath = '', $matchTypes = array())
	{
		if (!defined("NL")) define("NL", "<br>");
		$this->addRoutes($routes);
		$this->setBasePath($basePath);
		$this->addMatchTypes($matchTypes);
	}

	/**
	 * Retrieves all routes.
	 * Useful if you want to process or display routes.
	 * @return array All routes.
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Retrieves all private routes.
	 * Useful if you want to process or display private routes.
	 * @return array All private routes.
	 */
	public function getPrivateRoutes()
	{
		return $this->privateRoutes;
	}

	/**
	 * Add multiple routes at once from array in the following format:
	 *
	 *   $routes = array(
	 *      array($method, $route, $target, $name = null, $pathToFolder = null, $callback = null)
	 *   );
	 *
	 * @param array $routes
	 * @return void
	 * @throws Exception
	 */
	public function addRoutes($routes)
	{
		if (!is_array($routes) && !$routes instanceof \Traversable) {
			throw new \Exception('Routes should be an array or an instance of Traversable');
		}
		foreach ($routes as $route) {
			call_user_func_array(array($this, 'map'), $route);
		}
	}

	/**
	 * Add multiple private routes at once from array in the following format:
	 *
	 *   $routes = array(
	 *      array($method, $route, $target, $name = null, $pathToFolder = null, $callback = null)
	 *   );
	 *
	 * @param array $routes
	 * @return void
	 * @throws Exception
	 */
	public function addPrivateRoutes($routes)
	{
		if (!is_array($routes) && !$routes instanceof \Traversable) {
			throw new \Exception('Routes should be an array or an instance of Traversable');
		}
		foreach ($routes as $route) {
			call_user_func_array(array($this, 'mapPrivate'), $route);
		}
	}

	/**
	 * Set the base path.
	 * Useful if you are running your application from a subdirectory.
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	/**
	 * Add named match types. It uses array_merge so keys can be overwritten.
	 *
	 * @param array $matchTypes The key is the name and the value is the regex.
	 */
	public function addMatchTypes($matchTypes)
	{
		$this->matchTypes = array_merge($this->matchTypes, $matchTypes);
	}

	/**
	 * Map a route to a target
	 *
	 * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 * @param string $pathToFolder Optional file path to folder that contains files of this route.
	 * @param string $callback Optional function to call when the route reaches.
	 * @throws Exception
	 */
	public function map($method, $route, $target, $name = null, $pathToFolder = null, $callback = null)
	{

		$this->routes[] = array($method, $route, $target, $name, $pathToFolder, $callback);

		if ($name) {
			if (isset($this->namedRoutes[$name])) {
				throw new \Exception("Can not redeclare route '{$name}'");
			} else {
				$this->namedRoutes[$name] = $route;
			}
		}

		return;
	}

	/**
	 * Map a private route to a target
	 *
	 * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 * @param string $pathToFolder Optional file path to folder that contains files of this route.
	 * @param string $callback Optional function to call when the route reaches.
	 * @throws Exception
	 */
	public function mapPrivate($method, $route, $target, $name = null, $pathToFolder = null, $callback = null)
	{

		$this->privateRoutes[] = array($method, $route, $target, $name, $pathToFolder, $callback);

		if ($name) {
			if (isset($this->namedRoutes[$name])) {
				throw new \Exception("Can not redeclare route '{$name}'");
			} else {
				$this->namedRoutes[$name] = $route;
			}
		}

		return;
	}

	/**
	 * Reversed routing
	 *
	 * Generate the URL for a named route. Replace regexes with supplied parameters
	 *
	 * @param string $routeName The name of the route.
	 * @param array @params Associative array of parameters to replace placeholders with.
	 * @return string The URL of the route with named parameters in place.
	 * @throws Exception
	 */
	public function generate($routeName, array $params = array())
	{

		// Check if named route exists
		if (!isset($this->namedRoutes[$routeName])) {
			// throw new \Exception("Route '{$routeName}' does not exist.");
			// En lugar de error, devolvemos # o 404
			return "404";
		}

		// Replace named parameters
		$route = $this->namedRoutes[$routeName];

		// prepend base path to route url again
		$url = $this->basePath . $route;

		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

			foreach ($matches as $index => $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if ($pre) {
					$block = substr($block, 1);
				}

				if (isset($params[$param])) {
					// Part is found, replace for param value
					$url = str_replace($block, $params[$param], $url);
				} elseif ($optional && $index !== 0) {
					// Only strip preceeding slash if it's not at the base
					$url = str_replace($pre . $block, '', $url);
				} elseif ($param) {
					$url = str_replace($block, $type, $url);
				} else {
					// Strip match block
					$url = str_replace($block, '', $url);
				}
			}
		}

		return $url;
	}

	/**
	 * Match a given Request Url against stored routes
	 * @param string $requestUrl
	 * @param string $requestMethod
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match($routes = [], $requestUrl = null, $requestMethod = null)
	{

		$params = array();
		$match = false;

		// set Request Url if it isn't passed as parameter
		if ($requestUrl === null) {
			$requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		}

		// strip base path from request url
		$requestUrl = substr($requestUrl, strlen($this->basePath));

		// Strip query string (?a=b) from Request Url
		if (($strpos = strpos($requestUrl, '?')) !== false) {
			$requestUrl = substr($requestUrl, 0, $strpos);
		}

		// set Request Method if it isn't passed as a parameter
		if ($requestMethod === null) {
			$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		}

		if (!is_array($routes) || count($routes) === 0) {
			$routes = array_merge(
				array_map(function ($route) {
					array_push($route, false);
					return $route;
				}, $this->routes),
				array_map(function ($route) {
					array_push($route, true);
					return $route;
				}, $this->privateRoutes)
			);
		}

		foreach ($routes as $handler) {
			list($methods, $route, $target, $name, $pathToFolder, $callback, $isPrivateRoute) = $handler;

			$method_match = (stripos($methods, $requestMethod) !== false);

			// Method did not match, continue to next route.
			if (!$method_match) continue;

			// if (
			// 	strpos($target, ':') === false 
			// 	&& strpos($route, ':controller') === false 
			// 	&& is_null($callback)
			// ) {
			// 	echo "<span style=\"font-weight: bold; color: red;\">Warning</span>: routeconfig <strong>{$name}</strong> with <strong>{$route}</strong> <em>route</em> and <strong>{$target}</strong> <em>target</em> without <em>callback function</em> not configure any valid Controller." . NL . "<pre>" . print_r($handler, true) . "</pre>";
			// 	continue;
			// }

			if ($route === '*') {
				// * wildcard (matches all)
				$match = true;
			} elseif (isset($route[0]) && $route[0] === '@') {
				// @ regex delimiter
				$pattern = '`' . substr($route, 1) . '`u';
				$match = preg_match($pattern, $requestUrl, $params) === 1;
			} elseif (($position = strpos($route, '[')) === false) {
				// No params in url, do string comparison
				$match = strcmp($requestUrl, $route) === 0;
			} else {
				// Compare longest non-param string with url
				if (strncmp($requestUrl, $route, $position) !== 0) {
					continue;
				}
				$regex = $this->compileRoute($route);
				$match = preg_match($regex, $requestUrl, $params) === 1;
			}

			if (!$match) {
				continue;
			}

			if ($params) {
				foreach ($params as $key => $value) {
					if (is_numeric($key)) unset($params[$key]);
				}
			}

			if (strpos($target, ':') !== false) {
				$splitTarget = explode(":", $target);
				if (!array_key_exists("controller", $params)) {
					$controllerName = ucwords($splitTarget[0]);
					$params["controller"] = $controllerName;
				}
				if (!array_key_exists("method", $params)) {
					$methodName = $splitTarget[1];
					$params["method"] = $methodName;
				}
			}

			if ($pathToFolder === null) $pathToFolder = "";

			$arrReturn = array(
				'requestMethod' => $requestMethod,
				'controller' 	=> $controllerName ?: $params["controller"],
				'method' 		=> $methodName ?: $params["method"] ?: 'index',
				'target' 		=> $target,
				'params' 		=> $params,
				'name' 			=> $name,
				'slug' 			=> $requestUrl,
				'pathtofolder' 	=> $pathToFolder,
				'isPrivateRoute' => $isPrivateRoute
			);

			if ($callback != null && is_callable($callback)) {
				$arrReturn['callback'] = $callback;
			}

			return $arrReturn;
		}

		return false;
	}

	/**
	 * Compile the regex for a given route (EXPENSIVE)
	 */
	private function compileRoute($route)
	{
		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

			$matchTypes = $this->matchTypes;
			foreach ($matches as $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if (isset($matchTypes[$type])) {
					$type = $matchTypes[$type];
				}
				if ($pre === '.') {
					$pre = '\.';
				}

				$optional = $optional !== '' ? '?' : null;

				//Older versions of PCRE require the 'P' in (?P<named>)
				$pattern = '(?:'
					. ($pre !== '' ? $pre : null)
					. '('
					. ($param !== '' ? "?P<$param>" : null)
					. $type
					. ')'
					. $optional
					. ')'
					. $optional;

				$route = str_replace($block, $pattern, $route);
			}
		}
		return "`^$route$`u";
	}
}
