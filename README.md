# 🕯️ 亚尔买买提・阿不来提纪念网站

> 永远怀念亲爱的父亲 亚尔买买提・阿不来提 (1974.05.22 - 2011.11.01)

这是一个专属纪念网站，用于铭记和缅怀亲人。网站融入了新疆维吾尔族特色设计元素，包含生平介绍、照片集、纪念寄语等功能模块，并配备完整的后台管理系统。

---

## 🏗️ 系统架构

```
┌─────────────────────────────────────────────────────────┐
│                    用户访问 localhost:3000               │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│                 Nginx 前端容器 (Port 3000)               │
│  ├─ / → 主站页面 (index.html)                           │
│  ├─ /admin/ → 后台管理页面                               │
│  ├─ /api/* → 代理到后端                                 │
│  └─ /uploads/* → 代理到后端上传目录                      │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│              PHP+Apache 后端容器 (Port 8000)             │
│  ├─ API 路由分发                                         │
│  ├─ JWT 认证中间件                                       │
│  └─ /uploads/ → 上传文件存储                             │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────┐
│               MySQL 数据库 (Port 3306)                   │
└─────────────────────────────────────────────────────────┘
```

---

## 🛠 技术栈

| 层级 | 技术 |
|------|------|
| **Frontend** | HTML5 + CSS3 + Vanilla JavaScript |
| **Backend** | PHP 8.2 + Apache |
| **Database** | MySQL 8.0 |
| **Container** | Docker + Docker Compose |
| **Web Server** | Nginx (前端) + Apache (后端) |

---

## ✨ 核心功能

### 主站功能
- 🕐 **实时时间计算** - 精确显示"父亲已离开我们XX年XX月XX天"
- 📜 **生平时间线** - 记录重要人生节点
- 🖼️ **照片集** - 瀑布流展示珍贵照片
- 💬 **纪念寄语墙** - 访客可提交怀念留言
- 🎨 **维吾尔族特色设计** - 融入民族纹样与配色

### 后台功能
- 📊 仪表盘统计
- ⚙️ 网站配置管理（所有前端内容可编辑）
- 📅 生平事件CRUD
- 🖼️ 照片上传管理（支持20MB图片）
- 💬 寄语审核管理

---

## 🚀 快速启动 (Docker)

### 前置要求
- 安装 [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### 启动步骤

```bash
# 1. 进入项目目录

# 2. 构建并启动
docker compose up --build

# 3. 首次启动或需要重置数据时
docker compose down -v
docker compose up --build
```

### 访问地址

| 服务 | 地址 | 说明 |
|------|------|------|
| **主站** | http://localhost:3000 | 纪念网站首页 |
| **后台管理** | http://localhost:3000/admin/ | 管理员入口 |
| **后端API** | http://localhost:8000 | API接口（调试用） |
| **数据库** | localhost:3306 | MySQL数据库 |

---

## 🧪 测试账号

| 用户类型 | 用户名 | 密码 |
|----------|--------|------|
| 管理员 | admin | 123456 |

> 密码在容器启动时自动修复，无需手动配置。

---

## 📁 项目结构

```
纪念网站890/
├── docker-compose.yml          # Docker 编排配置
├── README.md                   # 项目说明文档
│
├── frontend/                   # 前端项目
│   ├── Dockerfile              # Nginx 容器配置
│   ├── nginx.conf              # Nginx 配置（含API代理）
│   ├── public/                 # 主站页面
│   │   ├── index.html          # 首页
│   │   ├── css/style.css       # 维吾尔族特色样式
│   │   ├── js/app.js           # 前端交互脚本
│   │   └── assets/images/      # 静态图片资源
│   └── admin/                  # 后台管理
│       ├── index.html          # 后台SPA主页
│       ├── login.html          # 登录页
│       ├── css/admin.css       # 后台样式
│       └── js/admin.js         # 后台脚本
│
├── backend/                    # PHP 后端
│   ├── Dockerfile              # PHP+Apache 容器配置
│   ├── init-data.php           # 启动时密码修复脚本
│   ├── public/index.php        # API入口（路由分发）
│   ├── src/
│   │   ├── Config/Database.php # 数据库连接
│   │   ├── Controllers/        # 控制器（7个）
│   │   ├── Models/             # 数据模型（5个）
│   │   ├── Middleware/         # JWT认证中间件
│   │   └── Utils/              # 工具类
│   └── uploads/                # 上传文件目录
│
└── database/
    └── init.sql                # 数据库初始化脚本
```

---

## 🎨 维吾尔族特色设计

### 配色方案

| 颜色 | 色值 | 用途 |
|------|------|------|
| 金色 | #D4AF37 | 主色调、按钮、装饰边框 |
| 深蓝 | #1a365d | 背景、导航栏 |
| 绿松石 | #40E0D0 | 点缀、链接强调 |
| 象牙白 | #FFFFF0 | 页面背景 |
| 沙色 | #F4E4BA | 区域背景 |

### 设计元素
- 几何纹样边框装饰
- 渐变背景和玻璃态效果
- 阿拉伯风格圆角和阴影
- 维吾尔语书写支持（RTL方向）

---

## 🔧 API接口

### 公开接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/config | 获取网站配置 |
| GET | /api/life-events | 获取生平事件 |
| GET | /api/photos | 获取照片列表 |
| GET | /api/messages | 获取已审核寄语 |
| GET | /api/time-since | 获取离开时间计算 |
| POST | /api/messages | 提交寄语 |
| POST | /api/auth/login | 管理员登录 |

### 后台接口（需JWT认证）
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/admin/dashboard | 仪表盘统计 |
| POST | /api/admin/config | 保存配置 |
| POST | /api/admin/upload | 上传图片 |
| CRUD | /api/admin/life-events | 生平事件管理 |
| CRUD | /api/admin/photos | 照片管理 |
| CRUD | /api/admin/messages | 寄语管理 |

---

## 🐳 Docker 常用命令

```bash
# 构建并启动
docker compose up --build

# 后台运行
docker compose up -d --build

# 查看日志
docker compose logs -f

# 查看特定服务日志
docker compose logs -f backend

# 停止服务
docker compose down

# 清理数据重建（重置数据库）
docker compose down -v
docker compose up --build
```

---

## ❓ 常见问题

**Q: 端口冲突怎么办？**
A: 修改 `docker-compose.yml` 中的端口映射，如 `3001:80`

**Q: Docker镜像拉取失败？**
A: 检查网络连接或配置Docker镜像加速器

**Q: 登录提示密码错误？**
A: 容器启动时会自动修复密码，请确保 `init-data.php` 正常执行

**Q: 上传图片不显示？**
A: 确保nginx配置正确代理 `/uploads/` 到后端

---

## 📝 版权信息

© 2024 **合肥市奕宁云网络科技有限公司**

官网：[www.yiningyun.com](https://www.yiningyun.com)

---

**永远怀念 亚尔买买提・阿不来提** 🕯️

يارمەھەممەت ئابلەت
