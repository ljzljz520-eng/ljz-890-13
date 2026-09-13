<?php
/**
 * 相册模型
 */

namespace App\Models;

class Album extends BaseModel
{
    protected string $table = 'albums';
    
    /**
     * 获取所有相册（按排序）
     */
    public function getAllSorted(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * 获取所有相册并附带照片数量
     */
    public function getAllWithPhotoCount(): array
    {
        $sql = "SELECT a.*, 
                       (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS photo_count
                FROM {$this->table} a
                ORDER BY a.sort_order ASC, a.id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
