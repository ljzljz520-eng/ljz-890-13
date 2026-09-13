<?php
/**
 * 后台管理控制器
 */

namespace App\Controllers;

use App\Models\SiteConfig;
use App\Models\LifeEvent;
use App\Models\Photo;
use App\Models\Message;
use App\Utils\Response;

class AdminController
{
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/SiteConfig.php';
        require_once __DIR__ . '/../Models/LifeEvent.php';
        require_once __DIR__ . '/../Models/Photo.php';
        require_once __DIR__ . '/../Models/Message.php';
    }
    
    /**
     * 仪表盘数据
     */
    public function dashboard(): array
    {
        $eventModel = new LifeEvent();
        $photoModel = new Photo();
        $messageModel = new Message();
        
        return Response::success([
            'stats' => [
                'lifeEventsCount' => $eventModel->count(),
                'photosCount' => $photoModel->count(),
                'messagesCount' => $messageModel->count(),
                'pendingMessagesCount' => $messageModel->countPending()
            ],
            'currentUser' => $GLOBALS['currentUser'] ?? null
        ]);
    }
}
