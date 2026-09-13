<?php
/**
 * 网站配置模型
 */

namespace App\Models;

class SiteConfig extends BaseModel
{
    protected string $table = 'site_config';
    
    /**
     * 根据配置键获取值
     */
    public function getValue(string $key): ?string
    {
        $config = $this->findBy('config_key', $key);
        return $config ? $config['config_value'] : null;
    }
    
    /**
     * 设置配置值
     */
    public function setValue(string $key, string $value): bool
    {
        $config = $this->findBy('config_key', $key);
        
        if ($config) {
            return $this->update($config['id'], [
                'config_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->create([
                'config_key' => $key,
                'config_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        }
    }
    
    /**
     * 获取所有公开配置
     */
    public function getPublicConfig(): array
    {
        $configs = $this->all('id', 'ASC');
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config['config_key']] = $config['config_value'];
        }
        
        return $result;
    }
}
