<?php
/**
 * 纪念寄语控制器
 */

namespace App\Controllers;

use App\Models\Message;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class MessageController
{
    private Message $messageModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/Message.php';
        $this->messageModel = new Message();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 获取已审核通过的寄语（前端展示用）
     */
    public function getApproved(): array
    {
        $messages = $this->messageModel->getApproved();
        return Response::success($messages);
    }
    
    /**
     * 获取所有寄语（后台管理用）
     */
    public function getAll(): array
    {
        $messages = $this->messageModel->getAllForAdmin();
        return Response::success($messages);
    }
    
    /**
     * 创建寄语（访客提交）
     */
    public function create(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('author_name', '您的称呼')
                  ->maxLength('author_name', 100, '您的称呼')
                  ->required('content', '寄语内容')
                  ->minLength('content', 5, '寄语内容')
                  ->maxLength('content', 1000, '寄语内容');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $insertData = [
            'author_name' => trim($data['author_name']),
            'content' => trim($data['content']),
            'status' => Message::STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->messageModel->create($insertData);
        
        $this->logger->info("Message submitted", ['id' => $id, 'author' => $insertData['author_name']]);
        
        return Response::success(['id' => $id], '寄语提交成功，待审核后展示');
    }
    
    /**
     * 更新寄语状态（后台审核）
     */
    public function update(int $id): array
    {
        $message = $this->messageModel->find($id);
        
        if (!$message) {
            return Response::error('寄语不存在', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!isset($data['status'])) {
            return Response::error('请指定审核状态', 400);
        }
        
        $status = (int)$data['status'];
        
        if (!in_array($status, [Message::STATUS_PENDING, Message::STATUS_APPROVED, Message::STATUS_REJECTED])) {
            return Response::error('无效的审核状态', 400);
        }
        
        $this->messageModel->update($id, ['status' => $status]);
        $message = $this->messageModel->find($id);
        
        $statusText = ['待审核', '已通过', '已拒绝'][$status];
        $this->logger->info("Message status updated", ['id' => $id, 'status' => $statusText]);
        
        return Response::success($message, '寄语状态更新成功');
    }
    
    /**
     * 删除寄语
     */
    public function delete(int $id): array
    {
        $message = $this->messageModel->find($id);
        
        if (!$message) {
            return Response::error('寄语不存在', 404);
        }
        
        $this->messageModel->delete($id);
        
        $this->logger->info("Message deleted", ['id' => $id]);
        
        return Response::success(null, '寄语删除成功');
    }
}
