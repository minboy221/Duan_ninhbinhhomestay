<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import RoomFormModal from "./RoomFormModal.vue";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { HA_NAM_COMMUNES } from "@/constants/locations.js";
import { showSuccess, showError, showWarning } from "@/Utils/swal";

const props = defineProps({
    floors: { type: Array, default: () => [] },
    allFloors: { type: Array, default: () => [] },
    statusCounts: { type: Object, default: () => ({}) },
    services: { type: Array, default: () => [] },
});
const floors = computed(() => props.floors);

const page = usePage();
const selectedPropertyName = computed(() => {
    const houses = page.props.auth?.boarding_houses || [];
    const selectedId = page.props.auth?.selected_boarding_house_id;
    const house = houses.find((h) => h.id === selectedId) || houses[0];
    return house ? house.name : "Chưa có cơ sở";
});

const getLocation = () => {
    if (!navigator.geolocation) {
        showWarning("Lỗi trình duyệt", "Trình duyệt không hỗ trợ geolocation.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            form.latitude = lat.toString();
            form.longitude = lon.toString();
            try {
                //gọi APT để dịch toạ độ thành địa chỉ
                const response = await axios.get(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`,
                );
                if (response.data) {
                    const addressObj = response.data.address || {};
                    //phân tích quận/huyện/xã
                    const districtVal =
                        addressObj.district ||
                        addressObj.suburd ||
                        addressObj.county ||
                        addressObj.city_district ||
                        addressObj.city ||
                        "";
                    form.district = districtVal;

                    //phân tích số nhà, ngõ ngách, tên đường
                    const road = (addressObj.road = addressObj.road || "");
                    const houseNumber = addressObj.house_number || "";
                    const neighbourhood = addressObj.quarter || "";
                    let detailAddress = "";
                    if (houseNumber) detailAddress += houseNumber + "";
                    if (road) detailAddress += road;
                    if (
                        neighbourhood &&
                        !detailAddress.includes(neighbourhood)
                    ) {
                        detailAddress +=
                            (detailAddress ? "," : "") + neighbourhood;
                    }
                    //nếu ko tách biệt được số nhà cụ thể, lấy tên địa điểm hiển thị chung
                    if (!detailAddress) {
                        detailAddress = response.data.display_name;
                    }
                    form.address_detail = detailAddress;
                    form.clearErrors(
                        "latitude",
                        "longitude",
                        "district",
                        "address_detail",
                    );
                }
            } catch (error) {
                console.error("Lỗi lấy địa chỉ từ GPS:", error);
            }
        },
        (error) => {
            showWarning(
                "Lỗi GPS",
                "Không thể lấy vị trí .Vui lòng nhập thủ công hoặc cho phép quyền truy cập vị trí.",
            );
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

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

const getEffectiveOccupants = (room) => {
    if (!room) return 0;
    if (room.status === "rented" || room.current_people > 0) {
        return Math.max(Number(room.current_people) || 0, 1);
    }
    return Number(room.current_people) || 0;
};

const activeTab = ref("all"); // 'all' | 'active' | 'inactive'
const searchQuery = ref("");
const floorFilter = ref("");
const statusFilter = ref("");
const viewMode = ref("grid"); // 'grid' | 'compact' | 'list'

const isRoomActive = (status) => {
    return [
        "available",
        "rented",
        "deposited",
        "expiring_soon",
        "pending_renewal",
    ].includes(status);
};

const floorPages = ref({});
const roomsPerPage = ref(8); // Mỗi trang của Tầng hiển thị tối đa 8 phòng

const getFloorPage = (floorId) => {
    return floorPages.value[floorId] || 1;
};

const setFloorPage = (floorId, page) => {
    floorPages.value[floorId] = page;
};

const groupedFloors = computed(() => {
    return floors.value
        .map((f) => {
            const filteredRooms = f.rooms
                .filter((r) => {
                    // Apply Tab filter
                    if (activeTab.value === "active" && !isRoomActive(r.status)) return false;
                    if (activeTab.value === "inactive" && isRoomActive(r.status)) return false;

                    // Apply Floor filter
                    if (floorFilter.value && f.id !== parseInt(floorFilter.value)) return false;

                    // Apply Status filter
                    if (statusFilter.value && r.status !== statusFilter.value) return false;

                    // Apply Search Query
                    if (searchQuery.value) {
                        const q = searchQuery.value.toLowerCase();
                        if (!r.name.toLowerCase().includes(q)) return false;
                    }

                    return true;
                })
                .map((r) => ({
                    ...r,
                    floor_name: f.name,
                    floor_id: f.id,
                }));

            const totalRooms = filteredRooms.length;
            const totalPages = Math.ceil(totalRooms / roomsPerPage.value) || 1;
            const currentPage = Math.min(getFloorPage(f.id), totalPages);

            const start = (currentPage - 1) * roomsPerPage.value;
            const paginatedRooms = filteredRooms.slice(start, start + roomsPerPage.value);

            return {
                ...f,
                rooms: filteredRooms,
                paginatedRooms,
                totalRooms,
                totalPages,
                currentPage,
            };
        })
        .filter((f) => {
            if (floorFilter.value && f.id !== parseInt(floorFilter.value)) return false;
            const hasActiveFilter = searchQuery.value || statusFilter.value || activeTab.value !== 'all';
            return f.rooms.length > 0 || !hasActiveFilter;
        });
});

const allFilteredRooms = computed(() => {
    return groupedFloors.value.flatMap((f) => f.rooms);
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
const formatFloorName = () => {
    const val = floorName.value.trim();
    if (/^\d+$/.test(val)) {
        floorName.value = "Tầng " + val;
    }
};

const showFloorModal = ref(false);
const isEditFloor = ref(false);
const editingFloorId = ref(null);
const floorName = ref("");
const floorAddress = ref("");
const selectedWard = ref("");
const addressDetail = ref("");
const floorLatitude = ref(null);
const floorLongitude = ref(null);
const isLocatingFloor = ref(false);
const floorError = ref("");

const isFloorDropdownOpen = ref(false);
const floorDropdownRef = ref(null);

const selectFloorCommune = (commune) => {
    selectedWard.value = commune;
    isFloorDropdownOpen.value = false;
};

const toggleFloorDropdown = () => {
    isFloorDropdownOpen.value = !isFloorDropdownOpen.value;
};

onMounted(() => {
    const handleClickOutside = (event) => {
        if (
            floorDropdownRef.value &&
            !floorDropdownRef.value.contains(event.target)
        ) {
            isFloorDropdownOpen.value = false;
        }
    };
    window.addEventListener("click", handleClickOutside, true);
    onUnmounted(() => {
        window.removeEventListener("click", handleClickOutside, true);
    });
});

watch([selectedWard, addressDetail], () => {
    const detail = addressDetail.value.trim();
    if (detail && selectedWard.value) {
        floorAddress.value = `${detail}, ${selectedWard.value}`;
    } else if (selectedWard.value) {
        floorAddress.value = selectedWard.value;
    } else {
        floorAddress.value = detail;
    }
});

const openAddFloor = () => {
    isEditFloor.value = false;
    editingFloorId.value = null;
    floorName.value = "";
    //lấy thông tin cơ sở trọ đang được chọn
    const houses = page.props.auth?.boarding_houses || [];
    const selectedId = page.props.auth?.selected_boarding_house_id;
    const currentHouse =
        houses.find((h) => h.id === selectedId) || houses[0] || null;
    //gán địa chỉ và toạ độ mặc định của tầng theo cơ sở trọ đó
    if (currentHouse) {
        addressDetail.value = currentHouse.address_detail || "";
        selectedWard.value = currentHouse.district || "";
        floorLatitude.value = currentHouse.latitude || null;
        floorLongitude.value = currentHouse.longitude || null;
    } else {
        addressDetail.value = "";
        selectedWard.value = "";
        floorLatitude.value = null;
        floorLongitude.value = null;
    }
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

    let wardFound = "";
    let detailFound = fl.address || "";
    if (fl.address) {
        for (const ward of HA_NAM_COMMUNES) {
            if (fl.address.includes(ward)) {
                wardFound = ward;
                detailFound = fl.address
                    .replace(ward, "")
                    .replace(/,\s*$/, "")
                    .replace(/^\s*,/, "")
                    .trim();
                break;
            }
        }
    }
    selectedWard.value = wardFound;
    addressDetail.value = detailFound;

    showFloorModal.value = true;
};

const getCurrentFloorPosition = () => {
    if (!navigator.geolocation) {
        showWarning(
            "Lỗi GPS",
            "Trình duyệt của bạn không hỗ trợ chức năng định vị GPS",
        );
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
                if (response.data) {
                    const addr = response.data.address || {};
                    let foundWard = "";
                    const possibleWardKeys = [
                        "suburb",
                        "village",
                        "town",
                        "quarter",
                        "city_district",
                        "neighbourhood",
                    ];

                    for (const key of possibleWardKeys) {
                        if (addr[key]) {
                            const val = addr[key].trim();
                            const matched = HA_NAM_COMMUNES.find(
                                (c) =>
                                    c
                                        .toLowerCase()
                                        .includes(val.toLowerCase()) ||
                                    val.toLowerCase().includes(c.toLowerCase()),
                            );
                            if (matched) {
                                foundWard = matched;
                                break;
                            }
                        }
                    }

                    if (foundWard) {
                        selectedWard.value = foundWard;
                    }

                    let displayName = response.data.display_name || "";
                    let detail = displayName;
                    if (foundWard) {
                        const idx = displayName.indexOf(foundWard);
                        if (idx !== -1) {
                            detail = displayName
                                .substring(0, idx)
                                .trim()
                                .replace(/,\s*$/, "")
                                .replace(/^\s*,/, "")
                                .trim();
                        }
                    } else {
                        const parts = displayName.split(",");
                        if (parts.length > 2) {
                            detail = parts.slice(0, 2).join(", ").trim();
                        }
                    }

                    addressDetail.value = detail || displayName;
                    floorAddress.value = displayName;
                }
            } catch (error) {
                console.error("Lỗi dịch toạ độ sang địa chỉ:", error);
                showWarning(
                    "Cảnh báo",
                    "Đã lấy được toạ độ nhưng không thể dịch thành địa chỉ.",
                );
            } finally {
                isLocatingFloor.value = false;
            }
        },
        (error) => {
            isLocatingFloor.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    showWarning(
                        "Từ chối truy cập",
                        "Bạn đã từ chối cấp quyền truy cập GPS.",
                    );
                    break;
                case error.POSITION_UNAVAILABLE:
                    showError(
                        "Lỗi vị trí",
                        "Không thể xác định được vị trí hiện tại.",
                    );
                    break;
                case error.TIMEOUT:
                    showWarning(
                        "Hết thời gian",
                        "Quá thời gian yêu cầu lấy vị trí.",
                    );
                    break;
                default:
                    showError(
                        "Lỗi không xác định",
                        "Đã xảy ra lỗi không xác định khi lấy vị trí.",
                    );
                    break;
            }
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const floorMapUrl = computed(() => {
    if (floorLatitude.value && floorLongitude.value) {
        return `https://maps.google.com/maps?q=${floorLatitude.value},${floorLongitude.value}&hl=vi&z=16&output=embed`;
    }
    if (floorAddress.value) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(floorAddress.value)}&hl=vi&z=16&output=embed`;
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
        (f) =>
            f.name.toLowerCase() === name.toLowerCase() &&
            (!isEditFloor.value || f.id !== editingFloorId.value),
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
                },
                onError: (errors) => {
                    if (errors.name) floorError.value = errors.name;
                },
            },
        );
    } else {
        router.post(route("landlord.floors.store"), payload, {
            onSuccess: (page) => {
                showFloorModal.value = false;
                const flash = page.props.flash;
                if (flash && flash.error) {
                    showAlert("Không thể thêm tầng", flash.error, "warning");
                } else {
                    showAlert(
                        "Thành công",
                        `Thêm tầng/khu "${name}" thành công!`,
                        "success",
                    );
                }
            },
            onError: (errors) => {
                if (errors.name) floorError.value = errors.name;
            },
        });
    }
};
const delFloor = (f) => {
    const restrictedStatuses = [
        "rented",
        "deposited",
        "expiring_soon",
        "pending_renewal",
    ];
    const hasRestrictedRoom = (f.rooms || []).some((r) =>
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
    //chặn nếu phòng bị đóng băng
    if (selRoom.value && selRoom.value.is_frozen) {
        showWarning("Phòng tạm khoá", "Phòng  này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ. Vui lòng nâng cấp gói!");
        return;
    }
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
const currentFloor = computed(
    () =>
        floors.value.find((fl) => fl.id === formFloorId.value) ||
        props.allFloors.find((fl) => fl.id === formFloorId.value),
);
const currentFloorName = computed(() =>
    currentFloor.value ? currentFloor.value.name : "",
);

const currentFloorRooms = computed(() =>
    currentFloor.value ? currentFloor.value.rooms || [] : [],
);

const openAddRoom = () => {
    if (props.allFloors.length === 0) {
        showAlert(
            "Cảnh báo",
            "Vui lòng thêm tầng trước khi thêm phòng!",
            "warning",
        );
        return;
    }
    isEditing.value = false;
    hideFloorSelect.value = false;
    formFloorId.value = floorFilter.value
        ? parseInt(floorFilter.value)
        : props.allFloors[0].id;
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

const submitRoom = (data) => {
    const fd = data.formData || data;
    const resetSubmitting = data.resetSubmitting || (() => { });
    if (isEditing.value && selRoom.value) {
        router.post(route("landlord.rooms.update", selRoom.value.id), fd, {
            onSuccess: (page) => {
                const flash = page.props.flash;
                if (flash && flash.error) {
                    showAlert("Lỗi", flash.error, "warning");
                    resetSubmitting();
                } else {
                    showForm.value = false;
                    showAlert(
                        "Thành công",
                        flash && flash.success
                            ? flash.success
                            : "Cập nhật thông tin phòng thành công!",
                        "success",
                    );
                }
            },
            onError: () => {
                resetSubmitting();
            },
            onFinish: () => {
                resetSubmitting();
            },
            forceFormData: true,
        });
    } else {
        router.post(route("landlord.rooms.store"), fd, {
            onSuccess: (page) => {
                const flash = page.props.flash;
                if (flash && flash.error) {
                    showAlert("Lỗi", flash.error, "warning");
                    resetSubmitting();
                } else {
                    showForm.value = false;
                    showAlert(
                        "Thành công",
                        flash && flash.success
                            ? flash.success
                            : "Thêm phòng mới thành công!",
                        "success",
                    );
                }
            },
            onError: () => {
                resetSubmitting();
            },
            onFinish: () => {
                resetSubmitting();
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
    const targetRoom = room || selRoom.value;
    if (!targetRoom) return;

    const allowedStatuses = [
        "deposited",
        "rented",
        "expiring_soon",
        "pending_renewal",
    ];
    if (!allowedStatuses.includes(targetRoom.status)) {
        showWarning(
            "Không hợp lệ",
            "Trạng thái phòng không cho phép thêm người!",
        );
        return;
    }
    const currentCount = getEffectiveOccupants(targetRoom);
    if (currentCount >= targetRoom.capacity) {
        showWarning("Không thể thêm", "Phòng đã đủ số lượng người tối đa.");
        return;
    }
    router.patch(
        route("landlord.rooms.add_person", targetRoom.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const newCount = currentCount + 1;
                targetRoom.current_people = newCount;
                if (selRoom.value && selRoom.value.id === targetRoom.id) {
                    selRoom.value.current_people = newCount;
                }
                props.floors?.forEach((f) => {
                    const r = f.rooms?.find((rm) => rm.id === targetRoom.id);
                    if (r) r.current_people = newCount;
                });
                showSuccess(
                    "Thành công",
                    `Đã thêm 1 người vào phòng! (Hiện tại: ${newCount}/${targetRoom.capacity} người)`,
                );
            },
            onError: () => {
                showWarning("Lỗi", "Không thể thêm người vào phòng.");
            },
        },
    );
};

const removePerson = (room) => {
    const currentCount = getEffectiveOccupants(room);
    if (currentCount <= 1 && room.status === "rented") {
        showWarning(
            "Không thể bớt",
            "Phòng đang có hợp đồng thuê hoạt động phải duy trì tối thiểu 1 người. Nếu khách trả phòng, vui lòng thanh lý hợp đồng!",
        );
        return;
    }
    if (currentCount <= 0) {
        showWarning("Không thể bớt", "Phòng hiện không có người.");
        return;
    }
    router.patch(
        route("landlord.rooms.remove_person", room.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const newCount = Math.max(0, currentCount - 1);
                room.current_people = newCount;
                if (selRoom.value && selRoom.value.id === room.id) {
                    selRoom.value.current_people = newCount;
                }
                props.floors.forEach((f) => {
                    const r = f.rooms?.find((rm) => rm.id === room.id);
                    if (r) r.current_people = newCount;
                });
                showSuccess(
                    "Thành công",
                    `Đã bớt 1 người khỏi phòng! (Hiện tại: ${newCount}/${room.capacity} người)`,
                );
            },
            onError: () => {
                showWarning("Lỗi", "Không thể bớt người khỏi phòng.");
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

//Hàm tự động kích hoạt GPS để lấy toạ độ
const getAutoCoordinates = () => {
    if (!navigator.geolocation) {
        showWarning("Lỗi GPS", "Trình duyệt của bạn không hỗ trợ định vị.");
        return;
    }
    //gọi GPS của thiết bị
    navigator.geolocation.getCurrentPosition(
        (position) => {
            latitude.value = position.coords.latitude;
            longitude.value = position.coords.longitude;
            //in toạ độ vào form
            form.latitude = lat;
            form.longitude = lng;
            if (typeof map !== "undefined" && map && marker) {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }
            showSuccess(
                "Đã định vị",
                "Đã tự động cập nhật toạ độ vị trí hiện tại của bạn thành công.",
            );
        },
        (error) => {
            console.error("lỗi định vị", error);
            //báo lỗi
            if (error.code === error.PERMISSION_DENIED) {
                showWarning(
                    "Từ chối truy cập",
                    "Bạn đã từ chối cấp quyền vị trí.",
                );
            } else {
                showError("Lỗi định vị", "Thiết bị không phản hồi toạ độ GPS.");
            }
        },
        {
            enableHighAccuracy: true, //ép bật định vị chính xác nhất
            timeout: 8000, //tối đa 8s
            maximumAge: 0, //không lấy lại toạ độ cũ bắt buộc phải lấy mới
        },
    );
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
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Quản lý Phòng trọ
                        <span
                            class="text-xs font-medium px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg whitespace-nowrap">
                            <i class="bi bi-building mr-1"></i>
                            {{ selectedPropertyName }}
                        </span>
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

                <!-- Chế độ xem -->
                <div class="flex items-center bg-slate-50 border border-slate-150 rounded-xl p-1 gap-0.5 ml-auto">
                    <button @click="viewMode = 'grid'" :class="[
                        'px-2.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1 font-bold',
                        viewMode === 'grid'
                            ? 'bg-white text-emerald-600 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600',
                    ]" title="Dạng lưới lớn">
                        <i class="bi bi-grid-fill"></i>
                        <span class="hidden sm:inline">Lưới</span>
                    </button>
                    <button @click="viewMode = 'compact'" :class="[
                        'px-2.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1 font-bold',
                        viewMode === 'compact'
                            ? 'bg-white text-emerald-600 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600',
                    ]" title="Dạng lưới thu gọn">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span class="hidden sm:inline">Thu gọn</span>
                    </button>
                    <button @click="viewMode = 'list'" :class="[
                        'px-2.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1 font-bold',
                        viewMode === 'list'
                            ? 'bg-white text-emerald-600 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600',
                    ]" title="Dạng danh sách dòng">
                        <i class="bi bi-list-ul"></i>
                        <span class="hidden sm:inline">Danh sách</span>
                    </button>
                </div>
            </div>

            <!-- Rooms Table (Phân tầng rõ ràng) -->
            <div v-if="allFilteredRooms.length === 0"
                class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-slate-400 text-xs font-medium space-y-2 shadow-sm">
                <i class="bi bi-inbox text-3xl text-slate-300 block"></i>
                <span>Không tìm thấy phòng nào phù hợp bộ lọc.</span>
            </div>

            <div v-else class="space-y-6">
                <div v-for="floor in groupedFloors" :key="floor.id"
                    class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm space-y-4">
                    <!-- Header Tầng -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-sm">
                                <i class="bi bi-layers-fill"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-2">
                                    {{ floor.name }}
                                    <span
                                        class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[11px] rounded-full font-bold">
                                        {{ floor.rooms.length }} phòng
                                    </span>
                                </h3>
                                <p v-if="floor.address"
                                    class="text-[11px] text-slate-400 font-medium mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-geo-alt-fill text-slate-400"></i> {{ floor.address }}
                                </p>
                            </div>
                        </div>

                        <!-- Thao tác trên Tầng -->
                        <div class="flex items-center gap-2">
                            <button @click="openAddRoomForFloor(floor.id)"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                                <i class="bi bi-plus-lg"></i> Thêm phòng
                            </button>
                            <button @click="openEditFloor(floor)"
                                class="w-8 h-8 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition"
                                title="Sửa tầng này">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button @click="openDeleteFloor(floor.id)"
                                class="w-8 h-8 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition"
                                title="Xóa tầng này">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Trường hợp Tầng chưa có phòng -->
                    <div v-if="floor.rooms.length === 0"
                        class="text-center py-6 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400 font-medium">Tầng này chưa có phòng trọ nào.</p>
                        <button @click="openAddRoomForFloor(floor.id)"
                            class="mt-2 text-xs font-bold text-indigo-600 hover:underline">
                            + Thêm phòng ngay
                        </button>
                    </div>

                    <!-- 1. Chế độ xem Lưới Lớn (viewMode === 'grid') -->
                    <div v-else-if="viewMode === 'grid'"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="room in floor.paginatedRooms" :key="room.id"
                            class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs flex flex-col justify-between hover:shadow-md transition-all duration-200 cursor-pointer group"
                            @click="openDetail(room)">
                            <div class="space-y-3">
                                <!-- Header: Tên phòng và Tầng -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-emerald-100/80 text-emerald-800 flex items-center justify-center font-black text-base border border-emerald-200">
                                            P
                                        </div>
                                        <div class="space-y-0.5">
                                            <h4 class="text-sm sm:text-base font-black text-slate-900">
                                                {{ room.name }}
                                            </h4>
                                            <span
                                                class="text-[11px] text-slate-600 font-bold uppercase tracking-wider">{{
                                                    room.floor_name }}</span>
                                        </div>
                                    </div>
                                    <button @click.stop="openDetail(room)"
                                        class="w-7 h-7 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center justify-center transition-colors">
                                        <i class="bi bi-three-dots-vertical text-sm"></i>
                                    </button>
                                </div>

                                <!-- Trạng thái phòng -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span v-if="room.is_frozen"
                                        class="px-2 py-0.5 rounded-md text-[11px] font-black border flex items-center gap-1 w-fit bg-sky-100 text-sky-800 border-sky-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600 animate-pulse"></span>
                                        🧊 Đóng băng (Vượt gói)
                                    </span>
                                    <span v-else :class="[
                                        'px-2.5 py-0.5 rounded-md text-[11px] font-black border flex items-center gap-1 w-fit shadow-2xs',
                                        statusConfig[room.status]?.cls ||
                                        'bg-slate-100 text-slate-800 border-slate-300',
                                    ]">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="statusConfig[room.status]?.dot"></span>
                                        {{
                                            statusConfig[room.status]?.label ||
                                            "Không rõ"
                                        }}
                                    </span>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider',
                                        room.is_frozen
                                            ? 'bg-sky-100 text-sky-800 border border-sky-300'
                                            : isRoomActive(room.status)
                                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                                : 'bg-slate-100 text-slate-700 border border-slate-200',
                                    ]">
                                        {{
                                            room.is_frozen
                                                ? "Tạm Khóa"
                                                : isRoomActive(room.status)
                                                    ? "Hoạt động"
                                                    : "Tạm ngưng"
                                        }}
                                    </span>
                                </div>

                                <!-- Giá và Diện tích -->
                                <div class="bg-slate-100/70 border border-slate-200/60 rounded-xl p-3 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700"><i
                                                class="bi bi-cash-stack mr-1 text-slate-500"></i>
                                            Giá thuê</span>
                                        <span class="text-sm sm:text-base font-black text-emerald-700">{{
                                            fmtMoney(room.price)
                                            }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700"><i
                                                class="bi bi-aspect-ratio mr-1 text-slate-500"></i>
                                            Diện tích</span>
                                        <span class="text-xs font-black text-slate-900">{{ room.area }} m²</span>
                                    </div>
                                </div>

                                <!-- Người ở -->
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <i class="bi bi-people-fill text-slate-500 text-xs"></i>
                                        <span class="text-xs font-bold text-slate-700">Tối đa: {{ room.capacity }}
                                            người</span>
                                    </div>

                                    <span v-if="getEffectiveOccupants(room) === 0"
                                        class="text-[11px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-200">Chưa
                                        có người ở</span>
                                    <span v-else-if="getEffectiveOccupants(room) < room.capacity"
                                        class="text-[11px] font-black text-blue-700 bg-blue-100 px-2 py-0.5 rounded-md border border-blue-200">Đã
                                        có {{ getEffectiveOccupants(room) }}/{{
                                            room.capacity
                                        }}
                                        người ở</span>
                                    <span v-else
                                        class="text-[11px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-md border border-rose-200">Đã
                                        đầy ({{ room.capacity }}/{{
                                            room.capacity
                                        }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Chế độ xem Thu Gọn (viewMode === 'compact') -->
                    <div v-else-if="viewMode === 'compact'"
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                        <div v-for="room in floor.paginatedRooms" :key="room.id"
                            class="bg-white border border-slate-200/90 rounded-2xl p-3 shadow-xs flex flex-col justify-between hover:shadow-md transition-all duration-200 cursor-pointer group relative overflow-hidden"
                            @click="openDetail(room)">
                            <div class="space-y-2">
                                <!-- Header: Tên phòng và Tầng -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <div
                                            class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm border border-emerald-200 flex-shrink-0">
                                            P
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-black text-slate-900 truncate" :title="room.name">
                                                {{ room.name }}
                                            </h4>
                                        </div>
                                    </div>
                                    <span
                                        class="text-[10px] text-slate-600 font-extrabold uppercase tracking-wider truncate max-w-[45px]">{{
                                            room.floor_name }}</span>
                                </div>

                                <!-- Trạng thái phòng gọn nhẹ -->
                                <div class="flex flex-wrap gap-1">
                                    <span v-if="room.is_frozen"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-black border flex items-center gap-1 w-fit bg-sky-100 text-sky-800 border-sky-300">
                                        <span class="w-1 h-1 rounded-full bg-sky-600 animate-pulse"></span>
                                        🧊 Đóng Băng
                                    </span>
                                    <span v-else :class="[
                                        'px-1.5 py-0.5 rounded text-[10px] font-black border flex items-center gap-1 w-fit',
                                        statusConfig[room.status]?.cls ||
                                        'bg-slate-100 text-slate-800 border-slate-300',
                                    ]">
                                        <span class="w-1 h-1 rounded-full"
                                            :class="statusConfig[room.status]?.dot"></span>
                                        {{
                                            statusConfig[room.status]?.label ||
                                            "Không rõ"
                                        }}
                                    </span>
                                </div>

                                <!-- Giá gọn nhẹ -->
                                <div class="bg-slate-100/80 border border-slate-200/50 rounded-xl p-2 text-center">
                                    <span class="text-xs font-black text-emerald-700 block">{{ fmtMoney(room.price)
                                    }}</span>
                                </div>
                            </div>

                            <!-- Footer: Người ở -->
                            <div
                                class="pt-1.5 mt-2 border-t border-slate-100 flex items-center justify-between text-[10px] font-bold text-slate-700">
                                <span>
                                    <i class="bi bi-people-fill text-slate-500 mr-0.5"></i>
                                    Tối đa: {{ room.capacity }}
                                </span>
                                <span v-if="getEffectiveOccupants(room) === 0"
                                    class="text-emerald-700 font-black">Trống</span>
                                <span v-else-if="getEffectiveOccupants(room) < room.capacity"
                                    class="text-blue-700 font-black">Đã có {{ getEffectiveOccupants(room) }}/{{
                                        room.capacity
                                    }}</span>
                                <span v-else class="text-rose-700 font-black">Đầy</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Chế độ xem Danh Sách Dòng (viewMode === 'list') -->
                    <div v-else-if="viewMode === 'list'" class="p-4 flex flex-col gap-2">
                        <div v-for="room in floor.paginatedRooms" :key="room.id"
                            class="bg-white border border-slate-200 hover:border-slate-300 rounded-2xl p-3 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between hover:shadow-md transition-all duration-200 cursor-pointer gap-2"
                            @click="openDetail(room)">
                            <!-- Left block: Name, floor, people -->
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                    :class="statusConfig[room.status]?.dot"></span>
                                <div class="flex items-baseline gap-2">
                                    <h4 class="text-sm sm:text-base font-black text-slate-900">
                                        Phòng {{ room.name }}
                                    </h4>
                                    <span class="text-xs text-slate-600 font-extrabold uppercase tracking-wider">{{
                                        room.floor_name }}</span>
                                </div>
                                <span class="text-xs text-slate-600 font-bold">
                                    <i class="bi bi-people-fill mr-0.5 text-slate-500"></i>
                                    {{ getEffectiveOccupants(room) }}/{{
                                        room.capacity
                                    }}
                                    người
                                </span>
                                <span v-if="room.is_frozen"
                                    class="px-2 py-0.5 rounded-md text-xs font-black border flex items-center gap-1 w-fit bg-sky-100 text-sky-800 border-sky-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600 animate-pulse"></span>
                                    🧊 Đóng Băng (Vượt Gói)
                                </span>
                            </div>

                            <!-- Right block: Price, status, actions -->
                            <div
                                class="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
                                <span class="text-xs font-bold text-slate-600 sm:hidden">Giá thuê:</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm sm:text-base font-black text-emerald-700">{{
                                        fmtMoney(room.price)
                                        }}</span>
                                    <span :class="[
                                        'px-2.5 py-1 rounded-md text-xs font-black border flex items-center gap-1 w-fit',
                                        statusConfig[room.status]?.cls ||
                                        'bg-slate-100 text-slate-800 border-slate-300',
                                    ]">
                                        {{
                                            statusConfig[room.status]?.label ||
                                            "Không rõ"
                                        }}
                                    </span>
                                    <button @click.stop="openDetail(room)"
                                        class="w-7 h-7 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center justify-center transition-colors">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- THANH PHÂN TRANG THEO TẦNG -->
                    <div v-if="floor.totalPages > 1"
                        class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100 mt-4 text-xs sm:text-sm">
                        <span class="text-slate-600 font-bold">
                            Hiển thị <span class="font-black text-slate-900">{{ floor.paginatedRooms.length }}</span> /
                            {{ floor.totalRooms }} phòng
                            (Trang <span class="font-black text-slate-900">{{ floor.currentPage }}</span> / {{
                            floor.totalPages }})
                        </span>

                        <div class="flex items-center gap-1.5">
                            <button @click="setFloorPage(floor.id, floor.currentPage - 1)"
                                :disabled="floor.currentPage === 1"
                                class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed font-extrabold transition shadow-2xs">
                                « Trước
                            </button>
                            <button v-for="p in floor.totalPages" :key="p" @click="setFloorPage(floor.id, p)" :class="[
                                'w-8 h-8 rounded-xl text-xs font-black transition flex items-center justify-center',
                                floor.currentPage === p
                                    ? 'bg-indigo-600 text-white shadow-xs font-black scale-105'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-100'
                            ]">
                                {{ p }}
                            </button>
                            <button @click="setFloorPage(floor.id, floor.currentPage + 1)"
                                :disabled="floor.currentPage === floor.totalPages"
                                class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed font-extrabold transition shadow-2xs">
                                Sau »
                            </button>
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
                            <input v-model="floorName" @blur="formatFloorName"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                placeholder="VD: Tầng 1 (hoặc nhập số 1)" @keyup.enter="submitFloor" />
                            <span v-if="floorError" class="text-[10px] text-rose-500 font-semibold block mt-1"><i
                                    class="bi bi-exclamation-circle"></i>
                                {{ floorError }}</span>
                        </div>
                        <!-- Địa chỉ Phường/Xã và Chi tiết -->
                        <div class="space-y-3">
                            <div class="space-y-1">
                                <div class="relative w-full" ref="floorDropdownRef">
                                    <!-- Custom Dropdown Options Menu -->
                                    <transition name="dropdown-fade">
                                        <div v-show="isFloorDropdownOpen"
                                            class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-slate-100 z-[999] p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                            <button v-for="commune in HA_NAM_COMMUNES" :key="commune" type="button"
                                                @click="
                                                    selectFloorCommune(commune)
                                                    "
                                                class="w-full px-3 py-2 rounded-lg text-left text-xs font-medium transition-all duration-150 flex items-center justify-between"
                                                :class="selectedWard === commune
                                                    ? 'bg-emerald-50 text-emerald-600 font-bold'
                                                    : 'hover:bg-slate-50 text-slate-600 hover:text-slate-800'
                                                    ">
                                                <span>{{ commune }}</span>
                                                <i v-if="
                                                    selectedWard === commune
                                                " class="bi bi-check text-emerald-600 text-sm font-bold"></i>
                                            </button>
                                        </div>
                                    </transition>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500">Địa chỉ chi tiết
                                        <span class="text-rose-500">*</span></label>
                                </div>
                                <input v-model="addressDetail"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    placeholder="Số nhà, tên đường..." />

                                <div v-if="floorLatitude && floorLongitude"
                                    class="mt-1.5 p-2 bg-emerald-50/50 border border-emerald-100/50 rounded-xl text-[10px] font-bold text-emerald-700 flex gap-4">
                                    <span><i class="bi bi-compass"></i> Vĩ độ
                                        (Lat): {{ floorLatitude }}</span>
                                    <span><i class="bi bi-compass"></i> Kinh độ
                                        (Lng): {{ floorLongitude }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Map Preview -->
                        <div class="rounded-xl overflow-hidden border border-slate-100" style="height: 150px">
                            <iframe v-if="floorMapUrl" :src="floorMapUrl" width="100%" height="100%" style="border: 0"
                                loading="lazy">
                            </iframe>
                            <div v-else
                                class="h-full bg-slate-50 flex items-center justify-center text-[10px] text-slate-400">
                                Nhập địa chỉ hoặc toạ độ để xem trước bản đồ vị
                                trí khu trọ/tầng
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
                            <span class="text-xs font-bold text-slate-800">{{ getEffectiveOccupants(selRoom) }}/{{
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
                                    getEffectiveOccupants(selRoom),
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
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <!-- Nút Khóa phòng / Xóa phòng -->
                        <button v-if="
                            [
                                'available',
                                'maintenance',
                                'under_construction',
                            ].includes(selRoom.status)
                        " class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
                                class="px-5 py-2.5 font-bold text-xs rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="selRoom.is_frozen
                                    ? 'bg-slate-200 text-slate-500 border border-slate-300 cursor-not-allowed'
                                    : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md shadow-emerald-500/10'"
                                :disabled="selRoom.has_approved_post || selRoom.is_frozen"
                                :title="selRoom.is_frozen ? 'Phòng này đang bị tạm đóng băng do vượt quá hạn mức gói dịch vụ' : ''"
                                @click="openEditRoom">
                                <span v-if="selRoom.is_frozen" class="flex items-center gap-1">
                                    <i class="bi bi-lock-fill"></i> Đã Đóng Băng
                                </span>
                                <span v-else>Sửa thông tin</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Room Modal Form -->
            <RoomFormModal :show="showForm" :isEdit="isEditing" :hideFloorSelect="hideFloorSelect" :room="selRoom"
                :floors="floors || props.floors || allFloors || []" v-model:floorId="formFloorId"
                :floorName="currentFloorName" :statusConfig="statusConfig" :existingRooms="currentFloorRooms"
                :services="services" @close="showForm = false" @submitted="submitRoom" />

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

<style scoped>
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
    transition: all 0.2s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
