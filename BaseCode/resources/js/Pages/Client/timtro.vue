<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

// Props nhận dữ liệu danh mục từ Server (DB → Repository → Service → Controller → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    amenities: { type: Array, default: () => [] },
    listings: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) }
})

const timeAgo = (date) => {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff} giây trước`;
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    return `${Math.floor(diff / 86400)} ngày trước`;
}

const showDropdown = ref(false)
const selectedArea = ref(null)
const areaSearchQuery = ref('')
const isFilterCollapsed = ref(true)
const areaDropdownRef = ref(null)

const filteredAreas = computed(() => {
    if (!areaSearchQuery.value.trim()) return props.areas || [];
    const q = areaSearchQuery.value.toLowerCase().trim();
    return (props.areas || []).filter(area => area.name.toLowerCase().includes(q));
});

// Đối tượng lưu trữ các giá trị lọc
const form = ref({
    area_id: null,
    price: null,
    dientich: null,
    categories: [],
    amenities: []
})

function selectArea(area) {
    selectedArea.value = area
    form.value.area_id = area ? area.id : null
    showDropdown.value = false
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

//Phần hiển thị trạng thái của phòng trọ
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

const getAvatarUrl = (avatar) => {
    if (!avatar) return '/anh/banner.png';
    if (avatar.startsWith('http') || avatar.startsWith('/') || avatar.startsWith('data:')) {
        return avatar;
    }
    return '/storage/' + avatar;
};
</script>

<template>

    <Head title="Tìm Phòng Trọ | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Trọ</h1>
                <p>
                <p><a href="/">Trang Chủ</a> / Tìm Trọ</p>
                </p>
            </div>
        </div>
        <!-- phần chia layout -->
        <div class="layout">
            <!-- phần bộ lọc tìm kiếm -->
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
                        <!-- khoảng giá -->
                        <div class="select_option">
                            <h3>Khoảng giá:</h3>
                            <div class="price_list">
                                <label><input type="radio" name="price" value="duoi-1-trieu" v-model="form.price"> Dưới
                                    1
                                    triệu</label>
                                <label><input type="radio" name="price" value="1-2-trieu" v-model="form.price"> 1 - 2
                                    triệu</label>
                                <label><input type="radio" name="price" value="2-3-trieu" v-model="form.price"> 2 - 3
                                    triệu</label>
                                <label><input type="radio" name="price" value="tren-3-trieu" v-model="form.price"> Trên
                                    3
                                    triệu</label>
                            </div>
                        </div>
                        <!-- Diện tích -->
                        <div class="select_option">
                            <h3>Diện Tích:</h3>
                            <div class="price_list">
                                <label><input type="radio" name="dientich" value="duoi-20" v-model="form.dientich">Dưới
                                    20m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="20-30" v-model="form.dientich">20 -
                                    30m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="30-50" v-model="form.dientich">30 -
                                    50m<sup>2</sup></label>
                                <label><input type="radio" name="dientich" value="tren-50" v-model="form.dientich">Trên
                                    50m<sup>2</sup></label>
                            </div>
                        </div>
                        <!-- Loại phòng (Dữ liệu từ DB) -->
                        <div class="select_option" v-if="categories.length">
                            <h3>Loại phòng:</h3>
                            <div class="feature_list">
                                <label v-for="cat in categories" :key="cat.id">
                                    <input type="checkbox" :value="cat.id" v-model="form.categories"> <i
                                        :class="['bi', cat.icon]"></i> {{ cat.name }}
                                </label>
                            </div>
                        </div>
                        <!-- tiện ích (Dữ liệu từ DB) -->
                        <div class="select_option" v-if="amenities.length">
                            <h3>Tiện ích:</h3>
                            <div class="feature_list">
                                <label v-for="amenity in amenities" :key="amenity.id">
                                    <input type="checkbox" :value="amenity.id" v-model="form.amenities"> <i
                                        :class="['bi', amenity.icon]"></i> {{ amenity.name }}
                                </label>
                            </div>
                        </div>

                        <!-- Bản đồ khu vực đã chọn -->
                        <div v-if="selectedArea?.map_embed" class="map_section">
                            <h3><i class="bi bi-map"></i> Bản đồ: {{ selectedArea.name }}</h3>
                            <div class="map_wrap" v-html="selectedArea.map_embed"></div>
                        </div>

                        <div class="bao_btn" style="display: flex; gap: 8px; margin-top: 15px;">
                            <button class="btn_filter" @click="submitSearch" style="flex: 2;">Tìm kiếm</button>
                            <button type="button" @click="resetFilters" style="flex: 1; padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.2s;">
                                Đặt lại
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <!-- phần hiển thị phòng -->
            <section class="room">
                <div class="baoroom">
                    <!-- Tiêu đề tổng quan số lượng phòng -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; width: 100%; padding: 0 4px;">
                        <div style="font-size: 15px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #eff6ff; color: #2563eb; border-radius: 8px;">
                                <i class="bi bi-houses-fill"></i>
                            </span>
                            <span>Danh sách phòng trọ</span>
                            <span style="font-size: 13px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 10px; border-radius: 20px;">{{ listings.total || 0 }} phòng</span>
                        </div>
                        <div v-if="listings.last_page > 1" style="font-size: 12px; font-weight: 700; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 20px;">
                            Trang {{ listings.current_page }} / {{ listings.last_page }}
                        </div>
                    </div>

                    <div v-if="listings.data.length === 0"
                        style="text-align: center; padding: 50px 0; width: 100%; color: #64748b;">
                        <i class="bi bi-house-x text-4xl mb-3 block"></i>
                        <p>Không có phòng trọ nào phù hợp</p>
                    </div>

                    <div class="item_room" v-for="post in listings.data" :key="post.id">
                        <div class="image_room">
                            <img :src="post.image && post.image.length > 0 ? post.image[0] : '/anh/banner_tro.png'"
                                alt="" style="object-fit: cover;">
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

                                <!-- THÊM BADGE TRẠNG THÁI PHÒNG Ở ĐÂY -->
                                <div v-if="post.room?.status" style="margin-top: 4px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
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
                            <div style="display: flex; align-items:  center; justify-content: space-between;">
                                <div class="user_room">
                                    <img :src="getAvatarUrl(post.landlord?.avatar)" alt="" style="object-fit: cover;">
                                    <h4>{{ post.landlord?.name || 'Chủ trọ' }}</h4>
                                    <p>cập nhật {{ timeAgo(post.updated_at) }}</p>
                                </div>
                                <Link :href="'/chitiettro/' + post.slug_with_hash" class="btn" style="position: unset;">
                                    Xem chi tiết
                                </Link>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Phân trang -->
                <div class="phantrang" v-if="listings.links && listings.links.length > 3">
                    <div class="baophantrang">
                        <template v-for="(link, index) in listings.links" :key="index">
                            <div class="so_trang" :class="{ 'active': link.active, 'disabled': !link.url }">
                                <Link v-if="link.url" :href="link.url" v-html="formatPaginationLabel(link.label)" preserve-scroll></Link>
                                <span v-else v-html="formatPaginationLabel(link.label)"></span>
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
</style>