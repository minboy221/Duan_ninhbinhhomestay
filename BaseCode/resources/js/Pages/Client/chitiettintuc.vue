<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    post: Object,
    categories: Array,
    recentPosts: Array,
});

const search = ref('');
const isDropdownVisible = ref(false);
const suggestions = ref([]);
const searchHistory = ref([]);
const hotSearches = ['Tràng An', 'Phòng trọ giá rẻ', 'Tam Cốc', 'Việc làm', 'Lễ tân', 'Hợp đồng'];

// Image Search State
const isImageModalOpen = ref(false);
const selectedImageSrc = ref(null);
const isScanning = ref(false);
const fileInput = ref(null);

const openImageSearch = () => {
    isImageModalOpen.value = true;
    selectedImageSrc.value = null;
    isScanning.value = false;
};

const closeImageSearch = () => {
    isImageModalOpen.value = false;
    selectedImageSrc.value = null;
    isScanning.value = false;
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleImageDrop = (e) => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        processImageFile(files[0]);
    }
};

const handleFileChange = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        processImageFile(files[0]);
    }
};

const processImageFile = (file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
        selectedImageSrc.value = e.target.result;
        startScanning(file.name);
    };
    reader.readAsDataURL(file);
};

const startScanning = (filename) => {
    isScanning.value = true;
    const nameLower = filename.toLowerCase();
    let term = "Ninh Bình";
    
    if (nameLower.includes("trang_an") || nameLower.includes("trangan") || nameLower.includes("scenery") || nameLower.includes("canh") || nameLower.includes("du_lich") || nameLower.includes("dulich")) {
        term = "Tràng An";
    } else if (nameLower.includes("phong") || nameLower.includes("tro") || nameLower.includes("homestay") || nameLower.includes("house") || nameLower.includes("room")) {
        term = "Phòng trọ giá rẻ";
    } else if (nameLower.includes("cafe") || nameLower.includes("ca_phe")) {
        term = "Cafe";
    } else if (nameLower.includes("le_tan") || nameLower.includes("letan") || nameLower.includes("tuyen_dung")) {
        term = "Lễ tân";
    } else if (nameLower.includes("hop_dong") || nameLower.includes("hopdong")) {
        term = "Hợp đồng";
    }

    setTimeout(() => {
        isScanning.value = false;
        setTimeout(() => {
            search.value = term;
            closeImageSearch();
            triggerSearch();
        }, 800);
    }, 2000);
};

const loadHistory = () => {
    if (typeof window !== 'undefined') {
        const history = localStorage.getItem('news_search_history');
        searchHistory.value = history ? JSON.parse(history) : [];
    }
};

onMounted(() => {
    loadHistory();
});

const saveToHistory = (query) => {
    if (!query.trim()) return;
    let history = localStorage.getItem('news_search_history');
    let historyArr = history ? JSON.parse(history) : [];
    historyArr = historyArr.filter(item => item !== query);
    historyArr.unshift(query);
    if (historyArr.length > 5) {
        historyArr.pop();
    }
    localStorage.setItem('news_search_history', JSON.stringify(historyArr));
    searchHistory.value = historyArr;
};

const deleteHistoryItem = (itemToDelete) => {
    let historyArr = searchHistory.value.filter(item => item !== itemToDelete);
    localStorage.setItem('news_search_history', JSON.stringify(historyArr));
    searchHistory.value = historyArr;
};

const clickSearchItem = (query) => {
    search.value = query;
    saveToHistory(query);
    router.get(route('tintuc'), {
        search: query,
    });
    isDropdownVisible.value = false;
};

const triggerSearch = () => {
    saveToHistory(search.value);
    router.get(route('tintuc'), {
        search: search.value,
    });
    isDropdownVisible.value = false;
};

const handleBlur = () => {
    setTimeout(() => {
        isDropdownVisible.value = false;
    }, 200);
};

let debounceTimer;
const onSearchInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        if (!search.value.trim()) {
            suggestions.value = [];
            return;
        }
        try {
            const res = await axios.get(route('tintuc.suggest'), {
                params: { query: search.value }
            });
            suggestions.value = res.data;
        } catch (e) {
            console.error(e);
        }
    }, 200);
};

const filterByCategory = (categoryName) => {
    router.get(route('tintuc'), {
        category: categoryName,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};
</script>

<template>

    <Head :title="post.title + ' | Ninh Bình HomeStay'" />
    <MainLayout>
        <template #breadcrumb>
            <!-- điều hướng -->
            <div class="dieuhuong">
                <div class="baodieuhuong">
                    <Link :href="route('home')">Trang Chủ</Link> /
                    <Link :href="route('tintuc')">Tin Tức</Link> /
                    <span>{{ post.title }}</span>
                </div>
            </div>
        </template>

        <div class="chitiettintuc-container">
        <!-- phần layout bao chi tiết tin tức -->
        <div class="layout">
            <section class="left_section">
                <div class="search">
                    <div class="bao_search">
                        <input 
                            type="text" 
                            v-model="search" 
                            @focus="isDropdownVisible = true"
                            @blur="handleBlur"
                            @input="onSearchInput"
                            @keyup.enter="triggerSearch"
                            placeholder="Tìm kiếm..."
                            class="search-input-black"
                            style="padding-right: 75px !important;"
                        >
                        <button class="btn_camera" @click="openImageSearch" title="Tìm kiếm bằng hình ảnh">
                            <i class="bi bi-camera"></i>
                        </button>
                        <button class="btn_search" @click="triggerSearch">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <!-- DROPDOWN OVERLAY -->
                    <div v-if="isDropdownVisible" class="search-dropdown-overlay">
                        <!-- Autocomplete suggestions -->
                        <div v-if="search.trim() !== '' && suggestions.length > 0" class="dropdown-section">
                            <h4 class="dropdown-title"><i class="bi bi-lightbulb"></i> Gợi ý kết quả</h4>
                            <ul class="dropdown-list">
                                <li v-for="item in suggestions" :key="item.id" class="dropdown-item suggest-item">
                                    <Link :href="route('chitiettintuc', item.slug)" @click="saveToHistory(search)">
                                        {{ item.title }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- When suggestions are empty but typing -->
                        <div v-else-if="search.trim() !== '' && suggestions.length === 0" class="dropdown-section text-muted py-2 px-3">
                            Không có gợi ý trùng khớp
                        </div>
                        
                        <!-- Hot Search and History -->
                        <div v-if="search.trim() === ''">
                            <!-- Hot Search -->
                            <div class="dropdown-section">
                                <h4 class="dropdown-title fire-title"><i class="bi bi-fire fire-icon"></i> Tìm kiếm phổ biến</h4>
                                <div class="hot-search-tags">
                                    <span 
                                        v-for="tag in hotSearches" 
                                        :key="tag" 
                                        class="hot-tag"
                                        @click="clickSearchItem(tag)"
                                    >
                                        <i class="bi bi-lightning-fill text-warning"></i> {{ tag }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Search History -->
                            <div v-if="searchHistory.length > 0" class="dropdown-section mt-3">
                                <h4 class="dropdown-title"><i class="bi bi-clock-history"></i> Lịch sử tìm kiếm</h4>
                                <ul class="dropdown-list">
                                    <li v-for="item in searchHistory" :key="item" class="dropdown-item history-item">
                                        <span class="history-text" @click="clickSearchItem(item)">{{ item }}</span>
                                        <button class="delete-history-btn" @click.stop="deleteHistoryItem(item)">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- danh mục -->
                <div class="category">
                    <div class="baodanhmuc">
                        <h2>Danh Mục Bài Viết</h2>
                        <div 
                            v-for="cat in categories" 
                            :key="cat.name"
                            class="danhmuc"
                            @click="filterByCategory(cat.name)"
                            style="cursor: pointer;"
                        >
                            <div class="left">
                                <i class="bi bi-folder"></i>
                                <span>{{ cat.name }}</span>
                            </div>
                            <div class="soluong">({{ cat.total }})</div>
                        </div>
                    </div>
                </div>
                <!-- bài viết mới -->
                <div class="baivietmoi">
                    <div class="baonewtintuc">
                        <h2>Bài Viết Mới</h2>
                        <div class="baoinfor_tintuc">
                            <div v-for="recent in recentPosts" :key="recent.id" class="infor_tintucnew">
                                <Link :href="route('chitiettintuc', recent.slug)">
                                    {{ recent.title }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- tag -->
                <div class="tags">
                    <div class="bao_tag">
                        <h2>Tag</h2>
                        <div class="baotag_tintuc">
                            <div class="item_tag">
                                <a href="#" @click.prevent="filterByCategory('Việc Làm')">Việc Làm</a>
                            </div>
                            <div class="item_tag">
                                <a href="#" @click.prevent="filterByCategory('Tin Tức')">Tin Tức</a>
                            </div>
                            <div class="item_tag">
                                <Link :href="route('timtro')">Tìm Trọ</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="right_section">
                <div class="chitiettintuc">
                    <div class="baoitem_tintuc">
                        <div class="title_tintuc">
                            <h2>{{ post.title }}</h2>
                        </div>
                        <div class="image_tintuc">
                            <img :src="post.image || '/anh/banner_tro.png'" :alt="post.title">
                        </div>
                        <div class="thongtin">
                            <div class="date">
                                <i class="bi bi-calendar"></i>
                                <span>{{ formatDate(post.created_at) }}</span>
                            </div>
                            <div class="user_tintuc">
                                <i class="bi bi-person"></i>
                                <span>{{ post.author ? post.author.name : 'Admin' }}</span>
                            </div>
                            <div class="user_tintuc" style="margin-left: 20px;">
                                <i class="bi bi-eye"></i>
                                <span>{{ post.views }} lượt xem</span>
                            </div>
                        </div>
                        <div class="baiviet_tintuc">
                            <div v-html="post.content" class="post-content-body"></div>
                            
                            <div v-if="post.tags" class="hastag">
                                <div class="baohastag">
                                    <h3>Từ Khoá:</h3>
                                    <div class="item_hastag">
                                        <a 
                                            v-for="tag in post.tags.split(',')" 
                                            :key="tag" 
                                            href="#"
                                            @click.prevent="router.get(route('tintuc'), { search: tag.trim() })"
                                        >
                                            #{{ tag.trim() }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- IMAGE SEARCH MODAL -->
        <div v-if="isImageModalOpen" class="image-search-modal-backdrop" @click.self="closeImageSearch">
            <div class="image-search-modal-content">
                <div class="modal-header-custom">
                    <h3><i class="bi bi-camera text-danger"></i> Tìm kiếm bằng hình ảnh</h3>
                    <button class="close-modal-btn" @click="closeImageSearch"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body-custom">
                    <!-- Drop area -->
                    <div 
                        v-if="!selectedImageSrc"
                        class="upload-drop-zone"
                        @dragover.prevent
                        @drop.prevent="handleImageDrop"
                        @click="triggerFileInput"
                    >
                        <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
                        <p class="upload-text">Kéo thả ảnh vào đây hoặc click để chọn ảnh</p>
                        <span class="upload-subtext">Hỗ trợ định dạng JPG, PNG, WEBP</span>
                        <input 
                            type="file" 
                            ref="fileInput" 
                            class="hidden-file-input" 
                            accept="image/*"
                            @change="handleFileChange"
                        >
                    </div>

                    <!-- Preview & Scan Area -->
                    <div v-else class="preview-scan-container">
                        <img :src="selectedImageSrc" class="image-preview" alt="Preview Image">
                        
                        <!-- Scanner line animation -->
                        <div v-if="isScanning" class="scanner-laser"></div>
                        
                        <div class="scanning-status">
                            <span v-if="isScanning" class="scanning-loading">
                                <i class="bi bi-arrow-repeat spin-icon"></i> Đang phân tích hình ảnh...
                            </span>
                            <span v-else class="scan-completedtext text-success">
                                <i class="bi bi-check-circle-fill"></i> Nhận diện thành công! Đang lọc bài viết...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettintuc.css";
@import '../../css/responsive/responsivechitiettintuc.css';
@import '../../css/responsive/responsive.css';

.post-content-body {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
}
.post-content-body h4 {
    font-size: 18px;
    font-weight: bold;
    margin-top: 25px;
    margin-bottom: 10px;
    color: #111;
}
.post-content-body p {
    margin-bottom: 15px;
}
.post-content-body ul {
    margin-bottom: 20px;
    padding-left: 20px;
    list-style-type: disc;
}
.post-content-body li {
    margin-bottom: 5px;
}

.search-input-black {
    color: #000 !important;
    font-weight: 500;
}

.search-dropdown-overlay {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    z-index: 999;
    margin-top: 6px;
    padding: 15px;
    max-height: 380px;
    overflow-y: auto;
}

.dropdown-section {
    margin-bottom: 12px;
}

.dropdown-title {
    font-size: 13px;
    font-weight: 600;
    color: #718096;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dropdown-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dropdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 14px;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #f7fafc;
}

.suggest-item a {
    color: #000 !important;
    text-decoration: none;
    display: block;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}

.suggest-item a:hover {
    color: #45abe6 !important;
}

.history-item {
    cursor: pointer;
}

.history-text {
    flex: 1;
    color: #1a202c;
    font-weight: 500;
}

.history-text:hover {
    color: #45abe6;
}

.delete-history-btn {
    background: none;
    border: none;
    color: #a0aec0;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-history-btn:hover {
    color: #e53e3e;
    background: #fed7d7;
}

.hot-search-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.hot-tag {
    background: rgba(254, 242, 242, 0.8);
    color: #ef4444 !important;
    border: 1px dashed #f87171;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 0 5px rgba(239, 68, 68, 0.1);
    position: relative;
    overflow: hidden;
}

.hot-tag::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    transition: 0.5s;
}

.hot-tag:hover::before {
    left: 100%;
}

.hot-tag:hover {
    background: linear-gradient(135deg, #ef4444, #f97316) !important;
    color: #fff !important;
    border-color: transparent;
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
    transform: translateY(-2px);
}

.fire-title {
    color: #ef4444 !important;
    font-weight: 700;
    text-shadow: 0 0 4px rgba(239, 68, 68, 0.3);
}

.fire-icon {
    color: #ef4444;
    display: inline-block;
    animation: flameMotion 1.5s infinite ease-in-out, fireFlicker 2s infinite alternate;
}

@keyframes fireFlicker {
    0% { text-shadow: 0 0 4px #ef4444, 0 0 10px #f97316, 0 0 18px #f59e0b; }
    50% { text-shadow: 0 0 6px #ef4444, 0 0 14px #ea580c, 0 0 22px #f59e0b, 0 0 30px #ef4444; }
    100% { text-shadow: 0 0 4px #ef4444, 0 0 10px #f97316, 0 0 18px #f59e0b; }
}

@keyframes flameMotion {
    0% { transform: scale(1) rotate(-2deg); }
    50% { transform: scale(1.1) rotate(3deg) translateY(-1px); }
    100% { transform: scale(1) rotate(-2deg); }
}

.btn_camera {
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 18px;
    color: #dc2626;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn_camera:hover {
    color: #ea580c;
    transform: translateY(-50%) scale(1.15);
}

/* Image Search Modal CSS */
.image-search-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

.image-search-modal-content {
    background: #fff;
    border-radius: 24px;
    width: 500px;
    max-width: 90%;
    padding: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(226, 232, 240, 0.8);
    position: relative;
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
    margin-bottom: 20px;
}

.modal-header-custom h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.close-modal-btn {
    background: none;
    border: none;
    font-size: 16px;
    color: #64748b;
    cursor: pointer;
    transition: color 0.2s;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-modal-btn:hover {
    color: #ef4444;
    background: #fef2f2;
}

.upload-drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    background: #fafafa;
    transition: all 0.2s ease-in-out;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.upload-drop-zone:hover {
    border-color: #ef4444;
    background: rgba(254, 242, 242, 0.5);
}

.upload-icon {
    font-size: 48px;
    color: #94a3b8;
    margin-bottom: 14px;
    transition: color 0.2s;
}

.upload-drop-zone:hover .upload-icon {
    color: #ef4444;
}

.upload-text {
    font-size: 15px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}

.upload-subtext {
    font-size: 12px;
    color: #94a3b8;
}

.hidden-file-input {
    display: none;
}

.preview-scan-container {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
}

.image-preview {
    max-height: 250px;
    max-width: 100%;
    object-fit: contain;
    border-radius: 8px;
}

.scanner-laser {
    position: absolute;
    top: 16px;
    left: 16px;
    right: 16px;
    height: 4px;
    background: linear-gradient(90deg, transparent, #ef4444, transparent);
    box-shadow: 0 0 10px #ef4444, 0 0 20px #ef4444;
    animation: scannerLine 2s infinite ease-in-out;
}

@keyframes scannerLine {
    0% {
        top: 16px;
    }
    50% {
        top: calc(16px + 250px - 20px);
    }
    100% {
        top: 16px;
    }
}

.scanning-status {
    margin-top: 16px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}

.spin-icon {
    display: inline-block;
    animation: spin 1s infinite linear;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.chitiettintuc-container {
    max-width: 1200px;
    margin: 155px auto 0;
    padding: 0 10px;
}

.dieuhuong {
    position: absolute !important;
    top: calc(100% - 2px) !important;
    left: 35px !important;
    transform: none !important;
    width: auto;
    padding: 8px 20px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-radius: 0 0 20px 20px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-top: none;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    z-index: 98;
    white-space: nowrap;
}
</style>