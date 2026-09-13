<?php
/**
 * 配置控制器
 */

namespace App\Controllers;

use App\Models\SiteConfig;
use App\Utils\Response;
use App\Utils\Logger;

class ConfigController
{
    private SiteConfig $configModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/SiteConfig.php';
        $this->configModel = new SiteConfig();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 获取公开配置
     */
    public function getPublicConfig(): array
    {
        $config = $this->configModel->getPublicConfig();
        return Response::success($config);
    }
    
    /**
     * 获取所有配置（后台用）
     */
    public function getAll(): array
    {
        $configs = $this->configModel->all('id', 'ASC');
        return Response::success($configs);
    }
    
    /**
     * 更新配置
     */
    public function update(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (!isset($data['configs']) || !is_array($data['configs'])) {
            return Response::error('配置数据格式错误', 400);
        }
        
        foreach ($data['configs'] as $key => $value) {
            $this->configModel->setValue($key, $value);
        }
        
        $this->logger->info("Config updated", ['keys' => array_keys($data['configs'])]);
        
        return Response::success(null, '配置更新成功');
    }
}
