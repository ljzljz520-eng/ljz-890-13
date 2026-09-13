<?php
/**
 * 文件上传控制器
 * 
 * 原图与缩略图分开保存：
 *   - 原图：/uploads/originals/
 *   - 缩略图：/uploads/thumbs/
 */

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;

class UploadController
{
    private Logger $logger;
    private string $uploadDir;
    private string $originalDir;
    private string $thumbDir;
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private int $maxSize = 20 * 1024 * 1024; // 20MB
    private int $thumbMaxSize = 480; // 缩略图最长边（像素）
    
    public function __construct()
    {
        $this->logger = Logger::getInstance();
        // 使用绝对路径确保正确
        $this->uploadDir = '/var/www/html/public/uploads/';
        $this->originalDir = $this->uploadDir . 'originals/';
        $this->thumbDir = $this->uploadDir . 'thumbs/';
        
        // 确保上传目录存在（原图与缩略图分开存放）
        foreach ([$this->uploadDir, $this->originalDir, $this->thumbDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * 处理文件上传
     */
    public function upload(): array
    {
        if (!isset($_FILES['file'])) {
            return Response::error('请选择要上传的文件', 400);
        }
        
        $file = $_FILES['file'];
        
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return Response::error($this->getUploadError($file['error']), 400);
        }
        
        // 检查文件大小
        if ($file['size'] > $this->maxSize) {
            return Response::error('文件大小不能超过20MB', 400);
        }
        
        // 检查文件类型
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return Response::error('只支持 JPG、PNG、GIF、WebP 格式的图片', 400);
        }
        
        // 生成新文件名
        $extension = $this->getExtension($mimeType);
        $newFilename = date('Ymd') . '_' . uniqid() . '.' . $extension;
        $originalPath = $this->originalDir . $newFilename;
        
        // 保存原图
        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            $this->logger->error("File upload failed", ['filename' => $file['name']]);
            return Response::error('文件上传失败，请重试', 500);
        }
        
        // 生成缩略图（失败时回退使用原图，保证页面总能正常展示）
        $thumbFilename = 'thumb_' . $newFilename;
        $thumbPath = $this->thumbDir . $thumbFilename;
        $thumbUrl = '/uploads/originals/' . $newFilename;
        
        if ($this->createThumbnail($originalPath, $thumbPath, $mimeType)) {
            $thumbUrl = '/uploads/thumbs/' . $thumbFilename;
        } else {
            $this->logger->warning("Thumbnail generation failed, fallback to original", [
                'filename' => $newFilename
            ]);
        }
        
        // 返回文件URL
        $fileUrl = '/uploads/originals/' . $newFilename;
        
        $this->logger->info("File uploaded", [
            'filename' => $newFilename,
            'size' => $file['size'],
            'thumb' => $thumbUrl
        ]);
        
        return Response::success([
            'url' => $fileUrl,
            'thumb_url' => $thumbUrl,
            'filename' => $newFilename,
            'size' => $file['size']
        ], '文件上传成功');
    }
    
    /**
     * 使用 GD 生成缩略图（保持比例，最长边不超过限制）
     */
    private function createThumbnail(string $sourcePath, string $targetPath, string $mimeType): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        // 按类型读取原图（GIF 取第一帧生成静态缩略图）
        switch ($mimeType) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
                break;
            default:
                $src = false;
        }
        
        if (!$src) {
            return false;
        }
        
        $width = imagesx($src);
        $height = imagesy($src);
        
        if ($width <= 0 || $height <= 0) {
            imagedestroy($src);
            return false;
        }
        
        // 等比缩放，小图不放大
        $scale = min(1, $this->thumbMaxSize / max($width, $height));
        $newWidth = max(1, (int)round($width * $scale));
        $newHeight = max(1, (int)round($height * $scale));
        
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if (!$dst) {
            imagedestroy($src);
            return false;
        }
        
        // PNG / GIF 保留透明通道
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // 按原格式保存缩略图
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($dst, $targetPath, 82);
                break;
            case 'image/png':
                $result = imagepng($dst, $targetPath, 6);
                break;
            case 'image/gif':
                $result = imagegif($dst, $targetPath);
                break;
            case 'image/webp':
                $result = function_exists('imagewebp') ? imagewebp($dst, $targetPath, 80) : false;
                break;
        }
        
        imagedestroy($src);
        imagedestroy($dst);
        
        return (bool)$result;
    }
    
    /**
     * 获取上传错误信息
     */
    private function getUploadError(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件上传不完整',
            UPLOAD_ERR_NO_FILE => '没有选择文件',
            UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不存在',
            UPLOAD_ERR_CANT_WRITE => '服务器写入失败',
            UPLOAD_ERR_EXTENSION => '文件类型被禁止'
        ];
        
        return $errors[$code] ?? '未知上传错误';
    }
    
    /**
     * 根据MIME类型获取扩展名
     */
    private function getExtension(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $extensions[$mimeType] ?? 'jpg';
    }
}
