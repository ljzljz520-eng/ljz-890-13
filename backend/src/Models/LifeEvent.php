<?php
/**
 * 生平事件模型
 */

namespace App\Models;

class LifeEvent extends BaseModel
{
    protected string $table = 'life_events';
    
    /**
     * 获取所有事件（按日期排序）
     */
    public function getAllSorted(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY event_date ASC, sort_order ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * 获取所有事件（后台管理用）
     */
    public function getAllForAdmin(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, event_date ASC");
        return $stmt->fetchAll();
    }
}
