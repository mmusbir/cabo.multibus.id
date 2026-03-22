<?php
/**
 * Simple Router Class
 * Basic routing system for handling GET/POST requests with action-based approach
 */
class Router
{
    private $routes = [];
    private $currentMethod = '';

    /**
     * Register GET route
     */
    public function get($action, $callback)
    {
        $this->routes['GET'][$action] = $callback;
        return $this;
    }

    /**
     * Register POST route
     */
    public function post($action, $callback)
    {
        $this->routes['POST'][$action] = $callback;
        return $this;
    }

    /**
     * Register both GET and POST route
     */
    public function any($action, $callback)
    {
        $this->get($action, $callback);
        $this->post($action, $callback);
        return $this;
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_REQUEST['action'] ?? null;

        if (!$action) {
            http_response_code(400);
            echo json_encode(['error' => 'No action specified']);
            return;
        }

        if (isset($this->routes[$method][$action])) {
            $callback = $this->routes[$method][$action];
            
            try {
                if (is_callable($callback)) {
                    call_user_func($callback);
                } else if (is_array($callback) && count($callback) === 2) {
                    // Support for class method callbacks [ClassName, 'methodName']
                    call_user_func_array($callback, []);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            return;
        }

        // Action not found
        http_response_code(404);
        echo json_encode(['error' => "Action '$action' not found"]);
    }

    /**
     * Get registered routes (for debugging)
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Check if route exists
     */
    public function hasRoute($method, $action)
    {
        return isset($this->routes[$method][$action]);
    }
}
?>
