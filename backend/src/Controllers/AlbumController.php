<?php
/**
 * 相册控制器
 */

namespace App\Controllers;

use App\Models\Album;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class AlbumController
{
    private Album $albumModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/Album.php';
        $this->albumModel = new Album();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 获取所有相册（前端展示用，含照片数量）
     */
    public function getAll(): array
    {
        $albums = $this->albumModel->getAllWithPhotoCount();
        return Response::success($albums);
    }
    
    /**
     * 获取所有相册（后台管理用，含照片数量）
     */
    public function getAllAdmin(): array
    {
        $albums = $this->albumModel->getAllWithPhotoCount();
        return Response::success($albums);
    }
    
    /**
     * 创建相册
     */
    public function create(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $validator = new Validator($data);
        $validator->required('name', '相册名称')
                  ->maxLength('name', 100, '相册名称')
                  ->maxLength('description', 255, '相册说明');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $insertData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->albumModel->create($insertData);
        $album = $this->albumModel->find($id);
        
        $this->logger->info("Album created", ['id' => $id, 'name' => $insertData['name']]);
        
        return Response::success($album, '相册创建成功');
    }
    
    /**
     * 更新相册
     */
    public function update(int $id): array
    {
        $album = $this->albumModel->find($id);
        
        if (!$album) {
            return Response::error('相册不存在', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $validator = new Validator($data);
        $validator->required('name', '相册名称')
                  ->maxLength('name', 100, '相册名称')
                  ->maxLength('description', 255, '相册说明');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $updateData = [
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ];
        
        $this->albumModel->update($id, $updateData);
        $album = $this->albumModel->find($id);
        
        $this->logger->info("Album updated", ['id' => $id]);
        
        return Response::success($album, '相册更新成功');
    }
    
    /**
     * 删除相册（相册内照片转为未分类，不删除照片本身）
     */
    public function delete(int $id): array
    {
        $album = $this->albumModel->find($id);
        
        if (!$album) {
            return Response::error('相册不存在', 404);
        }
        
        // 将该相册下的照片置为未分类
        $db = \App\Config\Database::getInstance();
        $stmt = $db->prepare("UPDATE photos SET album_id = NULL WHERE album_id = ?");
        $stmt->execute([$id]);
        
        $this->albumModel->delete($id);
        
        $this->logger->info("Album deleted", ['id' => $id]);
        
        return Response::success(null, '相册已删除，其中的照片已转为未分类');
    }
}
