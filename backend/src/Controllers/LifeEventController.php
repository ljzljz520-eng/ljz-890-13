<?php
/**
 * 生平事件控制器
 */

namespace App\Controllers;

use App\Models\LifeEvent;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class LifeEventController
{
    private LifeEvent $eventModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/LifeEvent.php';
        $this->eventModel = new LifeEvent();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 获取所有事件（前端展示用）
     */
    public function getAll(): array
    {
        $events = $this->eventModel->getAllSorted();
        return Response::success($events);
    }
    
    /**
     * 获取所有事件（后台管理用）
     */
    public function getAllAdmin(): array
    {
        $events = $this->eventModel->getAllForAdmin();
        return Response::success($events);
    }
    
    /**
     * 创建事件
     */
    public function create(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('title', '标题')
                  ->maxLength('title', 255, '标题')
                  ->required('event_date', '事件日期')
                  ->date('event_date', '事件日期');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $insertData = [
            'title' => trim($data['title']),
            'event_date' => $data['event_date'],
            'content' => $data['content'] ?? '',
            'image_url' => $data['image_url'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->eventModel->create($insertData);
        $event = $this->eventModel->find($id);
        
        $this->logger->info("Life event created", ['id' => $id, 'title' => $insertData['title']]);
        
        return Response::success($event, '事件创建成功');
    }
    
    /**
     * 更新事件
     */
    public function update(int $id): array
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return Response::error('事件不存在', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('title', '标题')
                  ->maxLength('title', 255, '标题')
                  ->required('event_date', '事件日期')
                  ->date('event_date', '事件日期');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $updateData = [
            'title' => trim($data['title']),
            'event_date' => $data['event_date'],
            'content' => $data['content'] ?? '',
            'image_url' => $data['image_url'] ?? '',
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ];
        
        $this->eventModel->update($id, $updateData);
        $event = $this->eventModel->find($id);
        
        $this->logger->info("Life event updated", ['id' => $id]);
        
        return Response::success($event, '事件更新成功');
    }
    
    /**
     * 删除事件
     */
    public function delete(int $id): array
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return Response::error('事件不存在', 404);
        }
        
        $this->eventModel->delete($id);
        
        $this->logger->info("Life event deleted", ['id' => $id]);
        
        return Response::success(null, '事件删除成功');
    }
}
