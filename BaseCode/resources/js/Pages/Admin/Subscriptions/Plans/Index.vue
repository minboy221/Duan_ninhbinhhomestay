<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { showConfirm, showSuccess } from "@/Utils/swal";

const props = defineProps({
    plans: Array,
    features: Array,
    adminBank: Object,
});

// Hàm nhận biết các tính năng thuộc dạng giới hạn số lượng
const isQuantityFeature = (code) => {
    return [
        "max_boarding_houses",
        "max_rooms",
        "max_listings",
        "max_properties",
        "priority_listing",
    ].includes(code);
};

// Hàm bấm 1 click để đặt giá trị vô hạn (-1)
const setUnlimited = (featId) => {
    form.features[featId] = "-1";
};

// Hàm đặt số ngày vô hạn cho gói miễn phí
const setUnlimitedDuration = () => {
    form.duration_days = -1;
};

// Hàm gạt nút Bật/Tắt Toggle Switch
const toggleFeature = (featId) => {
    const current = form.features[featId];
    if (current === "true" || current === true) {
        form.features[featId] = "false";
    } else {
        form.features[featId] = "true";
    }
};

const showModal = ref(false);
const editingPlan = ref(null);

// Modal Cấu hình Ngân Hàng Admin
const isBankModalOpen = ref(false);
const bankForm = useForm({
    bank_name: props.adminBank?.bank_name || "",
    account_no: props.adminBank?.account_no || "",
    account_name: props.adminBank?.account_name || "",
});

const popularBanks = [
    { name: "MBBank", code: "MB" },
    { name: "Vietcombank", code: "VCB" },
    { name: "Techcombank", code: "TCB" },
    { name: "BIDV", code: "BIDV" },
    { name: "VietinBank", code: "CTG" },
    { name: "TPBank", code: "TPB" },
];

const selectBank = (bank) => {
    bankForm.bank_name = bank.name;
};

const onAccountNameInput = (e) => {
    // Tự động bỏ dấu và in hoa tên tài khoản
    let val = e.target.value;
    val = val
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/Đ/g, "D")
        .toUpperCase();
    bankForm.account_name = val;
};

const openBankModal = () => {
    bankForm.bank_name = props.adminBank?.bank_name || "";
    bankForm.account_no = props.adminBank?.account_no || "";
    bankForm.account_name = props.adminBank?.account_name || "";
    bankForm.clearErrors();
    isBankModalOpen.value = true;
};

const submitBankSettings = () => {
    bankForm.post(route("admin.subscription-plans.bank-settings"), {
        preserveScroll: true,
        onSuccess: () => {
            isBankModalOpen.value = false;
        },
    });
};

const form = useForm({
    name: "",
    price: 0,
    duration_days: 30,
    badge: "",
    description: "",
    sort_order: 0,
    is_active: true,
    features: {}, // key: feature_id, value: string
});

const formatMoney = (val) =>
    new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
    }).format(val);

const getFeatureValue = (plan, featId) => {
    const feat = plan.features?.find((f) => f.id === featId);
    if (!feat) return "—";
    const val = feat.pivot.feature_value;
    if (val === "-1" || val === -1) return "Vô hạn";
    if (val === "true" || val === true) return "Có";
    if (val === "false" || val === false) return "Không";
    if (val === "gold") return "✨ Viền Vàng VIP";
    if (val === "none") return "Không";
    return val;
};

const openModal = (plan = null) => {
    editingPlan.value = plan;
    form.clearErrors();

    if (plan) {
        form.name = plan.name;
        form.price = plan.price;
        form.duration_days = plan.duration_days;
        form.badge = plan.badge || "";
        form.description = plan.description || "";
        form.sort_order = plan.sort_order || 0;
        form.is_active = Boolean(plan.is_active);

        // Fill features
        form.features = {};
        props.features.forEach((f) => {
            const current = plan.features?.find((pf) => pf.id === f.id);
            form.features[f.id] = current ? current.pivot.feature_value : "";
        });
    } else {
        form.reset();
        form.features = {};
        props.features.forEach((f) => {
            if (isQuantityFeature(f.feature_code)) {
                form.features[f.id] = "";
            } else if (f.feature_code === "avatar_frame") {
                form.features[f.id] = "none";
            } else {
                form.features[f.id] = "false";
            }
        });
    }
    showModal.value = true;
};

const savePlan = () => {
    if (editingPlan.value) {
        form.put(
            route("admin.subscription-plans.update", editingPlan.value.id),
            {
                onSuccess: () => (showModal.value = false),
            },
        );
    } else {
        form.post(route("admin.subscription-plans.store"), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const deletePlan = async (plan) => {
    const confirmed = await showConfirm("Xác nhận xóa", `Bạn có chắc muốn xóa gói "${plan.name}" không?`);
    if (confirmed) {
        router.delete(route("admin.subscription-plans.destroy", plan.id), {
            onSuccess: () => showSuccess("Thành công", "Đã xóa gói dịch vụ thành công!"),
        });
    }
};

// Hàm định dạng số tiền dạng dấu chấm phân cách hàng nghìn (VD: 1.999.000 đ)
const formatPriceDisplay = (value) => {
    if (value === null || value === undefined || value === "") return "0 đ";
    return new Intl.NumberFormat("vi-VN").format(value) + " đ";
};

// Computed property để tự động hiển thị dấu chấm ngay trực tiếp trong ô input khi gõ
const displayPrice = computed({
    get() {
        if (
            form.price === null ||
            form.price === undefined ||
            form.price === ""
        )
            return "";
        return new Intl.NumberFormat("vi-VN").format(form.price);
    },
    set(val) {
        // Lọc bỏ toàn bộ dấu chấm, ký tự chữ, chỉ giữ lại chữ số
        const rawNum = String(val).replace(/\D/g, "");
        form.price = rawNum ? parseInt(rawNum, 10) : 0;
    },
});
</script>

<template>
    <AdminLayout title="Quản lý Gói dịch vụ & Tính năng">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        Quản lý Gói dịch vụ
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Cấu hình giá gói và đặc quyền dành cho Chủ trọ
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="openBankModal()"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-bank text-sm"></i>
                        <span>Cấu Hình Ngân Hàng</span>
                    </button>
                    <button @click="openModal()"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-md transition-all flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i> Thêm Gói mới
                    </button>
                </div>
            </div>

            <!-- Modal Popup Cấu hình Ngân Hàng -->
            <div v-if="isBankModalOpen"
                class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div
                    class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-bank text-emerald-500 text-lg"></i>
                            <span>Cấu Hình Tài Khoản Ngân Hàng Nhận Thanh Toán VietQR</span>
                        </h3>
                        <button @click="isBankModalOpen = false"
                            class="text-slate-400 hover:text-rose-500 transition-colors p-1 rounded-lg">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body (Gồm 2 cột) -->
                    <div class="p-6 overflow-y-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Cột Trái (7 phần): Form nhập -->
                        <div class="lg:col-span-7 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Chọn nhanh ngân hàng</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" v-for="bank in popularBanks" :key="bank.code"
                                        @click="selectBank(bank)" :class="[
                                            'px-3 py-2.5 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5 cursor-pointer',
                                            bankForm.bank_name === bank.name
                                                ? 'bg-emerald-50 border-emerald-500 text-emerald-700 ring-2 ring-emerald-500/20'
                                                : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300',
                                        ]">
                                        <span class="w-2 h-2 rounded-full" :class="bankForm.bank_name === bank.name
                                                ? 'bg-emerald-500'
                                                : 'bg-slate-300'
                                            "></span>
                                        {{ bank.name }}
                                    </button>
                                </div>
                            </div>

                            <form @submit.prevent="submitBankSettings" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Tên Ngân Hàng
                                        <span class="text-rose-500">*</span></label>
                                    <input v-model="bankForm.bank_name" type="text"
                                        class="w-full px-4 py-3 rounded-2xl border text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                        :class="bankForm.errors.bank_name
                                                ? 'border-rose-400 bg-rose-50/20'
                                                : 'border-slate-200'
                                            " placeholder="Nhập tên ngân hàng" />
                                    <span v-if="bankForm.errors.bank_name"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ bankForm.errors.bank_name }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Số Tài Khoản Ngân Hàng
                                        <span class="text-rose-500">*</span></label>
                                    <input v-model="bankForm.account_no" type="text"
                                        class="w-full px-4 py-3 rounded-2xl border text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                        :class="bankForm.errors.account_no
                                                ? 'border-rose-400 bg-rose-50/20'
                                                : 'border-slate-200'
                                            " placeholder="Ví dụ: 0912345678" />
                                    <span v-if="bankForm.errors.account_no"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ bankForm.errors.account_no }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Tên Chủ Tài Khoản (Viết hoa không dấu)
                                        <span class="text-rose-500">*</span></label>
                                    <input :value="bankForm.account_name" @input="onAccountNameInput" type="text"
                                        class="w-full px-4 py-3 rounded-2xl border text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 uppercase transition-all"
                                        :class="bankForm.errors.account_name
                                                ? 'border-rose-400 bg-rose-50/20'
                                                : 'border-slate-200'
                                            " placeholder="VÍ DỤ: NGUYEN VAN A" />
                                    <span v-if="bankForm.errors.account_name"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ bankForm.errors.account_name }}
                                    </span>
                                </div>

                                <div class="pt-2 flex justify-end gap-3">
                                    <button type="button" @click="isBankModalOpen = false"
                                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                                        Đóng
                                    </button>
                                    <button type="submit" :disabled="bankForm.processing"
                                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm flex items-center gap-2 cursor-pointer">
                                        <i v-if="bankForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                                        <span>Lưu Thông Tin Ngân Hàng</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Cột Phải (5 phần): Xem trước VietQR Mẫu -->
                        <div class="lg:col-span-5 bg-slate-50 rounded-2xl p-5 border border-slate-100 flex flex-col items-center text-center justify-center">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Xem Trước Mã QR Quét Thử</h4>
                            
                            <div v-if="bankForm.bank_name && bankForm.account_no" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/60 inline-block">
                                <img :src="`https://img.vietqr.io/image/${bankForm.bank_name}-${bankForm.account_no}-compact2.png?amount=199000&addInfo=DEMO%20THANH%20TOAN&accountName=${encodeURIComponent(bankForm.account_name || '')}`"
                                    class="w-48 h-auto object-contain mx-auto rounded-lg" alt="VietQR Preview" />
                            </div>
                            <div v-else class="text-center py-10 text-slate-400 text-xs">
                                <i class="bi bi-qr-code-scan text-4xl mb-2 block text-slate-300"></i>
                                Nhập Tên Ngân Hàng & Số Tài Khoản để xem mã QR tạo tự động.
                            </div>

                            <p class="text-[11px] text-slate-400 mt-4 leading-relaxed">
                                Mã QR này sẽ được tự động hiển thị khi Chủ Trọ chọn mua gói dịch vụ và quét mã qua app Banking.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách gói dạng Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                <div v-for="plan in plans" :key="plan.id"
                    class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 p-6 flex flex-col justify-between relative overflow-hidden h-full">
                    
                    <div class="flex-1 flex flex-col">
                        <!-- Badge row -->
                        <div class="min-h-[28px] flex items-center justify-between mb-2">
                            <span v-if="plan.badge"
                                class="bg-indigo-50 text-indigo-600 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                                {{ plan.badge }}
                            </span>
                            <span v-else class="h-4"></span>
                        </div>

                        <!-- Title block -->
                        <div class="min-h-[56px] flex items-center">
                            <h3 class="text-lg font-extrabold text-slate-800 leading-snug">
                                {{ plan.name }}
                            </h3>
                        </div>

                        <!-- Description block -->
                        <div class="min-h-[48px] flex items-center mt-1">
                            <p class="text-slate-500 text-xs leading-relaxed">
                                {{ plan.description || "Chưa có mô tả" }}
                            </p>
                        </div>

                        <!-- Price block -->
                        <div class="my-4 min-h-[76px] flex flex-col justify-center bg-slate-50/70 p-3.5 rounded-2xl border border-slate-100/80">
                            <span class="text-2xl font-black text-indigo-600">
                                {{
                                    plan.price == 0
                                        ? "Miễn phí"
                                        : formatMoney(plan.price)
                                }}
                            </span>
                            <span v-if="
                                plan.duration_days == -1 ||
                                plan.duration_days == 3650
                            " class="text-emerald-600 font-bold text-xs mt-0.5">
                                / Vĩnh viễn (Miễn phí)</span>
                            <span v-else-if="plan.price > 0" class="text-slate-400 text-xs font-medium mt-0.5">
                                / {{ plan.duration_days }} ngày</span>
                            <span v-else class="text-slate-400 text-xs font-medium mt-0.5">
                                / {{ plan.duration_days }} ngày dùng thử</span>
                        </div>

                        <!-- Danh sách tính năng của gói -->
                        <div class="border-t border-slate-100 pt-4 space-y-2.5 mt-auto">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Tính năng đi kèm:
                            </p>
                            <div v-for="feature in features" :key="feature.id"
                                class="flex justify-between items-center text-xs py-0.5">
                                <span class="text-slate-600 font-medium">{{
                                    feature.name
                                }}</span>
                                <span class="font-bold text-slate-800">
                                    {{ getFeatureValue(plan, feature.id) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded text-xs font-medium" :class="plan.is_active
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-rose-100 text-rose-700'
                            ">
                            {{ plan.is_active ? "Đang hoạt động" : "Tạm ẩn" }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button @click="openModal(plan)"
                                class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button @click="deletePlan(plan)"
                                class="p-2 text-slate-500 hover:text-rose-600 hover:bg-slate-50 rounded-lg transition-colors">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Modal Thêm / Sửa Gói -->
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                        <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">
                                {{
                                    editingPlan
                                        ? "Chỉnh sửa Gói dịch vụ"
                                        : "Thêm Gói dịch vụ Mới"
                                }}
                            </h3>
                            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <form @submit.prevent="savePlan" class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tên gói dịch vụ
                                        *</label>
                                    <input v-model="form.name" type="text"
                                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-semibold text-slate-800"
                                        :class="form.errors.name
                                                ? 'border-rose-400 bg-rose-50/20'
                                                : ''
                                            " placeholder="VD: Gói Chuyên Nghiệp" />
                                    <span v-if="form.errors.name"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ form.errors.name }}
                                    </span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Giá tiền (VNĐ)
                                        *</label>
                                    <input v-model="displayPrice" type="text"
                                        class="w-full rounded-xl border-slate-200 text-sm font-bold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                        :class="form.errors.price
                                                ? 'border-rose-400 bg-rose-50/20'
                                                : ''
                                            " placeholder="VD: 1.999.000" />
                                    <span v-if="form.errors.price"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ form.errors.price }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Số ngày sử dụng
                                        *</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.duration_days" type="number"
                                            class="w-full rounded-xl border-slate-200 text-sm font-bold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
                                            :class="form.errors.duration_days
                                                    ? 'border-rose-400 bg-rose-50/20'
                                                    : ''
                                                " placeholder="VD: 30 hoặc -1" />
                                        <!-- Nút bấm 1-click chọn Vô hạn / Vĩnh viễn -->
                                        <button type="button" @click="setUnlimitedDuration"
                                            class="px-3 py-2.5 text-xs font-bold rounded-xl whitespace-nowrap transition-all cursor-pointer"
                                            :class="form.duration_days == -1
                                                    ? 'bg-emerald-600 text-white shadow-sm'
                                                    : 'bg-slate-200 text-slate-700 hover:bg-slate-300'
                                                ">
                                            ∞ Vô Hạn
                                        </button>
                                    </div>
                                    <span v-if="form.errors.duration_days"
                                        class="text-xs text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                        {{ form.errors.duration_days }}
                                    </span>
                                    <span class="text-[11px] text-emerald-600 font-semibold mt-1 block">
                                        💡 Nhập
                                        <strong class="font-bold">-1</strong>
                                        hoặc bấm <strong>∞ Vô Hạn</strong>
                                        dành cho Gói Cơ Bản sử dụng vĩnh viễn.
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Huy hiệu /
                                        Badge</label>
                                    <input v-model="form.badge" type="text"
                                        class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        placeholder="VD: Phổ biến" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Thứ tự sắp xếp</label>
                                    <input v-model="form.sort_order" type="number"
                                        class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Mô tả gói</label>
                                <textarea v-model="form.description" rows="2"
                                    class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    placeholder="Nhập mô tả ngắn..."></textarea>
                            </div>

                            <!-- Cấu hình Features -->
                            <div class="border-t border-slate-100 pt-4">
                                <h4 class="font-semibold text-slate-800 text-sm mb-3">
                                    Cấu hình giá trị cho các tính năng:
                                </h4>
                                <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200/60">
                                    <div v-for="feat in features" :key="feat.id"
                                        class="grid grid-cols-1 sm:grid-cols-12 items-center gap-2 pb-2 border-b border-slate-200/40 last:border-0 last:pb-0">
                                        <!-- Tên Tính Năng -->
                                        <span class="sm:col-span-6 text-xs font-bold text-slate-700">
                                            {{ feat.name }}
                                            <span class="text-[10px] text-slate-400 font-normal">({{ feat.feature_code }})</span>:
                                        </span>

                                        <!-- CỘT BÊN PHẢI: Ô NHẬP THEO TỪNG LOẠI TÍNH NĂNG -->
                                        <div class="sm:col-span-6">
                                            <!-- 1. DẠNG GIỚI HẠN SỐ LƯỢNG (Số phòng, cơ sở, tin đăng) -->
                                            <div v-if="isQuantityFeature(feat.feature_code)" class="flex items-center gap-2">
                                                <input v-model="form.features[feat.id]" type="text"
                                                    class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="VD: 5 hoặc -1" />
                                                <button type="button" @click="setUnlimited(feat.id)"
                                                    class="px-2.5 py-2 text-[11px] font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer"
                                                    :class="form.features[feat.id] == '-1'
                                                            ? 'bg-indigo-600 text-white shadow-sm'
                                                            : 'bg-slate-200 text-slate-700 hover:bg-slate-300'
                                                        ">
                                                    ∞ Vô hạn
                                                </button>
                                            </div>

                                            <!-- 2. DẠNG KHUNG AVATAR SPECIAL EFFECT -->
                                            <select v-else-if="feat.feature_code === 'avatar_frame'" v-model="form.features[feat.id]"
                                                class="w-full rounded-xl border-slate-200 text-xs font-bold text-slate-800 focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="gold">
                                                    ✨ Khung Vàng VIP Lấp Lánh
                                                </option>
                                                <option value="none">
                                                    ⚪ Khung Thường
                                                </option>
                                            </select>

                                            <!-- 3. DẠNG BẬT / TẮT QUYỀN (Nút Gạt Toggle Switch Xanh Ngọc Bật/Tắt) -->
                                            <div v-else class="flex items-center gap-3">
                                                <button type="button" @click="toggleFeature(feat.id)"
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                                    :class="form.features[feat.id] === 'true' || form.features[feat.id] === true
                                                            ? 'bg-emerald-500'
                                                            : 'bg-slate-300'
                                                        ">
                                                    <span
                                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                        :class="form.features[feat.id] === 'true' || form.features[feat.id] === true
                                                                ? 'translate-x-5'
                                                                : 'translate-x-0'
                                                            "></span>
                                                </button>
                                                <span class="text-xs font-bold transition-colors" :class="form.features[feat.id] === 'true' || form.features[feat.id] === true
                                                        ? 'text-emerald-600'
                                                        : 'text-slate-400'
                                                    ">
                                                    {{
                                                        form.features[feat.id] === "true" || form.features[feat.id] === true
                                                            ? "Bật"
                                                            : "Tắt"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input v-model="form.is_active" type="checkbox" id="is_active"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <label for="is_active" class="text-sm text-slate-700 font-medium">Bật trạng thái hoạt động</label>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-medium text-sm hover:bg-slate-200">
                                    Hủy
                                </button>
                                <button type="submit"
                                    class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-medium text-sm hover:bg-indigo-700 shadow-md">
                                    Lưu Gói
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
