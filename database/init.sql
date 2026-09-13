-- 亚尔买买提・阿不来提纪念网站 数据库初始化脚本
-- 版权所有 © 合肥市奕宁云网络科技有限公司 www.yiningyun.com

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 创建数据库
CREATE DATABASE IF NOT EXISTS memorial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE memorial;

-- =====================================================
-- 管理员表
-- =====================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码（BCrypt加密）',
    nickname VARCHAR(100) NOT NULL COMMENT '昵称',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 插入默认管理员 (密码: 123456)
INSERT INTO admins (username, password, nickname, created_at) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', NOW());

-- =====================================================
-- 网站配置表
-- =====================================================
CREATE TABLE IF NOT EXISTS site_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value TEXT COMMENT '配置值',
    description VARCHAR(255) COMMENT '配置说明',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站配置表';

-- 插入默认配置
INSERT INTO site_config (config_key, config_value, description) VALUES
('site_title', '亚尔买买提・阿不来提纪念网站', '网站标题'),
('site_subtitle', '永远怀念我们敬爱的父亲', '网站副标题'),
('father_name', '亚尔买买提・阿不来提', '父亲姓名'),
('father_name_uyghur', 'يارمەھەممەت ئابلەت', '父亲姓名（维吾尔语）'),
('birth_date', '1974-05-22', '出生日期'),
('death_date', '2011-11-01', '逝世日期'),
('hero_image', '/assets/images/hero-bg.png', '首页背景图'),
('avatar_image', '/assets/images/avatar.png', '头像图片'),
('hero_quote', '您的音容笑貌，永远铭刻在我们心中', '首页寄语'),
('about_text', '亚尔买买提・阿不来提，一位慈爱的父亲，一个正直善良的人。他用自己的一生诠释了什么是责任与担当，什么是爱与奉献。虽然他已经离开我们多年，但他的精神永远活在我们心中。', '关于简介'),
('footer_text', '永远怀念亲爱的父亲 亚尔买买提・阿不来提 (1974-2011)', '页脚文字'),
('copyright_company', '合肥市奕宁云网络科技有限公司', '版权公司'),
('copyright_website', 'www.yiningyun.com', '版权网站');

-- =====================================================
-- 生平事件表
-- =====================================================
CREATE TABLE IF NOT EXISTS life_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '事件标题',
    event_date DATE NOT NULL COMMENT '事件日期',
    content TEXT COMMENT '事件详情',
    image_url VARCHAR(500) COMMENT '配图URL',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='生平事件表';

-- 插入示例生平事件
INSERT INTO life_events (title, event_date, content, sort_order, created_at) VALUES
('出生', '1974-05-22', '亚尔买买提・阿不来提出生于新疆，开启了他精彩的人生旅程。', 1, NOW()),
('求学时期', '1980-09-01', '开始接受教育，展现出勤奋好学的品质。', 2, NOW()),
('成家立业', '1998-06-15', '组建了幸福的家庭，成为一位负责任的丈夫和父亲。', 3, NOW()),
('辛勤工作', '2000-03-01', '投身事业，用勤劳的双手为家庭创造美好生活。', 4, NOW()),
('永远离开', '2011-11-01', '不幸离世，留下了无尽的思念和怀念。', 5, NOW());

-- =====================================================
-- 照片表
-- =====================================================
CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '照片标题',
    image_url VARCHAR(500) NOT NULL COMMENT '图片URL',
    description TEXT COMMENT '照片描述',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='照片表';

-- 插入示例照片
INSERT INTO photos (title, image_url, description, sort_order, created_at) VALUES
('温馨时刻', '/assets/images/gallery/photo1.png', '父亲与家人在一起的温馨时光。', 1, NOW()),
('工作中的父亲', '/assets/images/gallery/photo2.png', '父亲认真工作的样子，总是那么专注。', 2, NOW()),
('节日合影', '/assets/images/gallery/photo3.png', '节日期间的全家福，幸福洋溢。', 3, NOW());

-- =====================================================
-- 纪念寄语表
-- =====================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(100) NOT NULL COMMENT '留言者称呼',
    content TEXT NOT NULL COMMENT '寄语内容',
    status TINYINT DEFAULT 0 COMMENT '状态：0-待审核，1-已通过，2-已拒绝',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='纪念寄语表';

-- 插入示例寄语（已审核通过）
INSERT INTO messages (author_name, content, status, created_at) VALUES
('您的孩子们', '亲爱的父亲，我们永远怀念您。您的教诲和关爱，我们铭记于心。愿您在天堂安息。', 1, NOW()),
('亲朋好友', '亚尔买买提是一位值得尊敬的人，他的善良和正直感染着身边的每一个人。愿他安息！', 1, NOW()),
('家乡的朋友', '还记得他总是乐于助人的样子，这样的好人值得被永远铭记。', 1, NOW());

-- =====================================================
-- 创建索引优化查询性能
-- =====================================================
CREATE INDEX idx_life_events_date ON life_events(event_date);
CREATE INDEX idx_photos_sort ON photos(sort_order);
CREATE INDEX idx_messages_status ON messages(status);
