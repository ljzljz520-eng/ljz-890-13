<?php
/**
 * 亚尔买买提・阿不来提纪念网站 - API入口
 * 
 * @copyright 合肥市奕宁云网络科技有限公司 www.yiningyun.com
 */

declare(strict_types=1);

// 错误处理
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// CORS 配置
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 自动加载
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 引入配置
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/JWT.php';
require_once __DIR__ . '/../src/Utils/Logger.php';

use App\Utils\Response;
use App\Utils\Logger;

// 初始化日志
$logger = Logger::getInstance();

try {
    // 获取请求信息
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH);
    
    // 移除基础路径
    $path = preg_replace('#^/api#', '', $path);
    $path = $path ?: '/';
    
    $logger->info("Request: {$method} {$path}");
    
    // 路由表
    $routes = [
        'GET' => [
            '/' => ['App\Controllers\HomeController', 'index'],
            '/config' => ['App\Controllers\ConfigController', 'getPublicConfig'],
            '/life-events' => ['App\Controllers\LifeEventController', 'getAll'],
            '/photos' => ['App\Controllers\PhotoController', 'getAll'],
            '/messages' => ['App\Controllers\MessageController', 'getApproved'],
            '/time-since' => ['App\Controllers\HomeController', 'getTimeSince'],
            
            // 后台路由
            '/admin/dashboard' => ['App\Controllers\AdminController', 'dashboard'],
            '/admin/config' => ['App\Controllers\ConfigController', 'getAll'],
            '/admin/life-events' => ['App\Controllers\LifeEventController', 'getAllAdmin'],
            '/admin/photos' => ['App\Controllers\PhotoController', 'getAllAdmin'],
            '/admin/messages' => ['App\Controllers\MessageController', 'getAll'],
        ],
        'POST' => [
            '/auth/login' => ['App\Controllers\AuthController', 'login'],
            '/auth/logout' => ['App\Controllers\AuthController', 'logout'],
            '/messages' => ['App\Controllers\MessageController', 'create'],
            
            // 后台路由
            '/admin/config' => ['App\Controllers\ConfigController', 'update'],
            '/admin/life-events' => ['App\Controllers\LifeEventController', 'create'],
            '/admin/photos' => ['App\Controllers\PhotoController', 'create'],
            '/admin/upload' => ['App\Controllers\UploadController', 'upload'],
        ],
        'PUT' => [
            '/admin/life-events/{id}' => ['App\Controllers\LifeEventController', 'update'],
            '/admin/photos/{id}' => ['App\Controllers\PhotoController', 'update'],
            '/admin/messages/{id}' => ['App\Controllers\MessageController', 'update'],
        ],
        'DELETE' => [
            '/admin/life-events/{id}' => ['App\Controllers\LifeEventController', 'delete'],
            '/admin/photos/{id}' => ['App\Controllers\PhotoController', 'delete'],
            '/admin/messages/{id}' => ['App\Controllers\MessageController', 'delete'],
        ],
    ];
    
    // 路由匹配
    $handler = null;
    $params = [];
    
    if (isset($routes[$method])) {
        foreach ($routes[$method] as $route => $routeHandler) {
            // 检查是否包含参数
            if (strpos($route, '{') !== false) {
                $pattern = preg_replace('#\{(\w+)\}#', '(\d+)', $route);
                $pattern = '#^' . $pattern . '$#';
                
                if (preg_match($pattern, $path, $matches)) {
                    $handler = $routeHandler;
                    // 将参数转换为整数
                    $params = array_map('intval', array_slice($matches, 1));
                    break;
                }
            } elseif ($route === $path) {
                $handler = $routeHandler;
                break;
            }
        }
    }
    
    if ($handler) {
        [$controllerClass, $action] = $handler;
        
        // 检查是否需要认证
        if (strpos($path, '/admin') === 0 && $path !== '/auth/login') {
            require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
            $authMiddleware = new \App\Middleware\AuthMiddleware();
            $authMiddleware->handle();
        }
        
        // 加载控制器
        $controllerFile = __DIR__ . '/../src/' . str_replace('\\', '/', str_replace('App\\', '', $controllerClass)) . '.php';
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller not found: {$controllerClass}");
        }
        require_once $controllerFile;
        
        $controller = new $controllerClass();
        $result = $controller->$action(...$params);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        $logger->warning("Route not found: {$method} {$path}");
        http_response_code(404);
        echo json_encode(Response::error('接口不存在', 404), JSON_UNESCAPED_UNICODE);
    }
    
} catch (Throwable $e) {
    $logger->error("Error: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $code = $e->getCode() ?: 500;
    if ($code < 100 || $code > 599) {
        $code = 500;
    }
    
    http_response_code($code);
    echo json_encode(Response::error($e->getMessage(), $code), JSON_UNESCAPED_UNICODE);
}
