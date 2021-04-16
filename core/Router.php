<?php
class Router {

    private $requestArray;

    public function route($baseUrl) {
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, "?")) {
            $uri = explode("?", $uri)[0];
        }
        $request = $_SERVER['HTTP_HOST'] . $uri;
        $request = str_replace($baseUrl, "", $request);
        $this->requestArray = explode("/", $request);
        array_splice($this->requestArray, 0, 1);
        if ($this->requestArray[0] == null || $this->requestArray[0] == "app") {
            $controllerName = "AppController";
        } else {
            $controllerName = ucFirst($this->requestArray[0]) . 'Controller';
        }
        $controller =  './../controller/' . $controllerName . '.php';
        if (!include($controller)) {
            include './../controller/ErrorController.php';
            new ErrorController();
        } else {
            $controllerName = $controllerName;
            $this->controller = new $controllerName;
            if (sizeof($this->requestArray) == 1) {
                $action = "indexAction";
            } else {
                $action = $this->requestArray[1] . "Action";
            }
            $this->controller->$action();
        }
    }

    /* Singleton */
    private static $inst = null;
    private function __construct() {}
    public static function getInstance() {
        if (null === self::$inst) {
            self::$inst = new self;
        }
        return self::$inst;
    }
}
?>
