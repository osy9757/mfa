# PHP 기반 다중 인증(MFA) 시스템

이 프로젝트는 PHP와 MySQL을 사용하여 구현한 다중 인증(Multi-Factor Authentication) 시스템입니다. 사용자 등록, 로그인, TOTP(Time-based One-Time Password) 인증, QR 코드 생성 등의 기능을 제공합니다.

## 주요 기능

- 사용자 등록 및 관리
- 시간 기반 일회용 비밀번호(TOTP) 인증
- QR 코드 생성 (OTP 앱 연동용)
- 세션 기반 인증 관리
- MFA 활성화/비활성화 토글 기능
- 로그인 실패 제한 (보안 기능)

## 설치 방법

### 요구 사항

- PHP 7.4 이상
- MySQL 5.7 이상
- Composer (의존성 관리)
- 다음 PHP 확장 필요:
  - PDO
  - libxml (SVG QR 코드 생성)

### 설치 단계

1. 저장소 클론:
   ```
   git clone https://github.com/yourusername/mfa-system.git
   cd mfa-system
   ```

2. Composer로 의존성 설치:
   ```
   composer install
   ```

3. 데이터베이스 설정:
   - `src/config/Database.php` 파일에서 데이터베이스 연결 정보를 수정하세요.
   - MySQL에서 데이터베이스 생성:
     ```sql
     CREATE DATABASE mfa;
     CREATE USER 'mfa_user'@'localhost' IDENTIFIED BY 'password123';
     GRANT ALL PRIVILEGES ON mfa.* TO 'mfa_user'@'localhost';
     FLUSH PRIVILEGES;
     ```

4. 테이블 생성:
   ```
   php src/setup_db.php
   ```
   또는 `src/user.sql` 파일을 MySQL에서 실행하여 필요한 테이블을 생성합니다.

5. 웹 서버 시작:
   ```
   cd src
   php -S localhost:8080
   ```

6. 브라우저에서 `http://localhost:8080`으로 접속합니다.

## 시스템 구조

이 프로젝트는 MVC 아키텍처를 따르며 다음과 같은 구조로 구성되어 있습니다:

```
src/
├── config/         - 데이터베이스 설정 등 구성 파일
├── controllers/    - API 엔드포인트 처리 로직
├── models/         - 데이터 모델 클래스
├── repositories/   - 데이터베이스 액세스 로직
├── services/       - 비즈니스 로직
├── vendor/         - Composer 의존성
├── index.php       - 메인 진입점 & 라우터
├── index.html      - 프론트엔드 UI
└── setup_db.php    - 데이터베이스 설정 스크립트
```

### 데이터베이스 구조

시스템은 다음 3개의 테이블을 사용합니다:

1. `users` - 사용자 정보 저장
   - id
   - username
   - password
   - secret (TOTP 비밀키)
   - is_mfa_enabled
   - created_at, updated_at

2. `mfa_sessions` - 인증 세션 관리
   - id
   - user_id
   - session_token
   - expires_at
   - created_at

3. `login_attempts` - 로그인 시도 기록 (보안용)
   - id
   - username
   - ip_address
   - success
   - attempt_time

## API 엔드포인트

### 관리자 API

- `POST /admin/users` - 사용자 등록
- `GET /admin/users` - 사용자 목록 조회
- `POST /admin/users/qr` - QR 코드 재생성
- `POST /admin/users/enable-mfa` - MFA 활성화
- `POST /admin/users/disable-mfa` - MFA 비활성화
- `DELETE /admin/users` - 사용자 삭제

### 인증 API

- `POST /auth/login` - 로그인 (MFA 코드 포함)
- `GET /auth/validate` - 세션 유효성 검증
- `POST /auth/logout` - 로그아웃

## 사용 방법

1. 사용자 등록:
   - 관리자 기능 섹션에서 사용자 등록 양식을 작성
   - QR 코드를 Google Authenticator, Microsoft Authenticator 등 OTP 앱에 스캔

2. 로그인:
   - 사용자명과 비밀번호 입력
   - MFA가 활성화된 경우 OTP 앱에서 생성된 6자리 코드 입력

3. MFA 관리:
   - 관리자는 사용자 목록에서 MFA 활성화/비활성화 가능
   - QR 코드 재생성 가능 (사용자가 코드를 분실한 경우)

## 보안 고려사항

- 이 데모 시스템은 **테스트 목적**으로 현재 비밀번호를 평문으로 저장합니다.
- 프로덕션 환경에서는 반드시 비밀번호 해싱을 사용해야 합니다. 
- 프로덕션 환경을 위해 `UserRepository.php`와 `User.php`의 비밀번호 처리 메서드를 수정하세요.
- 더 안전한 세션 관리와 HTTPS 사용을 권장합니다.

## 문제 해결

### 일반적인 문제

1. 데이터베이스 연결 오류
   - 데이터베이스 연결 정보가 정확한지 확인하세요
   - MySQL 서비스가 실행 중인지 확인하세요

2. QR 코드 생성 실패
   - PHP libxml 확장이 설치되어 있는지 확인하세요
   - SVG 생성에 필요한 의존성이 설치되었는지 확인하세요

3. 세션 관리 문제
   - 로그아웃 후에도 로그인 상태가 유지되면 세션 만료 시간을 확인하세요
   - 브라우저 로컬 스토리지를 삭제해보세요

### 테스트 환경 로그 확인

오류 디버깅을 위해 PHP 로그를 확인하세요:
```
php -S localhost:8080 -d error_reporting=E_ALL -d display_errors=1
```

