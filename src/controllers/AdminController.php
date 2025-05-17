<?php
// src/controllers/AdminController.php

namespace App\controllers;

use App\services\AuthService;
use App\services\QRService;
use App\repositories\UserRepository;

class AdminController {
    private $authService;
    private $qrService;
    private $userRepository;
    
    /**
     * 생성자에서 필요한 서비스 초기화
     */
    public function __construct() {
        $this->authService = new AuthService();
        $this->qrService = new QRService();
        $this->userRepository = new UserRepository();
    }
    
    /**
     * 사용자 등록 및 QR 코드 생성 API
     * POST /admin/users
     */
    public function registerUser() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['password'])) {
                throw new \Exception("사용자명과 비밀번호는 필수입니다.");
            }
            
            // 사용자 등록
            $result = $this->authService->registerUser(
                $input['username'], 
                $input['password']
            );
            
            // QR 코드 생성
            $qrCodeHtml = $this->qrService->generateQRCodeHtml($result['uri']);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'userId' => $result['userId'],
                    'username' => $input['username'],
                    'qrCode' => $qrCodeHtml,
                    'secret' => $result['secret']
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 모든 사용자 목록 조회 API
     * GET /admin/users
     */
    public function listUsers() {
        header('Content-Type: application/json');
        
        try {
            $users = $this->userRepository->getAllUsers();
            
            echo json_encode([
                'success' => true,
                'data' => $users
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 사용자 QR 코드 재생성 API
     * POST /admin/users/qr
     */
    public function regenerateQR() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['userId'])) {
                throw new \Exception("사용자 ID는 필수입니다.");
            }
            
            $user = $this->userRepository->findById($input['userId']);
            if (!$user) {
                throw new \Exception("사용자를 찾을 수 없습니다.");
            }
            
            // QR 코드 URI 재생성
            $uri = $this->authService->generateTOTPUri(
                $user->getSecret(), 
                $user->getUsername()
            );
            
            // QR 코드 이미지 생성
            $qrCodeHtml = $this->qrService->generateQRCodeHtml($uri);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'qrCode' => $qrCodeHtml,
                    'username' => $user->getUsername()
                ]
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * MFA 활성화 API
     * POST /admin/users/enable-mfa
     */
    public function enableMFA() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['userId'])) {
                throw new \Exception("사용자 ID는 필수입니다.");
            }
            
            $this->authService->enableMFA($input['userId']);
            
            echo json_encode([
                'success' => true,
                'message' => 'MFA가 활성화되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * MFA 비활성화 API
     * POST /admin/users/disable-mfa
     */
    public function disableMFA() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['userId'])) {
                throw new \Exception("사용자 ID는 필수입니다.");
            }
            
            $this->userRepository->disableMFA($input['userId']);
            
            echo json_encode([
                'success' => true,
                'message' => 'MFA가 비활성화되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 사용자 삭제 API
     * DELETE /admin/users
     */
    public function deleteUser() {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['userId'])) {
                throw new \Exception("사용자 ID는 필수입니다.");
            }
            
            $userId = $input['userId'];
            $success = $this->userRepository->deleteUser($userId);
            
            if (!$success) {
                throw new \Exception("사용자 삭제 중 오류가 발생했습니다.");
            }
            
            echo json_encode([
                'success' => true,
                'message' => '사용자가 삭제되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}