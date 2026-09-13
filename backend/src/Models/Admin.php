<?php
/**
 * 管理员模型
 */

namespace App\Models;

class Admin extends BaseModel
{
    protected string $table = 'admins';
    
    /**
     * 根据用户名查找
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
