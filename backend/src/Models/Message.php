<?php
/**
 * 纪念寄语模型
 */

namespace App\Models;

class Message extends BaseModel
{
    protected string $table = 'messages';
    
    // 状态常量
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝
    
    /**
     * 获取已审核通过的寄语
     */
    public function getApproved(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC");
        $stmt->execute([self::STATUS_APPROVED]);
        return $stmt->fetchAll();
    }
    
    /**
     * 获取所有寄语（后台管理用）
     */
    public function getAllForAdmin(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY status ASC, created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * 统计待审核数量
     */
    public function countPending(): int
    {
        return $this->count('status = ?', [self::STATUS_PENDING]);
    }
}
