<?php
// src/repositories/UserRepository.php

namespace App\repositories;

use App\config\Database;
use App\models\User;
use PDO;

class UserRepository {
    private $db;
    
    /**
     * 생성자에서 DB 연결 초기화
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * 신규 사용자 생성
     * @param string $username 사용자명
     * @param string $password 비밀번호
     * @param string $secret TOTP 비밀키
     * @return string 생성된 사용자 ID
     */
    public function createUser($username, $password, $secret) {
        // 평문 비밀번호 사용 (테스트용)
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, secret) 
            VALUES (:username, :password, :secret)
        ");
        
        $stmt->execute([
            'username' => $username,
            'password' => $password,
            'secret' => $secret
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 사용자명으로 사용자 조회
     * @param string $username 검색할 사용자명
     * @return User|null 사용자 객체 또는 null
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("
            SELECT * FROM users WHERE username = :username
        ");
        
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch();
        
        return $data ? new User($data) : null;
    }
    
    /**
     * ID로 사용자 조회
     * @param int $id 사용자 ID
     * @return User|null 사용자 객체 또는 null
     */
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM users WHERE id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        return $data ? new User($data) : null;
    }
    
    /**
     * MFA 활성화 처리
     * @param int $userId 사용자 ID
     */
    public function enableMFA($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET is_mfa_enabled = TRUE WHERE id = :id
        ");
        
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * MFA 비활성화 처리
     * @param int $userId 사용자 ID
     */
    public function disableMFA($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET is_mfa_enabled = FALSE WHERE id = :id
        ");
        
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * TOTP Secret 업데이트
     * @param int $userId 사용자 ID
     * @param string $secret 새 TOTP Secret 키
     * @return bool 성공 여부
     */
    public function updateSecret($userId, $secret) {
        $stmt = $this->db->prepare("
            UPDATE users SET secret = :secret WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $userId,
            'secret' => $secret
        ]);
    }
    
    /**
     * 모든 사용자 목록 조회
     * @return array User 객체 배열
     */
    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT * FROM users
        ");
        
        $results = $stmt->fetchAll();
        $users = [];
        
        foreach ($results as $row) {
            $users[] = new User($row);
        }
        
        return $users;
    }
    
    /**
     * 로그인 시도 기록 저장
     * @param string $username 사용자명
     * @param string $ipAddress IP 주소
     * @param bool $success 성공 여부
     */
    public function logLoginAttempt($username, $ipAddress, $success) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (username, ip_address, success) 
            VALUES (:username, :ip_address, :success)
        ");
        
        $stmt->execute([
            'username' => $username,
            'ip_address' => $ipAddress,
            'success' => $success
        ]);
    }
    
    /**
     * 최근 로그인 실패 횟수 조회 (계정 잠금 정책용)
     * @param string $username 사용자명
     * @param int $minutes 확인할 시간 범위(분)
     * @return int 실패 횟수
     */
    public function getRecentFailedAttempts($username, $minutes = 15) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM login_attempts 
            WHERE username = :username 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
        ");
        
        $stmt->execute([
            'username' => $username,
            'minutes' => $minutes
        ]);
        
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * 사용자 삭제 (관련 세션 및 로그인 기록 함께 삭제)
     * @param int $userId 사용자 ID
     * @return bool 성공 여부
     */
    public function deleteUser($userId) {
        // 사용자 세션 삭제
        $stmt = $this->db->prepare("
            DELETE FROM mfa_sessions WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        
        // 로그인 시도 기록 삭제
        $user = $this->findById($userId);
        if ($user) {
            $stmt = $this->db->prepare("
                DELETE FROM login_attempts WHERE username = :username
            ");
            $stmt->execute(['username' => $user->getUsername()]);
        }
        
        // 사용자 삭제
        $stmt = $this->db->prepare("
            DELETE FROM users WHERE id = :id
        ");
        
        return $stmt->execute(['id' => $userId]);
    }
}