<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props nhận dữ liệu danh mục từ Server (DB → Repository → Service → Controller → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas:      { type: Array, default: () => [] },
    amenities:  { type: Array, default: () => [] },
})

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

function submitSearch() {
    console.log('Dữ liệu tìm kiếm:', form.value);
    // TODO: Gửi request tìm kiếm phòng trọ sau này
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
                                <li
                                    v-for="area in areas" :key="area.id"
                                    :class="{ active: selectedArea?.id === area.id }"
                                    @click="selectArea(area)"
                                >
                                    <i :class="['bi', area.icon]"></i> {{ area.name }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- khoảng giá -->
                    <div class="select_option">
                        <h3>Khoảng giá:</h3>
                        <div class="price_list">
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
                            <label><input type="radio" name="dientich" value="duoi-20" v-model="form.dientich">Dưới 20m<sup>2</sup></label>
                            <label><input type="radio" name="dientich" value="20-30" v-model="form.dientich">20 - 30m<sup>2</sup></label>
                            <label><input type="radio" name="dientich" value="30-50" v-model="form.dientich">30 - 50m<sup>2</sup></label>
                            <label><input type="radio" name="dientich" value="tren-50" v-model="form.dientich">Trên 50m<sup>2</sup></label>
                        </div>
                    </div>
                    <!-- Loại phòng (Dữ liệu từ DB) -->
                    <div class="select_option" v-if="categories.length">
                        <h3>Loại phòng:</h3>
                        <div class="feature_list">
                            <label v-for="cat in categories" :key="cat.id">
                                <input type="checkbox" :value="cat.id" v-model="form.categories"> <i :class="['bi', cat.icon]"></i> {{ cat.name }}
                            </label>
                        </div>
                    </div>
                    <!-- tiện ích (Dữ liệu từ DB) -->
                    <div class="select_option" v-if="amenities.length">
                        <h3>Tiện ích:</h3>
                        <div class="feature_list">
                            <label v-for="amenity in amenities" :key="amenity.id">
                                <input type="checkbox" :value="amenity.id" v-model="form.amenities"> <i :class="['bi', amenity.icon]"></i> {{ amenity.name }}
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
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
                <div class="phantrang">
                    <div class="baophantrang">
                        <div class="so_trang">
                            <a href="">
                                <p>1</p>
                            </a>
                        </div>
                        <div class="so_trang">
                            <a href="">
                                <p>2</p>
                            </a>
                        </div>
                        <div class="so_trang">
                            <a href="">
                                <p>3</p>
                            </a>
                        </div>
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
.map_section { margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0; }
.map_section h3 { font-size:14px;font-weight:600;color:#0f172a;margin:0 0 10px;display:flex;align-items:center;gap:6px; }
.map_wrap { border-radius:12px;overflow:hidden;border:1px solid #e2e8f0; }
.map_wrap :deep(iframe) { width:100%;height:250px;border:none;display:block; }

/* Dropdown improvements */
.select { cursor:pointer; }
.dropdown { z-index:10; }
.dropdown ul li { cursor:pointer;display:flex;align-items:center;gap:6px; }
.dropdown ul li:hover { background:#f1f5f9; }
.dropdown ul li.active { background:#7c3aed;color:#fff; }
</style>