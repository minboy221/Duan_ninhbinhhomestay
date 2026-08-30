<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    }
})

function stars(n) { return '★'.repeat(n) + '☆'.repeat(5 - n) }
function starColor(n) { return n >= 4 ? '#f59e0b' : n === 3 ? '#94a3b8' : '#ef4444' }

function toggleVisible(r) {
    router.patch(route('admin.reviews.toggle-visibility', r.id), {}, {
        preserveScroll: true
    })
}

function deleteReview(r) {
    if (confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) {
        router.delete(route('admin.reviews.delete', r.id), {
            preserveScroll: true
        })
    }
}
</script>

<template>

    <Head title="Admin - Quản Lý Đánh Giá" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Đánh Giá</h1>
                <p class="page-sub">Kiểm duyệt đánh giá từ người dùng</p>
            </div>
        </template>

        <div class="stats-row">
            <div class="scard"><i class="bi bi-star-fill" style="color:#f59e0b"></i>
                <div>
                    <p class="snum">{{ reviews.length }}</p>
                    <p class="slbl">Tổng đánh giá</p>
                </div>
            </div>
            <div class="scard"><i class="bi bi-eye-fill" style="color:#3b82f6"></i>
                <div>
                    <p class="snum">{{reviews.filter(r => r.visible).length}}</p>
                    <p class="slbl">Đang hiển thị</p>
                </div>
            </div>
            <div class="scard"><i class="bi bi-eye-slash-fill" style="color:#94a3b8"></i>
                <div>
                    <p class="snum">{{reviews.filter(r => !r.visible).length}}</p>
                    <p class="slbl">Đã ẩn</p>
                </div>
            </div>
            <div class="scard"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>
                <div>
                    <p class="snum">{{reviews.filter(r => r.stars <= 2).length}}</p>
                            <p class="slbl">Tiêu cực</p>
                </div>
            </div>
        </div>

        <div v-if="reviews.length === 0" class="empty-state">
            <i class="bi bi-chat-square-quote"></i>
            <p>Chưa có đánh giá nào từ người dùng trong hệ thống.</p>
        </div>

        <div v-else class="reviews-list">
            <div v-for="r in reviews" :key="r.id" :class="['review-card', !r.visible ? 'review-hidden' : '']">
                <div class="rv-left">
                    <div class="rv-ava">{{ r.reviewer[0] }}</div>
                    <div>
                        <p class="rv-name">{{ r.reviewer }}</p>
                        <p class="rv-room"><i class="bi bi-house"></i> {{ r.room }}</p>
                        <p class="rv-date"><i class="bi bi-calendar3"></i> {{ r.date }}</p>
                    </div>
                </div>
                <div class="rv-content">
                    <div class="rv-stars" :style="`color:${starColor(r.stars)}`">
                        {{ stars(r.stars) }}
                        <span class="star-num">{{ r.stars }}/5</span>
                        <span v-if="r.stars <= 2" class="neg-badge"><i class="bi bi-flag-fill"></i> Tiêu cực</span>
                    </div>
                    <p class="rv-text">{{ r.content }}</p>
                </div>
                <div class="rv-actions">
                    <button @click="toggleVisible(r)" :class="['act-btn', r.visible ? 'act-hide' : 'act-show']">
                        <i :class="['bi', r.visible ? 'bi-eye-slash' : 'bi-eye']"></i>
                        {{ r.visible ? 'Ẩn' : 'Hiện' }}
                    </button>
                    <button @click="deleteReview(r)" class="act-btn act-del">
                        <i class="bi bi-trash3"></i> Xóa
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0
}

.page-sub {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px
}

.scard {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .05)
}

.scard i {
    font-size: 26px
}

.snum {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    line-height: 1
}

.slbl {
    font-size: 11px;
    color: #94a3b8;
    margin: 0
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 12px
}

.review-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    padding: 18px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .04)
}

.review-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, .08)
}

.review-hidden {
    opacity: .5;
    border-style: dashed
}

.rv-left {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
    width: 200px
}

.rv-ava {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0
}

.rv-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin: 0
}

.rv-room,
.rv-date {
    font-size: 11px;
    color: #94a3b8;
    margin: 2px 0 0
}

.rv-content {
    flex: 1
}

.rv-stars {
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px
}

.star-num {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a
}

.neg-badge {
    font-size: 11px;
    background: #fef2f2;
    color: #ef4444;
    padding: 2px 8px;
    border-radius: 99px;
    font-weight: 600
}

.rv-text {
    font-size: 13px;
    color: #334155;
    line-height: 1.5;
    margin: 0
}

.rv-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0
}

.act-btn {
    padding: 7px 12px;
    border-radius: 6px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px
}

.act-hide {
    background: #f8fafc;
    color: #64748b
}

.act-hide:hover {
    background: #e2e8f0
}

.act-show {
    background: #eff6ff;
    color: #3b82f6
}

.act-show:hover {
    background: #3b82f6;
    color: #fff
}

.act-del {
    background: #fef2f2;
    color: #ef4444
}

.act-del:hover {
    background: #ef4444;
    color: #fff
}

.empty-state {
    background: #fff;
    border-radius: 8px;
    padding: 48px 24px;
    text-align: center;
    color: #94a3b8;
    border: 1px solid #f1f5f9
}

.empty-state i {
    font-size: 42px;
    color: #cbd5e1;
    display: block;
    margin-bottom: 12px
}

.empty-state p {
    font-size: 14px;
    margin: 0
}
</style>
