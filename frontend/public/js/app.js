/**
 * 亚尔买买提・阿不来提纪念网站 - 主脚本
 * 版权所有 © 合肥市奕宁云网络科技有限公司 www.yiningyun.com
 */

// API基础地址
const API_BASE = '/api';

// ==================== 工具函数 ====================

/**
 * 发起API请求
 */
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE}${endpoint}`;
    const config = {
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        },
        ...options
    };
    
    try {
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || '请求失败');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * 显示Toast通知
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.success}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    
    container.appendChild(toast);
    
    // 自动移除
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * 格式化日期
 */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return `${date.getFullYear()}年${String(date.getMonth() + 1).padStart(2, '0')}月${String(date.getDate()).padStart(2, '0')}日`;
}

// ==================== 时间计算模块 ====================

/**
 * 计算并更新离开时间
 */
function updateTimeSince() {
    const deathDate = new Date('2011-11-01');
    const now = new Date();
    
    let years = now.getFullYear() - deathDate.getFullYear();
    let months = now.getMonth() - deathDate.getMonth();
    let days = now.getDate() - deathDate.getDate();
    
    if (days < 0) {
        months--;
        const prevMonth = new Date(now.getFullYear(), now.getMonth(), 0);
        days += prevMonth.getDate();
    }
    
    if (months < 0) {
        years--;
        months += 12;
    }
    
    // 更新DOM
    const yearsEl = document.getElementById('years');
    const monthsEl = document.getElementById('months');
    const daysEl = document.getElementById('days');
    
    if (yearsEl) yearsEl.textContent = years;
    if (monthsEl) monthsEl.textContent = months;
    if (daysEl) daysEl.textContent = days;
}

// ==================== 导航栏 ====================

/**
 * 初始化导航栏
 */
function initNavbar() {
    const navbar = document.getElementById('navbar');
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');
    
    // 滚动效果
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // 移动端菜单切换
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }
    
    // 导航链接点击
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            // 移除所有active类
            links.forEach(l => l.classList.remove('active'));
            // 添加当前active类
            link.classList.add('active');
            // 关闭移动端菜单
            navLinks.classList.remove('active');
        });
    });
    
    // 滚动时更新active状态
    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            if (window.scrollY >= sectionTop) {
                current = section.getAttribute('id');
            }
        });
        
        links.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
}

// ==================== 生平时间线 ====================

/**
 * 加载生平事件
 */
async function loadLifeEvents() {
    const container = document.getElementById('timelineContainer');
    
    try {
        const response = await apiRequest('/life-events');
        const events = response.data || [];
        
        if (events.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#666;">暂无生平事件</p>';
            return;
        }
        
        container.innerHTML = events.map((event, index) => `
            <div class="timeline-item fade-in" style="animation-delay: ${index * 0.1}s">
                <div class="timeline-dot"></div>
                <div class="timeline-date">
                    ${formatDate(event.event_date)}
                </div>
                <div class="timeline-content">
                    <h3>${escapeHtml(event.title)}</h3>
                    <p>${escapeHtml(event.content || '')}</p>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        container.innerHTML = '<p style="text-align:center;color:#666;">加载失败，请刷新重试</p>';
        console.error('加载生平事件失败:', error);
    }
}

// ==================== 照片集 ====================

/**
 * 加载照片
 */
async function loadPhotos() {
    const container = document.getElementById('galleryContainer');
    
    try {
        const response = await apiRequest('/photos');
        const photos = response.data || [];
        
        if (photos.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#fff;">暂无照片</p>';
            return;
        }
        
        container.innerHTML = photos.map((photo, index) => `
            <div class="gallery-item fade-in" style="animation-delay: ${index * 0.1}s" 
                 onclick="openLightbox('${escapeHtml(photo.image_url)}')">
                <img src="${escapeHtml(photo.image_url)}" alt="${escapeHtml(photo.title)}" 
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22%3E%3Crect fill=%22%23ddd%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%22200%22 y=%22150%22 text-anchor=%22middle%22 fill=%22%23999%22%3E暂无图片%3C/text%3E%3C/svg%3E'">
                <div class="gallery-overlay">
                    <div class="gallery-caption">
                        <h4>${escapeHtml(photo.title)}</h4>
                        <p>${escapeHtml(photo.description || '')}</p>
                    </div>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        container.innerHTML = '<p style="text-align:center;color:#fff;">加载失败，请刷新重试</p>';
        console.error('加载照片失败:', error);
    }
}

/**
 * 打开图片查看器
 */
function openLightbox(imageUrl) {
    const modal = document.getElementById('lightboxModal');
    const image = document.getElementById('lightboxImage');
    
    image.src = imageUrl;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * 关闭图片查看器
 */
function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * 初始化图片查看器
 */
function initLightbox() {
    const modal = document.getElementById('lightboxModal');
    const closeBtn = document.getElementById('lightboxClose');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLightbox);
    }
    
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeLightbox();
            }
        });
    }
    
    // ESC键关闭
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
}

// ==================== 纪念寄语 ====================

/**
 * 加载寄语
 */
async function loadMessages() {
    const container = document.getElementById('messagesContainer');
    
    try {
        const response = await apiRequest('/messages');
        const messages = response.data || [];
        
        if (messages.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#666;">暂无寄语，成为第一个留言的人吧！</p>';
            return;
        }
        
        container.innerHTML = messages.map((msg, index) => `
            <div class="message-card fade-in" style="animation-delay: ${index * 0.1}s">
                <p class="message-content">${escapeHtml(msg.content)}</p>
                <div class="message-author">
                    <div class="message-avatar">${msg.author_name.charAt(0).toUpperCase()}</div>
                    <div>
                        <p class="message-name">${escapeHtml(msg.author_name)}</p>
                        <p class="message-date">${formatDate(msg.created_at)}</p>
                    </div>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        container.innerHTML = '<p style="text-align:center;color:#666;">加载失败，请刷新重试</p>';
        console.error('加载寄语失败:', error);
    }
}

/**
 * 初始化留言表单
 */
function initMessageForm() {
    const form = document.getElementById('messageForm');
    
    if (!form) return;
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const authorName = document.getElementById('authorName').value.trim();
        const content = document.getElementById('messageContent').value.trim();
        
        // 验证
        if (!authorName) {
            showToast('请输入您的称呼', 'error');
            return;
        }
        
        if (!content || content.length < 5) {
            showToast('寄语内容至少需要5个字符', 'error');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;"></div> 提交中...';
        
        try {
            await apiRequest('/messages', {
                method: 'POST',
                body: JSON.stringify({
                    author_name: authorName,
                    content: content
                })
            });
            
            showToast('寄语提交成功，待审核后展示', 'success');
            form.reset();
            
        } catch (error) {
            showToast(error.message || '提交失败，请重试', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                提交寄语
            `;
        }
    });
}

// ==================== 配置加载 ====================

/**
 * 加载网站配置
 */
async function loadConfig() {
    try {
        const response = await apiRequest('/config');
        const config = response.data || {};
        
        // 更新页面内容
        if (config.site_title) {
            document.title = config.site_title;
        }
        
        if (config.father_name) {
            const nameEl = document.getElementById('fatherName');
            if (nameEl) nameEl.textContent = config.father_name;
        }
        
        if (config.father_name_uyghur) {
            const nameUyghurEl = document.getElementById('fatherNameUyghur');
            if (nameUyghurEl) nameUyghurEl.textContent = config.father_name_uyghur;
        }
        
        if (config.hero_quote) {
            const quoteEl = document.getElementById('heroQuote');
            if (quoteEl) quoteEl.textContent = `"${config.hero_quote}"`;
        }
        
        if (config.about_text) {
            const aboutEl = document.getElementById('aboutText');
            if (aboutEl) {
                aboutEl.innerHTML = config.about_text.split('\n').map(p => `<p>${escapeHtml(p)}</p>`).join('');
            }
        }
        
        if (config.footer_text) {
            const footerEl = document.getElementById('footerMemorial');
            if (footerEl) footerEl.textContent = config.footer_text;
        }
        
    } catch (error) {
        console.error('加载配置失败:', error);
    }
}

// ==================== 工具函数 ====================

/**
 * HTML转义
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== 初始化 ====================

/**
 * 页面初始化
 */
document.addEventListener('DOMContentLoaded', () => {
    // 初始化导航栏
    initNavbar();
    
    // 初始化时间计算
    updateTimeSince();
    // 每分钟更新一次（实际上每天才变化，但为了实时性）
    setInterval(updateTimeSince, 60000);
    
    // 初始化图片查看器
    initLightbox();
    
    // 初始化留言表单
    initMessageForm();
    
    // 加载数据
    loadConfig();
    loadLifeEvents();
    loadPhotos();
    loadMessages();
    
    console.log('亚尔买买提・阿不来提纪念网站 - 已加载');
    console.log('版权所有 © 合肥市奕宁云网络科技有限公司 www.yiningyun.com');
});
