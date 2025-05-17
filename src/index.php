<?php
// index.php

// 디버깅 활성화
ini_set('display_errors', 1); // 디버깅을 위해 오류 표시 활성화
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 기본 응답 헤더 설정
header('Content-Type: application/json');

// Composer 오토로더 로드
require_once __DIR__ . '/vendor/autoload.php';

// Database 클래스 직접 로드
require_once __DIR__ . '/config/Database.php';

// 필요한 테이블이 있는지 확인
function checkRequiredTables() {
    try {
        $db = \App\config\Database::getInstance()->getConnection();
        
        // users 테이블 확인
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        $usersExists = $stmt->rowCount() > 0;
        
        // login_attempts 테이블 확인
        $stmt = $db->query("SHOW TABLES LIKE 'login_attempts'");
        $loginAttemptsExists = $stmt->rowCount() > 0;
        
        // mfa_sessions 테이블 확인
        $stmt = $db->query("SHOW TABLES LIKE 'mfa_sessions'");
        $mfaSessionsExists = $stmt->rowCount() > 0;
        
        if (!$usersExists || !$loginAttemptsExists || !$mfaSessionsExists) {
            error_log("필요한 테이블이 없습니다. setup_db.php를 실행하세요.");
            // 여기서 자동으로 테이블 생성을 원한다면 setup_db.php의 코드를 호출할 수 있습니다.
            // include __DIR__ . '/setup_db.php';
        }
        
        return $usersExists && $loginAttemptsExists && $mfaSessionsExists;
    } catch (\Exception $e) {
        error_log("데이터베이스 테이블 확인 중 오류 발생: " . $e->getMessage());
        return false;
    }
}

// 테이블 확인 (필요한 경우 주석 해제)
// checkRequiredTables();

// vendor/autoload.php 경로 확인
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

$autoloadFile = null;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $autoloadFile = $path;
        break;
    }
}

if ($autoloadFile) {
    require_once $autoloadFile;
} else {
    // autoload.php 없을 경우 클래스 수동 로드
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = __DIR__ . '/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // 디버깅: 로드하려는 파일 경로 출력
        error_log("Trying to load: $file");
        
        if (file_exists($file)) {
            require $file;
            return true;
        } else {
            error_log("File not found: $file");
        }
        
        return false;
    });
}

// 필수 컨트롤러 수동 로드
$controllerFiles = [
    __DIR__ . '/controllers/AdminController.php',
    __DIR__ . '/controllers/AuthController.php'
];

foreach ($controllerFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        error_log("Loaded controller: $file");
    } else {
        error_log("Controller file not found: $file");
    }
}

// 라우팅
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 루트 경로(/)에 접속한 경우 index.html 제공
if ($requestMethod === 'GET' && ($requestUri === '/' || $requestUri === '')) {
    // HTML 응답이므로 헤더 변경
    header('Content-Type: text/html');
    if (file_exists(__DIR__ . '/index.html')) {
        echo file_get_contents(__DIR__ . '/index.html');
        exit;
    }
}

// CORS 설정 (필요한 경우)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["status" => "ok"]);
    exit();
}

// 라우트 정의
$routes = [
    // 관리자 라우트
    'POST /admin/users' => ['App\controllers\AdminController', 'registerUser'],
    'GET /admin/users' => ['App\controllers\AdminController', 'listUsers'],
    'POST /admin/users/qr' => ['App\controllers\AdminController', 'regenerateQR'],
    'POST /admin/users/enable-mfa' => ['App\controllers\AdminController', 'enableMFA'],
    'POST /admin/users/disable-mfa' => ['App\controllers\AdminController', 'disableMFA'],
    'DELETE /admin/users' => ['App\controllers\AdminController', 'deleteUser'],
    
    // 인증 라우트
    'POST /auth/login' => ['App\controllers\AuthController', 'login'],
    'GET /auth/validate' => ['App\controllers\AuthController', 'validateSession'],
    'POST /auth/logout' => ['App\controllers\AuthController', 'logout'],
];

// 라우트 매칭
$routeFound = false;
foreach ($routes as $route => $handler) {
    list($method, $path) = explode(' ', $route);
    
    if ($requestMethod === $method && $requestUri === $path) {
        try {
            // 디버깅: 클래스 존재 여부 확인
            if (!class_exists($handler[0])) {
                error_log("Class not found: " . $handler[0]);
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Controller not found: ' . $handler[0]]);
                exit;
            }
            
            $controller = new $handler[0]();
            $action = $handler[1];
            
            // 컨트롤러 메서드 실행을 try-catch로 감싸기
            try {
                $controller->$action();
            } catch (\Exception $e) {
                error_log("Controller error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
            }
            
            $routeFound = true;
            break;
        } catch (\Error $e) {
            error_log("PHP Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }
}

if (!$routeFound) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Route not found']);
}