<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import RoomFormModal from "./RoomFormModal.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    floors: { type: Array, default: () => [] },
    statusCounts: { type: Object, default: () => ({}) },
    services: { type: Array, default: () => [] },
});
const floors = computed(() => props.floors);

const statusConfig = {
    available: {
        label: "Còn Trống",
        icon: "bi-door-open",
        cls: "bg-emerald-50 text-emerald-600 border-emerald-250",
        dot: "bg-emerald-500",
    },
    rented: {
        label: "Đã Thuê",
        icon: "bi-person-check-fill",
        cls: "bg-blue-50 text-blue-600 border-blue-250",
        dot: "bg-blue-500",
    },
    maintenance: {
        label: "Bảo Trì",
        icon: "bi-wrench-adjustable",
        cls: "bg-amber-50 text-amber-600 border-amber-250",
        dot: "bg-amber-500",
    },
    deposited: {
        label: "Đã Đặt Cọc",
        icon: "bi-cash-stack",
        cls: "bg-purple-50 text-purple-600 border-purple-250",
        dot: "bg-purple-500",
    },
    expiring_soon: {
        label: "Sắp Hết HĐ",
        icon: "bi-clock-history",
        cls: "bg-orange-50 text-orange-600 border-orange-250",
        dot: "bg-orange-500",
    },
    pending_renewal: {
        label: "Chờ Gia Hạn",
        icon: "bi-hourglass-split",
        cls: "bg-cyan-50 text-cyan-600 border-cyan-250",
        dot: "bg-cyan-500",
    },
    suspended: {
        label: "Tạm Ngưng",
        icon: "bi-dash-circle-fill",
        cls: "bg-slate-50 text-slate-600 border-slate-250",
        dot: "bg-slate-500",
    },
    under_construction: {
        label: "Đang Xây",
        icon: "bi-cone-striped",
        cls: "bg-rose-50 text-rose-600 border-rose-250",
        dot: "bg-rose-500",
    },
};

const colorsConfig = {
    amber: {
        text: "text-amber-500",
        bg: "bg-amber-50",
        border: "border-amber-200",
    },
    blue: {
        text: "text-blue-500",
        bg: "bg-blue-50",
        border: "border-blue-200",
    },
    cyan: {
        text: "text-cyan-500",
        bg: "bg-cyan-50",
        border: "border-cyan-200",
    },
    rose: {
        text: "text-rose-500",
        bg: "bg-rose-50",
        border: "border-rose-200",
    },
    purple: {
        text: "text-purple-500",
        bg: "bg-purple-50",
        border: "border-purple-200",
    },
    emerald: {
        text: "text-emerald-500",
        bg: "bg-emerald-50",
        border: "border-emerald-200",
    },
};

const fmtMoney = (n) => new Intl.NumberFormat("vi-VN").format(n) + "đ";

const activeTab = ref("all"); // 'all' | 'active' | 'inactive'
const searchQuery = ref("");
const floorFilter = ref("");
const statusFilter = ref("");

const isRoomActive = (status) => {
    return [
        "available",
        "rented",
        "deposited",
        "expiring_soon",
        "pending_renewal",
    ].includes(status);
};

const allFilteredRooms = computed(() => {
    let result = [];
    floors.value.forEach((f) => {
        f.rooms.forEach((r) => {
            // Apply Tab filter
            if (activeTab.value === "active" && !isRoomActive(r.status)) return;
            if (activeTab.value === "inactive" && isRoomActive(r.status))
                return;

            // Apply Floor filter
            if (floorFilter.value && f.id !== parseInt(floorFilter.value))
                return;

            // Apply Status filter
            if (statusFilter.value && r.status !== statusFilter.value) return;

            // Apply Search Query
            if (searchQuery.value) {
                const q = searchQuery.value.toLowerCase();
                if (!r.name.toLowerCase().includes(q)) return;
            }

            result.push({
                ...r,
                floor_name: f.name,
                floor_id: f.id,
            });
        });
    });
    return result;
});

// Transitions rules
const statusTransitions = {
    available: ["deposited", "maintenance"],
    deposited: ["rented", "available"],
    rented: ["expiring_soon", "maintenance"],
    expiring_soon: ["pending_renewal"],
    pending_renewal: ["rented", "available"],
    maintenance: ["available"],
    suspended: ["available"],
    under_construction: ["available"],
};
const getAllowedStatuses = (current) => statusTransitions[current] || [];

// Floor Modals
const showFloorModal = ref(false);
const isEditFloor = ref(false);
const editingFloorId = ref(null);
const floorName = ref("");
const floorAddress = ref("");
const floorLatitude = ref(null);
const floorLongitude = ref(null);
const isLocatingFloor = ref(false);
const floorError = ref("");

const openAddFloor = () => {
    isEditFloor.value = false;
    editingFloorId.value = null;
    floorName.value = "";
    floorAddress.value = "";
    floorLatitude.value = null;
    floorLongitude.value = null;
    floorError.value = "";
    showFloorModal.value = true;
};

const openEditFloor = (fl) => {
    isEditFloor.value = true;
    editingFloorId.value = fl.id;
    floorName.value = fl.name;
    floorAddress.value = fl.address || "";
    floorLatitude.value = fl.latitude || null;
    floorLongitude.value = fl.longitude || null;
    floorError.value = "";
    showFloorModal.value = true;
};

const getCurrentFloorPosition = () => {
    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ chức năng định vị GPS");
        return;
    }
    isLocatingFloor.value = true;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            floorLatitude.value = lat;
            floorLongitude.value = lon;

            try {
                const response = await axios.get(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`,
                );
                if (response.data && response.data.display_name) {
                    floorAddress.value = response.data.display_name;
                }
            } catch (error) {
                console.error("Lỗi dịch toạ độ sang địa chỉ:", error);
                alert("Đã lấy được toạ độ nhưng không thể dịch thành địa chỉ");
            } finally {
                isLocatingFloor.value = false;
            }
        },
        (error) => {
            isLocatingFloor.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("Bạn đã từ chối cấp quyền truy cập GPS");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Không thể xác định được vị trí hiện tại");
                    break;
                case error.TIMEOUT:
                    alert("Quá thời gian yêu cầu lấy vị trí.");
                    break;
                default:
                    alert("Đã xảy ra lỗi không xác định khi lấy vị trí.");
                    break;
            }
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const floorMapUrl = computed(() => {
    if (floorLatitude.value && floorLongitude.value) {
        return `https://maps.google.com/maps?q=${floorLatitude.value},${floorLongitude.value}&z=15&output=embed`;
    }
    if (floorAddress.value) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(floorAddress.value)}&z=15&output=embed`;
    }
    return null;
});

const submitFloor = () => {
    floorError.value = "";
    if (!floorName.value.trim()) {
        floorError.value = "Vui lòng nhập tên tầng/khu";
        return;
    }
    let name = floorName.value.trim();
    name = name.charAt(0).toUpperCase() + name.slice(1);

    const isDuplicate = floors.value.some(
        (f) => f.name.toLowerCase() === name.toLowerCase() && (!isEditFloor.value || f.id !== editingFloorId.value),
    );

    if (isDuplicate) {
        floorError.value = `Tầng/Khu "${name}" đã tồn tại`;
        return;
    }

    const payload = {
        name,
        address: floorAddress.value,
        latitude: floorLatitude.value,
        longitude: floorLongitude.value,
    };

    if (isEditFloor.value) {
        router.put(
            route("landlord.floors.update", editingFloorId.value),
            payload,
            {
                onSuccess: () => {
                    showFloorModal.value = false;
                    showAlert(
                        "Thành công",
                        `Cập nhật tầng/khu "${name}" thành công!`,
                        "success",
                    );
                },
                onError: (errors) => {
                    if (errors.name) floorError.value = errors.name;
                },
            }
        );
    } else {
        router.post(
            route("landlord.floors.store"),
            payload,
            {
                onSuccess: () => {
                    showFloorModal.value = false;
                    showAlert(
                        "Thành công",
                        `Thêm tầng/khu "${name}" thành công!`,
                        "success",
                    );
                },
                onError: (errors) => {
                    if (errors.name) floorError.value = errors.name;
                },
            },
        );
    }
};
const delFloor = (f) => {
    const restrictedStatuses = [
        "rented",
        "deposited",
        "expiring_soon",
        "pending_renewal",
    ];
    const hasRestrictedRoom = f.rooms.some((r) =>
        restrictedStatuses.includes(r.status),
    );

    if (hasRestrictedRoom) {
        showAlert(
            "Không thể xóa",
            "Tầng này có phòng đang trong trạng thái Đã thuê, Đã đặt cọc, Sắp hết hạn HĐ hoặc Chờ gia hạn. Không thể xóa!",
            "warning",
        );
        return;
    }

    showConfirm(
        "Xác nhận xóa",
        `Xóa tầng "${f.name}" và toàn bộ phòng thuộc tầng?`,
        "danger",
        () => {
            router.delete(route("landlord.floors.delete", f.id));
        },
    );
};

// Room Actions
const showDetail = ref(false);
const selRoom = ref(null);
const selFloorId = ref(null);

const openDetail = (room) => {
    selRoom.value = { ...room };
    selFloorId.value = room.floor_id;
    showDetail.value = true;
};

const quickSt = (st) => {
    if (
        ["pending_renewal", "deposited"].includes(selRoom.value.status) &&
        st === "rented" &&
        selRoom.value.current_people === 0
    ) {
        showAlert(
            "Không hợp lệ",
            "Không thể chuyển sang trạng thái Đã Thuê khi phòng đang có 0 người!",
            "warning",
        );
        return;
    }

    if (st === "maintenance") {
        showPrompt(
            "Bảo trì phòng",
            "Vui lòng nhập lý do bảo trì phòng này:",
            "warning",
            (reason) => {
                router.patch(
                    route("landlord.rooms.status", selRoom.value.id),
                    { status: st, maintenance_reason: reason },
                    {
                        onSuccess: () => {
                            selRoom.value.status = st;
                            selRoom.value.maintenance_reason = reason;
                        },
                    },
                );
            },
        );
    } else {
        router.patch(
            route("landlord.rooms.status", selRoom.value.id),
            { status: st },
            {
                onSuccess: () => {
                    selRoom.value.status = st;
                    selRoom.value.maintenance_reason = null;
                },
            },
        );
    }
};

// Room Form Modals
const showForm = ref(false);
const isEditing = ref(false);
const hideFloorSelect = ref(false);
const formFloorId = ref(null);
const currentFloor = computed(() =>
    floors.value.find((fl) => fl.id === formFloorId.value),
);
const currentFloorRooms = computed(() =>
    currentFloor.value ? currentFloor.value.rooms : [],
);
const currentFloorName = computed(() =>
    currentFloor.value ? currentFloor.value.name : "",
);

const openAddRoom = () => {
    if (floors.value.length === 0) {
        alert("Vui lòng thêm tầng trước!");
        return;
    }
    isEditing.value = false;
    hideFloorSelect.value = false;
    formFloorId.value = floorFilter.value
        ? parseInt(floorFilter.value)
        : floors.value[0].id;
    selRoom.value = null;
    showForm.value = true;
};

const openAddRoomForFloor = (floorId) => {
    isEditing.value = false;
    hideFloorSelect.value = true;
    formFloorId.value = floorId;
    selRoom.value = null;
    showForm.value = true;
};

const openEditRoom = () => {
    if (selRoom.value && selRoom.value.has_approved_post) {
        showAlert(
            "Thao tác bị chặn",
            "Phòng này đang có bài đăng được duyệt và hiển thị ở client. Bạn không thể sửa thông tin phòng!",
            "warning",
        );
        return;
    }
    isEditing.value = true;
    formFloorId.value = selFloorId.value;
    showDetail.value = false;
    showForm.value = true;
};

const submitRoom = (fd) => {
    if (isEditing.value && selRoom.value) {
        router.post(route("landlord.rooms.update", selRoom.value.id), fd, {
            onSuccess: () => {
                showForm.value = false;
                showAlert(
                    "Thành công",
                    "Cập nhật thông tin phòng thành công!",
                    "success",
                );
            },
            forceFormData: true,
        });
    } else {
        router.post(route("landlord.rooms.store"), fd, {
            onSuccess: () => {
                showForm.value = false;
                showAlert(
                    "Thành công",
                    "Thêm phòng mới thành công!",
                    "success",
                );
            },
            forceFormData: true,
        });
    }
};

const remindTenant = () => {
    showAlert(
        "Gửi nhắc nhở",
        `Đã gửi thông báo nhắc nhở gia hạn hợp đồng thành công đến phòng ${selRoom.value.name}!`,
        "success",
    );
};

const lockRoom = (room) => {
    showConfirm(
        "Khóa phòng",
        `Khóa phòng "${room.name}"? Phòng sẽ chuyển sang trạng thái <strong class="text-rose-500">Tạm Ngưng</strong> và <strong class="text-rose-500">không thể cho thuê</strong>.`,
        "warning",
        () => {
            router.patch(
                route("landlord.rooms.status", room.id),
                { status: "suspended" },
                {
                    onSuccess: () => {
                        selRoom.value.status = "suspended";
                    },
                },
            );
        },
    );
};

const delRoom = (room) => {
    if (room && room.has_approved_post) {
        showAlert(
            "Thao tác bị chặn",
            "Phòng này đang có bài đăng được hiển thị ở trang clien.Bạn không thể xoá phòng",
            "warning",
        );
        return;
    }
    showConfirm(
        "Xác nhận xoá",
        `Xoá phòng "${room.name}" vĩnh viễn`,
        "danger",
        () => {
            router.delete(route("landlord.rooms.delete", room.id), {
                onSuccess: () => (showDetail.value = false),
            });
        },
    );
};

const addPerson = (room) => {
    const allowedStatuses = [
        "deposited",
        "rented",
        "expiring_soon",
        "pending_renewal",
    ];
    if (!allowedStatuses.includes(room.status)) {
        showAlert(
            "Không hợp lệ",
            "Trạng thái phòng không cho phép thêm người!",
            "warning",
        );
        return;
    }
    if (room.current_people >= room.capacity) {
        showAlert(
            "Không thể thêm",
            "Phòng đã đủ số lượng người tối đa.",
            "warning",
        );
        return;
    }
    router.patch(
        route("landlord.rooms.add_person", room.id),
        {},
        {
            onSuccess: () => {
                if (selRoom.value && selRoom.value.id === room.id) {
                    selRoom.value.current_people++;
                }
            },
        },
    );
};

const removePerson = (room) => {
    if (!["pending_renewal", "expiring_soon"].includes(room.status)) {
        showAlert(
            "Không hợp lệ",
            "Chỉ có thể bớt người ở trạng thái sắp hết hạn HĐ hoặc chờ gia hạn!",
            "warning",
        );
        return;
    }
    if (room.current_people <= 0) {
        showAlert("Không thể bớt", "Phòng hiện không có người.", "warning");
        return;
    }
    router.patch(
        route("landlord.rooms.remove_person", room.id),
        {},
        {
            onSuccess: () => {
                if (selRoom.value && selRoom.value.id === room.id) {
                    selRoom.value.current_people--;
                }
            },
        },
    );
};

// Custom Confirm Modal
const confirmModal = ref({
    show: false,
    title: "",
    message: "",
    type: "danger",
    onConfirm: null,
    isAlert: false,
    isPrompt: false,
    promptValue: "",
    promptError: "",
});
const showConfirm = (title, message, type, onConfirm) => {
    confirmModal.value = {
        show: true,
        title,
        message,
        type,
        onConfirm,
        isAlert: false,
        isPrompt: false,
        promptValue: "",
        promptError: "",
    };
};
const showAlert = (title, message, type) => {
    confirmModal.value = {
        show: true,
        title,
        message,
        type,
        onConfirm: null,
        isAlert: true,
        isPrompt: false,
        promptValue: "",
        promptError: "",
    };
};
const showPrompt = (title, message, type, onConfirm) => {
    confirmModal.value = {
        show: true,
        title,
        message,
        type,
        onConfirm,
        isAlert: false,
        isPrompt: true,
        promptValue: "",
        promptError: "",
    };
};

const handleConfirm = () => {
    if (confirmModal.value.isPrompt && !confirmModal.value.promptValue.trim()) {
        confirmModal.value.promptError = "Vui lòng nhập thông tin này";
        return;
    }
    if (confirmModal.value.onConfirm) {
        confirmModal.value.onConfirm(confirmModal.value.promptValue.trim());
    }
    confirmModal.value.show = false;
};
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Nhà & Phòng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">
                        Quản lý Phòng trọ
                    </h2>
                    <p class="text-xs text-slate-400">
                        Danh sách các tầng và các phòng cho thuê hiện tại
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="px-4 py-2 border border-emerald-200 hover:bg-emerald-50 text-emerald-600 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 bg-white"
                        @click="openAddFloor">
                        <i class="bi bi-plus-lg"></i> Thêm tầng
                    </button>
                    <button
                        class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5"
                        @click="openAddRoom">
                        <i class="bi bi-plus-lg"></i> Thêm phòng
                    </button>
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="border-b border-slate-100 flex gap-6 text-xs font-bold text-slate-400">
                <button @click="activeTab = 'all'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'all'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Tất cả ({{
                        floors.reduce((s, f) => s + f.rooms.length, 0)
                    }})
                </button>
                <button @click="activeTab = 'active'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'active'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Đang hoạt động ({{
                        floors.reduce(
                            (s, f) =>
                                s +
                                f.rooms.filter((r) => isRoomActive(r.status))
                                    .length,
                            0,
                        )
                    }})
                </button>
                <button @click="activeTab = 'inactive'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'inactive'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Không hoạt động ({{
                        floors.reduce(
                            (s, f) =>
                                s +
                                f.rooms.filter((r) => !isRoomActive(r.status))
                                    .length,
                            0,
                        )
                    }})
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 flex flex-wrap items-center gap-3 shadow-sm">
                <!-- Search -->
                <div
                    class="flex items-center bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-slate-400 gap-2 flex-1 min-w-[200px]">
                    <i class="bi bi-search text-xs"></i>
                    <input v-model="searchQuery"
                        class="bg-transparent border-none outline-none text-xs text-slate-700 w-full placeholder-slate-400"
                        placeholder="Tìm số phòng..." />
                </div>

                <!-- Tầng filter -->
                <select v-model="floorFilter"
                    class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-150 rounded-xl px-3 py-2 outline-none cursor-pointer min-w-[150px]">
                    <option value="">Tất cả tầng</option>
                    <option v-for="f in floors" :key="f.id" :value="f.id">
                        {{ f.name }}
                    </option>
                </select>

                <!-- Trạng thái filter -->
                <select v-model="statusFilter"
                    class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-150 rounded-xl px-3 py-2 outline-none cursor-pointer min-w-[150px]">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">
                        {{ cfg.label }}
                    </option>
                </select>
            </div>

            <!-- Rooms Table -->
            <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div v-if="allFilteredRooms.length === 0"
                    class="p-8 text-center text-slate-400 text-xs font-medium space-y-2">
                    <i class="bi bi-inbox text-3xl text-slate-300 block"></i>
                    <span>Không tìm thấy phòng nào phù hợp bộ lọc.</span>
                </div>
                <div v-else class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="room in allFilteredRooms" :key="room.id"
                        class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200 cursor-pointer group"
                        @click="openDetail(room)">
                        <div class="space-y-4">
                            <!-- Header: Tên phòng và Tầng -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-lg border border-emerald-100">
                                        P
                                    </div>
                                    <div class="space-y-0.5">
                                        <h4 class="text-sm font-bold text-slate-800">
                                            {{ room.name }}
                                        </h4>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{
                                            room.floor_name }}</span>
                                    </div>
                                </div>
                                <button @click.stop="openDetail(room)"
                                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 flex items-center justify-center transition-colors">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </div>

                            <!-- Trạng thái phòng -->
                            <div class="flex items-center gap-2">
                                <span :class="[
                                    'px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit',
                                    statusConfig[room.status]?.cls ||
                                    'bg-slate-50 text-slate-600 border-slate-200',
                                ]">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="statusConfig[room.status]?.dot"></span>
                                    {{
                                        statusConfig[room.status]?.label ||
                                        "Không rõ"
                                    }}
                                </span>
                                <span :class="[
                                    'px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider',
                                    isRoomActive(room.status)
                                        ? 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                                        : 'bg-slate-50 text-slate-500 border border-slate-100',
                                ]">
                                    {{
                                        isRoomActive(room.status)
                                            ? "Hoạt động"
                                            : "Tạm ngưng"
                                    }}
                                </span>
                            </div>

                            <!-- Giá và Diện tích -->
                            <div class="bg-slate-50 rounded-2xl p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400"><i class="bi bi-cash-stack mr-1"></i>
                                        Giá thuê</span>
                                    <span class="text-sm font-extrabold text-slate-800">{{ fmtMoney(room.price)
                                        }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400"><i
                                            class="bi bi-aspect-ratio mr-1"></i>
                                        Diện tích</span>
                                    <span class="text-xs font-bold text-slate-800">{{ room.area }} m²</span>
                                </div>
                            </div>

                            <!-- Người ở -->
                            <div class="pt-2 border-t border-slate-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-people-fill text-slate-400"></i>
                                    <span class="text-xs font-bold text-slate-600">{{ room.current_people }}/{{
                                        room.capacity
                                    }}
                                        người</span>
                                </div>

                                <span v-if="room.current_people === 0"
                                    class="text-[10px] font-bold text-emerald-500">Còn trống</span>
                                <span v-else-if="
                                    room.current_people < room.capacity
                                " class="text-[10px] font-bold text-blue-500">Còn chỗ</span>
                                <span v-else class="text-[10px] font-bold text-rose-500">Đã đầy</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floor List Settings at bottom -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    Cấu trúc các tầng
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="fl in floors" :key="fl.id"
                        class="p-4 bg-slate-50/50 border border-slate-100 rounded-2xl flex items-center justify-between">
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-slate-800">
                                {{ fl.name }}
                            </h4>
                            <p class="text-[10px] text-slate-400 font-semibold">
                                {{ fl.rooms.length }} phòng
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openAddRoomForFloor(fl.id)"
                                class="w-7 h-7 hover:bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center"
                                title="Thêm phòng vào tầng này">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                            <button @click="openEditFloor(fl)"
                                class="w-7 h-7 hover:bg-amber-50 text-amber-500 rounded-lg flex items-center justify-center"
                                title="Sửa tầng này">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button @click="delFloor(fl)"
                                class="w-7 h-7 hover:bg-rose-50 text-rose-500 rounded-lg flex items-center justify-center"
                                title="Xóa tầng này">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <!-- Floor Add/Edit Modal -->
            <div v-if="showFloorModal"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="showFloorModal = false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">
                            {{ isEditFloor ? "Sửa Tầng/Khu" : "Thêm Tầng Mới" }}
                        </h3>
                        <button @click="showFloorModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Tên tầng
                                <span class="text-rose-500">*</span></label>
                            <input v-model="floorName"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                placeholder="VD: Tầng 1" @keyup.enter="submitFloor" />
                            <span v-if="floorError" class="text-[10px] text-rose-500 font-semibold block mt-1"><i
                                    class="bi bi-exclamation-circle"></i>
                                {{ floorError }}</span>
                        </div>

                        <!-- Địa chỉ tầng/khu -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-500">Địa chỉ tầng/khu</label>
                                <button @click="getCurrentFloorPosition" type="button"
                                    class="text-[10px] text-emerald-600 font-bold hover:underline flex items-center gap-1">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    {{ isLocatingFloor ? 'Đang xác vị...' : 'Lấy vị trí hiện tại' }}
                                </button>
                            </div>
                            <input v-model="floorAddress"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                placeholder="VD: 123 Đường ABC, Ninh Bình..." />
                        </div>

                        <!-- Toạ độ GPS -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label class="text-[10px] text-slate-400 font-bold">Vĩ độ (Latitude)</label>
                                <input v-model="floorLatitude" type="number" step="any"
                                    class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-lg text-xs"
                                    placeholder="VD: 20.2506" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] text-slate-400 font-bold">Kinh độ (Longitude)</label>
                                <input v-model="floorLongitude" type="number" step="any"
                                    class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-lg text-xs"
                                    placeholder="VD: 105.9744" />
                            </div>
                        </div>

                        <!-- Map Preview -->
                        <div class="rounded-xl overflow-hidden border border-slate-100" style="height: 150px">
                            <iframe v-if="floorMapUrl"
                                :src="floorMapUrl"
                                width="100%" height="100%" style="border: 0" loading="lazy">
                            </iframe>
                            <div v-else
                                class="h-full bg-slate-50 flex items-center justify-center text-[10px] text-slate-400">
                                Nhập địa chỉ hoặc toạ độ để xem trước bản đồ vị trí khu trọ/tầng
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                        <button
                            class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors"
                            @click="showFloorModal = false">
                            Hủy
                        </button>
                        <button
                            class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors"
                            @click="submitFloor">
                            {{ isEditFloor ? "Lưu" : "Thêm" }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detail Modal -->
            <div v-if="showDetail && selRoom"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="showDetail = false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">
                            Chi tiết phòng {{ selRoom.name }}
                        </h3>
                        <button @click="showDetail = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <!-- Banner cảnh báo khi phòng đã có bài đăng được duyệt -->
                    <div v-if="selRoom.has_approved_post"
                        class="p-3 bg-rose-50 border border-rose-100 rounded-2xl flex items-start gap-2.5 text-left text-rose-700">
                        <i class="bi bi-exclamation-triangle-fill text-lg flex-shrink-0"></i>
                        <div class="space-y-0.5">
                            <h4 class="text-xs font-bold">
                                Phòng đang có tin đăng công khai
                            </h4>
                            <p class="text-[10px] leading-relaxed text-rose-600/95">
                                Hệ thống đã khóa tính năng Sửa/Xóa phòng này để
                                bảo toàn dữ liệu bài đăng đang hiển thị ở
                                client.
                            </p>
                        </div>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Trạng thái:</span>
                            <span :class="[
                                'px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5',
                                statusConfig[selRoom.status]?.cls,
                            ]">
                                <span class="w-1.5 h-1.5 rounded-full"
                                    :class="statusConfig[selRoom.status]?.dot"></span>
                                {{ statusConfig[selRoom.status]?.label }}
                            </span>
                        </div>
                        <div v-if="selRoom.status === 'maintenance'"
                            class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Lý do bảo trì:</span>
                            <span class="text-xs font-bold text-rose-500 text-right max-w-[200px] truncate"
                                :title="selRoom.maintenance_reason">{{
                                    selRoom.maintenance_reason ||
                                    "Không có lý do"
                                }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Giá thuê:</span>
                            <span class="text-xs font-bold text-slate-800">{{
                                fmtMoney(selRoom.price)
                                }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Diện tích:</span>
                            <span class="text-xs font-bold text-slate-800">{{ selRoom.area }} m²</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Số người:</span>
                            <span class="text-xs font-bold text-slate-800">{{ selRoom.current_people }}/{{
                                selRoom.capacity
                            }}
                                người</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Còn trống:</span>
                            <span class="text-xs font-bold text-emerald-600">{{
                                Math.max(
                                    0,
                                    selRoom.capacity -
                                    selRoom.current_people,
                                )
                            }}
                                chỗ</span>
                        </div>

                        <!-- Services List -->
                        <div class="pt-2 border-t border-slate-50">
                            <span class="text-xs font-bold text-slate-400 mb-2 block">Dịch vụ đang dùng:</span>
                            <div v-if="
                                selRoom.services &&
                                selRoom.services.length > 0
                            " class="grid grid-cols-2 gap-2">
                                <div v-for="srv in selRoom.services" :key="srv.id"
                                    class="flex items-center gap-2 p-2 border border-slate-100 rounded-xl bg-slate-50">
                                    <div class="w-6 h-6 rounded-md flex items-center justify-center text-[10px]" :class="[
                                        colorsConfig[srv.color || 'emerald']
                                            ?.bg,
                                        colorsConfig[srv.color || 'emerald']
                                            ?.text,
                                    ]">
                                        <i :class="[
                                            'bi',
                                            srv.icon ||
                                            'bi-lightning-charge-fill',
                                        ]"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-slate-700">{{ srv.name }}</span>
                                        <span class="text-[9px] font-semibold text-slate-500">{{ fmtMoney(srv.price)
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else
                                class="text-xs font-semibold text-slate-400 italic bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                Không có dịch vụ
                            </div>
                        </div>

                        <!-- Status transitions buttons -->
                        <div class="space-y-2 pt-2">
                            <p class="text-xs font-bold text-slate-400">
                                Chuyển đổi trạng thái nhanh:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="(cfg, key) in statusConfig" :key="key" v-show="getAllowedStatuses(
                                    selRoom.status,
                                ).includes(key)
                                    " :class="[
                                        'px-3 py-2 rounded-xl text-[10px] font-bold border cursor-pointer hover:shadow-sm transition-all',
                                        cfg.cls,
                                    ]" @click="quickSt(key)">
                                    <i :class="['bi mr-1', cfg.icon]"></i>
                                    {{ cfg.label }}
                                </button>
                                <button v-if="selRoom.status === 'expiring_soon'"
                                    class="px-3 py-2 rounded-xl text-[10px] font-bold border border-amber-300 bg-amber-50 text-amber-600 hover:bg-amber-100 cursor-pointer hover:shadow-sm transition-all"
                                    @click="remindTenant">
                                    <i class="bi bi-bell-fill mr-1"></i> Gửi
                                    nhắc nhở
                                </button>
                                <button v-if="
                                    [
                                        'deposited',
                                        'rented',
                                        'expiring_soon',
                                        'pending_renewal',
                                    ].includes(selRoom.status)
                                "
                                    class="px-3 py-2 rounded-xl text-[10px] font-bold border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 cursor-pointer hover:shadow-sm transition-all"
                                    @click="addPerson(selRoom)">
                                    <i class="bi bi-person-plus-fill mr-1"></i>
                                    Thêm người
                                </button>
                                <button v-if="
                                    [
                                        'pending_renewal',
                                        'expiring_soon',
                                    ].includes(selRoom.status)
                                "
                                    class="px-3 py-2 rounded-xl text-[10px] font-bold border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 cursor-pointer hover:shadow-sm transition-all"
                                    @click="removePerson(selRoom)">
                                    <i class="bi bi-person-dash-fill mr-1"></i>
                                    Bớt người
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <!-- Nút Khóa phòng / Xóa phòng -->
                        <button v-if="
                            [
                                'available',
                                'maintenance',
                                'under_construction',
                            ].includes(selRoom.status)
                        "
                            class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="selRoom.has_approved_post" @click="lockRoom(selRoom)">
                            <i class="bi bi-lock-fill mr-1"></i> Khóa phòng
                        </button>
                        <button v-else-if="selRoom.status === 'suspended'"
                            class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="selRoom.has_approved_post" @click="delRoom(selRoom)">
                            <i class="bi bi-trash mr-1"></i> Xóa phòng
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-2">
                            <button
                                class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors"
                                @click="showDetail = false">
                                Đóng
                            </button>

                            <!-- Nút Sửa thông tin -->
                            <button
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="selRoom.has_approved_post" @click="openEditRoom">
                                Sửa thông tin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Room Modal Form -->
            <RoomFormModal :show="showForm" :isEdit="isEditing" :hideFloorSelect="hideFloorSelect" :room="selRoom"
                :floors="floors" v-model:floorId="formFloorId" :floorName="currentFloorName"
                :statusConfig="statusConfig" :existingRooms="currentFloorRooms" :services="services"
                @close="showForm = false" @submitted="submitRoom" />

            <!-- Custom Confirm Modal -->
            <div v-if="confirmModal.show"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[100] p-4"
                @click.self="confirmModal.show = false">
                <div
                    class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden text-center transform transition-all">
                    <div class="p-6">
                        <div :class="[
                            'w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4',
                            confirmModal.type === 'danger'
                                ? 'bg-rose-50 text-rose-500'
                                : confirmModal.type === 'success'
                                    ? 'bg-emerald-50 text-emerald-500'
                                    : 'bg-amber-50 text-amber-500',
                        ]">
                            <i :class="[
                                'bi text-2xl',
                                confirmModal.type === 'danger'
                                    ? 'bi-trash-fill'
                                    : confirmModal.type === 'success'
                                        ? 'bi-check-circle-fill'
                                        : 'bi-exclamation-triangle-fill',
                            ]"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">
                            {{ confirmModal.title }}
                        </h3>
                        <p class="text-sm text-slate-500" v-html="confirmModal.message"></p>

                        <!-- Prompt Input -->
                        <div v-if="confirmModal.isPrompt" class="mt-4 text-left">
                            <input v-model="confirmModal.promptValue" type="text" :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                                confirmModal.promptError
                                    ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500'
                                    : 'border-slate-200 focus:border-emerald-500',
                            ]" placeholder="VD: Thay vòi sen hỏng..." @input="confirmModal.promptError = ''"
                                @keyup.enter="handleConfirm" />
                            <span v-if="confirmModal.promptError"
                                class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ confirmModal.promptError }}
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button v-if="!confirmModal.isAlert" @click="confirmModal.show = false"
                            class="flex-1 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-all">
                            Hủy
                        </button>
                        <button v-if="!confirmModal.isAlert" @click="handleConfirm" :class="[
                            'flex-1 px-4 py-2.5 text-white font-bold text-xs rounded-xl transition-all shadow-md',
                            confirmModal.type === 'danger'
                                ? 'bg-rose-500 hover:bg-rose-600 shadow-rose-500/20'
                                : 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20',
                        ]">
                            Xác nhận
                        </button>
                        <button v-if="confirmModal.isAlert" @click="confirmModal.show = false"
                            class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20 text-white font-bold text-xs rounded-xl transition-all shadow-md">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
