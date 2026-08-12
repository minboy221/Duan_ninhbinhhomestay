<script setup>
import { computed } from "vue";

const props = defineProps({
    avatar: {
        type: String,
        default: "/anh/banner.png",
    },
    name: {
        type: String,
        default: "Chủ trọ",
    },
    rankTier: {
        type: String,
        default: "gold", // diamond, gold, silver, bronze
    },
    rankPosition: {
        type: Number,
        default: 1,
    },
    isOnline: {
        type: Boolean,
        default: false,
    },
    size: {
        type: Number,
        default: 100, // Kích thước pixel
    },
});

const avatarUrl = computed(() => {
    if (!props.avatar) return "/anh/banner.png";
    if (props.avatar.startsWith("http") || props.avatar.startsWith("/")) {
        return props.avatar;
    }
    return "/storage/" + props.avatar;
});

const tierConfig = computed(() => {
    switch (props.rankTier?.toLowerCase()) {
        case "diamond":
            return {
                label: "Kim Cương",
                badgeIcon: "bi bi-crown-fill",
                badgeColor: "#06b6d4",
                frameClass: "rank-frame-diamond",
                topTitle: "👑 TOP 1 THẦN TRỌ UY TÍN",
                badgeText: "TOP 1",
            };
        case "gold":
            return {
                label: "Hạng Vàng",
                badgeIcon: "bi bi-award-fill",
                badgeColor: "#eab308",
                frameClass: "rank-frame-gold",
                topTitle: "🥇 TOP 5% CHỦ TRỌ XUẤT SẮC",
                badgeText: "TOP 5%",
            };
        case "silver":
            return {
                label: "Hạng Bạc",
                badgeIcon: "bi bi-shield-check",
                badgeColor: "#94a3b8",
                frameClass: "rank-frame-silver",
                topTitle: "🥈 CHỦ TRỌ THÂN THIỆN",
                badgeText: "TOP 15%",
            };
        case "bronze":
        default:
            return {
                label: "Đã Xác Thực",
                badgeIcon: "bi bi-check-circle-fill",
                badgeColor: "#22c55e",
                frameClass: "rank-frame-bronze",
                topTitle: "🥉 CHỦ TRỌ ĐÃ XÁC THỰC",
                badgeText: "XÁC THỰC",
            };
    }
});
</script>

<template>
    <div class="rank-avatar-wrapper" :style="{ width: `${size}px`, height: `${size}px` }">
        <!-- Khung viền động theo cấp bậc -->
        <div class="avatar-ring" :class="tierConfig.frameClass">
            <div class="ring-glow"></div>
            <!-- Ảnh Avatar chính -->
            <img :src="avatarUrl" :alt="name" class="avatar-img-main" />
        </div>

        <!-- Vương miện / Huy hiệu trên đỉnh đối với Kim Cương / Vàng -->
        <div v-if="rankTier === 'diamond'" class="crown-badge diamond-crown" title="Top 1 Thần Trọ Uy Tín">
            👑
        </div>
        <div v-else-if="rankTier === 'gold'" class="crown-badge gold-crown" title="Top 5% Chủ Trọ Xuất Sắc">
            🥇
        </div>

        <!-- Trạng thái Online / Offline -->
        <span class="status-indicator" :class="isOnline ? 'online' : 'offline'" :title="isOnline ? 'Đang hoạt động' : 'Ngoại tuyến'"></span>

        <!-- Badge nhãn hạng ở góc dưới -->
        <div class="rank-mini-badge" :style="{ background: tierConfig.badgeColor }">
            <i :class="tierConfig.badgeIcon"></i>
            <span>{{ tierConfig.badgeText }}</span>
        </div>
    </div>
</template>

<style scoped>
.rank-avatar-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.avatar-ring {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.avatar-img-main {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    z-index: 2;
    background: #fff;
    border: 2px solid #fff;
}

/* ==================== FRAME DIAMOND (KIM CƯƠNG 👑) ==================== */
.rank-frame-diamond {
    background: linear-gradient(135deg, #06b6d4, #3b82f6, #8b5cf6, #ec4899, #06b6d4);
    background-size: 300% 300%;
    animation: diamondGlow 4s ease infinite;
    box-shadow: 0 0 15px rgba(6, 182, 212, 0.6), 0 0 25px rgba(139, 92, 246, 0.4);
}

@keyframes diamondGlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ==================== FRAME GOLD (VÀNG 🥇) ==================== */
.rank-frame-gold {
    background: linear-gradient(135deg, #f59e0b, #fbbf24, #d97706, #fef08a, #f59e0b);
    background-size: 250% 250%;
    animation: goldGlow 3.5s linear infinite;
    box-shadow: 0 0 14px rgba(245, 158, 11, 0.6), 0 0 20px rgba(251, 191, 36, 0.3);
}

@keyframes goldGlow {
    0% { background-position: 0% 0%; }
    50% { background-position: 100% 100%; }
    100% { background-position: 0% 0%; }
}

/* ==================== FRAME SILVER (BẠC 🥈) ==================== */
.rank-frame-silver {
    background: linear-gradient(135deg, #cbd5e1, #94a3b8, #64748b, #f1f5f9, #cbd5e1);
    background-size: 200% 200%;
    box-shadow: 0 0 10px rgba(148, 163, 184, 0.5);
}

/* ==================== FRAME BRONZE (ĐỒNG 🥉) ==================== */
.rank-frame-bronze {
    background: linear-gradient(135deg, #10b981, #059669, #34d399);
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
}

/* ==================== CROWN BADGE ==================== */
.crown-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 20px;
    z-index: 5;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    animation: floatCrown 2.5s ease-in-out infinite;
}

@keyframes floatCrown {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-4px); }
}

/* ==================== ONLINE STATUS ==================== */
.status-indicator {
    position: absolute;
    bottom: 4px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    z-index: 4;
}
.status-indicator.online {
    background: #22c55e;
    box-shadow: 0 0 6px rgba(34, 197, 94, 0.8);
}
.status-indicator.offline {
    background: #94a3b8;
}

/* ==================== MINI RANK BADGE ==================== */
.rank-mini-badge {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
    z-index: 4;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    border: 1.5px solid #ffffff;
    letter-spacing: 0.5px;
}
</style>
