<?php
/**
 * 照片控制器
 */

namespace App\Controllers;

use App\Models\Photo;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class PhotoController
{
    private Photo $photoModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/Photo.php';
        $this->photoModel = new Photo();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 获取所有照片（前端展示用，支持按相册筛选）
     */
    public function getAll(): array
    {
        $albumId = null;
        if (isset($_GET['album_id']) && $_GET['album_id'] !== '') {
            $albumId = (int)$_GET['album_id'];
        }
        
        $photos = $this->photoModel->getAllSorted($albumId);
        return Response::success($photos);
    }
    
    /**
     * 获取所有照片（后台管理用，支持按相册筛选）
     */
    public function getAllAdmin(): array
    {
        $albumId = null;
        if (isset($_GET['album_id']) && $_GET['album_id'] !== '') {
            $albumId = (int)$_GET['album_id'];
        }
        
        $photos = $this->photoModel->getAllSorted($albumId);
        return Response::success($photos);
    }
    
    /**
     * 创建照片
     */
    public function create(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('title', '标题')
                  ->maxLength('title', 255, '标题')
                  ->required('image_url', '图片地址')
                  ->date('taken_at', '拍摄时间');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $insertData = [
            'album_id' => !empty($data['album_id']) ? (int)$data['album_id'] : null,
            'title' => trim($data['title']),
            'image_url' => $data['image_url'],
            'thumb_url' => $data['thumb_url'] ?? null,
            'description' => $data['description'] ?? '',
            'taken_at' => !empty($data['taken_at']) ? $data['taken_at'] : null,
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->photoModel->create($insertData);
        $photo = $this->photoModel->findWithAlbum($id);
        
        $this->logger->info("Photo created", ['id' => $id, 'title' => $insertData['title']]);
        
        return Response::success($photo, '照片创建成功');
    }
    
    /**
     * 更新照片
     */
    public function update(int $id): array
    {
        $photo = $this->photoModel->find($id);
        
        if (!$photo) {
            return Response::error('照片不存在', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('title', '标题')
                  ->maxLength('title', 255, '标题')
                  ->required('image_url', '图片地址')
                  ->date('taken_at', '拍摄时间');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $updateData = [
            'album_id' => !empty($data['album_id']) ? (int)$data['album_id'] : null,
            'title' => trim($data['title']),
            'image_url' => $data['image_url'],
            'thumb_url' => $data['thumb_url'] ?? null,
            'description' => $data['description'] ?? '',
            'taken_at' => !empty($data['taken_at']) ? $data['taken_at'] : null,
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ];
        
        $this->photoModel->update($id, $updateData);
        $photo = $this->photoModel->findWithAlbum($id);
        
        $this->logger->info("Photo updated", ['id' => $id]);
        
        return Response::success($photo, '照片更新成功');
    }
    
    /**
     * 删除照片（同时清理上传目录中的原图与缩略图文件）
     */
    public function delete(int $id): array
    {
        $photo = $this->photoModel->find($id);
        
        if (!$photo) {
            return Response::error('照片不存在', 404);
        }
        
        $this->photoModel->delete($id);
        
        // 清理本地上传的文件（仅处理 /uploads/ 路径，避免误删静态资源）
        $this->removeUploadedFile($photo['image_url'] ?? '');
        $this->removeUploadedFile($photo['thumb_url'] ?? '');
        
        $this->logger->info("Photo deleted", ['id' => $id]);
        
        return Response::success(null, '照片删除成功');
    }
    
    /**
     * 删除上传目录中的文件
     */
    private function removeUploadedFile(string $url): void
    {
        if ($url === '' || strpos($url, '/uploads/') !== 0) {
            return;
        }
        
        $path = '/var/www/html/public' . $url;
        $realBase = realpath('/var/www/html/public/uploads');
        $realPath = realpath($path);
        
        // 确保文件确实位于 uploads 目录内
        if ($realPath && $realBase && strpos($realPath, $realBase) === 0 && is_file($realPath)) {
            @unlink($realPath);
        }
    }
}
