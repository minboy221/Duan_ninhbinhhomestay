<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref } from 'vue'

const listings = ref([
    { id: 1, title: 'Phòng trọ sạch sẽ trung tâm Ninh Bình', room: 'Phòng 103', price: 3000000, area: 22, address: '15 Trần Hưng Đạo, P. Đông Thành, TP. Ninh Bình', status: 'active', views: 128, img: null, createdAt: '2026-04-01', aiPrice: 3200000 },
    { id: 2, title: 'Phòng tầng 2 thoáng mát, ban công riêng',  room: 'Phòng 204', price: 3500000, area: 28, address: '15 Trần Hưng Đạo, P. Đông Thành, TP. Ninh Bình', status: 'pending', views: 45, img: null, createdAt: '2026-05-10', aiPrice: 3400000 },
])

const statusMap = {
    active:   { label: 'Đang Hiển Thị', cls: 'st-active' },
    pending:  { label: 'Chờ Duyệt',     cls: 'st-pending' },
    rejected: { label: 'Bị Từ Chối',    cls: 'st-rejected' },
    hidden:   { label: 'Đã Ẩn',         cls: 'st-hidden' },
}

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Tin Đăng</h1></template>

        <div class="ls-wrap">
            <!-- Topbar -->
            <div class="ls-topbar">
                <div class="ls-tabs">
                    <button class="tab-btn tab-active">Tất cả ({{ listings.length }})</button>
                    <button class="tab-btn">Đang hiển thị</button>
                    <button class="tab-btn">Chờ duyệt</button>
                    <button class="tab-btn">Đã ẩn</button>
                </div>
                <a href="/landlord/listings/create" class="btn-create"><i class="bi bi-plus-circle-fill"></i> Đăng Tin Mới</a>
            </div>

            <!-- Listings -->
            <div class="ls-list">
                <div v-for="ls in listings" :key="ls.id" class="ls-card">
                    <!-- Image placeholder -->
                    <div class="ls-img">
                        <i class="bi bi-image"></i>
                    </div>
                    <div class="ls-body">
                        <div class="ls-head">
                            <h3 class="ls-title">{{ ls.title }}</h3>
                            <span :class="['status-pill', statusMap[ls.status].cls]">{{ statusMap[ls.status].label }}</span>
                        </div>
                        <div class="ls-meta">
                            <span><i class="bi bi-building"></i> {{ ls.room }}</span>
                            <span><i class="bi bi-rulers"></i> {{ ls.area }} m²</span>
                            <span><i class="bi bi-geo-alt"></i> {{ ls.address }}</span>
                        </div>
                        <div class="ls-pricing">
                            <div class="ls-price">{{ formatMoney(ls.price) }}<span>/tháng</span></div>
                            <div class="ai-suggest">
                                <i class="bi bi-stars"></i>
                                AI gợi ý: <strong>{{ formatMoney(ls.aiPrice) }}</strong>
                                <span :class="ls.aiPrice > ls.price ? 'ai-up' : 'ai-ok'">
                                    {{ ls.aiPrice > ls.price ? '↑ Có thể tăng giá' : '✓ Giá phù hợp' }}
                                </span>
                            </div>
                        </div>
                        <div class="ls-stats">
                            <span><i class="bi bi-eye"></i> {{ ls.views }} lượt xem</span>
                            <span><i class="bi bi-calendar3"></i> Đăng {{ new Date(ls.createdAt).toLocaleDateString('vi-VN') }}</span>
                        </div>
                    </div>
                    <div class="ls-actions">
                        <button class="la-btn la-edit"><i class="bi bi-pencil"></i> Chỉnh sửa</button>
                        <button class="la-btn la-hide"><i class="bi bi-eye-slash"></i> Ẩn tin</button>
                        <button class="la-btn la-del"><i class="bi bi-trash"></i> Xóa</button>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="listings.length === 0" class="ls-empty">
                <i class="bi bi-megaphone"></i>
                <p>Chưa có tin đăng nào. Hãy đăng tin ngay!</p>
                <a href="/landlord/listings/create" class="btn-create">Đăng Tin Ngay</a>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.ls-wrap { display: flex; flex-direction: column; gap: 20px; }

.ls-topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ls-tabs { display: flex; gap: 4px; background: #fff; border-radius: 10px; padding: 4px; border: 1px solid #d1fae5; }
.tab-btn { padding: 7px 14px; border: none; border-radius: 7px; background: transparent; font-size: 13px; font-weight: 500; color: #6b7280; cursor: pointer; }
.tab-active { background: #0f766e !important; color: #fff !important; }
.tab-btn:hover:not(.tab-active) { background: #f0fdf4; color: #0f766e; }

.btn-create { display: flex; align-items: center; gap: 7px; padding: 9px 18px; background: #0f766e; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-create:hover { background: #0d9488; }

.ls-list { display: flex; flex-direction: column; gap: 16px; }
.ls-card {
    background: #fff; border-radius: 16px;
    border: 1.5px solid #f0fdf4;
    display: flex; gap: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.ls-card:hover { box-shadow: 0 6px 20px rgba(15,118,110,0.1); }

.ls-img {
    width: 180px; flex-shrink: 0;
    background: #f0fdf4;
    display: flex; align-items: center; justify-content: center;
    color: #6ee7b7; font-size: 40px;
}

.ls-body { flex: 1; padding: 18px; display: flex; flex-direction: column; gap: 8px; }
.ls-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.ls-title { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.4; }

.ls-meta { display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px; color: #6b7280; }
.ls-meta span { display: flex; align-items: center; gap: 5px; }
.ls-meta i { color: #0f766e; }

.ls-pricing { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.ls-price { font-size: 20px; font-weight: 800; color: #0f766e; }
.ls-price span { font-size: 13px; color: #6b7280; font-weight: 400; }

.ai-suggest { display: flex; align-items: center; gap: 7px; background: #f0fdf4; border: 1px solid #d1fae5; border-radius: 8px; padding: 5px 12px; font-size: 12px; color: #374151; }
.ai-suggest i { color: #d97706; }
.ai-up { color: #2563eb; font-weight: 600; }
.ai-ok { color: #16a34a; font-weight: 600; }

.ls-stats { display: flex; gap: 16px; font-size: 12px; color: #9ca3af; }
.ls-stats span { display: flex; align-items: center; gap: 5px; }

.ls-actions { display: flex; flex-direction: column; gap: 6px; padding: 16px; justify-content: center; border-left: 1px solid #f0fdf4; min-width: 120px; }
.la-btn { padding: 7px 14px; border-radius: 8px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; width: 100%; }
.la-edit { background: #f0fdf4; color: #0f766e; }
.la-hide { background: #fef9c3; color: #854d0e; }
.la-del  { background: #fee2e2; color: #b91c1c; }

.status-pill { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.st-active   { background: #dcfce7; color: #15803d; }
.st-pending  { background: #fef9c3; color: #854d0e; }
.st-rejected { background: #fee2e2; color: #b91c1c; }
.st-hidden   { background: #f3f4f6; color: #6b7280; }

.ls-empty { text-align: center; padding: 60px 20px; color: #9ca3af; display: flex; flex-direction: column; align-items: center; gap: 12px; }
.ls-empty i { font-size: 48px; color: #d1fae5; }
.ls-empty p { font-size: 15px; }
</style>
