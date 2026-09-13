<?php
/**
 * 数据初始化脚本
 * 在应用启动时自动修复admin密码
 */

require_once __DIR__ . '/src/Config/Database.php';
require_once __DIR__ . '/src/Utils/Logger.php';

use App\Config\Database;
use App\Utils\Logger;

$logger = Logger::getInstance();
$logger->info("Running data initializer...");

try {
    $db = Database::getInstance();
    
    // 检查 admin 用户是否存在
    $stmt = $db->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    // 正确的密码 123456
    $correctPassword = '123456';
    
    if ($admin) {
        // 检查密码是否匹配
        if (!password_verify($correctPassword, $admin['password'])) {
            // 密码不匹配，重新生成正确的哈希
            $newHash = password_hash($correctPassword, PASSWORD_BCRYPT);
            
            $updateStmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $admin['id']]);
            
            $logger->info("Admin password has been fixed successfully");
            echo "✅ Admin password fixed: admin / 123456\n";
        } else {
            $logger->info("Admin password is already correct");
            echo "✅ Admin password is correct\n";
        }
    } else {
        // admin用户不存在，创建新用户
        $newHash = password_hash($correctPassword, PASSWORD_BCRYPT);
        
        $insertStmt = $db->prepare("INSERT INTO admins (username, password, nickname, created_at) VALUES (?, ?, ?, NOW())");
        $insertStmt->execute(['admin', $newHash, '系统管理员']);
        
        $logger->info("Admin user created successfully");
        echo "✅ Admin user created: admin / 123456\n";
    }
    
} catch (Exception $e) {
    $logger->error("Data initializer failed: " . $e->getMessage());
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ Data initialization completed\n";
