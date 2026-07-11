<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
    showInitially: {
        type: Boolean,
        default: true,
    },
});

const isVisible = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);

const getStorageKey = () => {
    return user.value
        ? `ninhbinh_welcome_seen_${user.value.id}`
        : "ninhbinh_welcome_seen_guest";
};

const closePopup = () => {
    isVisible.value = false;
    localStorage.setItem(getStorageKey(), "true");
};

//hàm kiểm tra và kích hoạt hiển thị popup
const checkAndShowPopup = () => {
    if (!user.value) return; //chỉ hiển thị cho người dùng đã đăng nhập
    const key = getStorageKey();
    const hasSeen = localStorage.getItem(key);
    //nếu chưa xem popup thì sẽ cấu hình hiển thị ban đầu
    if (!hasSeen && props.showInitially) {
        setTimeout(() => {
            isVisible.value = true;
            localStorage.setItem(key, "true");
        }, 1200);
    }
};

onMounted(() => {
    checkAndShowPopup();
});

//sử dụng watcher để theo dõi khi user đăng nhập thành công
watch(
    user,
    (newUser) => {
        if (newUser) {
            checkAndShowPopup();
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="popup-chuyen-quyen" v-if="!$page.props.auth.has_submitted_verification">
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="isVisible" class="popup-overlay" @click.self="closePopup">
                    <Transition name="zoom">
                        <div v-if="isVisible" class="popup-container">
                            <button class="close-btn" @click="closePopup" aria-label="Close">
                                <i class="bi bi-x-lg"></i>
                            </button>

                            <div class="popup-header">
                                <h2>
                                    Chào Mừng Đến Với
                                    <span>Ninh Bình HomeStay</span>
                                </h2>
                                <p>
                                    Vui lòng lựa chọn vai trò của bạn để chúng
                                    tôi có thể hỗ trợ tốt nhất.
                                </p>
                            </div>

                            <div class="role-selection">
                                <!-- Owner Role -->
                                <Link :href="route('landlord.verify.create')" class="role-card owner"
                                    @click="closePopup">
                                    <div class="role-icon">
                                        <span class="material-symbols-outlined">home_work</span>
                                    </div>
                                    <div class="role-info">
                                        <h3>Tôi là Chủ Trọ</h3>
                                        <p>
                                            Đăng tin, quản lý phòng và tìm kiếm
                                            người thuê dễ dàng.
                                        </p>
                                    </div>
                                    <div class="role-action">
                                        <span>Bắt đầu ngay</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </Link>

                                <!-- Renter Role -->
                                <Link :href="route('timtro')" class="role-card renter" @click="closePopup">
                                    <div class="role-icon">
                                        <span class="material-symbols-outlined">search_check</span>
                                    </div>
                                    <div class="role-info">
                                        <h3>Tôi Tìm Phòng</h3>
                                        <p>
                                            Khám phá hàng ngàn phòng trọ,
                                            homestay ưng ý nhất.
                                        </p>
                                    </div>
                                    <div class="role-action">
                                        <span>Khám phá ngay</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </Link>
                            </div>

                            <div class="popup-footer">
                                <p>
                                    <Link :href="route('home')" @click="closePopup">Bỏ qua</Link>
                                </p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 15px;
}

.popup-container {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 12px;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    position: relative;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        inset 0 0 20px rgba(255, 255, 255, 0.5);
    padding: 50px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

/* Custom scrollbar for webkit */
.popup-container::-webkit-scrollbar {
    width: 4px;
}

.popup-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .popup-container {
        padding: 40px 20px 30px;
        border-radius: 12px;
        max-width: 500px;
    }
}

.close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.05);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: #475569;
    z-index: 20;
}

.close-btn:hover {
    background: #ef4444;
    color: white;
    transform: rotate(90deg);
}

.popup-header {
    text-align: center;
    margin-bottom: 35px;
}

.popup-header h2 {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 10px;
    line-height: 1.3;
}

@media (max-width: 640px) {
    .popup-header h2 {
        font-size: 22px;
    }
}

.popup-header h2 span {
    background: linear-gradient(135deg, #102a6d 0%, #45abe6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-style: italic;
}

.popup-header p {
    color: #64748b;
    font-size: 15px;
}

.role-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 640px) {
    .role-selection {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

.role-card {
    display: flex;
    flex-direction: column;
    padding: 30px 25px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    position: relative;
    background: white;
}

@media (max-width: 640px) {
    .role-card {
        flex-direction: row;
        align-items: center;
        padding: 15px 20px;
        gap: 15px;
    }
}

.role-card.owner {
    background: linear-gradient(135deg,
            rgba(16, 42, 109, 0.05) 0%,
            rgba(69, 171, 230, 0.05) 100%);
    border-color: rgba(16, 42, 109, 0.1);
}

.role-card.renter {
    background: linear-gradient(135deg,
            rgba(248, 250, 252, 0.8) 0%,
            rgba(241, 245, 249, 0.8) 100%);
    border-color: rgba(0, 0, 0, 0.05);
}

.role-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
}

@media (max-width: 640px) {
    .role-card:hover {
        transform: scale(1.02);
    }
}

.role-card.owner:hover {
    border-color: #102a6d;
    background: white;
}

.role-card.renter:hover {
    border-color: #45abe6;
    background: white;
}

.role-icon {
    width: 55px;
    height: 55px;
    border-radius: 10px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.05);
    transition: transform 0.5s ease;
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .role-icon {
        width: 45px;
        height: 45px;
        margin-bottom: 0;
        border-radius: 8px;
    }
}

.role-card:hover .role-icon {
    transform: scale(1.1) rotate(5deg);
}

.role-icon span {
    font-size: 30px;
    background: linear-gradient(135deg, #102a6d 0%, #45abe6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

@media (max-width: 640px) {
    .role-icon span {
        font-size: 24px;
    }
}

.role-info {
    flex: 1;
}

.role-info h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

@media (max-width: 640px) {
    .role-info h3 {
        font-size: 16px;
        margin-bottom: 2px;
    }
}

.role-info p {
    color: #64748b;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 20px;
}

@media (max-width: 640px) {
    .role-info p {
        margin-bottom: 0;
        font-size: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
}

.role-action {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 13px;
    color: #102a6d;
    margin-top: auto;
}

@media (max-width: 640px) {
    .role-action {
        display: none;
    }
}

.role-action i {
    transition: transform 0.3s ease;
}

.role-card:hover .role-action i {
    transform: translateX(5px);
}

.popup-footer {
    text-align: center;
    font-size: 13px;
    color: #64748b;
}

.popup-footer a {
    color: #102a6d;
    font-weight: 700;
    text-decoration: none;
    margin-left: 5px;
}

.popup-footer a:hover {
    text-decoration: underline;
}

/* Animations */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.zoom-enter-active,
.zoom-leave-active {
    transition:
        transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1),
        opacity 0.6s ease;
}

.zoom-enter-from,
.zoom-leave-to {
    transform: scale(0.9) translateY(20px);
    opacity: 0;
}
</style>
