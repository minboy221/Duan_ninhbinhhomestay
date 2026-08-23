<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatMoney,timeAgo } from '@/Utils/formatters';
import { getAvatarUrl, getRoomImageUrl } from "@/Utils/media";
import { getStatusLabel, getStatusClass } from "@/Utils/statusHelper";
import Pagination from "@/Components/Pagination.vue";

// Props nhận dữ liệu từ Server (DB → Repository → Service → Controller → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    amenities: { type: Array, default: () => [] },
    listings: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    ai_parsed: { type: Object, default: () => null },
});

// Trạng thái AI Search
const aiPrompt = ref(props.filters?.ai_prompt || '');
const isAiSearching = ref(false);
const showDropdown = ref(false);
const selectedArea = ref(null);
const areaSearchQuery = ref('');
const isFilterCollapsed = ref(false);
const areaDropdownRef = ref(null);

const promptSuggestions = [
    { label: 'Tầng 1 Hoa Lư < 2.5tr', text: 'Tìm phòng tầng 1 quanh khu Hoa Lư, dưới 2.5 triệu' },
    { label: 'Studio gác xép nuôi pet', text: 'Phòng studio có gác xép, cho nuôi thú cưng' },
    { label: 'Có điều hòa & máy giặt', text: 'Phòng trọ có điều hòa, máy giặt, nóng lạnh dưới 3 triệu' },
    { label: 'Phòng ghép sinh viên', text: 'Phòng ghép sinh viên giá rẻ dưới 1.5 triệu' },
];

const filteredAreas = computed(() => {
    if (!areaSearchQuery.value.trim()) return props.areas || [];
    const q = areaSearchQuery.value.toLowerCase().trim();
    return (props.areas || []).filter(area => area.name.toLowerCase().includes(q));
});

const safeListings = computed(() => {
    if (!props.listings) {
        return { data: [], links: [], total: 0, current_page: 1, last_page: 1 };
    }
    return {
        data: props.listings.data || [],
        links: props.listings.links || [],
        total: props.listings.total ?? (props.listings.data ? props.listings.data.length : 0),
        current_page: props.listings.current_page || 1,
        last_page: props.listings.last_page || 1,
    };
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

    // Khởi tạo bộ lọc từ URL nếu có
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('area_id')) {
        form.value.area_id = Number(urlParams.get('area_id'));
        selectedArea.value = (props.areas || []).find(a => a.id === form.value.area_id) || null;
    }
    if (urlParams.get('price')) {
        form.value.price = urlParams.get('price');
    }
    if (urlParams.get('dientich')) {
        form.value.dientich = urlParams.get('dientich');
    }
    const catParams = urlParams.getAll('category_id[]').concat(urlParams.get('category_id') ? [urlParams.get('category_id')] : []);
    if (catParams.length > 0) {
        form.value.categories = catParams.map(Number).filter(Boolean);
    }
    const amenityParams = urlParams.getAll('amenities[]').concat(urlParams.get('amenities') ? [urlParams.get('amenities')] : []);
    if (amenityParams.length > 0) {
        form.value.amenities = amenityParams.map(Number).filter(Boolean);
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});


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

function submitSearch() {
    const params = {};
    if (form.value.area_id) params.area_id = form.value.area_id;
    if (form.value.price) params.price = form.value.price;
    if (form.value.dientich) params.dientich = form.value.dientich;
    if (form.value.categories && form.value.categories.length > 0) {
        params.category_id = form.value.categories;
    }
    if (form.value.amenities && form.value.amenities.length > 0) {
        params.amenities = form.value.amenities;
    }
    if (props.filters?.search) {
        params.search = props.filters.search;
    }

    router.get(route('timtro'), params, {
        preserveState: true,
        preserveScroll: false,
    });
}

function resetFilters() {
    form.value = {
        area_id: null,
        price: null,
        dientich: null,
        categories: [],
        amenities: []
    };
    selectedArea.value = null;
    router.get(route('timtro'), {}, {
        preserveState: true,
        preserveScroll: false,
    });
}

const formatPaginationLabel = (label) => {
    if (!label) return '';
    if (label.includes('Previous') || label.includes('&laquo;')) {
        return '<i class="bi bi-chevron-left" style="margin-right: 4px;"></i> Trước';
    }
    if (label.includes('Next') || label.includes('&raquo;')) {
        return 'Sau <i class="bi bi-chevron-right" style="margin-left: 4px;"></i>';
    }
    return label;
};
</script>

<template>

    <Head title="Tìm Phòng Trọ Thông Minh AI | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Trọ</h1>
                <h1>Tìm Phòng Trọ Thông Minh</h1>
                <p><a href="/">Trang Chủ</a> / Tìm Trọ</p>
            </div>
        </div>

        <!-- AI INSIGHT SUMMARY BANNER (HIỂN THỊ KHI TRUY CẬP TỪ CHATBOX AI) -->
        <div v-if="ai_parsed && ai_parsed.success" class="ai-active-filter-container">
            <div class="ai-insight-card">
                <div class="ai-insight-header">
                    <div class="ai-insight-info">
                        <div class="ai-insight-avatar">
                            <img src="/anh/popup_character.png" alt="AI Avatar"
                                class="w-full h-full object-cover rounded-full" />
                        </div>
                        <div class="ai-insight-text-col">
                            <div class="ai-insight-title-row">
                                <h4 class="ai-insight-title">Đang lọc theo gợi ý Trợ lý AI:</h4>
                                <span v-if="ai_parsed.engine === 'gemini'"
                                    class="ai-insight-engine-badge ai-insight-engine-gemini">Gemini Flash AI</span>
                                <span v-else class="ai-insight-engine-badge ai-insight-engine-smart">Smart
                                    Matcher</span>
                            </div>
                            <p class="ai-insight-explanation">{{ ai_parsed.explanation }}</p>
                        </div>
                    </div>
                    <button @click="clearAllFilters()" type="button" class="ai-insight-reset-btn">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Đặt lại tất cả</span>
                    </button>
                </div>

                <!-- Detected Badges -->
                <div class="ai-insight-badges-section">
                    <span class="ai-insight-badges-label">Tiêu chí đã lọc:</span>
                    <span v-if="ai_parsed.area_name"
                        class="ai-badge-item bg-blue-50 border border-blue-200 text-[#102a6d]">
                        <i class="bi bi-geo-alt-fill text-blue-600"></i> {{ ai_parsed.area_name }}
                    </span>
                    <span v-if="ai_parsed.price_max"
                        class="ai-badge-item bg-emerald-50 border border-emerald-200 text-emerald-700">
                        <i class="bi bi-tag-fill text-emerald-600"></i> ≤ {{ new
                            Intl.NumberFormat('vi-VN').format(ai_parsed.price_max) }} đ
                    </span>
                    <span v-if="ai_parsed.floor_number"
                        class="ai-badge-item bg-indigo-50 border border-indigo-200 text-indigo-700">
                        <i class="bi bi-layers-fill text-indigo-600"></i> Tầng {{ ai_parsed.floor_number }}
                    </span>
                    <span v-if="ai_parsed.category_name"
                        class="ai-badge-item bg-purple-50 border border-purple-200 text-purple-700">
                        <i class="bi bi-house-door-fill text-purple-600"></i> {{ ai_parsed.category_name }}
                    </span>
                    <span v-for="(amName, amIdx) in ai_parsed.amenity_names" :key="amIdx"
                        class="ai-badge-item bg-teal-50 border border-teal-200 text-teal-700">
                        <i class="bi bi-check-circle-fill text-teal-600"></i> {{ amName }}
                    </span>
                    <span v-if="ai_parsed.keyword"
                        class="ai-badge-item bg-amber-50 border border-amber-200 text-amber-800">
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
                    <div class="filter-title-wrapper flex items-center justify-between cursor-pointer pb-3 border-b border-slate-100"
                        @click="isFilterCollapsed = !isFilterCollapsed">
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
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Khu vực (Ninh
                                Bình):</h3>
                            <div class="select cursor-pointer flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-lg hover:border-blue-400 transition-colors"
                                @click.stop="showDropdown = !showDropdown">
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
                                        <input v-model="areaSearchQuery" type="text" placeholder="Gõ tìm phường, xã..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-md border border-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            @click.stop />
                                    </div>
                                </div>

                                <ul class="max-h-56 overflow-y-auto py-1 custom-scrollbar">
                                    <li class="px-3 py-2 text-xs text-slate-500 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                                        :class="{ 'active font-semibold text-blue-600 bg-blue-50/50': !selectedArea }"
                                        @click="selectArea(null)">
                                        <span>-- Tất cả khu vực --</span>
                                    </li>
                                    <li v-for="area in filteredAreas" :key="area.id"
                                        class="px-3 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-600 cursor-pointer flex items-center justify-between"
                                        :class="{ active: selectedArea?.id === area.id, 'bg-blue-50 font-semibold text-blue-600': selectedArea?.id === area.id }"
                                        @click="selectArea(area)">
                                        <span class="flex items-center gap-2">
                                            <i :class="['bi', area.icon || 'bi-geo-alt']"></i> {{ area.name }}
                                        </span>
                                        <i v-if="selectedArea?.id === area.id"
                                            class="bi bi-check2 text-blue-600 font-bold"></i>
                                    </li>
                                    <li v-if="filteredAreas.length === 0"
                                        class="px-3 py-4 text-center text-xs text-slate-400">
                                        Không tìm thấy khu vực
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Khoảng giá -->
                        <div class="select_option">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Khoảng giá:
                            </h3>
                            <div class="price_list space-y-1.5 text-sm">
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" :value="null" v-model="form.price"
                                        class="text-blue-600 focus:ring-blue-500"> Tất cả mức giá
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="duoi-1-trieu" v-model="form.price"
                                        class="text-blue-600 focus:ring-blue-500"> Dưới 1 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="1-2-trieu" v-model="form.price"
                                        class="text-blue-600 focus:ring-blue-500"> 1 - 2 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="2-3-trieu" v-model="form.price"
                                        class="text-blue-600 focus:ring-blue-500"> 2 - 3 triệu
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="price" value="tren-3-trieu" v-model="form.price"
                                        class="text-blue-600 focus:ring-blue-500"> Trên 3 triệu
                                </label>
                            </div>
                        </div>

                        <!-- Diện tích -->
                        <div class="select_option">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Diện Tích:
                            </h3>
                            <div class="price_list space-y-1.5 text-sm">
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" :value="null" v-model="form.dientich"
                                        class="text-blue-600 focus:ring-blue-500"> Tất cả diện tích
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="duoi-20" v-model="form.dientich"
                                        class="text-blue-600 focus:ring-blue-500"> Dưới 20m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="20-30" v-model="form.dientich"
                                        class="text-blue-600 focus:ring-blue-500"> 20 - 30m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="30-50" v-model="form.dientich"
                                        class="text-blue-600 focus:ring-blue-500"> 30 - 50m²
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="radio" name="dientich" value="tren-50" v-model="form.dientich"
                                        class="text-blue-600 focus:ring-blue-500"> Trên 50m²
                                </label>
                            </div>
                        </div>

                        <!-- Loại phòng (Lấy động từ DB) -->
                        <div class="select_option" v-if="categories.length">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Loại phòng:
                            </h3>
                            <div class="feature_list space-y-1.5 text-sm">
                                <label v-for="cat in categories" :key="cat.id"
                                    class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" :value="cat.id" v-model="form.categories"
                                        class="rounded text-blue-600 focus:ring-blue-500">
                                    <i :class="['bi', cat.icon || 'bi-house']"></i> {{ cat.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Tiện ích (Lấy động từ DB) -->
                        <div class="select_option" v-if="amenities.length">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tiện ích:
                            </h3>
                            <div
                                class="feature_list space-y-1.5 text-sm max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                <label v-for="amenity in amenities" :key="amenity.id"
                                    class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" :value="amenity.id" v-model="form.amenities"
                                        class="rounded text-blue-600 focus:ring-blue-500">
                                    <i :class="['bi', amenity.icon || 'bi-check-circle']"></i> {{ amenity.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Bản đồ khu vực đã chọn -->
                        <div v-if="selectedArea?.map_embed" class="map_section pt-2 border-t border-slate-100">
                            <h3 class="text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1">
                                <i class="bi bi-map text-blue-600"></i> Bản đồ: {{ selectedArea.name }}
                            </h3>
                            <div class="map_wrap rounded-lg overflow-hidden border border-slate-200"
                                v-html="selectedArea.map_embed"></div>
                        </div>

                        <div class="bao_btn" style="display: flex; gap: 8px; margin-top: 15px;">
                            <button class="btn_filter" @click="submitSearch" style="flex: 2;">Tìm kiếm</button>
                            <button type="button" @click="resetFilters"
                                style="flex: 1; padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.2s;">
                                Đặt lại
                            </button>
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
                </div>
            </section>

            <!-- PHẦN HIỂN THỊ PHÒNG -->
            <section class="room">
                <!-- Header số lượng kết quả -->
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
                    <div class="text-sm text-slate-600 font-medium">
                        Tìm thấy <strong class="text-blue-600 font-bold">{{ safeListings.total }}</strong> phòng trọ phù
                        hợp
                    </div>
                </div>

                <div class="baoroom">
                    <!-- Tiêu đề tổng quan số lượng phòng -->
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; width: 100%; padding: 0 4px;">
                        <div
                            style="font-size: 15px; font-weight: 800; color: #1e293b; display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span
                                style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #eff6ff; color: #2563eb; border-radius: 8px; flex-shrink: 0;">
                                <i class="bi bi-houses-fill"></i>
                            </span>
                            <span style="white-space: nowrap;">Danh sách phòng trọ</span>
                            <span
                                style="font-size: 13px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 10px; border-radius: 20px;">{{
                                    safeListings.total }} phòng</span>
                        </div>
                        <div v-if="safeListings.last_page > 1"
                            style="font-size: 12px; font-weight: 700; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;">
                            Trang {{ safeListings.current_page }} / {{ safeListings.last_page }}
                        </div>
                    </div>

                    <div v-if="safeListings.data.length === 0"
                        style="text-align: center; padding: 60px 20px; width: 100%; color: #64748b;"
                        class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <i class="bi bi-house-x text-5xl mb-3 text-slate-400 block"></i>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">Không tìm thấy phòng trọ nào phù hợp</h3>
                        <p class="text-sm text-slate-500 mb-4">Hãy thử nới lỏng các tiêu chí lọc hoặc thử một câu tìm
                            kiếm AI khác.</p>
                        <button @click="clearAllFilters()"
                            class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-arrow-counterclockwise"></i> Xem tất cả phòng trọ
                        </button>
                    </div>

                    <div class="item_room" v-for="post in safeListings.data" :key="post.id">
                        <div class="image_room">
                            <img :src="getRoomImageUrl(post.image)" alt="Ảnh phòng trọ" style="object-fit: cover;"
                                @error="$event.target.src = '/anh/banner_tro.png'">
                        </div>
                        <div class="infor_room">
                            <div class="title_room" style="min-height: 48px; display: flex; align-items: center;">
                                <h2>{{ post.title }}</h2>
                            </div>
                            <div class="infor">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-base font-bold text-rose-600">
                                        {{ new Intl.NumberFormat('vi-VN').format(post.room?.price || 0) }} <span
                                            class="text-xs font-normal text-slate-500">đ/tháng</span>
                                    </span>
                                    <span class="room-badge room-badge-area">
                                        {{ post.room?.area }} m²
                                    </span>
                                    <span
                                        class="text-xs text-slate-600 flex items-center gap-1.5 truncate max-w-[280px]"
                                        :title="post.room?.boarding_house?.address_detail || 'Ninh Bình'">
                                        <i class="bi bi-geo-alt text-blue-500"></i>
                                        {{ post.room?.boarding_house?.address_detail || 'Ninh Bình' }}
                                    </span>
                                </div>

                                <!-- BADGE TRẠNG THÁI & THÔNG TIN PHÒNG -->
                                <div v-if="post.room?.status" class="mt-2.5">
                                    <div class="room-badge-list">
                                        <span class="room-badge" :class="getStatusClass(post.room?.status)">
                                            {{ getStatusLabel(post.room?.status) }}
                                        </span>
                                        <span v-if="post.room?.floor?.name" class="room-badge room-badge-floor">
                                            <i class="bi bi-layers-fill"></i> {{ post.room?.floor?.name }}
                                        </span>
                                        <span v-if="post.room?.current_people > 0 || post.room?.status === 'rented'"
                                            class="room-badge room-badge-residents">
                                            <i class="bi bi-person-check-fill"></i> Đã có {{ post.room?.current_people
                                                || 1 }} người ở
                                        </span>
                                        <span v-if="post.room?.boarding_house?.average_rating > 0"
                                            class="room-badge-rating">
                                            <i class="bi bi-star-fill"></i> {{ post.room?.boarding_house?.average_rating
                                            }}
                                        </span>
                                        <span v-else class="room-badge-no-rating">Chưa có đánh giá</span>
                                    </div>
                                    <div class="about_room mt-2">
                                        <p
                                            v-html="post.description ? (post.description.length > 80 ? post.description.substring(0, 80) + '...' : post.description) : 'Không có mô tả'">
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="user_room">
                                <div class="user_infor_left">
                                    <img :src="getAvatarUrl(post.landlord?.avatar)" alt=""
                                        class="w-8 h-8 rounded-full object-cover"
                                        @error="$event.target.src = '/anh/banner.png'">
                                    <div>
                                        <div class="name_user">{{ post.landlord?.name || 'Chủ trọ' }}</div>
                                        <div class="text-[11px] text-slate-400">cập nhật {{ timeAgo(post.updated_at) }}
                                        </div>
                                    </div>
                                </div>
                                <Link :href="'/chitiettro/' + (post.slug_with_hash || post.id)" class="btn">
                                    Xem chi tiết <i class="bi bi-arrow-right text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Phân trang nằm chính giữa toàn bộ trang -->
        <Pagination :links="listings.links" />
    </MainLayout>
</template>

<style scoped>
@import "../../css/timtro.css";
@import '../../css/responsive/responsivetimtro.css';
@import '../../css/responsive/responsive.css';

.ai-hero-wrapper,
.ai-hero-wrapper * {
    font-family: Arial, sans-serif !important;
    letter-spacing: 0 !important;
}
</style>