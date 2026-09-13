/**
 * 后台管理系统脚本
 */

const API_BASE = '/api';

// 获取Token
function getToken() {
    return localStorage.getItem('adminToken');
}

// 检查登录状态
function checkAuth() {
    const token = getToken();
    if (!token) {
        window.location.href = '/admin/login.html';
        return false;
    }
    return true;
}

// API请求
async function apiRequest(endpoint, options = {}) {
    const token = getToken();
    const config = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...options.headers
        },
        ...options
    };
    
    const response = await fetch(`${API_BASE}${endpoint}`, config);
    const data = await response.json();
    
    if (response.status === 401) {
        localStorage.removeItem('adminToken');
        localStorage.removeItem('adminUser');
        window.location.href = '/admin/login.html';
        throw new Error('登录已过期');
    }
    
    if (!response.ok || data.code !== 200) {
        throw new Error(data.message || '请求失败');
    }
    
    return data;
}

// 文件上传
async function uploadFile(file) {
    const token = getToken();
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await fetch(`${API_BASE}/admin/upload`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        body: formData
    });
    
    const data = await response.json();
    
    if (!response.ok || data.code !== 200) {
        throw new Error(data.message || '上传失败');
    }
    
    return data.data;
}

// Toast通知
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = { success: '✓', error: '✕' };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || '✓'}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// 转义HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 照片加载失败时的温和占位图
const PHOTO_PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">' +
    '<rect width="400" height="300" fill="#ece7db"/>' +
    '<g fill="none" stroke="#b9b2a2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
    '<rect x="160" y="100" width="80" height="64" rx="4"/>' +
    '<circle cx="180" cy="120" r="6"/>' +
    '<path d="M166 156 L186 136 L200 148 L214 134 L234 156"/>' +
    '</g>' +
    '<text x="200" y="200" text-anchor="middle" fill="#8f8878" font-size="15" font-family="sans-serif">照片暂时无法显示</text>' +
    '</svg>'
);

function handleImgError(img) {
    img.onerror = null;
    img.src = PHOTO_PLACEHOLDER;
}
window.handleImgError = handleImgError;

// 格式化日期
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

// ==================== 页面导航 ====================

let currentPage = 'dashboard';

function navigateTo(page) {
    currentPage = page;
    
    // 更新导航状态
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.page === page) {
            item.classList.add('active');
        }
    });
    
    // 更新页面显示
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const targetPage = document.getElementById(`page-${page}`);
    if (targetPage) {
        targetPage.classList.add('active');
    }
    
    // 加载页面数据
    loadPageData(page);
    
    // 更新URL hash
    window.location.hash = page;
}

function loadPageData(page) {
    switch (page) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'config':
            loadConfig();
            break;
        case 'life-events':
            loadLifeEvents();
            break;
        case 'albums':
            loadAlbums();
            break;
        case 'photos':
            loadAlbumOptions().then(loadPhotos);
            break;
        case 'messages':
            loadMessages();
            break;
    }
}

// ==================== 仪表盘 ====================

async function loadDashboard() {
    try {
        const response = await apiRequest('/admin/dashboard');
        const stats = response.data.stats;
        
        document.getElementById('stat-events').textContent = stats.lifeEventsCount;
        document.getElementById('stat-photos').textContent = stats.photosCount;
        document.getElementById('stat-messages').textContent = stats.messagesCount;
        document.getElementById('stat-pending').textContent = stats.pendingMessagesCount;
        
    } catch (error) {
        console.error('加载仪表盘失败:', error);
    }
}

// ==================== 网站配置 ====================

async function loadConfig() {
    try {
        const response = await apiRequest('/admin/config');
        const configs = response.data;
        
        configs.forEach(config => {
            const input = document.getElementById(`config_${config.config_key}`);
            if (input) {
                input.value = config.config_value || '';
            }
        });
        
    } catch (error) {
        showToast('加载配置失败', 'error');
    }
}

async function saveConfig(e) {
    e.preventDefault();
    
    const form = document.getElementById('configForm');
    const inputs = form.querySelectorAll('input:not([readonly]), textarea');
    
    const configs = {};
    inputs.forEach(input => {
        const key = input.name;
        if (key) {
            configs[key] = input.value;
        }
    });
    
    try {
        await apiRequest('/admin/config', {
            method: 'POST',
            body: JSON.stringify({ configs })
        });
        
        showToast('配置保存成功');
        
    } catch (error) {
        showToast(error.message || '保存失败', 'error');
    }
}

// ==================== 生平事件 ====================

let eventsData = [];

async function loadLifeEvents() {
    const tbody = document.getElementById('eventsTableBody');
    
    try {
        const response = await apiRequest('/admin/life-events');
        eventsData = response.data || [];
        
        if (eventsData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#666;">暂无数据</td></tr>';
            return;
        }
        
        tbody.innerHTML = eventsData.map(event => `
            <tr>
                <td>${event.id}</td>
                <td>${formatDate(event.event_date)}</td>
                <td>${escapeHtml(event.title)}</td>
                <td>${escapeHtml(event.content?.substring(0, 50) || '')}...</td>
                <td>${event.sort_order}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-secondary btn-icon" onclick="editEvent(${event.id})" title="编辑">✏️</button>
                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteEvent(${event.id})" title="删除">🗑️</button>
                </td>
            </tr>
        `).join('');
        
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#f00;">加载失败</td></tr>';
    }
}

function showEventModal(event = null) {
    const modal = document.getElementById('eventModal');
    const title = document.getElementById('eventModalTitle');
    
    document.getElementById('eventId').value = event?.id || '';
    document.getElementById('eventDate').value = event?.event_date || '';
    document.getElementById('eventTitle').value = event?.title || '';
    document.getElementById('eventContent').value = event?.content || '';
    document.getElementById('eventSort').value = event?.sort_order || 0;
    
    title.textContent = event ? '编辑事件' : '添加事件';
    modal.classList.add('active');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('active');
}

function editEvent(id) {
    const event = eventsData.find(e => e.id === id);
    if (event) {
        showEventModal(event);
    }
}

async function saveEvent(e) {
    e.preventDefault();
    
    const id = document.getElementById('eventId').value;
    const data = {
        event_date: document.getElementById('eventDate').value,
        title: document.getElementById('eventTitle').value,
        content: document.getElementById('eventContent').value,
        sort_order: parseInt(document.getElementById('eventSort').value) || 0
    };
    
    try {
        if (id) {
            await apiRequest(`/admin/life-events/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
            showToast('事件更新成功');
        } else {
            await apiRequest('/admin/life-events', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showToast('事件添加成功');
        }
        
        closeEventModal();
        loadLifeEvents();
        
    } catch (error) {
        showToast(error.message || '保存失败', 'error');
    }
}

async function deleteEvent(id) {
    showConfirm('确定要删除这个事件吗？', async () => {
        try {
            await apiRequest(`/admin/life-events/${id}`, { method: 'DELETE' });
            showToast('事件删除成功');
            loadLifeEvents();
        } catch (error) {
            showToast(error.message || '删除失败', 'error');
        }
    });
}

// ==================== 相册管理 ====================

let albumsData = [];

async function loadAlbums() {
    const tbody = document.getElementById('albumsTableBody');

    try {
        const response = await apiRequest('/admin/albums');
        albumsData = response.data || [];

        if (albumsData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#666;">暂无相册，点击上方按钮添加</td></tr>';
            return;
        }

        tbody.innerHTML = albumsData.map(album => `
            <tr>
                <td>${album.id}</td>
                <td>${escapeHtml(album.name)}</td>
                <td>${escapeHtml(album.description || '—')}</td>
                <td>${album.photo_count ?? 0}</td>
                <td>${album.sort_order}</td>
                <td class="actions">
                    <button class="btn btn-sm btn-secondary btn-icon" onclick="editAlbum(${album.id})" title="编辑">✏️</button>
                    <button class="btn btn-sm btn-danger btn-icon" onclick="deleteAlbum(${album.id})" title="删除">🗑️</button>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#f00;">加载失败</td></tr>';
    }
}

function showAlbumModal(album = null) {
    const modal = document.getElementById('albumModal');
    const title = document.getElementById('albumModalTitle');

    document.getElementById('albumId').value = album?.id || '';
    document.getElementById('albumName').value = album?.name || '';
    document.getElementById('albumDescription').value = album?.description || '';
    document.getElementById('albumSort').value = album?.sort_order || 0;

    title.textContent = album ? '编辑相册' : '添加相册';
    modal.classList.add('active');
}

function closeAlbumModal() {
    document.getElementById('albumModal').classList.remove('active');
}

function editAlbum(id) {
    const album = albumsData.find(a => a.id === id);
    if (album) {
        showAlbumModal(album);
    }
}

async function saveAlbum(e) {
    e.preventDefault();

    const id = document.getElementById('albumId').value;
    const data = {
        name: document.getElementById('albumName').value.trim(),
        description: document.getElementById('albumDescription').value.trim(),
        sort_order: parseInt(document.getElementById('albumSort').value) || 0
    };

    try {
        if (id) {
            await apiRequest(`/admin/albums/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
            showToast('相册更新成功');
        } else {
            await apiRequest('/admin/albums', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showToast('相册添加成功');
        }

        closeAlbumModal();
        loadAlbums();

    } catch (error) {
        showToast(error.message || '保存失败', 'error');
    }
}

async function deleteAlbum(id) {
    const album = albumsData.find(a => a.id === id);
    const count = album?.photo_count ?? 0;
    const hint = count > 0
        ? `相册「${album?.name || id}」中有 ${count} 张照片，删除相册后这些照片将转为未分类。确定删除吗？`
        : `确定要删除相册「${album?.name || id}」吗？`;

    showConfirm(hint, async () => {
        try {
            await apiRequest(`/admin/albums/${id}`, { method: 'DELETE' });
            showToast('相册删除成功');
            loadAlbums();
        } catch (error) {
            showToast(error.message || '删除失败', 'error');
        }
    });
}

// ==================== 照片管理 ====================

let photosData = [];
let photoAlbumFilter = '';

/**
 * 加载相册选项（用于筛选下拉与照片表单下拉）
 */
async function loadAlbumOptions() {
    try {
        const response = await apiRequest('/admin/albums');
        albumsData = response.data || [];
    } catch (error) {
        albumsData = [];
    }

    const options = albumsData.map(a =>
        `<option value="${a.id}">${escapeHtml(a.name)}</option>`
    ).join('');

    // 照片页筛选下拉
    const filter = document.getElementById('photoAlbumFilter');
    if (filter) {
        const current = filter.value;
        filter.innerHTML = '<option value="">全部相册</option>' + options;
        filter.value = current;
        // 若原筛选相册已被删除，回退为"全部相册"并同步状态
        if (filter.value !== current) {
            photoAlbumFilter = '';
        }
    }

    // 照片表单下拉
    const select = document.getElementById('photoAlbum');
    if (select) {
        select.innerHTML = '<option value="">未分类</option>' + options;
    }
}

async function loadPhotos() {
    const grid = document.getElementById('photosGrid');

    try {
        const endpoint = photoAlbumFilter ? `/admin/photos?album_id=${photoAlbumFilter}` : '/admin/photos';
        const response = await apiRequest(endpoint);
        photosData = response.data || [];

        if (photosData.length === 0) {
            grid.innerHTML = '<p style="text-align:center;color:#666;grid-column:1/-1;">暂无照片，点击上方按钮添加</p>';
            return;
        }

        grid.innerHTML = photosData.map(photo => `
            <div class="photo-card">
                <img class="photo-image" src="${escapeHtml(photo.thumb_url || photo.image_url)}" alt="${escapeHtml(photo.title)}"
                     loading="lazy" onerror="handleImgError(this)">
                <div class="photo-info">
                    <h4 class="photo-title">${escapeHtml(photo.title)}</h4>
                    <p class="photo-meta">
                        <span class="photo-album-tag">${escapeHtml(photo.album_name || '未分类')}</span>
                        ${photo.taken_at ? `<span class="photo-date">📅 ${photo.taken_at}</span>` : ''}
                    </p>
                    <p class="photo-desc">${escapeHtml(photo.description) || '暂无说明'}</p>
                    <div class="photo-actions">
                        <button class="btn btn-sm btn-secondary" onclick="editPhoto(${photo.id})">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePhoto(${photo.id})">删除</button>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        grid.innerHTML = '<p style="text-align:center;color:#f00;grid-column:1/-1;">加载失败</p>';
    }
}

function showPhotoModal(photo = null) {
    const modal = document.getElementById('photoModal');
    const title = document.getElementById('photoModalTitle');
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('uploadPlaceholder');

    document.getElementById('photoId').value = photo?.id || '';
    document.getElementById('photoTitle').value = photo?.title || '';
    document.getElementById('photoAlbum').value = photo?.album_id || '';
    document.getElementById('photoTakenAt').value = photo?.taken_at || '';
    document.getElementById('photoDescription').value = photo?.description || '';
    document.getElementById('photoUrl').value = photo?.image_url || '';
    document.getElementById('photoThumbUrl').value = photo?.thumb_url || '';
    document.getElementById('photoSort').value = photo?.sort_order || 0;

    if (photo?.image_url) {
        preview.src = photo.thumb_url || photo.image_url;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }

    title.textContent = photo ? '编辑照片' : '添加照片';
    modal.classList.add('active');
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.remove('active');
    document.getElementById('photoFile').value = '';
}

function editPhoto(id) {
    const photo = photosData.find(p => p.id === id);
    if (photo) {
        showPhotoModal(photo);
    }
}

async function handlePhotoUpload(file) {
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('uploadPlaceholder');

    try {
        placeholder.innerHTML = '<span>⏳</span><p>上传中...</p>';

        const result = await uploadFile(file);

        document.getElementById('photoUrl').value = result.url;
        document.getElementById('photoThumbUrl').value = result.thumb_url || result.url;
        preview.src = result.thumb_url || result.url;
        preview.style.display = 'block';
        placeholder.style.display = 'none';

        showToast('图片上传成功');

    } catch (error) {
        placeholder.innerHTML = '<span>📷</span><p>点击上传照片</p>';
        showToast(error.message || '上传失败', 'error');
    }
}

async function savePhoto(e) {
    e.preventDefault();

    const id = document.getElementById('photoId').value;
    const imageUrl = document.getElementById('photoUrl').value;

    if (!imageUrl) {
        showToast('请上传照片', 'error');
        return;
    }

    const data = {
        title: document.getElementById('photoTitle').value,
        album_id: document.getElementById('photoAlbum').value || null,
        taken_at: document.getElementById('photoTakenAt').value || null,
        description: document.getElementById('photoDescription').value,
        image_url: imageUrl,
        thumb_url: document.getElementById('photoThumbUrl').value || null,
        sort_order: parseInt(document.getElementById('photoSort').value) || 0
    };

    try {
        if (id) {
            await apiRequest(`/admin/photos/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
            showToast('照片更新成功');
        } else {
            await apiRequest('/admin/photos', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            showToast('照片添加成功');
        }

        closePhotoModal();
        loadPhotos();

    } catch (error) {
        showToast(error.message || '保存失败', 'error');
    }
}

async function deletePhoto(id) {
    showConfirm('确定要删除这张照片吗？', async () => {
        try {
            await apiRequest(`/admin/photos/${id}`, { method: 'DELETE' });
            showToast('照片删除成功');
            loadPhotos();
        } catch (error) {
            showToast(error.message || '删除失败', 'error');
        }
    });
}

// ==================== 寄语管理 ====================

let messagesData = [];

const STATUS_LABELS = {
    0: { text: '待审核', class: 'status-pending' },
    1: { text: '已通过', class: 'status-approved' },
    2: { text: '已拒绝', class: 'status-rejected' }
};

async function loadMessages() {
    const tbody = document.getElementById('messagesTableBody');
    
    try {
        const response = await apiRequest('/admin/messages');
        messagesData = response.data || [];
        
        if (messagesData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#666;">暂无寄语</td></tr>';
            return;
        }
        
        tbody.innerHTML = messagesData.map(msg => {
            const status = STATUS_LABELS[msg.status] || STATUS_LABELS[0];
            return `
                <tr>
                    <td>${msg.id}</td>
                    <td>${escapeHtml(msg.author_name)}</td>
                    <td>${escapeHtml(msg.content.substring(0, 50))}...</td>
                    <td><span class="status-badge ${status.class}">${status.text}</span></td>
                    <td>${formatDate(msg.created_at)}</td>
                    <td class="actions">
                        ${msg.status === 0 ? `
                            <button class="btn btn-sm btn-success btn-icon" onclick="updateMessageStatus(${msg.id}, 1)" title="通过">✓</button>
                            <button class="btn btn-sm btn-danger btn-icon" onclick="updateMessageStatus(${msg.id}, 2)" title="拒绝">✕</button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger btn-icon" onclick="deleteMessage(${msg.id})" title="删除">🗑️</button>
                    </td>
                </tr>
            `;
        }).join('');
        
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#f00;">加载失败</td></tr>';
    }
}

async function updateMessageStatus(id, status) {
    const statusText = status === 1 ? '通过' : '拒绝';
    
    try {
        await apiRequest(`/admin/messages/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
        
        showToast(`已${statusText}该寄语`);
        loadMessages();
        loadDashboard();
        
    } catch (error) {
        showToast(error.message || '操作失败', 'error');
    }
}

async function deleteMessage(id) {
    showConfirm('确定要删除这条寄语吗？', async () => {
        try {
            await apiRequest(`/admin/messages/${id}`, { method: 'DELETE' });
            showToast('寄语删除成功');
            loadMessages();
            loadDashboard();
        } catch (error) {
            showToast(error.message || '删除失败', 'error');
        }
    });
}

// ==================== 确认对话框 ====================

let confirmCallback = null;

function showConfirm(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').classList.add('active');
    confirmCallback = callback;
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    confirmCallback = null;
}

// ==================== 初始化 ====================

document.addEventListener('DOMContentLoaded', () => {
    // 检查登录状态
    if (!checkAuth()) return;
    
    // 加载用户信息
    const user = JSON.parse(localStorage.getItem('adminUser') || '{}');
    document.getElementById('userName').textContent = user.nickname || '管理员';
    document.getElementById('userAvatar').textContent = (user.nickname || 'A').charAt(0).toUpperCase();
    
    // 导航点击事件
    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            navigateTo(item.dataset.page);
        });
    });
    
    // 移动端菜单切换
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // 退出登录
    document.getElementById('logoutBtn').addEventListener('click', () => {
        localStorage.removeItem('adminToken');
        localStorage.removeItem('adminUser');
        window.location.href = '/admin/login.html';
    });
    
    // 配置表单提交
    document.getElementById('configForm').addEventListener('submit', saveConfig);
    
    // 事件表单提交
    document.getElementById('eventForm').addEventListener('submit', saveEvent);

    // 相册表单提交
    document.getElementById('albumForm').addEventListener('submit', saveAlbum);

    // 照片表单提交
    document.getElementById('photoForm').addEventListener('submit', savePhoto);

    // 照片相册筛选
    document.getElementById('photoAlbumFilter').addEventListener('change', (e) => {
        photoAlbumFilter = e.target.value;
        loadPhotos();
    });
    
    // 照片上传
    document.getElementById('photoFile').addEventListener('change', (e) => {
        if (e.target.files[0]) {
            handlePhotoUpload(e.target.files[0]);
        }
    });
    
    // 确认按钮
    document.getElementById('confirmBtn').addEventListener('click', () => {
        if (confirmCallback) {
            confirmCallback();
        }
        closeConfirmModal();
    });
    
    // 根据URL hash导航
    const hash = window.location.hash.slice(1) || 'dashboard';
    navigateTo(hash);
    
    console.log('后台管理系统已加载');
});
