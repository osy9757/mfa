<?php
// src/models/User.php

namespace App\models;

use JsonSerializable;

class User implements JsonSerializable {
    private $id;
    private $username;
    private $password;
    private $secret;
    private $isMfaEnabled;
    private $createdAt;
    private $updatedAt;
    
    /**
     * 생성자
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * 배열 데이터로 객체 속성 채우기
     */
    public function hydrate(array $data) {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->secret = $data['secret'] ?? null;
        $this->isMfaEnabled = isset($data['is_mfa_enabled']) ? (bool)$data['is_mfa_enabled'] : false;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }
    
    /**
     * 객체를 배열로 변환
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'secret' => $this->secret,
            'is_mfa_enabled' => $this->isMfaEnabled,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
    
    /**
     * ID 접근자
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * ID 설정자
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * 사용자명 접근자
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * 사용자명 설정자
     */
    public function setUsername($username) {
        $this->username = $username;
    }
    
    /**
     * 비밀번호 접근자
     */
    public function getPassword() {
        return $this->password;
    }
    
    /**
     * 비밀번호 설정자 (평문 저장)
     */
    public function setPassword($password) {
        $this->password = $password; // 테스트용으로 평문 저장
    }
    
    /**
     * 비밀번호 확인
     */
    public function verifyPassword($password) {
        return $this->password === $password; // 평문 비교
    }
    
    /**
     * MFA Secret 접근자
     */
    public function getSecret() {
        return $this->secret;
    }
    
    /**
     * MFA Secret 설정자
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }
    
    /**
     * MFA 활성화 상태 접근자
     */
    public function getIsMfaEnabled() {
        return $this->isMfaEnabled;
    }
    
    /**
     * MFA 활성화 설정자
     */
    public function setIsMfaEnabled($enabled) {
        $this->isMfaEnabled = (bool)$enabled;
    }
    
    /**
     * 생성일 접근자
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }
    
    /**
     * 생성일 설정자
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }
    
    /**
     * 수정일 접근자
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
    
    /**
     * 수정일 설정자
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;
    }
    
    /**
     * 유효성 검사
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->username)) {
            $errors[] = '사용자명은 필수입니다.';
        } elseif (strlen($this->username) < 3) {
            $errors[] = '사용자명은 최소 3자 이상이어야 합니다.';
        } elseif (strlen($this->username) > 50) {
            $errors[] = '사용자명은 최대 50자 이하여야 합니다.';
        }
        
        if (empty($this->password) && $this->id === null) {
            $errors[] = '비밀번호는 필수입니다.';
        }
        
        return $errors;
    }
    
    /**
     * 비밀번호 강도 검사
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = '비밀번호는 최소 8자 이상이어야 합니다.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = '비밀번호는 대문자를 포함해야 합니다.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = '비밀번호는 소문자를 포함해야 합니다.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = '비밀번호는 숫자를 포함해야 합니다.';
        }
        
        return $errors;
    }
    
    /**
     * 내보낼 안전한 데이터 (비밀번호 제외)
     */
    public function toSafeArray() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'is_mfa_enabled' => $this->isMfaEnabled ? true : false,
            'created_at' => $this->createdAt
        ];
    }
    
    /**
     * JSON 직렬화
     */
    public function jsonSerialize(): array {
        return $this->toSafeArray();
    }
}