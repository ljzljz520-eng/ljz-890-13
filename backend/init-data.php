<?php
/**
 * 数据初始化脚本
 * 在应用启动时自动修复admin密码，并执行幂等的数据库结构迁移
 */

require_once __DIR__ . '/src/Config/Database.php';
require_once __DIR__ . '/src/Utils/Logger.php';

use App\Config\Database;
use App\Utils\Logger;

$logger = Logger::getInstance();
$logger->info("Running data initializer...");

try {
    $db = Database::getInstance();
    
    // =====================================================
    // 1. 检查并修复 admin 用户
    // =====================================================
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
    
    // =====================================================
    // 2. 相册表迁移（家庭、工作、朋友、故乡、重要时刻）
    // =====================================================
    $db->exec("CREATE TABLE IF NOT EXISTS albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL COMMENT '相册名称',
        description VARCHAR(255) DEFAULT '' COMMENT '相册说明',
        sort_order INT DEFAULT 0 COMMENT '排序顺序',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='相册表'");
    
    $albumCount = (int)$db->query("SELECT COUNT(*) AS c FROM albums")->fetch()['c'];
    if ($albumCount === 0) {
        $db->exec("INSERT INTO albums (name, description, sort_order, created_at) VALUES
            ('家庭', '与家人共度的温馨时光', 1, NOW()),
            ('工作', '辛勤工作留下的身影', 2, NOW()),
            ('朋友', '与朋友相聚的珍贵瞬间', 3, NOW()),
            ('故乡', '故乡的山山水水与旧居', 4, NOW()),
            ('重要时刻', '人生中值得铭记的重要时刻', 5, NOW())");
        echo "✅ Default albums created (家庭/工作/朋友/故乡/重要时刻)\n";
    } else {
        echo "✅ Albums table ready ({$albumCount} albums)\n";
    }
    
    // =====================================================
    // 3. 照片表结构迁移（相册、缩略图、拍摄时间）
    // =====================================================
    $photoColumns = $db->query("SHOW COLUMNS FROM photos")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('album_id', $photoColumns, true)) {
        $db->exec("ALTER TABLE photos ADD COLUMN album_id INT NULL COMMENT '所属相册ID' AFTER id");
        echo "✅ photos.album_id column added\n";
    }
    
    if (!in_array('thumb_url', $photoColumns, true)) {
        $db->exec("ALTER TABLE photos ADD COLUMN thumb_url VARCHAR(500) DEFAULT NULL COMMENT '缩略图URL' AFTER image_url");
        echo "✅ photos.thumb_url column added\n";
    }
    
    if (!in_array('taken_at', $photoColumns, true)) {
        $db->exec("ALTER TABLE photos ADD COLUMN taken_at DATE DEFAULT NULL COMMENT '拍摄时间' AFTER description");
        echo "✅ photos.taken_at column added\n";
    }
    
    // 外键约束（若不存在则添加）
    $fkExists = $db->query("SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'photos' 
        AND CONSTRAINT_NAME = 'fk_photos_album'")->fetch()['c'];
    if ((int)$fkExists === 0) {
        try {
            $db->exec("ALTER TABLE photos ADD CONSTRAINT fk_photos_album 
                FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL");
            echo "✅ photos foreign key added\n";
        } catch (Exception $e) {
            // 外键添加失败不阻断启动（例如存在脏数据）
            $logger->warning("Foreign key creation skipped: " . $e->getMessage());
            echo "⚠️  Foreign key skipped: " . $e->getMessage() . "\n";
        }
    }
    
    // 索引（若不存在则添加）
    $indexes = $db->query("SHOW INDEX FROM photos")->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_column($indexes, 'Key_name');
    
    if (!in_array('idx_photos_album', $indexNames, true)) {
        $db->exec("CREATE INDEX idx_photos_album ON photos(album_id)");
    }
    if (!in_array('idx_photos_taken_at', $indexNames, true)) {
        $db->exec("CREATE INDEX idx_photos_taken_at ON photos(taken_at)");
    }
    echo "✅ Photos table migration completed\n";
    
    // =====================================================
    // 4. 上传目录（原图与缩略图分开存放）
    // =====================================================
    foreach (['/var/www/html/public/uploads/originals', '/var/www/html/public/uploads/thumbs'] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    echo "✅ Upload directories ready (originals & thumbs)\n";
    
} catch (Exception $e) {
    $logger->error("Data initializer failed: " . $e->getMessage());
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ Data initialization completed\n";
