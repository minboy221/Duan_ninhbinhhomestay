<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { showConfirm } from '@/Utils/swal';

const props = defineProps({
    user: { type: Object, required: true },
    favoriteRooms: { type: Array, default: () => [] }
});

// Remove from favorites
async function unfavorite(roomId) {
    const confirmed = await showConfirm(
        'Bỏ quan tâm phòng trọ',
        'Bạn có chắc chắn muốn bỏ lưu phòng trọ này khỏi danh sách quan tâm?',
        'Bỏ quan tâm',
        'Hủy'
    );
    if (confirmed) {
        router.post(route('rooms.favorite', roomId), {}, {
            preserveScroll: true
        });
    }
}

// Format price helper
const formatPrice = (price) => {
    const p = parseFloat(price);
    if (p >= 1000000) {
        return (p / 1000000).toFixed(1).replace('.0', '') + ' Triệu/Tháng';
    }
    return p.toLocaleString('vi-VN') + ' đ/Tháng';
};
</script>

<template>
    <Head title="Trọ Đang Quan Tâm | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio" style="margin-bottom: 20px;">
                    <h2>TRỌ ĐANG QUAN TÂM (YÊU THÍCH)</h2>
                    <p class="text-xs text-slate-400">Danh sách các phòng trọ bạn đã thả tim lưu lại để theo dõi</p>
                </div>

                <!-- Favorites Grid -->
                <div class="favorites-grid">
                    <div v-if="favoriteRooms.length === 0" class="no-favorites">
                        <i class="bi bi-heart-broken" style="font-size: 40px; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
                        <p>Danh sách yêu thích trống.</p>
                        <Link :href="route('timtro')" class="btn-discover">Khám phá phòng trọ ngay</Link>
                    </div>
                    
                    <div v-for="room in favoriteRooms" :key="room.id" class="fav-card animate-fadeIn">
                        <div class="card-img-wrapper">
                            <img :src="(room.images && room.images[0]) || '/anh/banner_tro.png'" alt="Room image">
                            <button @click="unfavorite(room.id)" class="unfav-btn" title="Hủy quan tâm">
                                <i class="bi bi-heart-fill text-red"></i>
                            </button>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">Phòng {{ room.room_number }} - {{ room.property?.name }}</h3>
                            <div class="card-meta">
                                <span class="price">{{ formatPrice(room.price) }}</span>
                                <span class="divider">·</span>
                                <span class="area">{{ parseFloat(room.area) }} m²</span>
                            </div>
                            <p class="card-address"><i class="bi bi-geo-alt-fill"></i> {{ room.address || room.property?.address }}</p>
                            
                            <div class="card-landlord">
                                <img :src="room.property?.landlord?.avatar ? '/storage/' + room.property.landlord.avatar : '/anh/banner.png'" alt="Landlord">
                                <span>Chủ trọ: {{ room.property?.landlord?.name }}</span>
                            </div>
                            
                            <div class="card-actions">
                                <Link :href="route('chitiettro', room.id)" class="btn-view-details">
                                    <i class="bi bi-eye-fill"></i> Xem chi tiết
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/user.css";
@import '../../css/responsive/responsivetranguser.css';

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.no-favorites {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
    color: #64748b;
}

.btn-discover {
    display: inline-block;
    margin-top: 16px;
    padding: 10px 20px;
    background-color: #166ea9;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    transition: background 0.2s;
}

.btn-discover:hover {
    background-color: #0f4f7a;
}

.fav-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
}

.fav-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card-img-wrapper {
    position: relative;
    height: 160px;
    overflow: hidden;
}

.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.unfav-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.1s;
}

.unfav-btn:hover {
    transform: scale(1.1);
}

.text-red {
    color: #ef4444 !important;
}

.card-content {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    display: flex;
    align-items: center;
    font-size: 13px;
    margin-bottom: 8px;
}

.price {
    color: #166ea9;
    font-weight: 700;
}

.divider {
    color: #cbd5e1;
    margin: 0 6px;
}

.area {
    color: #64748b;
    font-weight: 550;
}

.card-address {
    font-size: 12px;
    color: #64748b;
    margin: 0 0 12px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-landlord {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    margin-bottom: 16px;
    font-size: 12px;
    color: #475569;
    font-weight: 550;
}

.card-landlord img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.card-actions {
    margin-top: auto;
}

.btn-view-details {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 8px;
    background-color: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-view-details:hover {
    background-color: #166ea9;
    color: #fff;
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
