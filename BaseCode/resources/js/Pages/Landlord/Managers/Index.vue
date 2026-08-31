<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, onUnmounted } from "vue";
import QrcodeVue from "qrcode.vue";
import { showSuccess, showWarning, showConfirm, showError } from "@/Utils/swal";
import axios from "axios";

const props = defineProps({
    managers: Array,
    boardingHouses: Array,
});

const showInviteModal = ref(false);
const invitationHouse = ref(null);
const selectedPermissions = ref([]);
const qrUrl = ref("");
const isGenerating = ref(false);

const permissionsList = [
    {
        key: "manage_rooms",
        label: "Quản lý phòng trọ",
        desc: "Cho phép thêm, sửa, xóa và đổi trạng thái phòng.",
    },
    {
        key: "manage_contracts",
        label: "Quản lý hợp đồng",
        desc: "Cho phép tạo và kích hoạt hợp đồng thuê.",
    },
    {
        key: "manage_invoices",
        label: "Quản lý hóa đơn",
        desc: "Cho phép lập hóa đơn dịch vụ và xác nhận thanh toán.",
    },
    {
        key: "manage_reports",
        label: "Quản lý khiếu nại",
        desc: "Cho phép xem và phản hồi các khiếu nại từ khách thuê.",
    },
    {
        key: "manage_listings",
        label: "Quản lý tin đăng",
        desc: "Cho phép đăng tin mới, đóng/mở tin và chỉnh sửa bài viết quảng cáo phòng.",
    },
];

// Mở modal phân quyền cho cơ sở trọ cụ thể
const openInviteModal = (house) => {
    invitationHouse.value = house;
    selectedPermissions.value = [];
    qrUrl.value = "";
    showInviteModal.value = true;
};

// Gửi request lấy QR
const generateQR = async () => {
    if (selectedPermissions.value.length === 0) {
        showWarning("Chưa chọn quyền", "Vui lòng chọn ít nhất một quyền hạn.");
        return;
    }
    if (selectedPermissions.value.length >= permissionsList.length) {
        showWarning(
            "Không hợp lệ",
            "Bạn không được phép giao toàn quyền quản lý cho tài khoản phụ.",
        );
        return;
    }

    isGenerating.value = true;
    try {
        const response = await axios.post(
            route(
                "landlord.boarding-houses.generate-qr",
                invitationHouse.value.id,
            ),
            { permissions: selectedPermissions.value },
        );
        qrUrl.value = response.data.url;
        startTimer();
        showSuccess("Đã sinh mã QR", "Mã QR tự hủy sau 15 phút.");
    } catch (error) {
        showWarning(
            "Lỗi hệ thống",
            error.response?.data?.message || "Không thể tạo mã QR lúc này.",
        );
    } finally {
        isGenerating.value = false;
    }
};

// Xóa/Hủy quyền quản lý
const revokeManager = async (manager) => {
    const isConfirmed = await showConfirm(
        "Huỷ quyền đồng quản lý?",
        `Tài khoản ${manager.user.name} sẽ không còn quyền quản lý cơ sở ${manager.boarding_house.name}. `,
        "Huỷ quyền",
        "Huỷ bỏ"
    );
    if (isConfirmed) {
        router.delete(route("landlord.managers.destroy", manager.id), {
            preserveScroll: true,
            onSuccess: () => {
                showSuccess("Đã huỷ quyền!", "Huỷ quyền quản lý thành công.");
            },
            onError: (errs) => {
                showError("Lỗi", Object.values(errs).join("/n"));
            }
        });
    }
};

const getPermLabel = (key) => {
    const item = permissionsList.find((p) => p.key === key);
    return item ? item.label : key;
};

//hàm đếm ngược
const timeLeft = ref(900);
let timerInerval = null;
//hàm định dạng số giây thành MM:SS
const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes.toString().padStart(2, "0")}:${remainingSeconds.toString().padStart(2, "0")}`;
};
//bắt đầu đếm ngược
const startTimer = () => {
    if (timerInerval) clearInterval(timerInerval);
    timeLeft.value = 900;
    timerInerval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(timerInerval);
        }
    }, 1000);
};

//hàm đóng modal
const closeInviteModal = () => {
    showInviteModal.value = false;
    qrUrl.value = "";
    if (timerInerval) {
        clearInterval(timerInerval);
        timerInerval = null;
    }
};
//dọn dẹp timer khi người dùng rời khỏi trang
onUnmounted(() => {
    if (timerInerval) clearInterval(timerInerval);
});

const showEditModal = ref(false);
const editingManager = ref(null);
const editPermissions = ref([]);
const openEditModal = (manager) => {
    editingManager.value = manager;
    editPermissions.value = [...manager.permissions];
    showEditModal.value = true;
};

const updateManagerPermissions = () => {
    if (editPermissions.value.length === 0) {
        showWarning("Lỗi", "Vui lòng chọn ít nhất 1 quyền hạn.");
        return;
    }
    if (editPermissions.value.length >= permissionsList.length) {
        showWarning(
            "Lỗi",
            "Tài khoản phụ phải bị giới hạn ít nhất 1 chức năng.",
        );
        return;
    }
    router.put(
        route("landlord.managers.update", editingManager.value.id),
        {
            permissions: editPermissions.value,
        },
        {
            onSuccess: () => {
                showSuccess("Thành công", "Đã cập nhật quyền thành công.");
                showEditModal.value = false;
            },
        },
    );
};
</script>

<template>

    <Head title="Quản Lý Phân Quyền" />
    <LandlordLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                    Quản Lý Phân Quyền
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Giao quyền quản trị cho nhân viên hoặc người quản lý phụ
                    bằng mã QR.
                </p>
            </div>
        </div>

        <!-- DANH SÁCH CƠ SỞ TRỌ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div v-for="house in boardingHouses" :key="house.id"
                class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                        <i class="bi bi-buildings text-emerald-500"></i>
                        {{ house.name }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        <i class="bi bi-geo-alt"></i> {{ house.address_detail }}
                    </p>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <!-- Hiển thị tài khoản quản lý hiện tại (nếu có) -->
                        <div v-if="
                            managers.some(
                                (m) => m.boarding_house_id === house.id,
                            )
                        ">
                            <span class="text-xs font-bold text-slate-400 block uppercase mb-2">Đang Đồng Quản
                                Lý:</span>
                            <div v-for="m in managers.filter(
                                (m) => m.boarding_house_id === house.id,
                            )" :key="m.id" class="p-3 bg-slate-50 rounded-xl flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">
                                        {{ m.user.name }}
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        {{ m.user.email }}
                                    </p>
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        <span v-for="p in m.permissions" :key="p"
                                            class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold">
                                            {{ getPermLabel(p) }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="revokeManager(m)"
                                    class="text-red-500 hover:text-red-700 p-2 text-sm border-none bg-transparent cursor-pointer"
                                    title="Hủy quyền">
                                    <i class="bi bi-trash-fill text-lg"></i>
                                </button>
                                <button @click="openEditModal(m)" title="Chỉnh sửa quyền"
                                    class="text-blue-500 hover:text-blue-700 p-2 text-sm border-none bg-transparent cursor-pointer">
                                    <i class="bi bi-pencil-square text-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-slate-400 text-xs py-2">
                            Chưa có tài khoản phụ quản lý cơ sở này. (Tối đa 1
                            tài khoản phụ).
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex justify-end">
                    <button v-if="
                        !managers.some(
                            (m) => m.boarding_house_id === house.id,
                        )
                    " @click="openInviteModal(house)"
                        class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 border-none cursor-pointer shadow-lg shadow-emerald-500/10">
                        <i class="bi bi-qr-code"></i> Giao Quyền Quản Lý
                    </button>
                    <span v-else class="text-xs text-amber-500 font-bold bg-amber-50 px-3 py-1.5 rounded-lg">
                        Đã đạt giới hạn (1 tài khoản phụ)
                    </span>
                </div>
            </div>
        </div>

        <!-- MODAL PHÂN QUYỀN QUA MÃ QR -->
        <div v-if="showInviteModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showInviteModal = false">
            <div
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-150 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-emerald-500"></i>
                        Phân Quyền QR - {{ invitationHouse?.name }}
                    </h3>
                    <button @click="showInviteModal = false"
                        class="text-slate-400 hover:text-red-500 font-bold border-none bg-transparent cursor-pointer text-xl">
                        &times;
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    <div v-if="!qrUrl">
                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                            Chọn quyền muốn ủy thác. Bạn không thể tích chọn
                            toàn bộ quyền của cơ sở.
                        </p>

                        <div class="space-y-3">
                            <label v-for="perm in permissionsList" :key="perm.key"
                                class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                                <input type="checkbox" :value="perm.key" v-model="selectedPermissions"
                                    class="mt-1 rounded text-emerald-600 focus:ring-emerald-500" />
                                <div class="ml-2">
                                    <h4 class="text-sm font-bold text-slate-700">
                                        {{ perm.label }}
                                    </h4>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ perm.desc }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-6 text-center space-y-4">
                        <!-- Bộ đếm ngược thời gian thực -->
                        <div v-if="timeLeft > 0"
                            class="bg-emerald-50 text-emerald-700 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 mx-auto">
                            <i class="bi bi-clock-history"></i>
                            Mã QR hết hạn sau:
                            <span class="font-mono text-sm text-emerald-800">{{
                                formatTime(timeLeft)
                                }}</span>
                        </div>
                        <div v-else
                            class="bg-rose-50 text-rose-700 px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 mx-auto">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Mã QR đã hết hạn! Vui lòng đóng và tạo mã mới.
                        </div>

                        <!-- Chỉ hiện mã QR khi chưa hết thời gian -->
                        <div v-if="timeLeft > 0"
                            class="p-4 bg-white border border-slate-100 rounded-2xl shadow-inner mx-auto">
                            <qrcode-vue :value="qrUrl" :size="200" level="H" />
                        </div>

                        <p class="text-xs text-slate-500 px-4 leading-relaxed">
                            {{
                                timeLeft > 0
                                    ? "Hãy đưa mã này cho người nhận quyền quét để xác nhận liên kết."
                                    : "Liên kết bảo mật đã tự hủy để đảm bảo an toàn."
                            }}
                        </p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" @click="showInviteModal = false"
                        class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 bg-white hover:bg-slate-50 font-bold transition text-sm cursor-pointer">
                        Đóng
                    </button>
                    <button v-if="!qrUrl" type="button" @click="generateQR" :disabled="isGenerating"
                        class="px-5 py-2 rounded-xl text-white bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 font-bold transition text-sm flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-qr-code"></i>
                        {{ isGenerating ? "Đang tạo..." : "Tạo Mã QR" }}
                    </button>
                </div>
            </div>
        </div>
        <!-- MODAL CHỈNH SỬA QUYỀN HẠN TÀI KHOẢN PHỤ -->
        <div v-if="showEditModal"
            class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showEditModal = false">
            <div
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-150 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-pencil-square text-blue-500"></i>
                        Chỉnh Sửa Quyền - {{ editingManager?.user?.name }}
                    </h3>
                    <button @click="showEditModal = false"
                        class="text-slate-400 hover:text-red-500 font-bold border-none bg-transparent cursor-pointer text-xl">
                        &times;
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        Tích chọn các quyền muốn cập nhật cho <strong>{{ editingManager?.user?.name }}</strong>.
                        Tài khoản phụ phải bị giới hạn ít nhất 1 chức năng.
                    </p>

                    <div class="space-y-3">
                        <label v-for="perm in permissionsList" :key="perm.key"
                            class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" :value="perm.key" v-model="editPermissions"
                                class="mt-1 rounded text-blue-600 focus:ring-blue-500" />
                            <div class="ml-2">
                                <h4 class="text-sm font-bold text-slate-700">
                                    {{ perm.label }}
                                </h4>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ perm.desc }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" @click="showEditModal = false"
                        class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 bg-white hover:bg-slate-50 font-bold transition text-sm cursor-pointer">
                        Hủy
                    </button>
                    <button type="button" @click="updateManagerPermissions"
                        class="px-5 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold transition text-sm flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-check-lg"></i>
                        Lưu Cập Nhật
                    </button>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
