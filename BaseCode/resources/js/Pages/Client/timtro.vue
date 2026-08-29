<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatMoney, timeAgo } from '@/Utils/formatters';
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
const isPriceCollapsed = ref(false);
const isDientichCollapsed = ref(false);
const isAmenitiesCollapsed = ref(false);
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

const uniqueAmenities = computed(() => {
    const list = props.amenities || [];
    const seen = new Set();
    return list.filter(item => {
        const nameKey = item.name ? item.name.trim().toLowerCase() : item.id;
        if (seen.has(nameKey)) return false;
        seen.add(nameKey);
        return true;
    });
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
    // Mặc định thu gọn bộ lọc trên màn hình nhỏ (Mobile/Tablet < 1024px)
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        isFilterCollapsed.value = true;
    }
    window.addEventListener('click', handleClickOutside);

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('area_id')) {
        form.value.area_id = Number(urlParams.get('area_id'));
        selectedArea.value = (props.areas || []).find(a => a.id === form.value.area_id) || null;
    }
    if (urlParams.get('price')) form.value.price = urlParams.get('price');
    if (urlParams.get('dientich')) form.value.dientich = urlParams.get('dientich');
    const catParams = urlParams.getAll('categories[]');
    if (catParams.length > 0) {
        form.value.categories = catParams.map(Number).filter(Boolean);
    }
    const amParams = urlParams.getAll('amenities[]');
    if (amParams.length > 0) {
        form.value.amenities = amParams.map(Number).filter(Boolean);
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});

// Xử lý Tìm kiếm AI
const handleAiSearch = () => {
    if (!aiPrompt.value.trim() || isAiSearching.value) return;
    isAiSearching.value = true;

    router.get('/timtro', { ai_prompt: aiPrompt.value.trim() }, {
        preserveState: false,
        preserveScroll: false,
        onFinish: () => {
            isAiSearching.value = false;
        }
    });
};

const applySuggestion = (text) => {
    aiPrompt.value = text;
    handleAiSearch();
};

const clearAllFilters = () => {
    aiPrompt.value = '';
    selectedArea.value = null;
    form.value = {
        area_id: null,
        price: null,
        dientich: null,
        categories: [],
        amenities: [],
        search: '',
    };
    router.get('/timtro', {}, { preserveState: false, preserveScroll: false });
};

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
</script>

<template>

    <Head title="Tìm Phòng Trọ | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Trọ</h1>
                <p><a href="/">Trang Chủ</a> / Tìm Trọ</p>
            </div>
        </div>

        <!-- PHẦN CHIA LAYOUT BỘ LỌC + DANH SÁCH PHÒNG -->
        <div class="layout">
            <!-- CỘT TRÁI: BỘ LỌC TÌM KIẾM -->
            <section class="filter">
                <div class="baofilter">
                    <div class="filter-title-wrapper" @click="isFilterCollapsed = !isFilterCollapsed">
                        <h2>Bộ Lọc Tìm Kiếm</h2>
                        <button class="filter-toggle-btn" type="button">
                            <i :class="['bi', isFilterCollapsed ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
                        </button>
                    </div>

                    <div class="filter-body transition-all duration-300" v-show="!isFilterCollapsed"
                        :class="{ 'collapsed': isFilterCollapsed }">
                        <!-- Khu vực (Searchable Dropdown) -->
                        <div class="select_box relative" ref="areaDropdownRef">
                            <div class="select cursor-pointer flex items-center justify-between"
                                @click.stop="showDropdown = !showDropdown">
                                <span class="selected flex items-center gap-2">
                                    <i class="bi bi-geo-alt text-blue-600"></i>
                                    {{ selectedArea ? selectedArea.name : 'Chọn khu vực' }}
                                </span>
                                <span class="arrow"><i class="bi bi-caret-down"></i></span>
                            </div>

                            <div class="dropdown" :class="{ show: showDropdown }">
                                <!-- Search Input -->
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
                                        :class="{ 'active': !selectedArea }" @click="selectArea(null)">
                                        <span>-- Tất cả khu vực --</span>
                                    </li>
                                    <li v-for="area in filteredAreas" :key="area.id"
                                        :class="{ active: selectedArea?.id === area.id }" @click="selectArea(area)">
                                        <i :class="['bi', area.icon || 'bi-geo-alt']"></i> {{ area.name }}
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
                            <div class="flex items-center justify-between cursor-pointer py-1 mb-2 border-b border-slate-100"
                                @click="isPriceCollapsed = !isPriceCollapsed">
                                <h3 class="!mb-0 font-semibold text-slate-800 flex items-center gap-1.5">
                                    Khoảng giá:
                                </h3>
                                <button type="button" class="text-slate-400 hover:text-slate-600 text-xs">
                                    <i :class="['bi', isPriceCollapsed ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
                                </button>
                            </div>
                            <div v-show="!isPriceCollapsed" class="price_list transition-all duration-300">
                                <label><input type="radio" name="price" :value="null" v-model="form.price"> Tất cả mức
                                    giá</label>
                                <label><input type="radio" name="price" value="duoi-1-trieu" v-model="form.price"> Dưới
                                    1 triệu</label>
                                <label><input type="radio" name="price" value="1-2-trieu" v-model="form.price"> 1 - 2
                                    triệu</label>
                                <label><input type="radio" name="price" value="2-3-trieu" v-model="form.price"> 2 - 3
                                    triệu</label>
                                <label><input type="radio" name="price" value="tren-3-trieu" v-model="form.price"> Trên
                                    3 triệu</label>
                            </div>
                        </div>

                        <!-- Diện tích -->
                        <div class="select_option">
                            <div class="flex items-center justify-between cursor-pointer py-1 mb-2 border-b border-slate-100"
                                @click="isDientichCollapsed = !isDientichCollapsed">
                                <h3 class="!mb-0 font-semibold text-slate-800 flex items-center gap-1.5">
                                    Diện Tích:
                                </h3>
                                <button type="button" class="text-slate-400 hover:text-slate-600 text-xs">
                                    <i :class="['bi', isDientichCollapsed ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
                                </button>
                            </div>
                            <div v-show="!isDientichCollapsed" class="price_list transition-all duration-300">
                                <label><input type="radio" name="dientich" :value="null" v-model="form.dientich"> Tất cả
                                    diện tích</label>
                                <label><input type="radio" name="dientich" value="duoi-20" v-model="form.dientich"> Dưới
                                    20m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="20-30" v-model="form.dientich"> 20 -
                                    30m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="30-50" v-model="form.dientich"> 30 -
                                    50m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="tren-50" v-model="form.dientich"> Trên
                                    50m<sup>2</sup></label>
                            </div>
                        </div>

                        <!-- Tiện ích (Dữ liệu từ DB) -->
                        <div class="select_option" v-if="uniqueAmenities.length">
                            <div class="flex items-center justify-between cursor-pointer py-1 mb-2 border-b border-slate-100"
                                @click="isAmenitiesCollapsed = !isAmenitiesCollapsed">
                                <h3 class="!mb-0 font-semibold text-slate-800 flex items-center gap-1.5">
                                    Tiện ích:
                                </h3>
                                <button type="button" class="text-slate-400 hover:text-slate-600 text-xs">
                                    <i :class="['bi', isAmenitiesCollapsed ? 'bi-chevron-down' : 'bi-chevron-up']"></i>
                                </button>
                            </div>
                            <div v-show="!isAmenitiesCollapsed" class="feature_list transition-all duration-300">
                                <label v-for="amenity in uniqueAmenities" :key="amenity.id">
                                    <input type="checkbox" :value="amenity.id" v-model="form.amenities">
                                    <i :class="['bi', amenity.icon || 'bi-check-circle']"></i> {{ amenity.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Bản đồ khu vực đã chọn -->
                        <div v-if="selectedArea?.map_embed" class="map_section">
                            <h3><i class="bi bi-map"></i> Bản đồ: {{ selectedArea.name }}</h3>
                            <div class="map_wrap" v-html="selectedArea.map_embed"></div>
                        </div>

                        <div class="bao_btn">
                            <button class="btn_filter" @click="submitSearch">Tìm kiếm</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PHẦN HIỂN THỊ PHÒNG -->
            <section class="room">
                <div class="baoroom">
                    <div v-if="safeListings.data.length === 0"
                        style="text-align: center; padding: 50px 0; width: 100%; color: #64748b;">
                        <i class="bi bi-house-x text-4xl mb-3 block"></i>
                        <p>Không có phòng trọ nào phù hợp</p>
                    </div>

                    <div class="item_room" v-for="post in safeListings.data" :key="post.id">
                        <div class="image_room cursor-pointer">
                            <Link :href="'/chitiettro/' + (post.slug_with_hash || post.id)" class="block h-full w-full">
                                <img :src="getRoomImageUrl(post.image)" alt="Ảnh phòng trọ" style="object-fit: cover;"
                                    @error="$event.target.src = '/anh/banner_tro.png'">
                            </Link>
                        </div>
                        <div class="infor_room flex flex-col justify-between p-4 sm:p-5 flex-1 min-w-0">
                            <!-- Tiêu đề bài đăng -->
                            <div class="title_room mb-2">
                                <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug line-clamp-2">
                                    <Link :href="'/chitiettro/' + (post.slug_with_hash || post.id)"
                                        class="hover:text-blue-600 transition-colors">
                                        {{ post.title }}
                                    </Link>
                                </h2>
                            </div>

                            <!-- Thông tin chi tiết phòng (Giá, Diện tích, Địa chỉ, Badges, Mô tả) -->
                            <div class="infor_detail space-y-2 py-1 flex-1">
                                <!-- Giá tiền & Diện tích -->
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="text-base sm:text-lg font-black text-blue-600">
                                        {{ new Intl.NumberFormat('vi-VN').format(post.room?.price || 0) }} đ/tháng
                                    </span>
                                    <span
                                        class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                        <i class="bi bi-aspect-ratio text-slate-500"></i> {{ post.room?.area }} m²
                                    </span>
                                </div>

                                <!-- Địa chỉ -->
                                <p class="text-xs text-slate-500 flex items-center gap-1.5 truncate max-w-full"
                                    :title="post.room?.boarding_house?.address_detail || 'Ninh Bình'">
                                    <i class="bi bi-geo-alt-fill text-blue-500 shrink-0"></i>
                                    <span class="truncate font-medium">{{ post.room?.boarding_house?.address_detail ||
                                        'Ninh Bình' }}</span>
                                </p>

                                <!-- BADGE TRẠNG THÁI & THÔNG TIN PHÒNG -->
                                <div v-if="post.room?.status" class="pt-3 space-y-2">
                                    <div class="flex items-center gap-2 flex-wrap">

                                        <!-- Trạng thái phòng -->
                                        <span :class="[
                                            'inline-flex items-center gap-2 !px-3 !py-1.5 rounded-full text-[11px] font-bold border transition-all duration-200',
                                            getStatusClass(post.room.status)
                                        ]">
                                            <i class="bi bi-circle-fill text-[6px] opacity-90"></i>
                                            <span>{{ getStatusLabel(post.room.status) }}</span>
                                        </span>


                                        <!-- Số người đang ở -->
                                        <span v-if="post.room?.current_people > 0 || post.room?.status === 'rented'"
                                            class="inline-flex items-center gap-2 !px-3 !py-1.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 transition-all duration-200">
                                            <i class="bi bi-people-fill text-[12px] text-emerald-600"></i>
                                            <span>
                                                Đã có {{ post.room?.current_people || 0 }}/{{ post.room?.capacity || 1 }} người ở
                                            </span>
                                        </span>
                                        <!-- Đánh giá -->
                                        <span v-if="post.room?.boarding_house?.average_rating > 0"
                                            class="inline-flex items-center gap-2 !px-3 !py-1.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 transition-all duration-200">
                                            <i class="bi bi-star-fill text-[11px] text-amber-500"></i>
                                            <span>
                                                {{ Number(post.room.boarding_house.average_rating).toFixed(1) }}
                                            </span>
                                        </span>
                                    </div>

                                    <!-- Mô tả phòng -->
                                    <div
                                        class="about_room text-xs text-slate-500 line-clamp-2 leading-relaxed break-words overflow-hidden">
                                        <p class="break-words overflow-hidden"
                                            v-html="post.description ? (post.description.length > 90 ? post.description.substring(0, 90) + '...' : post.description) : 'Không có mô tả'">
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Chủ trọ & Nút xem chi tiết -->
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 mt-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="relative inline-block shrink-0"
                                        :title="post.landlord?.has_vip_frame ? `Chủ trọ VIP` : (post.landlord?.name || 'Chủ trọ')">
                                        <div :class="[
                                            'w-9 h-9 rounded-full p-[2px] flex items-center justify-center transition-all duration-300',
                                            post.landlord?.has_vip_frame
                                                ? 'bg-gradient-to-tr from-amber-500 via-yellow-300 to-amber-600 shadow-md shadow-amber-500/30 ring-2 ring-amber-400/40'
                                                : 'bg-slate-200'
                                        ]">
                                            <img :src="getAvatarUrl(post.landlord?.avatar)" alt=""
                                                style="object-fit: cover;" class="w-full h-full rounded-full bg-white">
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="flex items-center gap-1 font-bold text-slate-800 text-xs">
                                            {{ post.landlord?.name || 'Chủ trọ' }}
                                            <i v-if="post.landlord?.has_vip_frame"
                                                class="bi bi-patch-check-fill text-amber-500 text-xs"
                                                title="Chủ trọ VIP"></i>
                                        </h4>
                                        <p class="text-[10px] text-slate-400">Cập nhật {{ timeAgo(post.updated_at) }}
                                        </p>
                                    </div>
                                </div>
                                <Link :href="'/chitiettro/' + (post.slug_with_hash || post.id)" class="btn">
                                    <span>Xem chi tiết</span>
                                </Link>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Phân trang -->
                <div class="phantrang" v-if="safeListings.links && safeListings.links.length > 3">
                    <Pagination :links="safeListings.links" />
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/timtro.css";
@import '../../css/responsive/responsivetimtro.css';
@import '../../css/responsive/responsive.css';
</style>