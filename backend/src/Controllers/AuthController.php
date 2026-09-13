<?php
/**
 * 认证控制器
 */

namespace App\Controllers;

use App\Models\Admin;
use App\Utils\Response;
use App\Utils\JWT;
use App\Utils\Validator;
use App\Utils\Logger;

class AuthController
{
    private Admin $adminModel;
    private Logger $logger;
    
    public function __construct()
    {
        require_once __DIR__ . '/../Models/BaseModel.php';
        require_once __DIR__ . '/../Models/Admin.php';
        $this->adminModel = new Admin();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * 管理员登录
     */
    public function login(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 验证输入
        $validator = new Validator($data);
        $validator->required('username', '用户名')
                  ->required('password', '密码');
        
        if (!$validator->validate()) {
            return Response::error($validator->getFirstError(), 400);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        
        // 查找用户
        $admin = $this->adminModel->findByUsername($username);
        
        if (!$admin) {
            $this->logger->warning("Login failed: user not found", ['username' => $username]);
            return Response::error('用户名或密码错误', 401);
        }
        
        // 验证密码
        if (!$this->adminModel->verifyPassword($password, $admin['password'])) {
            $this->logger->warning("Login failed: wrong password", ['username' => $username]);
            return Response::error('用户名或密码错误', 401);
        }
        
        // 生成Token
        $token = JWT::generate([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'nickname' => $admin['nickname']
        ]);
        
        $this->logger->info("Login success", ['username' => $username]);
        
        return Response::success([
            'token' => $token,
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname']
            ]
        ], '登录成功');
    }
    
    /**
     * 退出登录
     */
    public function logout(): array
    {
        $this->logger->info("User logged out");
        return Response::success(null, '退出成功');
    }
}
