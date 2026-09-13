<?php
/**
 * 首页控制器
 */

namespace App\Controllers;

use App\Models\SiteConfig;
use App\Models\LifeEvent;
use App\Models\Photo;
use App\Models\Message;
use App\Utils\Response;

class HomeController
{
    private SiteConfig $configModel;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/SiteConfig.php';
        $this->configModel = new SiteConfig();
    }
    
    /**
     * 首页数据
     */
    public function index(): array
    {
        $config = $this->configModel->getPublicConfig();
        
        return Response::success([
            'config' => $config,
            'timeSince' => $this->calculateTimeSince()
        ]);
    }
    
    /**
     * 计算离开时间
     */
    public function getTimeSince(): array
    {
        return Response::success($this->calculateTimeSince());
    }
    
    /**
     * 计算从逝世日期到现在的时间差
     */
    private function calculateTimeSince(): array
    {
        // 逝世日期：2011年11月01日
        $deathDate = new \DateTime('2011-11-01');
        $now = new \DateTime();
        
        $interval = $deathDate->diff($now);
        
        return [
            'years' => $interval->y,
            'months' => $interval->m,
            'days' => $interval->d,
            'totalDays' => $interval->days,
            'formatted' => sprintf('%d年%d月%d天', $interval->y, $interval->m, $interval->d),
            'deathDate' => '2011-11-01',
            'birthDate' => '1974-05-22'
        ];
    }
}
