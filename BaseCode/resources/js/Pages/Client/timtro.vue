<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props nhận dữ liệu danh mục từ Server (DB → Repository → Service → Controller → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    amenities: { type: Array, default: () => [] },
    listings: { type: Object, default: () => ({ data: [], links: [] }) },
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
    form.value.area_id = area.id
    showDropdown.value = false
}

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
    console.log('Dữ liệu tìm kiếm:', form.value);
    // TODO: Gửi request tìm kiếm phòng trọ sau này
}
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
                    <h2>Bộ Lọc Tìm Kiếm</h2>
                    <!-- Khu vực (Dữ liệu từ DB) -->
                    <div class="select_box">
                        <div class="select" @click="showDropdown = !showDropdown">
                            <span class="selected">
                                <i class="bi bi-geo-alt"></i>
                                {{ selectedArea ? selectedArea.name : 'Chọn khu vực' }}
                            </span>
                            <span class="arrow"><i class="bi bi-caret-down"></i></span>
                        </div>

                        <div class="dropdown" :class="{ show: showDropdown }">
                            <div class="dropdown-header">
                                <span>Khu vực:</span>
                            </div>
                            <ul>
                                <li v-for="area in areas" :key="area.id"
                                    :class="{ active: selectedArea?.id === area.id }" @click="selectArea(area)">
                                    <i :class="['bi', area.icon]"></i> {{ area.name }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- khoảng giá -->
                    <div class="select_option">
                        <h3>Khoảng giá:</h3>
                        <div class="price_list">
                            <label><input type="radio" name="price" value="duoi-1-trieu" v-model="form.price"> Dưới 1
                                triệu</label>
                            <label><input type="radio" name="price" value="1-2-trieu" v-model="form.price"> 1 - 2
                                triệu</label>
                            <label><input type="radio" name="price" value="2-3-trieu" v-model="form.price"> 2 - 3
                                triệu</label>
                            <label><input type="radio" name="price" value="tren-3-trieu" v-model="form.price"> Trên 3
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

                    <div class="bao_btn">
                        <button class="btn_filter" @click="submitSearch">Tìm kiếm</button>
                        <button class="btn_filter_mic"><i class="bi bi-mic"></i>
                        </button>
                    </div>
                </div>
            </section>
            <!-- phần hiển thị phòng -->
            <section class="room">
                <div class="baoroom">
                    <div v-if="listings.data.length === 0"
                        style="text-align: center; padding: 50px 0; width: 100%; color: #64748b;">
                        <i class="bi bi-house-x text-4xl mb-3 block"></i>
                        <p>Không có phòng trọ nào phù hợp</p>
                    </div>

                    <div class="item_room" v-for="post in listings.data" :key="post.id">
                        <img :src="post.image && post.image.length > 0 ? post.image[0] : '/anh/banner_tro.png'" alt=""
                            style="object-fit: cover;">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2
                                    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                                    {{ post.title }}</h2>
                            </div>
                            <div class="infor">
                                <p>{{ new Intl.NumberFormat('vi-VN').format(post.room?.price || 0) }} đ/tháng</p>
                                <p>{{ post.room?.area }}m<sup>2</sup></p>
                                <p style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"
                                    :title="post.room?.boarding_house?.address_detail || 'Ninh Bình'">
                                    <span><i class="bi bi-geo-alt"></i></span>
                                    {{ post.room?.boarding_house?.address_detail || 'Ninh Bình' }}
                                </p>

                                <!-- THÊM BADGE TRẠNG THÁI PHÒNG Ở ĐÂY -->
                                <div v-if="post.room?.status" style="margin-top: 4px;">
                                    <span
                                        :class="['px-2 py-0.5 rounded border text-[11px] font-semibold', getStatusClass(post.room.status)]">
                                        {{ getStatusLabel(post.room.status) }}
                                    </span>
                                    <div class="about_room">
                                        <p
                                            v-html="post.description ? (post.description.length > 80 ? post.description.substring(0, 80) + '...' : post.description) : 'Không có mô tả'">
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="user_room">
                                <img :src="getAvatarUrl(post.landlord?.avatar)" alt=""
                                    style="object-fit: cover;">
                                <h4>{{ post.landlord?.name || 'Chủ trọ' }}</h4>
                                <p>cập nhật {{ timeAgo(post.updated_at) }}</p>
                            </div>
                        </div>
                        <Link :href="'/chitiettro?id=' + post.id" class="btn">
                            Xem chi tiết
                        </Link>
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

/* Map section */
.map_section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.map_section h3 {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.map_wrap {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.map_wrap :deep(iframe) {
    width: 100%;
    height: 250px;
    border: none;
    display: block;
}

/* Dropdown improvements */
.select {
    cursor: pointer;
}

.dropdown {
    z-index: 10;
}

.dropdown ul li {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dropdown ul li:hover {
    background: #f1f5f9;
}

.dropdown ul li.active {
    background: #7c3aed;
    color: #fff;
}

/* Styles cho phân trang */
.phantrang {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}

.baophantrang {
    display: flex;
    gap: 8px;
}

.so_trang {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.so_trang a,
.so_trang span {
    display: block;
    padding: 8px 12px;
    color: #475569;
    font-weight: 500;
    text-decoration: none;
}

.so_trang:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.so_trang.active {
    background: #38bdf8;
    border-color: #38bdf8;
}

.so_trang.active a,
.so_trang.active span {
    color: white;
}
</style>