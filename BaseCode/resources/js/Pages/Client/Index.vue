<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import HomePopup from '@/Components/HomePopup.vue';

// Props nhận dữ liệu danh mục từ Server (DB → Repository → Service → Route → Inertia)
const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    canVerfyEmail: Boolean,
    laravelVersion: String,
    phpVersion: String,
    categories: { type: Array, default: () => [] },
    areas:      { type: Array, default: () => [] },
    amenities:  { type: Array, default: () => [] },
    settings:   { type: Object, default: () => ({}) }
})

const activeBanners = computed(() => {
    const list = props.settings?.banners || [];
    const active = list.filter(b => b.active);
    if (active.length > 0) {
        return active.sort((a, b) => a.order - b.order);
    }
    return [{ id: 1, title: 'Default', img: '/anh/banner.png' }];
});

const currentBannerIndex = ref(0);
let bannerInterval = null;

// Slider Phòng Trọ
const currentPtSlide = ref(0);
const totalPtSlides = 3;
let ptSlideInterval = null;

const nextPtSlide = () => {
    currentPtSlide.value = (currentPtSlide.value + 1) % totalPtSlides;
};

const prevPtSlide = () => {
    currentPtSlide.value =
        (currentPtSlide.value - 1 + totalPtSlides) % totalPtSlides;
};

const goToPtSlide = (index) => {
    currentPtSlide.value = index;
};

// Quản lý Searchable Dropdown cho Khu Vực
const showAreaDropdown = ref(false);
const areaSearchQuery = ref('');
const selectedArea = ref(null);
const areaDropdownRef = ref(null);

const filteredAreas = computed(() => {
    if (!areaSearchQuery.value.trim()) return props.areas || [];
    const q = areaSearchQuery.value.toLowerCase().trim();
    return (props.areas || []).filter(area => area.name.toLowerCase().includes(q));
});

const selectArea = (area) => {
    selectedArea.value = area;
    showAreaDropdown.value = false;
};

const clearAreaSelection = () => {
    selectedArea.value = null;
    showAreaDropdown.value = false;
};

// Quản lý Custom Dropdown cho Mức Giá
const showPriceDropdown = ref(false);
const selectedPrice = ref(null);
const priceDropdownRef = ref(null);

const priceOptions = [
    { id: '1', name: 'Dưới 1 triệu', icon: 'bi-cash' },
    { id: '2', name: '1 - 2 triệu', icon: 'bi-cash-coin' },
    { id: '3', name: '2 - 3 triệu', icon: 'bi-wallet2' },
    { id: '4', name: 'Trên 3 triệu', icon: 'bi-bank' },
];

const selectPrice = (price) => {
    selectedPrice.value = price;
    showPriceDropdown.value = false;
};

// Quản lý Custom Dropdown cho Loại Phòng
const showCategoryDropdown = ref(false);
const selectedCategory = ref(null);
const categoryDropdownRef = ref(null);

const selectCategory = (cat) => {
    selectedCategory.value = cat;
    showCategoryDropdown.value = false;
};

const handleGlobalClick = (event) => {
    if (areaDropdownRef.value && !areaDropdownRef.value.contains(event.target)) {
        showAreaDropdown.value = false;
    }
    if (priceDropdownRef.value && !priceDropdownRef.value.contains(event.target)) {
        showPriceDropdown.value = false;
    }
    if (categoryDropdownRef.value && !categoryDropdownRef.value.contains(event.target)) {
        showCategoryDropdown.value = false;
    }
};

// Tự động chuyển slide sau mỗi 5s
onMounted(() => {
    ptSlideInterval = setInterval(nextPtSlide, 5000);
    
    // Tự động chuyển banner sau mỗi 6s
    bannerInterval = setInterval(() => {
        if (activeBanners.value.length > 1) {
            currentBannerIndex.value = (currentBannerIndex.value + 1) % activeBanners.value.length;
        }
    }, 6000);

    window.addEventListener('click', handleGlobalClick);
});

onUnmounted(() => {
    if (ptSlideInterval) clearInterval(ptSlideInterval);
    if (bannerInterval) clearInterval(bannerInterval);
    window.removeEventListener('click', handleGlobalClick);
});

// Slider Đánh Giá
const currentReviewIndex = ref(0);
const maxReviewIndex = 3; // Tổng 6 card, hiển thị 3 card cùng lúc -> max index = 3

const scrollReview = (direction) => {
    currentReviewIndex.value += direction;
    if (currentReviewIndex.value < 0) currentReviewIndex.value = 0;
    if (currentReviewIndex.value > maxReviewIndex)
        currentReviewIndex.value = maxReviewIndex;
};
</script>
<template>
    <Head title="Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER SLIDESHOW -->
        <div class="banner">
            <div 
                v-for="(banner, index) in activeBanners" 
                :key="banner.id" 
                :class="['banner-slide', index === currentBannerIndex ? 'active' : '']"
            >
                <img :src="banner.img || '/anh/banner.png'" alt="banner">
            </div>
            <div class="banner-text">
                <h1>{{ props.settings?.hero_title || 'Tìm Phòng Và Nhà Trọ Phù Hợp' }}</h1>
                <p>
                    {{ props.settings?.hero_subtitle || 'Hệ thống tìm kiếm và quản lý phòng trọ thông minh số 1 tại Ninh Bình.' }}
                </p>
            </div>
            
            <!-- Banner Navigation dots if there are multiple active banners -->
            <div v-if="activeBanners.length > 1" class="banner-dots">
                <span 
                    v-for="(banner, index) in activeBanners" 
                    :key="'dot-' + banner.id" 
                    :class="['banner-dot', index === currentBannerIndex ? 'active' : '']"
                    @click="currentBannerIndex = index"
                ></span>
            </div>
        </div>
        <!-- phần tìm kiếm -->
        <div class="boloc">
            <div class="search">
                <div class="location relative" ref="areaDropdownRef">
                    <label for="">Khu Vực:</label>
                    <div 
                        class="custom-select-trigger cursor-pointer flex items-center justify-between px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 shadow-sm hover:border-blue-400 transition-all"
                        @click.stop="showAreaDropdown = !showAreaDropdown"
                    >
                        <span class="truncate flex items-center gap-2 font-medium">
                            <i class="bi bi-geo-alt text-blue-600"></i>
                            {{ selectedArea ? selectedArea.name : '--Chọn khu vực--' }}
                        </span>
                        <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': showAreaDropdown }"></i>
                    </div>

                    <!-- Searchable Dropdown Popup -->
                    <div 
                        v-if="showAreaDropdown" 
                        class="absolute left-0 top-full mt-2 w-full min-w-[220px] bg-white rounded-xl shadow-2xl border border-slate-100 z-50 overflow-hidden text-left"
                    >
                        <!-- Search Box -->
                        <div class="p-2 border-b border-slate-100 bg-slate-50/80 sticky top-0 z-10">
                            <div class="relative flex items-center">
                                <i class="bi bi-search absolute left-3 text-slate-400 text-xs"></i>
                                <input 
                                    v-model="areaSearchQuery"
                                    type="text"
                                    placeholder="Gõ tìm phường, xã..."
                                    class="w-full pl-8 pr-3 py-2 text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                    @click.stop
                                />
                            </div>
                        </div>

                        <!-- Area Options List -->
                        <ul class="max-h-56 overflow-y-auto py-1 custom-scrollbar">
                            <li 
                                class="px-3.5 py-2 text-xs text-slate-500 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                                :class="{ 'font-semibold text-blue-600 bg-blue-50/50': !selectedArea }"
                                @click="clearAreaSelection"
                            >
                                <span>-- Tất cả khu vực --</span>
                                <i v-if="!selectedArea" class="bi bi-check2 text-blue-600 font-bold"></i>
                            </li>
                            <li 
                                v-for="area in filteredAreas" 
                                :key="area.id"
                                class="px-3.5 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-600 cursor-pointer flex items-center justify-between transition-colors"
                                :class="{ 'bg-blue-50 font-semibold text-blue-600': selectedArea?.id === area.id }"
                                @click="selectArea(area)"
                            >
                                <span class="flex items-center gap-2">
                                    <i :class="['bi', area.icon || 'bi-geo-alt']"></i>
                                    {{ area.name }}
                                </span>
                                <i v-if="selectedArea?.id === area.id" class="bi bi-check2 text-blue-600 font-bold"></i>
                            </li>
                            <li v-if="filteredAreas.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
                                Không tìm thấy khu vực phù hợp
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Price Range Dropdown -->
                <div class="price_select relative" ref="priceDropdownRef">
                    <label for="">Mức Giá:</label>
                    <div 
                        class="custom-select-trigger cursor-pointer flex items-center justify-between px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 shadow-sm hover:border-blue-400 transition-all"
                        @click.stop="showPriceDropdown = !showPriceDropdown"
                    >
                        <span class="truncate flex items-center gap-2 font-medium">
                            <i class="bi bi-tag text-emerald-600"></i>
                            {{ selectedPrice ? selectedPrice.name : '--Chọn Giá--' }}
                        </span>
                        <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': showPriceDropdown }"></i>
                    </div>

                    <!-- Price Dropdown Menu -->
                    <div 
                        v-if="showPriceDropdown" 
                        class="absolute left-0 top-full mt-2 w-full min-w-[180px] bg-white rounded-xl shadow-2xl border border-slate-100 z-50 overflow-hidden text-left"
                    >
                        <ul class="py-1">
                            <li 
                                class="px-3.5 py-2 text-xs text-slate-500 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                                :class="{ 'font-semibold text-emerald-600 bg-emerald-50/50': !selectedPrice }"
                                @click="selectPrice(null)"
                            >
                                <span>-- Tất cả giá --</span>
                                <i v-if="!selectedPrice" class="bi bi-check2 text-emerald-600 font-bold"></i>
                            </li>
                            <li 
                                v-for="price in priceOptions" 
                                :key="price.id"
                                class="px-3.5 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-600 cursor-pointer flex items-center justify-between transition-colors"
                                :class="{ 'bg-emerald-50 font-semibold text-emerald-600': selectedPrice?.id === price.id }"
                                @click="selectPrice(price)"
                            >
                                <span class="flex items-center gap-2">
                                    <i :class="['bi', price.icon]"></i>
                                    {{ price.name }}
                                </span>
                                <i v-if="selectedPrice?.id === price.id" class="bi bi-check2 text-emerald-600 font-bold"></i>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Room Type Dropdown -->
                <div class="roomtype_select relative" ref="categoryDropdownRef">
                    <label for="">Loại Phòng:</label>
                    <div 
                        class="custom-select-trigger cursor-pointer flex items-center justify-between px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 shadow-sm hover:border-blue-400 transition-all"
                        @click.stop="showCategoryDropdown = !showCategoryDropdown"
                    >
                        <span class="truncate flex items-center gap-2 font-medium">
                            <i class="bi bi-houses text-purple-600"></i>
                            {{ selectedCategory ? selectedCategory.name : '--Chọn Loại Phòng--' }}
                        </span>
                        <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': showCategoryDropdown }"></i>
                    </div>

                    <!-- Room Type Dropdown Menu -->
                    <div 
                        v-if="showCategoryDropdown" 
                        class="absolute left-0 top-full mt-2 w-full min-w-[200px] bg-white rounded-xl shadow-2xl border border-slate-100 z-50 overflow-hidden text-left"
                    >
                        <ul class="py-1">
                            <li 
                                class="px-3.5 py-2 text-xs text-slate-500 hover:bg-slate-50 cursor-pointer flex items-center justify-between"
                                :class="{ 'font-semibold text-purple-600 bg-purple-50/50': !selectedCategory }"
                                @click="selectCategory(null)"
                            >
                                <span>-- Tất cả loại phòng --</span>
                                <i v-if="!selectedCategory" class="bi bi-check2 text-purple-600 font-bold"></i>
                            </li>
                            <li 
                                v-for="cat in categories" 
                                :key="cat.id"
                                class="px-3.5 py-2 text-xs text-slate-700 hover:bg-purple-50 hover:text-purple-600 cursor-pointer flex items-center justify-between transition-colors"
                                :class="{ 'bg-purple-50 font-semibold text-purple-600': selectedCategory?.id === cat.id }"
                                @click="selectCategory(cat)"
                            >
                                <span class="flex items-center gap-2">
                                    <i :class="['bi', cat.icon || 'bi-house']"></i>
                                    {{ cat.name }}
                                </span>
                                <i v-if="selectedCategory?.id === cat.id" class="bi bi-check2 text-purple-600 font-bold"></i>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="login-btn">
                    <i class="bi bi-search"></i> <span>Tìm Kiếm</span>
                </button>
            </div>
        </div>
        <section class="section_noidung">
            <div class="image_noidung">
                <img src="/anh/image_noidung.png" alt="" />
            </div>
            <div class="noidung">
                <div class="text_support">
                    <p>TÌM HIỂU</p>
                </div>
                <div class="title">
                    <h2>Ninh Bình <span>HomeStay.</span></h2>
                </div>
                <p>
                    Ninh Bình HomeStay là nền tảng chuyên biệt giúp bạn tìm kiếm
                    và quản lý nhà trọ một cách minh bạch, an toàn tại Ninh
                    Bình. chúng tôi kết nối người thuê với hệ thống chủ trọ uy
                    tín, đồng thời cung cấp giải pháp quản lý vận hành thông
                    minh cho các hộ kinh doanh lưu trú.
                    <br />
                    Với giao diện thân thiện, công cụ tìm kiếm thông minh và
                    thông tin được cập nhật liên tục, Ninh Bình HomeStay giúp
                    bạn dễ dàng tìm được nơi ở phù hợp trong thời gian ngắn
                    nhất.
                </p>
                <button class="btn">
                    Xem Thêm <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </section>
        <section class="phongtro">
            <div class="pt-header">
                <p class="text_support">KHÁM PHÁ</p>
                <h2>Phòng Trọ <span>Nổi Bật</span></h2>
            </div>

            <!-- Slider fullscreen background -->
            <div class="pt-slider" id="ptSlider">
                <!-- Slide 1 -->
                <div
                    class="pt-slide"
                    :class="{ active: currentPtSlide === 0 }"
                    style="background-image: url(&quot;anh/phong1.jpg&quot;)"
                >
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Nổi Bật</span>
                        <h3 class="pt-name">Phòng số 1</h3>
                        <p class="pt-addr">
                            <i class="bi bi-geo-alt-fill"></i> Duy Tiên, Ninh
                            Bình
                        </p>
                        <div class="pt-meta">
                            <span class="pt-price"
                                >1.500.000 <small>/Tháng</small></span
                            >
                            <span class="pt-area"
                                ><i class="bi bi-aspect-ratio"></i> 20m²</span
                            >
                        </div>
                        <a class="pt-btn" href="#"
                            >Xem Chi Tiết <i class="bi bi-arrow-right"></i
                        ></a>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div
                    class="pt-slide"
                    :class="{ active: currentPtSlide === 1 }"
                    style="background-image: url(&quot;anh/phong1.jpg&quot;)"
                >
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Hot</span>
                        <h3 class="pt-name">Phòng số 2</h3>
                        <p class="pt-addr">
                            <i class="bi bi-geo-alt-fill"></i> Hoa Lư, Ninh Bình
                        </p>
                        <div class="pt-meta">
                            <span class="pt-price"
                                >2.000.000 <small>/Tháng</small></span
                            >
                            <span class="pt-area"
                                ><i class="bi bi-aspect-ratio"></i> 25m²</span
                            >
                        </div>
                        <a class="pt-btn" href="#"
                            >Xem Chi Tiết <i class="bi bi-arrow-right"></i
                        ></a>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div
                    class="pt-slide"
                    :class="{ active: currentPtSlide === 2 }"
                    style="background-image: url(&quot;anh/phong1.jpg&quot;)"
                >
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Mới</span>
                        <h3 class="pt-name">Phòng số 3</h3>
                        <p class="pt-addr">
                            <i class="bi bi-geo-alt-fill"></i> Gia Viễn, Ninh
                            Bình
                        </p>
                        <div class="pt-meta">
                            <span class="pt-price"
                                >1.800.000 <small>/Tháng</small></span
                            >
                            <span class="pt-area"
                                ><i class="bi bi-aspect-ratio"></i> 22m²</span
                            >
                        </div>
                        <a class="pt-btn" href="#"
                            >Xem Chi Tiết <i class="bi bi-arrow-right"></i
                        ></a>
                    </div>
                </div>

                <!-- Nút điều hướng -->
                <button class="pt-nav pt-prev" @click="prevPtSlide">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="pt-nav pt-next" @click="nextPtSlide">
                    <i class="bi bi-chevron-right"></i>
                </button>

                <!-- Dots -->
                <div class="pt-dots">
                    <span
                        class="pt-dot"
                        :class="{ active: currentPtSlide === 0 }"
                        @click="goToPtSlide(0)"
                    ></span>
                    <span
                        class="pt-dot"
                        :class="{ active: currentPtSlide === 1 }"
                        @click="goToPtSlide(1)"
                    ></span>
                    <span
                        class="pt-dot"
                        :class="{ active: currentPtSlide === 2 }"
                        @click="goToPtSlide(2)"
                    ></span>
                </div>
            </div>
        </section>

        <!-- phần cam kết -->
        <section class="camket">
            <div class="baocamket">
                <div class="about_camket">
                    <div class="text_support">
                        <p>CAM KẾT</p>
                    </div>
                    <div class="title">
                        <h2>Ninh Bình Home Stay <span>Cam Kết.</span></h2>
                    </div>
                    <div class="timeline">
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconhome.png" alt="" />
                            </div>
                            <div class="content">
                                <h2>Thông Tin Minh Bạch</h2>
                                <p>
                                    Tất cả thông tin nhà trọ và việc làm đều
                                    được đăng tải rõ ràng, giúp người dùng dễ
                                    dàng tìm hiểu và lựa chọn.
                                </p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconsearch.png" alt="" />
                            </div>
                            <div class="content">
                                <h2>Tìm Kiếm Nhanh Chóng</h2>
                                <p>
                                    Hệ thống tìm kiếm thông minh giúp bạn nhanh
                                    chóng tìm được phòng trọ phù hợp với mọi nhu
                                    cầu.
                                </p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconhandshake.png" alt="" />
                            </div>
                            <div class="content">
                                <h2>Kết Nối Uy Tín</h2>
                                <p>
                                    Nền tảng kết nối minh bạch giữa người tìm
                                    trọ và chủ cho thuê. Hệ thống quản lý thông
                                    minh giúp tối ưu hóa việc tìm kiếm nơi ở và
                                    vận hành nhà trọ chuyên nghiệp.
                                </p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconstar.png" alt="" />
                            </div>
                            <div class="content">
                                <h2>Trải Nghiệm Tiện Lợi</h2>
                                <p>
                                    Giao diện thân thiện, dễ sử dụng giúp người
                                    dùng tìm kiếm và tiếp cận thông tin một cách
                                    nhanh chóng.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="img_camket">
                    <img src="anh/camket.png" alt="" />
                </div>
                <div class="house_3d">
                    <img src="anh/3d-house.png" alt="" />
                </div>
            </div>
        </section>
        <!-- phần thông số người dùng -->
        <section class="thongso">
            <div class="baothongso">
                <div class="infor_thongso">
                    <div class="item_thongso">
                        <h2>100+</h2>
                        <p>Người Đã Tìm Được Phòng Ưng Ý</p>
                    </div>
                    <div class="item_thongso">
                        <h2>100+</h2>
                        <p>Chủ trọ uy tín và chuyên nghiệp</p>
                    </div>
                    <div class="item_thongso">
                        <h2>4.9+</h2>
                        <p>Điếm Đánh Giá Từ Người Dùng</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- phần đánh giá -->
        <section class="review">
            <div class="title">
                <p class="sub">Đánh Giá Dịch Vụ</p>
                <h2>ĐÁNH GIÁ KHÁCH HÀNG</h2>
                <p class="desc">
                    Chúng tôi tự hào khi nhận được phản hồi tích cực từ khách
                    hàng
                </p>
            </div>

            <div class="review-container">
                <div
                    class="review-track"
                    :style="{
                        transform: `translateX(-${currentReviewIndex * 380}px)`,
                    }"
                >
                    <!-- card -->
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>
                            Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ
                            chuyên nghiệp.
                        </p>
                        <div class="user">
                            <img src="anh/banner.png" />
                            <div class="name_user">
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- nút -->
            <div class="btns">
                <button id="prev" @click="scrollReview(-1)">❮</button>
                <button id="next" @click="scrollReview(1)">❯</button>
            </div>
        </section>
        <HomePopup />
        <AppointmentCountdown />
    </MainLayout>
</template>

<style scoped>
.banner-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1.2s ease-in-out;
    z-index: 1;
}
.banner-slide.active {
    opacity: 1;
    z-index: 2;
}
.banner-text {
    z-index: 10;
}
.banner-dots {
    position: absolute;
    bottom: 95px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 20;
}
.banner-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
}
.banner-dot.active {
    background: #ffffff;
    transform: scale(1.3);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
}
</style>
