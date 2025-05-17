<?php
// 인증 기능 컨트롤러
// 로그인, 세션 검증, 로그아웃 등 인증 관련 API 엔드포인트 처리

namespace App\controllers;

use App\services\AuthService;

class AuthController {
    private $authService;
    
    /**
     * 생성자에서 인증 서비스 초기화
     */
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * 로그인 처리 API
     * POST /auth/login
     */
    public function login() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['password'])) {
                throw new \Exception("사용자명과 비밀번호는 필수입니다.");
            }
            
            $totpCode = $input['totpCode'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $result = $this->authService->login(
                $input['username'], 
                $input['password'], 
                $totpCode,
                $ipAddress
            );
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 세션 검증 API
     * GET /auth/validate
     */
    public function validateSession() {
        header('Content-Type: application/json');
        
        try {
            $headers = getallheaders();
            $sessionToken = $headers['Authorization'] ?? null;
            
            if (!$sessionToken) {
                throw new \Exception("인증 토큰이 필요합니다.");
            }
            
            $user = $this->authService->validateSession($sessionToken);
            
            if (!$user) {
                throw new \Exception("유효하지 않은 세션입니다.");
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'userId' => $user->getId(),
                    'username' => $user->getUsername()
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 로그아웃 처리 API
     * POST /auth/logout
     */
    public function logout() {
        header('Content-Type: application/json');
        
        try {
            $headers = getallheaders();
            $sessionToken = $headers['Authorization'] ?? null;
            
            if ($sessionToken) {
                // 세션 무효화
                $db = \App\config\Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    DELETE FROM mfa_sessions WHERE session_token = :session_token
                ");
                $stmt->execute(['session_token' => $sessionToken]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => '로그아웃되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}