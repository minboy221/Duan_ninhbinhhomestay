<script setup>
import { computed } from "vue";
import RankAvatar from "./RankAvatar.vue";

const props = defineProps({
    landlord: {
        type: Object,
        default: () => ({}),
    },
    reviewsCount: {
        type: Number,
        default: 128,
    },
    avgRating: {
        type: Number,
        default: 4.9,
    },
});


// Xác định hạng chủ trọ dựa trên tổng điểm và số đánh giá
const rankTier = computed(() => {
    if (props.landlord?.rank_tier) return props.landlord.rank_tier;
    if (props.avgRating >= 4.8 && props.reviewsCount >= 20) return "diamond";
    if (props.avgRating >= 4.5) return "gold";
    if (props.avgRating >= 4.0) return "silver";
    return "bronze";
});

const rankTitle = computed(() => {
    switch (rankTier.value) {
        case "diamond":
            return "👑 TOP 1 CHỦ TRỌ UY TÍN NINH BÌNH";
        case "gold":
            return "🥇 TOP 5% CHỦ TRỌ XUẤT SẮC";
        case "silver":
            return "🥈 CHỦ TRỌ THÂN THIỆN 5 SAO";
        default:
            return "🥉 CHỦ TRỌ ĐÃ XÁC THỰC";
    }
});

const satisfactionRate = computed(() => {
    return Math.min(99, Math.round((props.avgRating / 5) * 100));
});
</script>

<template>
    <div class="landlord-rank-card">
        <!-- Banner Header Bảng Xếp Hạng -->
        <div class="rank-card-header">
            <div class="header-title">
                <i class="bi bi-trophy-fill trophy-icon"></i>
                <span>BẢNG XẾP HẠNG UY TÍN</span>
            </div>
            <div class="rank-top-badge">
                {{ rankTitle }}
            </div>
        </div>

        <!-- Khối Thông Tin Avatar Khung Xếp Hạng -->
        <div class="rank-card-body">
            <div class="avatar-center-box">
                <RankAvatar
                    :avatar="landlord?.avatar"
                    :name="landlord?.name"
                    :rank-tier="rankTier"
                    :is-online="landlord?.is_online || false"
                    :size="96"
                />
            </div>

            <!-- Tên & Tích Xanh -->
            <div class="landlord-identity">
                <h3 class="landlord-name">
                    {{ landlord?.name || "Chủ Trọ Uy Tín" }}
                    <i class="bi bi-patch-check-fill verified-icon" title="Chủ Trọ Đã Xác Thực CMND/CCCD"></i>
                </h3>
                <p class="landlord-subtext">
                    <i class="bi bi-geo-alt-fill"></i> Ninh Bình · Tham gia 2 năm
                </p>
            </div>


            <!-- Chỉ Số Phản Hồi Tải Nhanh -->
            <div class="response-stats-grid">
                <div class="response-stat">
                    <i class="bi bi-lightning-charge-fill text-amber-500"></i>
                    <div>
                        <span class="stat-label">Tỷ lệ phản hồi</span>
                        <strong class="stat-value">99%</strong>
                    </div>
                </div>
                <div class="response-stat">
                    <i class="bi bi-clock-history text-emerald-500"></i>
                    <div>
                        <span class="stat-label">Thời gian phản hồi</span>
                        <strong class="stat-value">&lt; 15 phút</strong>
                    </div>
                </div>
            </div>

            <!-- Tag Từ Khóa Nổi Bật -->
            <div class="tags-row">
                <span class="rank-tag tag-blue"><i class="bi bi-shield-check"></i> #AnNinh247</span>
                <span class="rank-tag tag-green"><i class="bi bi-heart-fill"></i> #SiêuNhiệtTình</span>
                <span class="rank-tag tag-amber"><i class="bi bi-sparkles"></i> #CơSởMớiĐẹp</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.landlord-rank-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-top: 16px;
    font-family: inherit;
}

/* Header Bảng Xếp Hạng */
.rank-card-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 14px 16px;
    text-align: center;
    position: relative;
}

.header-title {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.8px;
    color: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.trophy-icon {
    color: #f59e0b;
    font-size: 16px;
}

.rank-top-badge {
    margin-top: 6px;
    display: inline-block;
    background: linear-gradient(90deg, #f59e0b, #d97706);
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
}

/* Body Card */
.rank-card-body {
    padding: 20px 16px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.avatar-center-box {
    display: flex;
    justify-content: center;
    margin-top: 4px;
    margin-bottom: 6px;
}

.landlord-identity {
    text-align: center;
}

.landlord-name {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.verified-icon {
    color: #2563eb;
    font-size: 17px;
}

.landlord-subtext {
    font-size: 12px;
    color: #64748b;
    margin: 4px 0 0 0;
}

/* Score Summary */
.score-summary-box {
    background: #f8fafc;
    border-radius: 14px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #f1f5f9;
}

.score-number {
    font-size: 32px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    background: linear-gradient(135deg, #1e293b, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.score-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stars-row {
    display: flex;
    gap: 3px;
    font-size: 14px;
}

.star-active {
    color: #f59e0b;
}

.star-inactive {
    color: #cbd5e1;
}

.score-sub {
    font-size: 12px;
    color: #475569;
}

.divider {
    height: 1px;
    background: #f1f5f9;
    margin: 0 -16px;
}

/* Progress Breakdown */
.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.breakdown-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}

.breakdown-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-info {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #475569;
}

.item-score {
    color: #0f172a;
    font-weight: 700;
}

.progress-bar-bg {
    height: 6px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.8s ease;
}

/* Response Stats */
.response-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 2px;
}

.response-stat {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
}

.response-stat i {
    font-size: 16px;
}

.stat-label {
    display: block;
    color: #64748b;
    font-size: 10px;
}

.stat-value {
    color: #0f172a;
    font-weight: 800;
    font-size: 11.5px;
}

/* Tags */
.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 2px;
}

.rank-tag {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 9px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.tag-blue {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.tag-green {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

.tag-amber {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
</style>
