<?php
// TOTP 서비스
// TOTP(시간 기반 일회용 비밀번호) 생성 및 검증 기능 제공

namespace App\services;

use OTPHP\TOTP;

class TOTPService {
    
    /**
     * TOTP 비밀키 생성
     * @return string 랜덤 비밀키
     */
    public function generateSecret() {
        $otp = TOTP::create();
        return $otp->getSecret();
    }
    
    /**
     * TOTP URI 생성 (QR 코드 생성용)
     * @param string $secret TOTP 비밀키
     * @param string $username 사용자명
     * @param string $issuer 발급자명 (앱 표시용)
     * @return string URI 문자열
     */
    public function generateUri($secret, $username, $issuer = 'MFA System') {
        $otp = TOTP::create($secret);
        $otp->setLabel($username);
        $otp->setIssuer($issuer);
        return $otp->getProvisioningUri();
    }
    
    /**
     * TOTP 코드 검증
     * @param string $secret TOTP 비밀키
     * @param string $code 사용자 입력 코드
     * @return bool 검증 결과
     */
    public function verifyCode($secret, $code) {
        if (!$code) {
            return false;
        }
        
        $otp = TOTP::create($secret);
        // 시간 오차를 고려하여 30초 전후까지 허용
        return $otp->verify($code, null, 1);
    }
    
    /**
     * 현재 TOTP 코드 가져오기 (디버깅용)
     * @param string $secret TOTP 비밀키
     * @return string 현재 유효한 OTP 코드
     */
    public function getCurrentCode($secret) {
        $otp = TOTP::create($secret);
        return $otp->now();
    }
}