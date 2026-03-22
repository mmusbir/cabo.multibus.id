<?php
/**
 * API Response Helper Functions
 * Standardize API responses across the application
 */

/**
 * Send success JSON response
 * @param mixed $data The data to return
 * @param int $statusCode HTTP status code (default: 200)
 */
function apiSuccess($data = [], $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Send error JSON response
 * @param string $message Error message
 * @param int $statusCode HTTP status code (default: 400)
 * @param mixed $errors Additional error details
 */
function apiError($message = 'An error occurred', $statusCode = 400, $errors = null)
{
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Validate required fields in request
 * @param array $required Fields that must be present
 * @param array $data Data source (usually $_GET, $_POST, or json input)
 * @return bool
 */
function validateRequired($required, $data = null)
{
    if ($data === null) {
        // Try to get JSON body
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data === null) {
            $data = $_REQUEST;
        }
    }
    
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? true : $missing;
}

/**
 * Get JSON request body
 * @return array
 */
function getJsonInput()
{
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Get query parameter safely
 * @param string $key Parameter name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function getQuery($key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Get POST parameter safely
 * @param string $key Parameter name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function getPost($key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Check if request method matches
 * @param string $method GET, POST, PUT, DELETE, etc.
 * @return bool
 */
function isMethod($method)
{
    return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
}
?>
