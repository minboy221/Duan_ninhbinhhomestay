<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { showSuccess, showError, showToast } from "@/Utils/swal";

const props = defineProps({
    activeSubscription: Object,
    daysRemaining: Number,
    plans: Array,
    history: Array,
    pendingSubscription: Object,
    adminBank: Object,
});

//hàm chuyển đổi -1 thành vô hạn của gói
const formatFeatureValue = (val) => {
    if (val === "-1" || val === -1) return "Vô hạn";
    if (val === "true" || val === true) return "Có";
    if (val === "false" || val === false) return "Không";
    if (val === "gold") return "Khung VIP Vàng";
    return val;
};

const showQRModal = ref(false);
const proofFile = ref(null);
const isUploading = ref(false);
let statusInterval = null;

const formatMoney = (val) =>
    new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
    }).format(val);

const buyPlan = (plan) => {
    router.post(
        route("landlord.subscriptions.purchase"),
        { plan_id: plan.id },
        {
            onSuccess: () => {
                showQRModal.value = true;
                startPolling();
            },
        },
    );
};

//nút huỷ đơn mua hàng
const cancelPendingSubscription = () => {
    if (!props.pendingSubscription)
        return;
    if (confirm("Bạn có chắc chắn muốn huỷ đơn mua gói này không!")) {
        router.post(
            route("landlord.subscriptions.cancel", props.pendingSubscription.id),
            {},
            {
                onSuccess: () => {
                    showQRModal.value = false;
                    showToast("Đã huỷ đơn thanh toán thành công!", "info");
                },
            }
        );
    }
};

const getVietQRUrl = (sub) => {
    const bank = props.adminBank.bank_code || props.adminBank.bank_name;
    const acc = props.adminBank.account_no;
    const name = encodeURIComponent(props.adminBank.account_name);
    const amount = sub.price_at_purchase;
    const memo = sub.payment_code;
    return `https://img.vietqr.io/image/${bank}-${acc}-compact2.png?amount=${amount}&addInfo=${memo}&accountName=${name}`;
};

const copyText = (txt) => {
    navigator.clipboard.writeText(txt);
    showToast(`Đã sao chép: ${txt}`, "info");
};

const onFileChange = (e) => {
    proofFile.value = e.target.files[0];
};

const uploadProof = () => {
    if (!proofFile.value) {
        showError("Lỗi", "Vui lòng chọn ảnh hóa đơn chuyển khoản trước khi bấm Tải bill!");
        return;
    }
    if (!props.pendingSubscription) return;

    const fileExt = proofFile.value.name.split('.').pop().toLowerCase();
    const allowedExts = ['jpeg', 'png', 'jpg', 'webp', 'heic', 'heif'];
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/heic', 'image/heif'];

    if (!allowedExts.includes(fileExt) && !allowedTypes.includes(proofFile.value.type)) {
        showError("Lỗi", "Tệp tải lên không phải là ảnh hợp lệ (Hỗ trợ JPG, PNG, WEBP, HEIC, HEIF)!");
        return;
    }
    if (proofFile.value.size > 15 * 1024 * 1024) {
        showError("Lỗi", "Dung lượng ảnh vượt quá 15MB. Vui lòng chọn ảnh dung lượng nhỏ hơn!");
        return;
    }

    isUploading.value = true;
    router.post(
        route(
            "landlord.subscriptions.upload-proof",
            props.pendingSubscription.id
        ),
        { proof_image: proofFile.value },
        {
            forceFormData: true,
            onSuccess: () => {
                isUploading.value = false;
                proofFile.value = null;
                showSuccess(
                    "Thành công",
                    "Đã tải ảnh hóa đơn thành công! Vui lòng chờ Admin kiểm tra và duyệt."
                );
            },
            onError: (errs) => {
                isUploading.value = false;
                if (errs.proof_image) {
                    showError("Lỗi", errs.proof_image);
                } else {
                    showError("Lỗi", "Không thể tải ảnh bill lên, vui lòng thử lại!");
                }
            }
        }
    );
};

// Polling tự động kiểm tra xem Webhook đã kích hoạt chưa
const startPolling = () => {
    if (!props.pendingSubscription) return;
    statusInterval = setInterval(async () => {
        try {
            const res = await axios.get(
                route(
                    "landlord.subscriptions.check-status",
                    props.pendingSubscription.id,
                ),
            );
            if (res.data.success && res.data.status === "active") {
                clearInterval(statusInterval);
                showQRModal.value = false;
                triggerToast(
                    "Thanh toán thành công! Gói dịch vụ đã được tự động kích hoạt.",
                    "success"
                );
                router.reload();
            }
        } catch (e) { }
    }, 4000);
};

onMounted(() => {
    if (
        props.pendingSubscription &&
        props.pendingSubscription.status === "pending"
    ) {
        showQRModal.value = true;
        startPolling();
    }
});

onUnmounted(() => {
    if (statusInterval) clearInterval(statusInterval);
});

const formatDate = (dateStr) => {
    if (!dateStr) return "---";
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};


</script>

<template>
    <LandlordLayout title="Gói dịch vụ Chủ trọ">
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Quản Lý Gói Dịch Vụ</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Nâng cấp gói dịch vụ để mở rộng quy mô kinh doanh
                        phòng trọ của bạn.</p>
                </div>
                <Link :href="route('landlord.subscriptions.history')"
                    class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 border border-slate-200 shadow-2xs">
                    <i class="bi bi-clock-history text-indigo-600 text-sm"></i> Xem Lịch Sử Mua Gói
                </Link>
            </div>

            <!-- Banner cảnh báo gói dịch vụ sắp hết hạn trong 3 ngày -->
            <div v-if="activeSubscription && activeSubscription.end_date && daysRemaining !== null && daysRemaining <= 3"
                class="p-4 sm:p-5 bg-amber-50/90 border border-amber-300/80 rounded-2xl flex items-center justify-between gap-4 text-amber-900 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg shrink-0 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm sm:text-base text-amber-950">Gói dịch vụ của bạn sắp hết hạn!
                        </h4>
                        <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                            Gói dịch vụ hiện tại còn <strong class="text-rose-600 font-black text-sm">{{ daysRemaining
                            }} ngày</strong> nữa là hết hạn (Hạn dùng: {{ activeSubscription.end_date ?
                                    formatDate(activeSubscription.end_date) : '' }}). Vui lòng gia hạn hoặc chọn gói dịch vụ bên
                            dưới để không bị gián đoạn hoạt động kinh doanh.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Banner Gói hiện tại -->
            <div
                class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-800 rounded-2xl sm:rounded-3xl p-5 sm:p-8 text-white shadow-xl relative overflow-hidden">
                <div
                    class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none">
                </div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="space-y-2">
                        <span
                            class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-[11px] sm:text-xs font-semibold uppercase tracking-wider">
                            Gói Dịch Vụ Của Bạn
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            {{ activeSubscription?.plan?.name || "Gói Cơ Bản (Miễn Phí)" }}
                        </h2>
                        <p class="text-indigo-100 text-xs sm:text-sm max-w-xl leading-relaxed">
                            <template v-if="activeSubscription">
                                Gói dịch vụ của bạn đang có hiệu lực. Bạn có thể nâng cấp gói cao hơn bất kỳ lúc nào.
                            </template>
                            <template v-else>
                                Gói cũ của bạn đã hết hạn. Bạn đang sử dụng <strong>Gói Cơ Bản (Miễn phí)</strong> của
                                hệ thống. Vui lòng nâng cấp gói VIP để mở khóa thêm tài nguyên!
                            </template>
                        </p>
                    </div>

                    <div v-if="activeSubscription"
                        class="w-full md:w-auto bg-white/10 backdrop-blur-md border border-white/20 p-4 sm:p-5 rounded-2xl text-center min-w-[200px]">
                        <span class="text-xs text-indigo-200 block font-medium">Thời gian sử dụng</span>

                        <template v-if="!activeSubscription.end_date || activeSubscription.plan?.duration_days == -1 || activeSubscription.plan?.duration_days >= 3650 || daysRemaining > 3000">
                            <span class="text-2xl sm:text-3xl font-extrabold text-white my-1 block">Vĩnh viễn</span>
                            <span class="text-xs text-emerald-300 font-semibold">Gói Miễn Phí Vĩnh Viễn</span>
                        </template>

                        <template v-else>
                            <span class="text-3xl sm:text-4xl font-extrabold text-white my-1 block">{{ daysRemaining }}</span>
                            <span class="text-xs text-indigo-200">
                                {{ daysRemaining === 0 ? "(Ngày cuối cùng - Hết hạn: " : "ngày (Hết hạn: " }}
                                {{ activeSubscription.end_date ? formatDate(activeSubscription.end_date) : 'Vĩnh viễn' }})
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Banner Đơn mua gói đang chờ thanh toán -->
            <div v-if="pendingSubscription"
                class="p-4 sm:p-5 bg-amber-50/90 border border-amber-300/80 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-amber-900 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg shrink-0 shadow-sm animate-pulse">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm sm:text-base text-amber-950">
                            Đơn đăng ký mua gói "{{ pendingSubscription.plan?.name }}" đang chờ thanh toán
                        </h4>
                        <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                            Mã giao dịch: <strong class="font-extrabold text-slate-900">{{
                                pendingSubscription.payment_code
                                }}</strong> &bull;
                            Số tiền: <strong class="font-extrabold text-indigo-700">{{
                                formatMoney(pendingSubscription.price_at_purchase) }}</strong>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button @click="showQRModal = true"
                        class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 w-1/2 sm:w-auto cursor-pointer">
                        <i class="bi bi-qr-code-scan"></i> Xem QR
                    </button>
                    <button @click="cancelPendingSubscription"
                        class="px-4 py-2.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5 w-1/2 sm:w-auto cursor-pointer">
                        <i class="bi bi-x-circle-fill"></i> Hủy Đơn
                    </button>
                </div>
            </div>

            <!-- Bảng giá các Gói dịch vụ -->
            <div>
                <div class="text-center mb-6 sm:mb-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">
                        Các Gói Dịch Vụ Dành Cho Chủ Trọ
                    </h2>
                    <p class="text-slate-500 text-xs sm:text-sm mt-1">
                        Lựa chọn giải pháp phù hợp với quy mô kinh doanh của bạn
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div v-for="plan in plans" :key="plan.id"
                        class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 flex flex-col justify-between hover:shadow-2xl transition-all duration-300 relative group"
                        :class="{
                            'border-2 border-indigo-500 shadow-indigo-100 shadow-xl':
                                plan.badge === 'Khuyên dùng' ||
                                plan.badge === 'Đặc quyền VIP',
                        }">
                        <div v-if="plan.badge" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span
                                class="px-3.5 py-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[11px] font-bold rounded-full shadow-md whitespace-nowrap">
                                {{ plan.badge }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-slate-800 text-center mt-2">
                                {{ plan.name }}
                            </h3>
                            <p class="text-xs text-slate-500 text-center mt-1 min-h-[32px] leading-relaxed">
                                {{ plan.description }}
                            </p>

                            <div class="text-center my-5 sm:my-6">
                                <span class="text-2xl sm:text-3xl font-black text-indigo-600">
                                    {{
                                        plan.price == 0
                                            ? "Miễn phí"
                                            : formatMoney(plan.price)
                                    }}
                                </span>
                                <span v-if="
                                    plan.duration_days == -1 ||
                                    plan.duration_days == 3650
                                " class="text-xs text-emerald-600 font-semibold block mt-1">
                                    Sử dụng Vĩnh viễn (Miễn phí)
                                </span>
                                <span v-else-if="plan.price > 0" class="text-xs text-slate-400 block font-medium mt-1">/
                                    {{ plan.duration_days }} ngày</span>
                                <span v-else class="text-xs text-emerald-600 font-semibold block mt-1">Miễn phí {{
                                    plan.duration_days }} ngày
                                    đầu</span>
                            </div>

                            <!-- Features List -->
                            <ul class="space-y-2.5 text-xs sm:text-sm border-t border-slate-100 pt-5">
                                <li v-for="feat in plan.features" :key="feat.id"
                                    class="flex items-center gap-2 text-slate-700">
                                    <i
                                        class="bi bi-check-circle-fill text-emerald-500 text-sm sm:text-base shrink-0"></i>
                                    <span>{{ feat.name }}:
                                        <strong class="text-slate-900 font-bold">{{
                                            formatFeatureValue(
                                                feat.pivot.feature_value,
                                            )
                                        }}</strong></span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-6 sm:mt-8">
                            <!-- TRƯỜNG HỢP 1: Đang có đơn Mua gói CHỜ THANH TOÁN (pendingSubscription) -->
                            <template v-if="pendingSubscription">
                                <div v-if="pendingSubscription.plan_id === plan.id" class="space-y-2">
                                    <button @click="showQRModal = true"
                                        class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer animate-pulse">
                                        <i class="bi bi-qr-code-scan text-base"></i> Xem QR Thanh Toán (Đang Chờ)
                                    </button>
                                    <button @click="cancelPendingSubscription"
                                        class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-2xl font-bold text-xs border border-rose-200 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="bi bi-x-circle-fill"></i> Hủy Đơn Đang Chờ Này
                                    </button>
                                </div>
                                <button v-else disabled
                                    class="w-full py-3 bg-slate-100 text-slate-400 rounded-2xl font-bold text-xs border border-slate-200 cursor-not-allowed flex items-center justify-center gap-2">
                                    <i class="bi bi-clock-history"></i> Đang Có Đơn Chờ Xử Lý
                                </button>
                            </template>

                            <!-- TRƯỜNG HỢP 2: KHÔNG CÓ đơn pending -> Xét theo Gói đang sử dụng (activeSubscription) -->
                            <template v-else>
                                <button v-if="activeSubscription?.plan_id === plan.id" disabled
                                    class="w-full py-3 bg-emerald-50 text-emerald-600 rounded-2xl font-bold text-xs sm:text-sm border border-emerald-200 cursor-default flex items-center justify-center gap-2">
                                    <i class="bi bi-patch-check-fill text-base"></i> Gói Đang Sử Dụng
                                </button>
                                <button
                                    v-else-if="activeSubscription && Number(plan.price) < Number(activeSubscription.plan?.price)"
                                    disabled
                                    class="w-full py-3 bg-slate-100 text-slate-400 rounded-2xl font-bold text-xs border border-slate-200 cursor-not-allowed flex items-center justify-center gap-2"
                                    title="Bạn cần chờ gói hiện tại hết hạn mới có thể chuyển sang gói giá thấp hơn">
                                    <i class="bi bi-lock-fill"></i> Không Thể Hạ Gói
                                </button>

                                <!-- Gói cao hơn gói đang dùng -> NÂNG CẤP NGAY -->
                                <button v-else @click="buyPlan(plan)"
                                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-xs sm:text-sm shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="bi bi-rocket-takeoff-fill"></i>
                                    {{ plan.price == 0 ? "Kích hoạt ngay" : "Nâng Cấp Ngay" }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <!-- Modal Quét Mã VietQR Chuyển Khoản Admin -->
                <div v-if="showQRModal && pendingSubscription"
                    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-md overflow-y-auto">
                    <div
                        class="bg-white rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl border border-slate-100 relative my-auto max-h-[92vh] overflow-y-auto">
                        <!-- Nút đóng -->
                        <button @click="showQRModal = false" type="button"
                            class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                            <i class="bi bi-x-lg text-base"></i>
                        </button>

                        <!-- Tiêu đề -->
                        <div class="text-center">
                            <span
                                class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-[10px] sm:text-xs font-bold rounded-full">
                                Thanh toán chuyển khoản VietQR
                            </span>

                            <h3 class="text-base sm:text-lg font-bold text-slate-800 mt-2">
                                Quét Mã QR Thanh Toán Gói
                            </h3>

                            <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
                                Hệ thống sẽ tự động kích hoạt gói sau khi nhận chuyển khoản
                            </p>
                        </div>

                        <!-- QR -->
                        <div class="my-3 p-3 bg-slate-50 rounded-xl border border-slate-200 text-center">
                            <img :src="getVietQRUrl(pendingSubscription)" alt="VietQR Code"
                                class="w-full max-w-[280px] h-auto mx-auto object-contain rounded-lg shadow-2xs border border-white" />

                            <div
                                class="mt-2 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-emerald-600 animate-pulse">
                                <i class="bi bi-arrow-repeat text-sm"></i>
                                Đang chờ xác nhận chuyển khoản...
                            </div>
                        </div>

                        <!-- Thông tin ngân hàng -->
                        <div class="space-y-2 text-xs bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100">
                            <div class="flex justify-between items-center gap-3">
                                <span class="text-slate-500 shrink-0">
                                    Ngân hàng:
                                </span>
                                <span class="font-bold text-slate-800 text-right">
                                    {{ adminBank.bank_name }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center gap-3">
                                <span class="text-slate-500 shrink-0">
                                    Số tài khoản:
                                </span>

                                <div class="flex items-center gap-1.5 font-bold text-indigo-700">
                                    <span>{{ adminBank.account_no }}</span>

                                    <button @click="copyText(adminBank.account_no)" type="button"
                                        class="text-xs text-slate-400 hover:text-indigo-600 p-0.5" title="Sao chép">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between items-center gap-3">
                                <span class="text-slate-500 shrink-0">
                                    Chủ tài khoản:
                                </span>

                                <span class="font-bold text-slate-800 uppercase text-right truncate">
                                    {{ adminBank.account_name }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center border-t border-indigo-100/80 pt-2 gap-3">
                                <span class="text-slate-500"> Số tiền: </span>

                                <span class="font-black text-indigo-600 text-sm">
                                    {{
                                        formatMoney(
                                            pendingSubscription.price_at_purchase,
                                        )
                                    }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center gap-3">
                                <span class="text-slate-500 shrink-0">
                                    Nội dung CK:
                                </span>

                                <div
                                    class="flex items-center gap-1.5 font-extrabold text-rose-600 bg-white px-2 py-0.5 rounded border border-rose-200">
                                    <span>{{ pendingSubscription.payment_code }}</span>

                                    <button @click="
                                        copyText(
                                            pendingSubscription.payment_code,
                                        )
                                        " type="button" class="text-xs text-slate-400 hover:text-rose-600 p-0.5"
                                        title="Sao chép">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Upload bill -->
                        <div class="mt-3.5 pt-3.5 border-t border-slate-100">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">
                                Tải ảnh bill chuyển khoản nếu chưa được duyệt tự động:
                            </label>

                            <form @submit.prevent="uploadProof"
                                class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                <input type="file" @change="onFileChange" accept="image/*,.heic,.heif"
                                    class="text-[11px] w-full min-w-0 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />

                                <button type="submit" :disabled="isUploading"
                                    class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-bold whitespace-nowrap hover:bg-slate-900 transition-colors shadow-2xs disabled:opacity-50 flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span v-if="isUploading"><i class="bi bi-arrow-repeat animate-spin"></i> Đang tải...</span>
                                    <span v-else><i class="bi bi-upload"></i> Tải bill</span>
                                </button>
                            </form>
                        </div>

                        <!-- Nút Hủy đơn mua gói trong Modal -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button type="button" @click="showQRModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors">
                                Đóng
                            </button>
                            <button type="button" @click="cancelPendingSubscription"
                                class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition-colors border border-rose-200 flex items-center gap-1.5 cursor-pointer">
                                <i class="bi bi-x-circle-fill"></i> Hủy Đơn Thanh Toán Này
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
