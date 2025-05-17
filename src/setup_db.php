<?php
// setup_db.php - 데이터베이스 테이블 생성 스크립트

require_once __DIR__ . '/config/Database.php';

use App\config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "데이터베이스 연결 성공!\n";
    
    // users 테이블 생성
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            secret VARCHAR(255) NOT NULL,
            is_mfa_enabled BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "users 테이블 생성 완료\n";
    
    // login_attempts 테이블 생성
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success BOOLEAN DEFAULT FALSE
        )
    ");
    echo "login_attempts 테이블 생성 완료\n";
    
    // mfa_sessions 테이블 생성
    $db->exec("
        CREATE TABLE IF NOT EXISTS mfa_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    echo "mfa_sessions 테이블 생성 완료\n";
    
    echo "모든 테이블이 성공적으로 생성되었습니다!\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
} 