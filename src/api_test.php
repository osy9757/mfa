<?php
// api_test.php - API 테스트 스크립트

// .env 파일 로드
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                putenv("$key=$value");
            }
        }
    }
}
loadEnv();

// 환경 변수에서 API 호스트 가져오기 (기본값은 localhost)
$apiHost = getenv('API_HOST') ?: 'localhost';
$apiPort = getenv('API_PORT') ?: '8080';

// 테스트할 API 엔드포인트
$apiUrl = "http://{$apiHost}:{$apiPort}/admin/users";

echo "=== API 테스트 시작 ===\n";
echo "URL: $apiUrl\n";

// cURL 세션 초기화
$ch = curl_init();

// cURL 옵션 설정
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// 요청 실행
echo "요청 전송 중...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 요청 결과 확인
echo "HTTP 상태 코드: $httpCode\n";
echo "응답 헤더:\n";
curl_setopt($ch, CURLOPT_HEADER, true);
$headerResponse = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($headerResponse, 0, $headerSize);
echo $header . "\n";

// JSON 형식 확인
echo "응답 내용:\n";
echo $response . "\n";

$isJson = json_decode($response) !== null;
echo "유효한 JSON 형식: " . ($isJson ? "예" : "아니오") . "\n";

if (!$isJson) {
    echo "JSON 디코딩 오류: " . json_last_error_msg() . "\n";
}

// cURL 세션 종료
curl_close($ch);

echo "=== API 테스트 종료 ===\n"; 