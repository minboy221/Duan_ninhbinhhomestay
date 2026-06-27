<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
    similarPosts: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    averageRating: { type: Number, default: 0 }
});

const currentImageIndex = ref(0);

const images = computed(() => {
    return props.post.image && props.post.image.length > 0 
        ? props.post.image 
        : ['/anh/banner_tro.png'];
});

const nextImage = () => {
    if (currentImageIndex.value < images.value.length - 1) {
        currentImageIndex.value++;
    } else {
        currentImageIndex.value = 0;
    }
};

const prevImage = () => {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
    } else {
        currentImageIndex.value = images.value.length - 1;
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN').format(price || 0) + ' đ';
};

const timeAgo = (date) => {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff} giây trước`;
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    return `${Math.floor(diff / 86400)} ngày trước`;
};
</script>

<template>

    <Head :title="(post?.title || 'Xem Chi Tiết Phòng') + ' | Ninh Bình HomeStay'" />
    <MainLayout>
        <div class="page-wrapper" style="padding-top: 100px; max-width: 1200px; margin: 0 auto;">
            <!-- điều hướng -->
            <div class="dieuhuong">
                <div class="baodieuhuong">
                    <Link href="/">Trang Chủ</Link> /
                    <Link href="/timtro">Tìm Phòng Trọ</Link> /
                    <a href="#">{{ post?.title || 'Xem Chi Tiết Phòng' }}</a>
                </div>
            </div>

            <!-- phần chi tiết phòng -->
            <div class="layout" style="margin-top: 20px;">
                <section class="room_detail">
                <div class="container">
                    <div class="gallery">
                        <!-- Ảnh lớn -->
                        <div class="main-image">
                            <button class="prev" @click="prevImage" v-if="images.length > 1">&#10094;</button>
                            <img id="currentImage" :src="images[currentImageIndex]" style="object-fit: cover;">
                            <button class="next" @click="nextImage" v-if="images.length > 1">&#10095;</button>
                        </div>

                        <!-- Thumbnail -->
                        <div class="thumbnails" v-if="images.length > 1">
                            <img v-for="(img, idx) in images" :key="idx" 
                                 :src="img" 
                                 class="thumb" 
                                 :class="{ active: currentImageIndex === idx }"
                                 @click="currentImageIndex = idx"
                                 style="object-fit: cover; cursor: pointer;">
                        </div>
                    </div>
                    <div class="detail_tro">
                        <div class="theloai">
                            <i class="bi bi-award"></i>
                            <span>ĐÃ ĐƯỢC KIỂM CHỨNG</span>
                        </div>
                        <div class="tieude">
                            <h2>{{ post.title }}</h2>
                        </div>
                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>Địa điểm: {{ post.room?.boarding_house?.address_detail || 'Đang cập nhật' }}</span>
                        </div>
                        <div class="infor_tro">
                            <div class="price_tro">
                                <p>{{ formatPrice(post.room?.price) }}/Tháng</p>
                            </div>
                            <div class="trangthai">
                                <p>Cập nhật: {{ timeAgo(post.updated_at) }}</p>
                            </div>
                        </div>

                        <!-- Thông tin cơ bản -->
                        <div style="display: flex; gap: 20px; margin-bottom: 20px; font-weight: 500; font-size: 15px;">
                            <span><i class="bi bi-aspect-ratio"></i> Diện tích: {{ post.room?.area }} m²</span>
                            <span><i class="bi bi-people"></i> Sức chứa: {{ post.room?.capacity }} người</span>
                        </div>

                        <div class="thongtin_tro">
                            <h4>Thông tin mô tả:</h4>
                            <p v-html="post.description || 'Không có mô tả'"></p>
                            
                            <div class="tienich" v-if="post.room?.services && post.room.services.length">
                                <div class="baotienich" v-for="service in post.room.services" :key="service.id">
                                    <i :class="['bi', service.icon || 'bi-check-circle-fill']"></i>
                                    <span>{{ service.name }}</span>
                                </div>
                            </div>
                            
                            <div class="bando" v-if="post.room?.boarding_house">
                                <h2>Vị trí & Bản Đồ</h2>
                                <div style="width: 100%; border-radius: 8px; overflow: hidden; margin-top: 10px; height: 350px;">
                                    <iframe 
                                        v-if="post.room.boarding_house.latitude && post.room.boarding_house.longitude"
                                        :src="`https://www.google.com/maps?q=${post.room.boarding_house.latitude},${post.room.boarding_house.longitude}&output=embed`" 
                                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                                    </iframe>
                                    <iframe 
                                        v-else-if="post.room.boarding_house.address_detail"
                                        :src="`https://www.google.com/maps?q=${encodeURIComponent(post.room.boarding_house.address_detail)}&output=embed`" 
                                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                                    </iframe>
                                    <div v-else style="padding: 20px; text-align: center; background: #f1f5f9; border-radius: 8px; color: #64748b;">
                                        Chưa có thông tin địa chỉ
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Phần Đánh giá và Bình luận -->
                        <div class="reviews_section" style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                                <h3 style="font-size: 20px; font-weight: 600; color: #1e293b; margin: 0;">Đánh giá nhà trọ</h3>
                                <div class="rating-badge" style="display: flex; align-items: center; gap: 5px; background: #fffbeb; color: #f59e0b; padding: 5px 12px; border-radius: 20px; font-weight: bold; border: 1px solid #fde68a;">
                                    <i class="bi bi-star-fill"></i>
                                    <span v-if="averageRating > 0">{{ averageRating.toFixed(1) }} / 5</span>
                                    <span v-else>Chưa có xếp hạng</span>
                                </div>
                            </div>

                            <div v-if="!reviews || reviews.length === 0" style="text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: 12px; color: #64748b;">
                                <i class="bi bi-chat-square-text" style="font-size: 32px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                <p style="margin: 0; font-size: 15px;">Khu trọ này chưa có bình luận nào.</p>
                                <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.8;">Hãy là người đầu tiên trải nghiệm và đánh giá nhé!</p>
                            </div>

                            <div v-else class="review-list" style="display: flex; flex-direction: column; gap: 15px;">
                                <div v-for="review in reviews" :key="review.id" class="review-item" style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b;">
                                                {{ review.tenant?.name ? review.tenant.name.charAt(0).toUpperCase() : 'U' }}
                                            </div>
                                            <div>
                                                <h4 style="margin: 0; font-size: 15px; color: #1e293b;">{{ review.tenant?.name || 'Khách hàng' }}</h4>
                                                <span style="font-size: 12px; color: #94a3b8;">{{ timeAgo(review.created_at) }}</span>
                                            </div>
                                        </div>
                                        <div style="color: #f59e0b; font-size: 14px;">
                                            <i v-for="n in 5" :key="n" :class="n <= review.rating ? 'bi bi-star-fill' : 'bi bi-star'" style="margin-left: 2px;"></i>
                                        </div>
                                    </div>
                                    <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.5;">
                                        {{ review.comment }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="info_chutro">
                <div class="baochutro">
                    <div class="avatar1">
                        <div class="avatar-img">
                            <img :src="post.landlord?.avatar || '/anh/banner.png'" alt="" style="object-fit: cover;">
                            <span class="status1 online"></span>
                        </div>
                        <div class="name_chutro">
                            <h3>{{ post.landlord?.name || 'Chủ trọ' }}</h3>
                            <div class="kiem_chung">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Đã Được Xác Thực</span>
                            </div>
                        </div>
                    </div>
                    <div class="content_chutro">
                        <div class="phone" v-if="post.landlord?.phone">
                            <a class="btn_content" :href="'tel:' + post.landlord.phone">
                                <i class="bi bi-telephone"></i>
                                <span>{{ post.landlord.phone }}</span>
                            </a>
                        </div>
                        <div class="nhantin_chutro">
                            <a class="btn_mess" href="#">
                                <i class="bi bi-chat-dots-fill"></i>
                                <span>Nhắn Tin</span>
                            </a>
                        </div>
                        <div class="warning">
                            <a class="btn_waring" href="#">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Báo Xấu</span>
                            </a>
                        </div>
                    </div>
                    <div class="luu_y">
                        <h5>Lưu ý an toàn</h5>
                        <div class="warning_content">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Không đặt cọc nếu chưa xem phòng</span>
                        </div>
                        <div class="success_content">
                            <i class="bi bi-check-circle"></i>
                            <span>Kiểm tra giấy tờ chính chủ</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- phần bài đăng liên quan -->
        <section class="tindang_tro">
            <h2>Tin đăng tương tự</h2>
            
            <div v-if="!similarPosts || similarPosts.length === 0" style="text-align: center; padding: 30px; color: #64748b; font-size: 16px;">
                <i class="bi bi-info-circle" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Không có phòng nào giống
            </div>

            <div class="bao_tindang" v-else>
                <Link :href="'/chitiettro?id=' + sp.id" class="item_tindang" v-for="sp in similarPosts" :key="sp.id" style="text-decoration: none; color: inherit;">
                    <div class="img">
                        <img :src="sp.image && sp.image.length > 0 ? sp.image[0] : '/anh/banner.png'" style="object-fit: cover;">
                        <span class="count"><i class="bi bi-camera"></i> {{ sp.image ? sp.image.length : 0 }}</span>
                    </div>
                    <div class="content">
                        <h3 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ sp.title }}</h3>
                        <p class="dientich">Diện tích: {{ sp.room?.area }} m²</p>
                        <p class="diachi" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="bi bi-geo-alt"></i> {{ sp.room?.boarding_house?.address_detail || 'Đang cập nhật' }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettro.css";
@import '../../css/responsive/responsivechitiettro.css';
@import '../../css/responsive/responsive.css';

/* Fix breadcrumb (điều hướng) */
.dieuhuong {
    position: static !important;
    transform: none !important;
    width: 100% !important;
    margin: 0 0 20px 0 !important;
    background: none !important;
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
}
.baodieuhuong {
    font-size: 15px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.baodieuhuong a {
    color: #0284c7;
    text-decoration: none;
    font-weight: 500;
}
.baodieuhuong a:hover {
    text-decoration: underline;
}
</style>