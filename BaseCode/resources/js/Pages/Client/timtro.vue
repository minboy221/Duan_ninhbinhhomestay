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

                    <div class="filter-body" :class="{ 'collapsed': isFilterCollapsed }">
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
                            <h3>Khoảng giá:</h3>
                            <div class="price_list">
                                <label><input type="radio" name="price" :value="null" v-model="form.price"> Tất cả mức giá</label>
                                <label><input type="radio" name="price" value="duoi-1-trieu" v-model="form.price"> Dưới 1 triệu</label>
                                <label><input type="radio" name="price" value="1-2-trieu" v-model="form.price"> 1 - 2 triệu</label>
                                <label><input type="radio" name="price" value="2-3-trieu" v-model="form.price"> 2 - 3 triệu</label>
                                <label><input type="radio" name="price" value="tren-3-trieu" v-model="form.price"> Trên 3 triệu</label>
                            </div>
                        </div>

                        <!-- Diện tích -->
                        <div class="select_option">
                            <h3>Diện Tích:</h3>
                            <div class="price_list">
                                <label><input type="radio" name="dientich" :value="null" v-model="form.dientich"> Tất cả diện tích</label>
                                <label><input type="radio" name="dientich" value="duoi-20" v-model="form.dientich"> Dưới 20m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="20-30" v-model="form.dientich"> 20 - 30m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="30-50" v-model="form.dientich"> 30 - 50m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="tren-50" v-model="form.dientich"> Trên 50m<sup>2</sup></label>
                            </div>
                        </div>

                        <!-- Tiện ích (Dữ liệu từ DB) -->
                        <div class="select_option" v-if="amenities.length">
                            <h3>Tiện ích:</h3>
                            <div class="feature_list">
                                <label v-for="amenity in amenities" :key="amenity.id">
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
                        <div class="image_room">
                            <img :src="getRoomImageUrl(post.image)" alt="Ảnh phòng trọ" style="object-fit: cover;"
                                @error="$event.target.src = '/anh/banner_tro.png'">
                        </div>
                        <div class="infor_room">
                            <div class="title_room">
                                <h2 style="overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                                    {{ post.title }}</h2>
                            </div>
                            <div class="infor">
                                <p>{{ new Intl.NumberFormat('vi-VN').format(post.room?.price || 0) }} đ/tháng</p>
                                <p>{{ post.room?.area }}m<sup>2</sup></p>
                                <p style="overflow: hidden; text-overflow: ellipsis; max-width: 250px;"
                                    :title="post.room?.boarding_house?.address_detail || 'Ninh Bình'">
                                    <span><i class="bi bi-geo-alt"></i></span>
                                    {{ post.room?.boarding_house?.address_detail || 'Ninh Bình' }}
                                </p>

                                <!-- BADGE TRẠNG THÁI & THÔNG TIN PHÒNG -->
                                <div v-if="post.room?.status" style="margin-top: 4px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <span :class="[
                                            'inline-flex items-center px-4 py-1.5 rounded-full text-[13px] font-semibold border',
                                            getStatusClass(post.room.status)
                                        ]">
                                            {{ getStatusLabel(post.room.status) }}
                                        </span>
                                        <span v-if="post.room?.current_people > 0 || post.room?.status === 'rented'"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-[12px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="bi bi-person-check-fill" style="margin-right: 4px;"></i> Đã có {{ post.room?.current_people || 1 }} người ở
                                        </span>
                                        <span v-if="post.room?.boarding_house?.average_rating > 0" class="text-yellow-500 font-bold" style="font-size: 13px;">
                                            <i class="bi bi-star-fill"></i> {{ post.room.boarding_house.average_rating }}
                                        </span>
                                    </div>
                                    <div class="about_room">
                                        <p
                                            v-html="post.description ? (post.description.length > 80 ? post.description.substring(0, 80) + '...' : post.description) : 'Không có mô tả'">
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div class="user_room">
                                    <img :src="getAvatarUrl(post.landlord?.avatar)" alt="" style="object-fit: cover;">
                                    <h4>{{ post.landlord?.name || 'Chủ trọ' }}</h4>
                                    <p>cập nhật {{ timeAgo(post.updated_at) }}</p>
                                </div>
                                <Link :href="'/chitiettro/' + (post.slug_with_hash || post.id)" class="btn" style="position: unset;">
                                    Xem chi tiết
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