<?php

namespace TodosList\Infraestructure;

use Config\Config;
use TodosList\Infraestructure\Router;
use src\Infraestructure\HTTP\Response;
use src\Infraestructure\HTTP\ServerRequest;

class Bootstrap
{

    private $router;
    private $middleware;
    private $config;
    private $routesFile;
    private $arrConfigRoutes;
    private $match;

    private $publicRoutes = [];
    private $privateRoutes = [];

    public function __construct(Router $router, Middleware $middleware, Config $config)
    {
        $this->router = $router;
        $this->middleware = $middleware;
        $this->config = $config;

        $this->setIsLoggedIn();
        $this->setLoginData();
    }

    public function loadRoutes()
    {
        if (file_exists($this->routesFile)) {
            $this->arrConfigRoutes = require_once $this->routesFile;
            // echo "ROUTES: " . print_r($arrConfigRoutes, true) . NL;
            foreach ($this->arrConfigRoutes["routes"]["public"] as $route) {
                $this->router->map($route[0], $route[1], $route[2], $route[3], $route[4], $route[5]);
            }
            foreach ($this->arrConfigRoutes["routes"]["private"] as $route) {
                $this->router->mapPrivate($route[0], $route[1], $route[2], $route[3], $route[4], $route[5]);
            }
        }
    }

    public function loadAndMatchRoutes(){
        $this->loadRoutes();
        $this->debugAllRoutes();
        $this->match = $this->router->match();
    }

    public function matchRoute() {
        return (!$this->match) ? false : true; 
    }

    public function NL()
    {
        return $this->config::NL;
    }

    public function schema()
    {
        return $this->config::DB_SCHEMA;
    }

    public function isDebug()
    {
        return $this->config::RETURN_DEBUG_DATA;
    }

    public function getJWTSecret()
    {
        return $this->config::JWT_SECRET;
    }

    public function getJWTAlg()
    {
        return $this->config::JWT_ALGORITHM;
    }

    public function addMiddlewareService(string $name, object $middleware): void
    {
        $this->middleware->setService($name, $middleware);
    }

    public function getMiddlewareService(string $name): object
    {
        return $this->middleware->getService($name);
    }

    public function toStringMiddlewares()
    {
        foreach ($this->middleware->getAllServices() as $key => $value) {
            try {
                $this->printScreenDebug("Middleare: " . $key . $this->config::NL);
            } catch (\Exception $e) {
                // no hacemos nada
                echo "Error" . $e->getMessage();
            }
        }
    }

    public function setRoutesFile($file)
    {
        $this->routesFile = $file;
    }

    public function setBasePath($path)
    {
        $this->router->setBasePath($path);
    }

    private function setIsLoggedIn($status = false)
    {
        $this->isLoggedIn = $status;
    }

    private function isLoggedIn()
    {
        return $this->isLoggedIn;
    }

    private function setLoginData($data = [])
    {
        $this->logginData = $data;
    }

    public function getLoginData()
    {
        return $this->logginData;
    }

    private function isPrivateRoute($bootstrapConfig)
    {
        if (!array_key_exists("isPrivateRoute", $bootstrapConfig)) {
            return false;
        }
        if ($bootstrapConfig["isPrivateRoute"]) {
            return true;
        } else {
            return false;
        }
    }

    private function getNamespace()
    {
        return $this->config::NAMESPACE;
    }

    public function debugAllRoutes()
    {
        $this->printScreenDebug("DEBUG - printAllRoutes(): <pre>" . print_r($this->router->getRoutes(), true) . "</pre>");
    }

    public function run()
    {
        // $this->loadAndMatchRoutes();
        $this->toStringMiddlewares();
        $this->printScreenDebug($this->match);
        if (!$this->matchRoute()) {
            $this->match = $this->arrConfigRoutes["errors"]["404"];
        }
        $this->debugMatchRoute();
        $this->prepareHTTPAuth();
        $this->checkAndPrepareAuthData();
        $resp = $this->bootstrap($this->match);
        // return $this->match;
        $resp->send();
    }

    public function debugMatchRoute()
    {
        $this->printScreenDebug("DEBUG - match: <pre>" . print_r($this->match, true) . "</pre>");
    }

    public function printScreenDebug($debuggable)
    {
        if ($this->config::DEBUG) {
            if (is_string($debuggable))
                echo '<div style="background-color: red;">' . $debuggable . '</div>';
            else
                echo '<div style="background-color: red;">' . var_export($debuggable, true) . '</div>';
        }
    }

    private function bootstrap($bootstrapConfig): Response
    {
        if (!$this->isLoggedIn() && $this->isPrivateRoute($bootstrapConfig)) {
            $this->responseWith401NotAuth();
        }

        $request = $this->prepareRequest($bootstrapConfig);
        $response = $this->prepareResponse($bootstrapConfig);

        $respCallback = $this->executeCallback($request, $response, $bootstrapConfig);
        if($respCallback){
            return $respCallback;
        }

        $controller = ucwords($bootstrapConfig["controller"]);
        $varController = mb_strtolower($bootstrapConfig["controller"]);
        $method = $bootstrapConfig["method"];
        $namespace = $this->getNamespace();
        $controller = $namespace . $controller;

        if (class_exists($controller) && method_exists($controller, $method)) {
            $$varController = new $controller();
            return $$varController->$method($this, $response, $request);
        } else {
            return $response;
        }

        // $$varController->$method($arg1, $arg2, $arg3);
        // call_user_func_array(array($$varController, $method), array($arg1, $arg2, $arg3));
    }

    private function prepareRequest($bootstrapConfig)
    {
        $request = new ServerRequest();
        // $request->setImmutable(false);
        $request = $request->fromGlobals();
        foreach ($bootstrapConfig["params"] as $paramKey => $paramVal) {
            $request = $request->withAttribute($paramKey, $paramVal);
        }
        unset($bootstrapConfig["params"]);
        foreach ($bootstrapConfig as $paramKey => $paramVal) {
            $request = $request->withAttribute($paramKey, $paramVal);
        }
        $request = $request->withAttribute("isLoggedIn", $this->isLoggedIn());
        $request = $request->withAttribute("logginData", $this->getLoginData());
        $this->printScreenDebug("DEBUG - Atributes: <pre>" . print_r($request->getAttributes(), true) . "</pre>");
        return $request;
    }

    private function prepareResponse($bootstrapConfig = [])
    {
        $response = new Response();
        // $response->setImmutable(false);
        // $response->withHeader("Api-Key", "101010");
        // $response->getBody()->write("testingggg");

        // $response->send();
        return $response;
    }

    private function checkAndPrepareAuthData()
    {
        if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
            return false;
        }
        if (stripos($_SERVER["HTTP_AUTHORIZATION"], 'bearer ') === false) {
            return false;
        }
        if (stripos($_SERVER["HTTP_AUTHORIZATION"], 'bearer ') !== 0) {
            return false;
        }

        $jwt = $this->getMiddlewareService("jwt");
        if (!$jwt) {
            return false;
        }

        try {
            $token = substr($_SERVER["HTTP_AUTHORIZATION"], 7);
            $secret = $this->getJWTSecret();
            $algs = $this->getJWTAlg();
            $data = $jwt->decode($token, $secret, $algs);
            // var_dump($data);
            $this->setIsLoggedIn(true);
            $this->setLoginData($data);
            // die("BEARER" . $token);
        } catch (\Exception $e) {
            $this->responseWith401NotAuth();
        }
    }

    private function executeCallback($request, $response, $bootstrapConfig)
    {
        if (!array_key_exists("callback", $bootstrapConfig)) {
            return false;
        }
        if (!isset($bootstrapConfig["callback"])) {
            return false;
        }
        if ($bootstrapConfig["callback"] === null) {
            return false;
        }
        if ($bootstrapConfig["callback"] === "") {
            return false;
        }
        if (!is_callable($bootstrapConfig["callback"])) {
            return false;
        }
        $function = $bootstrapConfig["callback"];
        return $function($request, $response);
    }

    private function prepareHTTPAuth()
    {
        $headers = getallheaders();
        if (array_key_exists("authorization", $headers)) {
            $_SERVER["HTTP_AUTHORIZATION"] = $headers["authorization"];
        }
    }

    private function responseWith401NotAuth()
    {
        // $resp = $this->prepareResponse();
        // $resp->withHeader("HTTP/1.1", )
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }

    private function responseWith400BadReq($message = "")
    {
        header('HTTP/1.1 400 Bad Request');
        echo $message;
        exit;
    }
}
