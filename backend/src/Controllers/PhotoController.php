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
     * 获取所有照片（前端展示用）
     */
    public function getAll(): array
    {
        $photos = $this->photoModel->getAllSorted();
        return Response::success($photos);
    }
    
    /**
     * 获取所有照片（后台管理用）
     */
    public function getAllAdmin(): array
    {
        $photos = $this->photoModel->getAllSorted();
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
                  ->required('image_url', '图片地址');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $insertData = [
            'title' => trim($data['title']),
            'image_url' => $data['image_url'],
            'description' => $data['description'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->photoModel->create($insertData);
        $photo = $this->photoModel->find($id);
        
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
                  ->required('image_url', '图片地址');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $updateData = [
            'title' => trim($data['title']),
            'image_url' => $data['image_url'],
            'description' => $data['description'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ];
        
        $this->photoModel->update($id, $updateData);
        $photo = $this->photoModel->find($id);
        
        $this->logger->info("Photo updated", ['id' => $id]);
        
        return Response::success($photo, '照片更新成功');
    }
    
    /**
     * 删除照片
     */
    public function delete(int $id): array
    {
        $photo = $this->photoModel->find($id);
        
        if (!$photo) {
            return Response::error('照片不存在', 404);
        }
        
        $this->photoModel->delete($id);
        
        $this->logger->info("Photo deleted", ['id' => $id]);
        
        return Response::success(null, '照片删除成功');
    }
}
