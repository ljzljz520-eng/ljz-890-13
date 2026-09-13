<?php
/**
 * 照片模型
 */

namespace App\Models;

class Photo extends BaseModel
{
    protected string $table = 'photos';
    
    /**
     * 获取所有照片（按排序，附带相册名称）
     * 排序规则：自定义排序优先，其后按拍摄时间倒序（未填写拍摄时间的排最后）
     */
    public function getAllSorted(?int $albumId = null): array
    {
        $sql = "SELECT p.*, a.name AS album_name
                FROM {$this->table} p
                LEFT JOIN albums a ON p.album_id = a.id";
        $params = [];
        
        if ($albumId !== null) {
            $sql .= " WHERE p.album_id = ?";
            $params[] = $albumId;
        }
        
        $sql .= " ORDER BY p.sort_order ASC, (p.taken_at IS NULL) ASC, p.taken_at DESC, p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * 根据ID查找（附带相册名称）
     */
    public function findWithAlbum(int $id): ?array
    {
        $sql = "SELECT p.*, a.name AS album_name
                FROM {$this->table} p
                LEFT JOIN albums a ON p.album_id = a.id
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
