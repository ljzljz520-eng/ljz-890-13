<?php
/**
 * 文件上传控制器
 */

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;

class UploadController
{
    private Logger $logger;
    private string $uploadDir;
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private int $maxSize = 20 * 1024 * 1024; // 20MB
    
    public function __construct()
    {
        $this->logger = Logger::getInstance();
        // 使用绝对路径确保正确
        $this->uploadDir = '/var/www/html/public/uploads/';
        
        // 确保上传目录存在
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
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
        $targetPath = $this->uploadDir . $newFilename;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->logger->error("File upload failed", ['filename' => $file['name']]);
            return Response::error('文件上传失败，请重试', 500);
        }
        
        // 返回文件URL
        $fileUrl = '/uploads/' . $newFilename;
        
        $this->logger->info("File uploaded", ['filename' => $newFilename, 'size' => $file['size']]);
        
        return Response::success([
            'url' => $fileUrl,
            'filename' => $newFilename,
            'size' => $file['size']
        ], '文件上传成功');
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
