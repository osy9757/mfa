<?php
// QR 코드 서비스
// QR 코드 생성 및 처리 기능 제공

namespace App\services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QRService {
    
    /**
     * QR 코드 이미지(SVG) 생성
     * @param string $uri 인코딩할 URI 또는 텍스트
     * @param int $size 이미지 크기(픽셀)
     * @return string SVG 형식의 QR 코드 이미지 데이터
     */
    public function generateQRCode($uri, $size = 300) {
        // SVG 백엔드 사용 (libxml 필요)
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        // SVG 이미지 반환
        return $writer->writeString($uri);
    }
    
    /**
     * QR 코드 이미지를 파일로 저장
     * @param string $uri 인코딩할 URI 또는 텍스트
     * @param string $filePath 저장할 파일 경로
     * @param int $size 이미지 크기(픽셀)
     */
    public function saveQRCodeToFile($uri, $filePath, $size = 300) {
        // SVG 백엔드 사용
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $writer->writeFile($uri, $filePath);
    }
    
    /**
     * QR 코드 HTML 이미지 태그 생성
     * @param string $uri 인코딩할 URI 또는 텍스트
     * @param int $size 이미지 크기(픽셀)
     * @return string HTML 형식의 SVG 태그
     */
    public function generateQRCodeHtml($uri, $size = 300) {
        $svgData = $this->generateQRCode($uri, $size);
        
        // SVG 데이터를 HTML에 직접 삽입
        return sprintf(
            '<div style="width:%dpx; height:%dpx;">%s</div>',
            $size,
            $size,
            $svgData
        );
    }
}