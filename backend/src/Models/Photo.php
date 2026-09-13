<?php
/**
 * 照片模型
 */

namespace App\Models;

class Photo extends BaseModel
{
    protected string $table = 'photos';
    
    /**
     * 获取所有照片（按排序）
     */
    public function getAllSorted(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, created_at DESC");
        return $stmt->fetchAll();
    }
}
