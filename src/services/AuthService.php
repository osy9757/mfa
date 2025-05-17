<?php
// src/services/AuthService.php

namespace App\services;

use App\repositories\UserRepository;
use App\services\TOTPService;

class AuthService {
    private $userRepository;
    private $totpService;
    
    /**
     * 생성자에서 필요한 의존성 초기화
     */
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->totpService = new TOTPService();
    }
    
    /**
     * 관리자용 사용자 등록 처리
     * @param string $username 사용자명
     * @param string $password 비밀번호
     * @return array 사용자 ID, secret, URI 정보
     * @throws \Exception 중복 사용자명 등 오류 발생 시
     */
    public function registerUser($username, $password) {
        // 중복 사용자 확인
        if ($this->userRepository->findByUsername($username)) {
            throw new \Exception("사용자명이 이미 존재합니다.");
        }
        
        // TOTP secret 생성
        $secret = $this->totpService->generateSecret();
        
        // 사용자 생성
        $userId = $this->userRepository->createUser($username, $password, $secret);
        
        // QR 코드 URI 생성
        $uri = $this->totpService->generateUri($secret, $username);
        
        return [
            'userId' => $userId,
            'secret' => $secret,
            'uri' => $uri
        ];
    }
    
    /**
     * 로그인 처리 및 세션 생성
     * @param string $username 사용자명
     * @param string $password 비밀번호
     * @param string $totpCode TOTP 인증 코드
     * @param string $ipAddress 접속 IP 주소
     * @return array 세션 정보
     * @throws \Exception 로그인 실패 시
     */
    public function login($username, $password, $totpCode, $ipAddress = null) {
        // 로그인 시도 제한 확인
        $failedAttempts = $this->userRepository->getRecentFailedAttempts($username);
        if ($failedAttempts >= 5) {
            throw new \Exception("너무 많은 로그인 시도가 있었습니다. 잠시 후 다시 시도해주세요.");
        }
        
        // 사용자 확인
        $user = $this->userRepository->findByUsername($username);
        if (!$user) {
            $this->userRepository->logLoginAttempt($username, $ipAddress, false);
            throw new \Exception("아이디 또는 비밀번호가 올바르지 않습니다.");
        }
        
        // 비밀번호 확인
        if (!$user->verifyPassword($password)) {
            $this->userRepository->logLoginAttempt($username, $ipAddress, false);
            throw new \Exception("아이디 또는 비밀번호가 올바르지 않습니다.");
        }
        
        // MFA가 활성화된 경우 TOTP 코드 확인
        if ($user->getIsMfaEnabled()) {
            if (!$this->totpService->verifyCode($user->getSecret(), $totpCode)) {
                $this->userRepository->logLoginAttempt($username, $ipAddress, false);
                throw new \Exception("인증 코드가 올바르지 않습니다.");
            }
        }
        
        // 로그인 성공
        $this->userRepository->logLoginAttempt($username, $ipAddress, true);
        
        // 세션 생성
        $sessionToken = $this->generateSessionToken();
        $this->createSession($user->getId(), $sessionToken);
        
        return [
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'sessionToken' => $sessionToken
        ];
    }
    
    /**
     * MFA 활성화 처리
     * @param int $userId 사용자 ID
     */
    public function enableMFA($userId) {
        $this->userRepository->enableMFA($userId);
    }
    
    /**
     * MFA 비활성화 처리
     * @param int $userId 사용자 ID
     */
    public function disableMFA($userId) {
        $this->userRepository->disableMFA($userId);
    }
    
    /**
     * TOTP QR 코드 URI 생성
     * @param string $secret TOTP 비밀키
     * @param string $username 사용자명
     * @return string URI 문자열
     */
    public function generateTOTPUri($secret, $username) {
        return $this->totpService->generateUri($secret, $username);
    }
    
    /**
     * 세션 토큰 생성
     * @return string 랜덤 세션 토큰
     */
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * 세션 생성 및 저장
     * @param int $userId 사용자 ID
     * @param string $sessionToken 세션 토큰
     */
    private function createSession($userId, $sessionToken) {
        $db = \App\config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO mfa_sessions (user_id, session_token, expires_at) 
            VALUES (:user_id, :session_token, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'session_token' => $sessionToken
        ]);
    }
    
    /**
     * 세션 토큰 검증
     * @param string $sessionToken 세션 토큰
     * @return User|null 사용자 객체 또는 null
     */
    public function validateSession($sessionToken) {
        $db = \App\config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.* 
            FROM mfa_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.session_token = :session_token 
            AND s.expires_at > NOW()
        ");
        
        $stmt->execute(['session_token' => $sessionToken]);
        $data = $stmt->fetch();
        
        return $data ? new \App\models\User($data) : null;
    }
}