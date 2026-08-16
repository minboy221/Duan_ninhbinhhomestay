<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

// Props nhận dữ liệu từ Server (DB → Repository → Service → Controller → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    amenities: { type: Array, default: () => [] },
    listings: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    ai_parsed: { type: Object, default: () => null },
});

const timeAgo = (date) => {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff} giây trước`;
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    return `${Math.floor(diff / 86400)} ngày trước`;
};

// Trạng thái AI Search
const aiPrompt = ref(props.filters?.ai_prompt || '');
const isAiSearching = ref(false);
const showDropdown = ref(false);
const selectedArea = ref(null);
const areaSearchQuery = ref('');
const isFilterCollapsed = ref(false);
const areaDropdownRef = ref(null);

const promptSuggestions = [
    { label: '🏢 Tầng 1 Hoa Lư < 2.5tr', text: 'Tìm phòng tầng 1 quanh khu Hoa Lư, dưới 2.5 triệu' },
    { label: '🌿 Studio gác xép nuôi pet', text: 'Phòng studio có gác xép, cho nuôi thú cưng' },
    { label: '❄️ Có điều hòa & máy giặt', text: 'Phòng trọ có điều hòa, máy giặt, nóng lạnh dưới 3 triệu' },
    { label: '👥 Phòng ghép sinh viên', text: 'Phòng ghép sinh viên giá rẻ dưới 1.5 triệu' },
];

const filteredAreas = computed(() => {
    if (!areaSearchQuery.value.trim()) return props.areas || [];
    const q = areaSearchQuery.value.toLowerCase().trim();
    return (props.areas || []).filter(area => area.name.toLowerCase().includes(q));
});

// Đối tượng lưu trữ các giá trị lọc thủ công
const form = ref({
    area_id: props.filters?.area_id ? parseInt(props.filters.area_id) : null,
    price: props.filters?.price || null,
    dientich: props.filters?.dientich || null,
    categories: Array.isArray(props.filters?.categories) 
        ? props.filters.categories.map(id => parseInt(id)) 
        : (props.filters?.categories ? [parseInt(props.filters.categories)] : []),
    amenities: Array.isArray(props.filters?.amenities) 
        ? props.filters.amenities.map(id => parseInt(id)) 
        : (props.filters?.amenities ? [parseInt(props.filters.amenities)] : []),
    search: props.filters?.search || '',
});

// Khởi tạo và đồng bộ khi nhận prop từ Server
const syncFromProps = () => {
    if (props.filters?.area_id) {
        selectedArea.value = (props.areas || []).find(a => a.id == props.filters.area_id) || null;
    } else if (props.ai_parsed?.area_id) {
        selectedArea.value = (props.areas || []).find(a => a.id == props.ai_parsed.area_id) || null;
        form.value.area_id = props.ai_parsed.area_id;
    } else {
        selectedArea.value = null;
        form.value.area_id = null;
    }

    if (props.ai_parsed) {
        if (props.ai_parsed.amenity_ids && props.ai_parsed.amenity_ids.length > 0) {
            form.value.amenities = [...props.ai_parsed.amenity_ids];
        }
        if (props.ai_parsed.category_id) {
            form.value.categories = [props.ai_parsed.category_id];
        }
    }
};

watch(() => props.filters, () => {
    syncFromProps();
}, { deep: true, immediate: true });

watch(() => props.ai_parsed, () => {
    syncFromProps();
}, { deep: true, immediate: true });

function selectArea(area) {
    selectedArea.value = area;
    form.value.area_id = area ? area.id : null;
    showDropdown.value = false;
}

const handleClickOutside = (event) => {
    if (areaDropdownRef.value && !areaDropdownRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    syncFromProps();
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});

// Thực thi lọc thủ công
function submitSearch() {
    const queryParams = {};
    if (form.value.area_id) queryParams.area_id = form.value.area_id;
    if (form.value.price) queryParams.price = form.value.price;
    if (form.value.dientich) queryParams.dientich = form.value.dientich;
    if (form.value.categories && form.value.categories.length) queryParams.categories = form.value.categories;
    if (form.value.amenities && form.value.amenities.length) queryParams.amenities = form.value.amenities;
    if (form.value.search && form.value.search.trim()) queryParams.search = form.value.search.trim();

    router.get('/timtro', queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Thực thi AI Search
function handleAiSearch(promptText = null) {
    if (typeof promptText === 'string') {
        aiPrompt.value = promptText;
    }
    if (!aiPrompt.value.trim()) {
        clearAllFilters();
        return;
    }

    isAiSearching.value = true;
    router.get('/timtro', { ai_prompt: aiPrompt.value.trim() }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isAiSearching.value = false;
        }
    });
}

// Xóa tất cả bộ lọc
function clearAllFilters() {
    aiPrompt.value = '';
    form.value = {
        area_id: null,
        price: null,
        dientich: null,
        categories: [],
        amenities: [],
        search: '',
    };
    selectedArea.value = null;
    router.get('/timtro', {}, {
        preserveState: false,
        preserveScroll: true,
    });
}

// Xóa 1 tiêu chí AI
function removeAiCriteria(type) {
    if (type === 'all') {
        clearAllFilters();
        return;
    }
    // Gửi lại lọc bằng các tham số thủ công ngoại trừ tiêu chí vừa xóa
    const newForm = { ...form.value };
    if (type === 'area') {
        newForm.area_id = null;
        selectedArea.value = null;
    } else if (type === 'price') {
        newForm.price = null;
    } else if (type === 'amenities') {
        newForm.amenities = [];
    } else if (type === 'category') {
        newForm.categories = [];
    }

    const queryParams = {};
    if (newForm.area_id) queryParams.area_id = newForm.area_id;
    if (newForm.price) queryParams.price = newForm.price;
    if (newForm.dientich) queryParams.dientich = newForm.dientich;
    if (newForm.categories?.length) queryParams.categories = newForm.categories;
    if (newForm.amenities?.length) queryParams.amenities = newForm.amenities;

    router.get('/timtro', queryParams, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Hiển thị trạng thái của phòng trọ
const getStatusLabel = (status) => {
    const labels = {
        available: 'Còn phòng',
        rented: 'Đã thuê',
        maintenance: 'Bảo trì',
        deposited: 'Đã cọc',
        expiring_soon: 'Sắp hết hạn',
        pending_renewal: 'Chờ gia hạn',
        suspended: 'Tạm ngưng',
        under_construction: 'Đang xây'
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        available: 'bg-green-100 text-green-700 border-green-200',
        rented: 'bg-gray-100 text-gray-600 border-gray-200',
        maintenance: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        deposited: 'bg-blue-100 text-blue-700 border-blue-200',
        expiring_soon: 'bg-orange-100 text-orange-700 border-orange-200',
        pending_renewal: 'bg-purple-100 text-purple-700 border-purple-200',
        suspended: 'bg-red-100 text-red-700 border-red-200',
        under_construction: 'bg-teal-100 text-teal-700 border-teal-200'
    };
    return classes[status] || 'bg-gray-100 text-gray-600 border-gray-200';
};

const getAvatarUrl = (avatar) => {
    if (!avatar || typeof avatar !== 'string' || !avatar.trim()) return '/anh/banner.png';
    const trimmed = avatar.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')) {
        return trimmed;
    }
    if (trimmed.startsWith('/storage/')) return trimmed;
    if (trimmed.startsWith('storage/')) return '/' + trimmed;
    if (trimmed.startsWith('/')) return trimmed;
    return '/storage/' + trimmed;
};

const getRoomImageUrl = (images) => {
    if (!images) return '/anh/banner_tro.png';
    let firstImg = images;
    if (Array.isArray(images)) {
        if (images.length === 0) return '/anh/banner_tro.png';
        firstImg = images[0];
    }
    if (typeof firstImg !== 'string' || !firstImg.trim()) return '/anh/banner_tro.png';
    const trimmed = firstImg.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')) {
        return trimmed;
    }
    if (trimmed.startsWith('/storage/')) return trimmed;
    if (trimmed.startsWith('storage/')) return '/' + trimmed;
    if (trimmed.startsWith('/')) return trimmed;
    return '/storage/' + trimmed;
};
</script>

<template>
    <Head title="Tìm Phòng Trọ Thông Minh AI | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Phòng Trọ Thông Minh</h1>
                <p><a href="/">Trang Chủ</a> / Tìm Trọ</p>
            </div>
        </div>

        <!-- AI SEARCH SECTION (KHUNG KÍNH BO TRÒN Ở GIỮA) -->
        <div class="ai-hero-wrapper">
            <div class="outer-glass-frame">
                <div class="inner-search-card">
                    <!-- Heading -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-normal flex items-center justify-center gap-2.5">
                            <i class="bi bi-stars text-blue-600 text-2xl"></i>
                            Tìm phòng trọ thông minh bằng AI
                        </h2>
                    </div>

                    <!-- Input Bar -->
                    <div class="ai-search-bar">
                        <div class="ai-search-input-wrapper">
                            <i class="bi bi-search ai-search-icon"></i>
                            <input
                                v-model="aiPrompt"
                                @keyup.enter="handleAiSearch()"
                                type="text"
                                placeholder="Nhập yêu cầu: Tìm phòng tầng 1 quanh Hoa Lư, dưới 2.5 triệu, có gác xép, thú cưng..."
                                class="ai-search-input"
                            />
                            <button
                                v-if="aiPrompt"
                                @click="aiPrompt = ''"
                                type="button"
                                class="ai-search-clear-btn"
                                title="Xóa nội dung"
                            >
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                        <button
                            @click="handleAiSearch()"
                            :disabled="isAiSearching"
                            class="ai-search-submit-btn"
                        >
                            <i v-if="isAiSearching" class="bi bi-arrow-repeat animate-spin text-base"></i>
                            <i v-else class="bi bi-stars text-base text-cyan-200"></i>
                            <span>{{ isAiSearching ? 'Đang phân tích...' : 'AI Tìm Kiếm' }}</span>
                        </button>
                    </div>

                    <!-- Prompt Suggestion Chips -->
                    <div class="ai-suggestions-wrapper">
                        <span class="text-slate-400 font-medium flex items-center gap-1 mr-1 text-xs">
                            <i class="bi bi-lightbulb text-amber-500"></i> Gợi ý:
                        </span>
                        <button
                            v-for="(chip, idx) in promptSuggestions"
                            :key="idx"
                            @click="handleAiSearch(chip.text)"
                            type="button"
                            class="ai-suggestion-chip"
                        >
                            {{ chip.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- AI INSIGHT SUMMARY BANNER (NẾU CÓ TIÊU CHÍ LỌC) -->
            <div v-if="ai_parsed && ai_parsed.success" class="ai-insight-card">
                <div class="ai-insight-header">
                    <div class="ai-insight-info">
                        <div class="ai-insight-avatar">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div class="ai-insight-text-col">
                            <div class="ai-insight-title-row">
                                <h4 class="ai-insight-title">Kết quả phân tích từ AI:</h4>
                                <span v-if="ai_parsed.engine === 'gemini'" class="ai-insight-engine-badge ai-insight-engine-gemini">Gemini Flash AI</span>
                                <span v-else class="ai-insight-engine-badge ai-insight-engine-smart">Smart Matcher</span>
                            </div>
                            <p class="ai-insight-explanation">{{ ai_parsed.explanation }}</p>
                        </div>
                    </div>
                    <button
                        @click="clearAllFilters()"
                        type="button"
                        class="ai-insight-reset-btn"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Đặt lại tất cả</span>
                    </button>
                </div>

                <!-- Detected Badges -->
                <div class="ai-insight-badges-section">
                    <span class="ai-insight-badges-label">Tiêu chí đã lọc:</span>
                    <span v-if="ai_parsed.area_name" class="ai-badge-item bg-blue-50 border border-blue-200 text-[#102a6d]">
                        <i class="bi bi-geo-alt-fill text-blue-600"></i> {{ ai_parsed.area_name }}
                    </span>
                    <span v-if="ai_parsed.price_max" class="ai-badge-item bg-emerald-50 border border-emerald-200 text-emerald-700">
                        <i class="bi bi-tag-fill text-emerald-600"></i> ≤ {{ new Intl.NumberFormat('vi-VN').format(ai_parsed.price_max) }} đ
                    </span>
                    <span v-if="ai_parsed.floor_number" class="ai-badge-item bg-indigo-50 border border-indigo-200 text-indigo-700">
                        <i class="bi bi-layers-fill text-indigo-600"></i> Tầng {{ ai_parsed.floor_number }}
                    </span>
                    <span v-if="ai_parsed.category_name" class="ai-badge-item bg-purple-50 border border-purple-200 text-purple-700">
                        <i class="bi bi-house-door-fill text-purple-600"></i> {{ ai_parsed.category_name }}
                    </span>
                    <span v-for="(amName, amIdx) in ai_parsed.amenity_names" :key="amIdx" class="ai-badge-item bg-teal-50 border border-teal-200 text-teal-700">
                        <i class="bi bi-check-circle-fill text-teal-600"></i> {{ amName }}
                    </span>
                    <span v-if="ai_parsed.keyword" class="ai-badge-item bg-amber-50 border border-amber-200 text-amber-800">
                        <i class="bi bi-search text-amber-600"></i> "{{ ai_parsed.keyword }}"
                    </span>
                </div>
            </div>
        </div>

        <!-- PHẦN CHIA LAYOUT BỘ LỌC + DANH SÁCH PHÒNG -->
        <div class="layout">
            <!-- CỘT TRÁI: BỘ LỌC TÌM KIẾM -->
            <section class="filter">
                <div class="baofilter bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="filter-title-wrapper flex items-center justify-between cursor-pointer pb-3 border-b border-slate-100" @click="isFilterCollapsed = !isFilterCollapsed">
                        <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-sliders text-blue-600"></i> Bộ Lọc Tìm Kiếm
                        </h2>
                        <button class="filter-toggle-btn text-slate-400 hover:text-slate-600" type="button">
                            <i :class="['bi', isFilterCollapsed ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
                        </button>
                    </div>

                    <div class="filter-body mt-4 space-y-5" :class="{ 'collapsed': isFilterCollapsed }">
                        <!-- Khu vực (Searchable Dropdown từ DB) -->
                        <div class="select_box relative" ref="areaDropdownRef">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Khu vực (Ninh Bình):</h3>
                            <div class="select cursor-pointer flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-lg hover:border-blue-400 transition-colors" @click.stop="showDropdown = !showDropdown">
                                <span class="selected flex items-center gap-2 text-sm text-slate-700 font-medium">
                                    <i class="bi bi-geo-alt text-blue-600"></i>
                                    {{ selectedArea ? selectedArea.name : 'Tất cả khu vực' }}
                                </span>
                                <span class="arrow text-slate-400 text-xs"><i class="bi bi-caret-down-fill"></i></span>
                            </div>

                            <div class="dropdown shadow-xl" :class="{ show: showDropdown }">
                                <!-- Search Input trong dropdown -->
                                <div class="p-2 border-b border-slate-100 bg-slate-50 sticky top-0 z-10">
                                    <div class="relative flex items-center">
                                        <i class="bi bi-search absolute left-3 text-slate-400 text-xs"></i>
                                        <input 
                                            v-model="areaSearchQuery"
                                            type="text"
                                            placeholder="Gõ tìm phường, xã..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-md border border-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            @click.stop
                                        />
                                    </div>
                                </div>

                                <ul class="max-h-56 overflow-y-auto py-1 custom-scrollbar">
                                    <li 
                                        class="px-3 py-2 text-xs text-slate-500 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                                        :class="{ 'active font-semibold text-blue-600 bg-blue-50/50': !selectedArea }" 
                                        @click="selectArea(null)"
                                    >
                                        <span>-- Tất cả khu vực --</span>
                                    </li>
                                    <li 
                                        v-for="area in filteredAreas" 
                                        :key="area.id"
                                        class="px-3 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-600 cursor-pointer flex items-center justify-between"
                                        :class="{ active: selectedArea?.id === area.id, 'bg-blue-50 font-semibold text-blue-600': selectedArea?.id === area.id }" 
                                        @click="selectArea(area)"
                                    >
                                        <span class="flex items-center gap-2">
                                            <i :class="['bi', area.icon || 'bi-geo-alt']"></i> {{ area.name }}
                                        </span>
                                        <i v-if="selectedArea?.id === area.id" class="bi bi-check2 text-blue-600 font-bold"></i>
                                    </li>
                                    <li v-if="filteredAreas.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
                                        Không tìm thấy khu vực
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Khoảng giá -->
                        <div class="select_option">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Khoảng giá:</h3>
                            <div class="price_list space-y-1.5 text-sm">
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" :value="null" v-model="form.price" class="text-blue-600 focus:ring-blue-500"> Tất cả mức giá
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="duoi-1-trieu" v-model="form.price" class="text-blue-600 focus:ring-blue-500"> Dưới 1 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="1-2-trieu" v-model="form.price" class="text-blue-600 focus:ring-blue-500"> 1 - 2 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="2-3-trieu" v-model="form.price" class="text-blue-600 focus:ring-blue-500"> 2 - 3 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="tren-3-trieu" v-model="form.price" class="text-blue-600 focus:ring-blue-500"> Trên 3 triệu
                                </label>
                            </div>
                        </div>

                        <!-- Diện tích -->
                        <div class="select_option">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Diện Tích:</h3>
                            <div class="price_list space-y-1.5 text-sm">
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" :value="null" v-model="form.dientich" class="text-blue-600 focus:ring-blue-500"> Tất cả diện tích
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="duoi-20" v-model="form.dientich" class="text-blue-600 focus:ring-blue-500"> Dưới 20m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="20-30" v-model="form.dientich" class="text-blue-600 focus:ring-blue-500"> 20 - 30m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="30-50" v-model="form.dientich" class="text-blue-600 focus:ring-blue-500"> 30 - 50m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="tren-50" v-model="form.dientich" class="text-blue-600 focus:ring-blue-500"> Trên 50m²
                                </label>
                            </div>
                        </div>

                        <!-- Loại phòng (Lấy động từ DB) -->
                        <div class="select_option" v-if="categories.length">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Loại phòng:</h3>
                            <div class="feature_list space-y-1.5 text-sm">
                                <label v-for="cat in categories" :key="cat.id" class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" :value="cat.id" v-model="form.categories" class="rounded text-blue-600 focus:ring-blue-500">
                                    <i :class="['bi', cat.icon || 'bi-house']"></i> {{ cat.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Tiện ích (Lấy động từ DB) -->
                        <div class="select_option" v-if="amenities.length">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tiện ích:</h3>
                            <div class="feature_list space-y-1.5 text-sm max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                <label v-for="amenity in amenities" :key="amenity.id" class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" :value="amenity.id" v-model="form.amenities" class="rounded text-blue-600 focus:ring-blue-500">
                                    <i :class="['bi', amenity.icon || 'bi-check-circle']"></i> {{ amenity.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Bản đồ khu vực đã chọn -->
                        <div v-if="selectedArea?.map_embed" class="map_section pt-2 border-t border-slate-100">
                            <h3 class="text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1">
                                <i class="bi bi-map text-blue-600"></i> Bản đồ: {{ selectedArea.name }}
                            </h3>
                            <div class="map_wrap rounded-lg overflow-hidden border border-slate-200" v-html="selectedArea.map_embed"></div>
                        </div>

                        <!-- Nút Lọc và Đặt lại -->
                        <div class="bao_btn">
                            <button type="button" class="btn-apply-filter" @click="submitSearch">
                                <i class="bi bi-funnel-fill text-base"></i>
                                <span>Áp Dụng Bộ Lọc</span>
                            </button>
                            <button type="button" class="btn-reset-filter" @click="clearAllFilters">
                                <i class="bi bi-arrow-counterclockwise text-base"></i>
                                <span>Xóa Bộ Lọc</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PHẦN HIỂN THỊ PHÒNG -->
            <section class="room">
                <!-- Header số lượng kết quả -->
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                    <div class="text-sm text-slate-600 font-medium">
                        Tìm thấy <strong class="text-blue-600 font-bold">{{ listings.total || listings.data.length }}</strong> phòng trọ phù hợp
                    </div>
                </div>

                <div class="baoroom">
                    <div v-if="listings.data.length === 0"
                        style="text-align: center; padding: 60px 20px; width: 100%; color: #64748b;"
                        class="bg-white rounded-2xl border border-slate-200 shadow-sm"
                    >
                        <i class="bi bi-house-x text-5xl mb-3 text-slate-400 block"></i>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">Không tìm thấy phòng trọ nào phù hợp</h3>
                        <p class="text-sm text-slate-500 mb-4">Hãy thử nới lỏng các tiêu chí lọc hoặc thử một câu tìm kiếm AI khác.</p>
                        <button @click="clearAllFilters()" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-arrow-counterclockwise"></i> Xem tất cả phòng trọ
                        </button>
                    </div>

                    <div class="item_room" v-for="post in listings.data" :key="post.id">
                        <div class="image_room">
                            <img :src="getRoomImageUrl(post.image)"
                                alt="Ảnh phòng trọ"
                                style="object-fit: cover;"
                                @error="$event.target.src = '/anh/banner_tro.png'">
                        </div>
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>{{ post.title }}</h2>
                            </div>
                            <div class="infor">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-base font-bold text-rose-600">
                                        {{ new Intl.NumberFormat('vi-VN').format(post.room?.price || 0) }} <span class="text-xs font-normal text-slate-500">đ/tháng</span>
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md">
                                        {{ post.room?.area }} m²
                                    </span>
                                    <span class="text-xs text-slate-600 flex items-center gap-1 truncate max-w-[280px]"
                                        :title="post.room?.boarding_house?.address_detail || 'Ninh Bình'">
                                        <i class="bi bi-geo-alt text-blue-500"></i>
                                        {{ post.room?.boarding_house?.address_detail || 'Ninh Bình' }}
                                    </span>
                                </div>

                                <!-- BADGE TRẠNG THÁI & THÔNG TIN PHÒNG -->
                                <div v-if="post.room?.status" class="mt-2">
                                    <div class="flex items-center gap-2 flex-wrap text-xs">
                                        <span :class="[
                                            'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border',
                                            getStatusClass(post.room.status)
                                        ]">
                                            {{ getStatusLabel(post.room.status) }}
                                        </span>
                                        <span v-if="post.room?.floor?.name"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <i class="bi bi-layers-fill mr-1"></i> {{ post.room.floor.name }}
                                        </span>
                                        <span v-if="post.room?.current_people > 0 || post.room?.status === 'rented'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="bi bi-person-check-fill mr-1"></i> Đã có {{ post.room?.current_people || 1 }} người ở
                                        </span>
                                        <span v-if="post.room?.boarding_house?.average_rating > 0" class="text-yellow-500 font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-star-fill"></i> {{ post.room.boarding_house.average_rating }}
                                        </span>
                                        <span v-else class="text-gray-400 italic text-xs">Chưa có đánh giá</span>
                                    </div>
                                    <div class="about_room mt-2">
                                        <p
                                            v-html="post.description ? (post.description.length > 90 ? post.description.substring(0, 90) + '...' : post.description) : 'Không có mô tả'">
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="user_room">
                                <div class="user_infor_left">
                                    <img :src="getAvatarUrl(post.landlord?.avatar)" 
                                        alt="" 
                                        class="w-8 h-8 rounded-full object-cover"
                                        @error="$event.target.src = '/anh/banner.png'">
                                    <div>
                                        <div class="name_user">{{ post.landlord?.name || 'Chủ trọ' }}</div>
                                        <div class="text-[11px] text-slate-400">cập nhật {{ timeAgo(post.updated_at) }}</div>
                                    </div>
                                </div>
                                <Link :href="'/chitiettro/' + post.id" class="btn">
                                    Xem chi tiết <i class="bi bi-arrow-right text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phân trang -->
                <div class="phantrang" v-if="listings.links && listings.links.length > 3">
                    <div class="baophantrang">
                        <template v-for="(link, index) in listings.links" :key="index">
                            <div class="so_trang" :class="{ 'active': link.active }">
                                <Link v-if="link.url" :href="link.url" v-html="link.label"></Link>
                                <span v-else v-html="link.label" style="opacity: 0.5; padding: 8px 12px;"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/timtro.css";
@import '../../css/responsive/responsivetimtro.css';
@import '../../css/responsive/responsive.css';

.ai-hero-wrapper,
.ai-hero-wrapper * {
    font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif !important;
    letter-spacing: 0 !important;
}
</style>