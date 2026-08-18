<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed, watch, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import CustomSwal, {
    showSuccess,
    showWarning,
    showConfirm,
    showPrompt,
    showError,
} from "@/Utils/swal";
import axios from "axios";

const props = defineProps({
    dbContracts: Array,
    appointments: Array,
    boardingHouses: Array,
    authLandlord: Object,
    pendingRoommateRequests: Array,
    selectedBoardingHouseId: [Number, String],
});

const contracts = computed(() => {
    return (props.dbContracts || []).map((c) => ({
        id: c.id,
        hash_id: c.hash_id,
        room: c.room ? c.room.room_number : "",
        tenant: c.tenant ? c.tenant.name : "",
        phone: c.tenant ? c.tenant.phone : "",
        tenant_cccd: c.tenant ? c.tenant.cccd_number : "",
        start: c.start_date,
        end: c.end_date,
        rent: c.monthly_rent,
        deposit: c.deposit_amount,
        depositPaid: true,
        status: c.status,
        ocr_status: c.ocr_status,
        ocr_rejection_reason: c.ocr_rejection_reason,
        terms_accepted: c.terms_accepted,
        original_contract: c,
    }));
});

// --- PHÂN TRANG CHO HỢP ĐỒNG ---
const contractPage = ref(1);
const contractPageSize = 10;
const contractTotalPages = computed(
    () => Math.ceil(contracts.value.length / contractPageSize) || 1,
);
const paginatedContracts = computed(() => {
    const start = (contractPage.value - 1) * contractPageSize;
    return contracts.value.slice(start, start + contractPageSize);
});
watch(contracts, () => {
    contractPage.value = 1;
});

// --- PHÂN TRANG CHO YÊU CẦU Ở GHÉP ---
const roommatePage = ref(1);
const roommatePageSize = 5;
const roommateTotalPages = computed(
    () =>
        Math.ceil(
            (props.pendingRoommateRequests?.length || 0) / roommatePageSize,
        ) || 1,
);
const paginatedRoommateRequests = computed(() => {
    const list = props.pendingRoommateRequests || [];
    const start = (roommatePage.value - 1) * roommatePageSize;
    return list.slice(start, start + roommatePageSize);
});
watch(
    () => props.pendingRoommateRequests,
    () => {
        roommatePage.value = 1;
    },
);

//hiển thị khách huỷ trong giao diện
const rejectAppointment = async (aptId) => {
    const reason = await showPrompt(
        "Hủy lịch hẹn & Giải phóng phòng",
        "Nhập lý do loại bỏ khách này khỏi danh sách chờ hợp đồng và giải phóng phòng:",
        "Ví dụ: Khách hẹn không đến / Khách đã đổi ý..."
    );

    if (reason) {
        router.post(
            route("landlord.appointments.confirm_cancel", aptId),
            { cancellation_reason: reason },
            {
                onSuccess: () => {
                    showSuccess(
                        "Thành công",
                        "Đã loại bỏ lịch hẹn và gửi lý do cho khách thành công!",
                    );
                },
                onError: (errs) => {
                    showError("Lỗi", Object.values(errs).join("\n"));
                },
            },
        );
    }
};

// Helper hiển thị danh sách trang có dấu 3 chấm
const getVisiblePages = (currentPage, totalPages) => {
    const pages = [];
    const maxVisible = 5;
    if (totalPages <= maxVisible) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (currentPage > 3) pages.push("...");

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (currentPage < totalPages - 2) pages.push("...");
        pages.push(totalPages);
    }
    return pages;
};

const showModal = ref(false);
const showAddModal = ref(false);
const showExtendModal = ref(false);
const showLiquidationModal = ref(false);
const showPendingRoommateModal = ref(false);
const showPendingRequestsModal = ref(false);
const showAddResidentModal = ref(false);
const selectedContract = ref(null);
// Ngày bắt đầu được phép lùi về quá khứ tối đa 1 tháng
const minStartDate = computed(() => {
    const d = new Date();
    d.setMonth(d.getMonth() - 1);
    return d.toISOString().split("T")[0];
});

// Ngày kết thúc không cho chọn quá khứ (phải lớn hơn hoặc bằng ngày bắt đầu và từ hôm nay)
const minDate = computed(() => {
    const today = new Date().toISOString().split("T")[0];
    if (addForm.value.start_date && addForm.value.start_date > today) {
        return addForm.value.start_date;
    }
    return today;
});

// Lấy danh sách cư dân ở ghép hiện có của phòng đang được chọn trong form tạo hợp đồng
const availableRoomResidents = computed(() => {
    if (!addForm.value.room_id) return [];

    let targetRoom = null;
    (props.boardingHouses || []).forEach((bh) => {
        (bh.floors || []).forEach((f) => {
            (f.rooms || []).forEach((r) => {
                if (String(r.id) === String(addForm.value.room_id)) {
                    targetRoom = r;
                }
            });
        });
    });
    return (
        targetRoom?.residents?.filter((res) => res.status === "active") || []
    );
});

// Danh sách người ở ghép thực sự của hợp đồng đang xem (Đã lọc bỏ Chủ hợp đồng chính)
const filteredContractRoommates = computed(() => {
    const orig = selectedContract.value?.original_contract;
    if (!orig || !orig.room || !Array.isArray(orig.room.residents)) return [];
    const tenantId = orig.tenant_id || (orig.tenant && orig.tenant.id);
    return orig.room.residents.filter(
        (r) => String(r.user_id) !== String(tenantId),
    );
});

const residentForm = ref({
    phone: "",
    start_date: new Date().toISOString().split("T")[0],
});

// Chế độ tạo hợp đồng và lựa chọn cư dân ở ghép
const creationMode = ref("appointment"); // 'appointment', 'roommate' hoặc 'direct'
const selectedBoardingHouseId = ref("");
const selectedResidentId = ref("");

// State tìm kiếm Khách thuê cho Luồng C (trực tiếp)
const searchQuery = ref("");
const searchResults = ref([]);
const selectedDirectUser = ref(null);
let searchTimeout = null;

const performSearchTenant = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!searchQuery.value || searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get("/landlord/search-tenant", {
                params: { q: searchQuery.value.trim() },
            });
            searchResults.value = response.data || [];
        } catch (err) {
            searchResults.value = [];
        }
    }, 300);
};

const selectDirectUser = (user) => {
    selectedDirectUser.value = user;
    addForm.value.tenant_id = user.id;
    searchQuery.value = `${user.name} (SĐT: ${user.phone || "---"})`;
    searchResults.value = [];
};

const openContractForAppointment = (apt) => {
    showPendingRequestsModal.value = false;
    openAddContract(apt.id);
};

const residentStep = ref(1);
const selectedRoommateReq = ref(null);

const isRoommateCccdValid = computed(() => {
    const cccd =
        selectedRoommateReq.value?.new_resident_cccd ||
        selectedRoommateReq.value?.tenant?.cccd_number;
    return cccd && String(cccd).trim().length === 12;
});

const openAddResidentModal = () => {
    selectedRoommateReq.value = null;
    residentStep.value = 1;
    residentForm.value = {
        phone: "",
        start_date: new Date().toISOString().split("T")[0],
    };
    showAddResidentModal.value = true;
};

const openAddResidentFromRequest = (req) => {
    selectedRoommateReq.value = req;
    residentStep.value = 1;
    residentForm.value = {
        room_id: req.room_id,
        phone: req.new_resident_phone || req.tenant?.phone || "",
        start_date: new Date().toISOString().split("T")[0],
    };
    showPendingRoommateModal.value = false;
    showAddResidentModal.value = true;
};

const submitAddResident = () => {
    if (!residentForm.value.phone) {
        showWarning(
            "Thiếu thông tin",
            "Vui lòng nhập Số điện thoại của thành viên ở ghép!",
        );
        return;
    }
    const roomId =
        residentForm.value.room_id ||
        selectedContract.value?.original_contract?.room_id;
    if (!roomId) {
        showError("Lỗi", "Không tìm thấy thông tin phòng.");
        return;
    }
    const currentContractId = selectedContract.value?.id;
    router.post(`/landlord/rooms/${roomId}/residents`, residentForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddResidentModal.value = false;
            showSuccess("Thành công", "Đã thêm thành viên ở ghép thành công");
            router.reload({
                only: ["dbContracts", "pendingRoommateRequests"],
                onSuccess: () => {
                    if (selectedContract.value && currentContractId) {
                        const updatedC = props.dbContracts.find(
                            (c) => c.id === currentContractId,
                        );
                        if (updatedC) {
                            selectedContract.value.original_contract = updatedC;
                        }
                    }
                },
            });
        },
        onError: (errs) => {
            showError("Lỗi", Object.values(errs).join("\n"));
        },
    });
};

//hàm mở modal tạo hợp đồng tự điền phòng
const openContractForRoommateRequest = (req) => {
    showPendingRoommateModal.value = false;
    openAddContract();
    creationMode.value = "roommate";
    selectedBoardingHouseId.value = req.room?.boarding_house_id || "";
    addForm.value.room_id = req.room_id;
    addForm.value.room = req.room ? req.room.room_number : "";
    addForm.value.rent = req.room
        ? Math.round(Number(req.room.price))
        : 3000000;
    addForm.value.deposit = req.room
        ? Math.round(Number(req.room.price))
        : 3000000;
    addForm.value.tenant_id = req.tenant_id;
};

const removeResident = async (resident) => {
    if (!resident) return;

    const roomId =
        resident.room_id ||
        selectedContract.value?.original_contract?.room_id ||
        selectedContract.value?.original_contract?.room?.id ||
        selectedContract.value?.room?.id ||
        selectedContract.value?.room_id;

    if (!roomId) {
        showError("Lỗi", "Không tìm thấy ID phòng trọ.");
        return;
    }

    const confirmed = await showConfirm(
        "Xóa thành viên ở ghép",
        `Bạn có chắc chắn muốn xóa thành viên ${resident.user?.name || "này"} ra khỏi phòng?`,
        "Xóa",
        "Hủy",
    );

    if (confirmed) {
        const currentContractId = selectedContract.value?.id;
        router.delete(`/landlord/rooms/${roomId}/residents/${resident.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showSuccess(
                    "Thành công",
                    "Đã xóa thành viên ở ghép khỏi phòng.",
                );
                router.reload({
                    only: ["dbContracts"],
                    onSuccess: () => {
                        // Kiểm tra an toàn trước khi gán lại contract
                        if (selectedContract.value && currentContractId) {
                            const updatedC = props.dbContracts.find(
                                (c) => c.id === currentContractId,
                            );
                            if (updatedC) {
                                selectedContract.value.original_contract =
                                    updatedC;
                            }
                        }
                    },
                });
            },
            onError: (errs) => {
                showError("Lỗi", Object.values(errs).join("\n"));
            },
        });
    }
};

const statusMap = {
    pending: {
        label: "Chờ ký/duyệt",
        code: "Chờ duyệt",
        cls: "bg-amber-50 text-amber-600 border-amber-150",
        dot: "bg-amber-500",
    },
    signed: {
        label: "Đã ký kết",
        code: "Đã ký",
        cls: "bg-blue-50 text-blue-600 border-blue-150",
        dot: "bg-blue-500",
    },
    awaiting_upload: {
        label: "Chờ Upload",
        code: "Chờ Upload",
        cls: "bg-amber-50 text-amber-600 border-amber-150",
        dot: "bg-amber-500",
    },
    active: {
        label: "Đang Hiệu Lực",
        code: "Hiệu lực",
        cls: "bg-emerald-50 text-emerald-600 border-emerald-150",
        dot: "bg-emerald-500",
    },
    expiring: {
        label: "Sắp Hết Hạn",
        code: "Sắp hết hạn",
        cls: "bg-orange-50 text-orange-600 border-orange-150",
        dot: "bg-orange-500",
    },
    expired: {
        label: "Đã Hết Hạn",
        code: "Đã Hết Hạn",
        cls: "bg-rose-50 text-rose-600 border-rose-150",
        dot: "bg-rose-500",
    },
    terminated: {
        label: "Đã Thanh Lý",
        code: "Đã Thanh Lý",
        cls: "bg-slate-50 text-slate-500 border-slate-150",
        dot: "bg-slate-500",
    },
    termination_requested: {
        label: "Yêu Cầu Chấm Dứt",
        code: "Yêu cầu chấm dứt",
        cls: "bg-orange-50 text-orange-600 border-orange-200",
        dot: "bg-orange-500",
    },
    cancelled: {
        label: "Đã Hủy",
        code: "Đã Hủy",
        cls: "bg-slate-50 text-slate-500 border-slate-150",
        dot: "bg-slate-500",
    },
    draft: {
        label: "Bản Nháp",
        code: "Bản Nháp",
        cls: "bg-slate-50 text-slate-600 border-slate-150",
        dot: "bg-slate-500",
    },
};

const defaultStatus = {
    label: "Khác",
    cls: "bg-slate-50 text-slate-500 border-slate-150",
    dot: "bg-slate-400",
};
const getStatusConfig = (status) => statusMap[status] || defaultStatus;

const expiringCount = computed(
    () => contracts.value.filter((c) => c.status === "expiring").length,
);
const openContract = (c) => {
    selectedContract.value = c;
    showModal.value = true;
};
const closeModal = () => {
    showModal.value = false;
    selectedContract.value = null;
};

const formatMoney = (n) => new Intl.NumberFormat("vi-VN").format(n || 0) + "đ";
const formatDate = (d) => (d ? new Date(d).toLocaleDateString("vi-VN") : "---");

// 3-step contract creation state
const activeStep = ref(1); // 1: Tenant & Room, 2: Terms & Period, 3: Direct Upload

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        addForm.value.contract_file = file;
    }
};

const getInitialAddForm = (appointmentId = "") => {
    return {
        appointment_id: appointmentId,
        room_id: "",
        room: "",
        rent: 3000000,
        deposit: 3000000,
        start_date: new Date().toISOString().split("T")[0],
        end_date: "",
        number_of_tenants: 1,
        billing_cycle: 1,
        contract_file: null,
        tenant_id: "", // Thêm trường này để lưu ID cư dân ở ghép
    };
};

const addForm = ref(getInitialAddForm());

// Hàm hiển thị tên phòng kèm trạng thái người ở ghép
const getRoomStatusLabel = (r) => {
    const activeResidents = (r.residents || []).filter(res => res.status === 'active');
    const residentCount = activeResidents.length || r.current_people || 0;

    if (residentCount > 0) {
        return `Phòng ${r.room_number} (Đang có ${residentCount} người ở ghép - Có thể nâng lên Chủ HĐ)`;
    }
    return `Phòng ${r.room_number} (Phòng trống)`;
};

// Lấy các phòng thuộc nhà trọ đang chọn
const availableRooms = computed(() => {
    if (!selectedBoardingHouseId.value) return [];
    const bh = (props.boardingHouses || []).find(
        (h) => String(h.id) === String(selectedBoardingHouseId.value),
    );
    if (!bh) return [];

    const roomMap = new Map();

    // Thu thập phòng từ bh.floors nếu có
    if (Array.isArray(bh.floors) && bh.floors.length > 0) {
        bh.floors.forEach((f) => {
            (f.rooms || []).forEach((r) => {
                if (
                    r &&
                    r.id &&
                    String(r.boarding_house_id || bh.id) ===
                    String(selectedBoardingHouseId.value)
                ) {
                    if (!roomMap.has(String(r.id))) {
                        roomMap.set(String(r.id), r);
                    }
                }
            });
        });
    }

    // Thu thập phòng từ bh.rooms nếu có
    if (Array.isArray(bh.rooms) && bh.rooms.length > 0) {
        bh.rooms.forEach((r) => {
            if (
                r &&
                r.id &&
                String(r.boarding_house_id || bh.id) ===
                String(selectedBoardingHouseId.value)
            ) {
                if (!roomMap.has(String(r.id))) {
                    roomMap.set(String(r.id), r);
                }
            }
        });
    }

    let all = Array.from(roomMap.values());

    // Nếu là Luồng B (Nâng ở ghép lên làm Chủ HĐ): Chỉ lọc những phòng thực sự có cư dân ở ghép
    if (creationMode.value === "roommate") {
        return all.filter((r) => {
            const activeRes = (r.residents || []).filter(
                (res) => res.status === "active",
            );
            return activeRes.length > 0 || r.current_people > 0;
        });
    }

    return all;
});

// Lọc cư dân ở ghép trong phòng đã chọn
const activeRoomResidents = computed(() => {
    if (creationMode.value !== "roommate" || !addForm.value.room_id) return [];
    const room = availableRooms.value.find(
        (r) => String(r.id) === String(addForm.value.room_id),
    );
    return room?.residents || [];
});

// Lấy thông tin cư dân được chọn
const selectedResidentOption = computed(() => {
    if (!selectedResidentId.value) return null;
    const res = activeRoomResidents.value.find(
        (r) => String(r.id) === String(selectedResidentId.value),
    );
    if (!res) return null;
    return res.user || res;
});

watch(selectedBoardingHouseId, () => {
    addForm.value.room_id = "";
    addForm.value.room = "";
    selectedResidentId.value = "";
    addForm.value.tenant_id = "";
});

watch(creationMode, () => {
    selectedBoardingHouseId.value =
        props.selectedBoardingHouseId || props.boardingHouses?.[0]?.id || "";
    addForm.value.room_id = "";
    addForm.value.room = "";
    addForm.value.appointment_id = "";
    addForm.value.tenant_id = "";
    selectedResidentId.value = "";
    selectedDirectUser.value = null;
    searchQuery.value = "";
    searchResults.value = [];
});

watch(
    () => addForm.value.appointment_id,
    (newVal) => {
        if (newVal && creationMode.value === "appointment") {
            const apt = props.appointments.find(
                (a) => String(a.id) === String(newVal),
            );
            if (apt) {
                const bhId =
                    apt.room?.boarding_house_id || apt.room?.boardingHouse?.id;
                if (bhId) {
                    selectedBoardingHouseId.value = bhId;
                }
                addForm.value.room_id = apt.room_id || "";
                addForm.value.room = apt.room ? apt.room.room_number : "";
                addForm.value.rent = apt.room
                    ? Math.round(Number(apt.room.price))
                    : 3000000;
                addForm.value.deposit = apt.room
                    ? Math.round(Number(apt.room.price))
                    : 3000000;

                // Ở ghép logic: tự động lùi ngày bắt đầu sau 7 ngày nếu phòng đang có người ở
                const currentPeople = apt.room
                    ? apt.room.current_people || 0
                    : 0;
                if (currentPeople > 0) {
                    const futureDate = new Date();
                    futureDate.setDate(futureDate.getDate() + 7);
                    addForm.value.start_date = futureDate
                        .toISOString()
                        .split("T")[0];
                } else {
                    addForm.value.start_date = new Date()
                        .toISOString()
                        .split("T")[0];
                }
            }
        }
    },
);

watch(
    () => addForm.value.room_id,
    (newVal) => {
        if (newVal && creationMode.value === "roommate") {
            const room = availableRooms.value.find(
                (r) => String(r.id) === String(newVal),
            );
            if (room) {
                addForm.value.room = room.room_number;
                addForm.value.rent = Math.round(Number(room.price));
                addForm.value.deposit = Math.round(Number(room.price));
                selectedResidentId.value = "";
                addForm.value.tenant_id = "";
            }
        }
    },
);

watch(selectedResidentId, (newVal) => {
    if (newVal) {
        const res = activeRoomResidents.value.find(
            (r) => String(r.id) === String(newVal),
        );
        if (res) {
            addForm.value.tenant_id = res.user_id;
        }
    } else {
        addForm.value.tenant_id = "";
    }
});

watch(
    () => addForm.value.start_date,
    (newVal) => {
        if (newVal) {
            const startDate = new Date(newVal);
            startDate.setDate(startDate.getDate() + 30);
            const newMinEndDate = startDate.toISOString().split("T")[0];
            if (
                !addForm.value.end_date ||
                addForm.value.end_date < newMinEndDate
            ) {
                addForm.value.end_date = newMinEndDate;
            }
        }
    },
);
//tự động làm mới phòng & user được chọn khi thay đổi cở sở
watch(selectedBoardingHouseId, () => {
    addForm.value.room_id = "";
    addForm.value.room = "";
    selectedResidentId.value = "";
    addForm.value.tenant_id = "";
});

const displayRent = computed({
    get() {
        if (
            addForm.value.rent === null ||
            addForm.value.rent === undefined ||
            addForm.value.rent === ""
        )
            return "";
        return new Intl.NumberFormat("en-US").format(addForm.value.rent);
    },
    set(val) {
        const raw = String(val).replace(/\D/g, "");
        addForm.value.rent = raw ? parseInt(raw, 10) : 0;
    },
});

const displayDeposit = computed({
    get() {
        if (
            addForm.value.deposit === null ||
            addForm.value.deposit === undefined ||
            addForm.value.deposit === ""
        )
            return "";
        return new Intl.NumberFormat("en-US").format(addForm.value.deposit);
    },
    set(val) {
        const raw = String(val).replace(/\D/g, "");
        addForm.value.deposit = raw ? parseInt(raw, 10) : 0;
    },
});

const openAddContract = (appointmentId = "") => {
    activeStep.value = 1;
    creationMode.value = "appointment"; // Active mặc định là Ký từ Lịch hẹn
    selectedBoardingHouseId.value =
        props.selectedBoardingHouseId || props.boardingHouses?.[0]?.id || "";
    selectedResidentId.value = "";
    searchQuery.value = "";
    searchResults.value = [];
    selectedDirectUser.value = null;
    addForm.value = getInitialAddForm(appointmentId);

    if (appointmentId) {
        const apt = props.appointments.find(
            (a) => String(a.id) === String(appointmentId),
        );
        if (apt) {
            addForm.value.room_id = apt.room_id || "";
            addForm.value.room = apt.room ? apt.room.room_number : "";
            addForm.value.rent = apt.room
                ? Math.round(Number(apt.room.price))
                : 3000000;
            addForm.value.deposit = apt.room
                ? Math.round(Number(apt.room.price))
                : 3000000;

            const currentPeople = apt.room ? apt.room.current_people || 0 : 0;
            if (currentPeople > 0) {
                const futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 7);
                addForm.value.start_date = futureDate
                    .toISOString()
                    .split("T")[0];
            } else {
                addForm.value.start_date = new Date()
                    .toISOString()
                    .split("T")[0];
            }
        }
    }
    showAddModal.value = true;
};

onMounted(() => {
    selectedBoardingHouseId.value =
        props.selectedBoardingHouseId || props.boardingHouses?.[0]?.id || "";
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("action") === "create_contract") {
        const appointmentId = urlParams.get("appointment_id");
        if (appointmentId) {
            openAddContract(parseInt(appointmentId));
            setTimeout(() => {
                router.visit(window.location.pathname, {
                    replace: true,
                    preserveState: true,
                    preserveScroll: true,
                });
            }, 100);
        }
    }
});

const selectedAppointment = computed(() => {
    if (!addForm.value.appointment_id) return null;
    return (
        props.appointments?.find(
            (a) => String(a.id) === String(addForm.value.appointment_id),
        ) || null
    );
});

const selectedRoomData = computed(() => {
    if (creationMode.value === "appointment") {
        return selectedAppointment.value?.room || null;
    } else {
        if (!addForm.value.room_id) return null;
        return (
            availableRooms.value.find(
                (r) => String(r.id) === String(addForm.value.room_id),
            ) || null
        );
    }
});

const selectedRoomCapacity = computed(() => {
    return selectedRoomData.value?.capacity ?? 2;
});

const selectedRoomCurrentPeople = computed(() => {
    return selectedRoomData.value?.current_people ?? 0;
});

const isSharingRoom = computed(() => {
    return selectedRoomCurrentPeople.value > 0;
});

const maxAvailableTenants = computed(() => {
    const cap = selectedRoomCapacity.value;
    const curr = selectedRoomCurrentPeople.value;
    return Math.max(1, cap - curr);
});

const tenantCountErrorMsg = computed(() => {
    const num = Number(addForm.value.number_of_tenants || 0);
    if (!num || num < 1) {
        return "Số lượng người ở phải lớn hơn 0.";
    }
    if (creationMode.value === "roommate") return null;

    if (selectedRoomData.value) {
        const max = maxAvailableTenants.value;
        if (num > max) {
            return `⚠️ Số người ở (${num} người) vượt quá sức chứa còn lại (Phòng tối đa ${selectedRoomCapacity.value} người, hiện có ${selectedRoomCurrentPeople.value} người, chỉ thêm được tối đa ${max} người).`;
        }
    }
    return null;
});

const tenantCccd = computed(() => {
    if (creationMode.value === "appointment") {
        return selectedAppointment.value?.user?.cccd_number || "";
    } else if (creationMode.value === "roommate") {
        return selectedResidentOption.value?.cccd_number || "";
    } else if (creationMode.value === "direct") {
        return selectedDirectUser.value?.cccd_number || "";
    }
    return "";
});

const isCccdValid = computed(() => {
    const cccd = String(tenantCccd.value).trim();
    return cccd.length === 12 && /^\d+$/.test(cccd);
});

const isStep1Valid = computed(() => {
    if (creationMode.value === "appointment") {
        return (
            !!addForm.value.appointment_id &&
            isCccdValid.value &&
            !tenantCountErrorMsg.value
        );
    } else if (creationMode.value === "roommate") {
        return (
            !!addForm.value.room_id &&
            !!addForm.value.tenant_id &&
            isCccdValid.value &&
            !tenantCountErrorMsg.value
        );
    } else if (creationMode.value === "direct") {
        return (
            !!addForm.value.room_id &&
            !!selectedDirectUser.value &&
            isCccdValid.value &&
            !tenantCountErrorMsg.value
        );
    }
    return false;
});

const goToNextStep = () => {
    if (activeStep.value === 1) {
        if (creationMode.value === "appointment") {
            if (!addForm.value.appointment_id) {
                showWarning(
                    "Bắt buộc chọn người thuê",
                    "Vui lòng chọn khách thuê từ Lịch hẹn trước khi tiếp tục!",
                );
                return;
            }
        } else if (creationMode.value === "roommate") {
            if (!addForm.value.room_id || !addForm.value.tenant_id) {
                showWarning(
                    "Bắt buộc chọn khách thuê & phòng trọ",
                    "Vui lòng chọn khách thuê và phòng trọ trước khi tiếp tục!",
                );
                return;
            }
        } else if (creationMode.value === "direct") {
            if (!addForm.value.room_id || !selectedDirectUser.value) {
                showWarning(
                    "Bắt buộc chọn phòng trọ & tìm khách thuê",
                    "Vui lòng chọn phòng trọ và tìm chọn tài khoản khách thuê trước khi tiếp tục!",
                );
                return;
            }
        }
        if (!isCccdValid.value) {
            showError(
                "Lỗi CCCD",
                "Khách thuê chưa cập nhật đúng số CCCD 12 số trong profile. Không thể tiếp tục.",
            );
            return;
        }
        if (tenantCountErrorMsg.value) {
            showError("Vượt quá sức chứa phòng", tenantCountErrorMsg.value);
            return;
        }
        activeStep.value = 2;
    } else if (activeStep.value === 2) {
        if (!addForm.value.start_date || !addForm.value.end_date) {
            showWarning(
                "Thiếu thông tin",
                "Vui lòng điền đầy đủ Ngày bắt đầu và Ngày kết thúc hợp đồng!",
            );
            return;
        }
        activeStep.value = 3;
    }
};

const goToStep = (step) => {
    if (step > 1) {
        if (creationMode.value === "appointment") {
            if (!addForm.value.appointment_id) {
                showWarning(
                    "Bắt buộc chọn người thuê",
                    "Vui lòng chọn khách thuê trước.",
                );
                return;
            }
        } else {
            if (!addForm.value.room_id || !addForm.value.tenant_id) {
                showWarning(
                    "Bắt buộc chọn khách thuê & phòng trọ",
                    "Vui lòng chọn phòng và khách thuê trước.",
                );
                return;
            }
        }
        if (!isCccdValid.value) {
            showError(
                "Lỗi CCCD",
                "Khách thuê chưa cập nhật đúng số CCCD 12 số trong profile.",
            );
            return;
        }
    }
    if (step > 2) {
        if (!addForm.value.start_date || !addForm.value.end_date) {
            showWarning(
                "Thiếu thông tin",
                "Vui lòng điền đầy đủ thời hạn hợp đồng ở Bước 2.",
            );
            return;
        }
    }
    activeStep.value = step;
};

const checkTenantActiveContract = (tenantId) => {
    if (!tenantId) return null;
    const activeStatuses = [
        "active",
        "signed",
        "pending",
        "awaiting_upload",
        "termination_requested",
        "expiring",
    ];
    return (props.dbContracts || []).find((c) => {
        if (!activeStatuses.includes(c.status)) return false;
        const cTenantId = c.tenant_id || (c.tenant && c.tenant.id);
        if (String(cTenantId) === String(tenantId)) return true;
        return false;
    });
};

const submitAddContract = () => {
    const tenantId =
        creationMode.value === "appointment"
            ? selectedAppointment.value?.user_id
            : creationMode.value === "roommate"
                ? selectedResidentOption.value?.id || addForm.value.tenant_id
                : addForm.value.tenant_id;

    if (!tenantId) {
        showWarning("Thiếu thông tin", "Vui lòng chọn người thuê!");
        return;
    }
    if (!isCccdValid.value) {
        showError(
            "Chưa cập nhật CCCD",
            "Khách thuê bắt buộc phải cập nhật CCCD 12 số trước khi tạo hợp đồng!",
        );
        return;
    }
    if (!addForm.value.start_date || !addForm.value.end_date) {
        showWarning(
            "Thiếu thông tin",
            "Vui lòng điền đầy đủ Ngày bắt đầu và Ngày kết thúc!",
        );
        return;
    }
    if (!addForm.value.contract_file) {
        showWarning(
            "Thiếu file hợp đồng",
            "Vui lòng tải lên ảnh chụp hoặc file PDF hợp đồng giấy đã ký!",
        );
        return;
    }

    const activeContract = checkTenantActiveContract(tenantId);
    if (activeContract && creationMode.value !== "roommate") {
        showError(
            "Không thể tạo hợp đồng!",
            "Khách thuê này đã có hợp đồng đang có hiệu lực trong hệ thống.",
        );
        return;
    }

    const payload = new FormData();
    if (creationMode.value === "appointment") {
        payload.append("appointment_id", addForm.value.appointment_id);
        if (addForm.value.room_id) {
            payload.append("room_id", addForm.value.room_id);
        }
    } else {
        payload.append("room_id", addForm.value.room_id);
        payload.append("tenant_id", tenantId);
    }
    payload.append("start_date", addForm.value.start_date);
    payload.append("end_date", addForm.value.end_date);
    payload.append("deposit", addForm.value.deposit);
    payload.append("billing_cycle", addForm.value.billing_cycle);
    payload.append("number_of_tenants", addForm.value.number_of_tenants);
    payload.append("contract_file", addForm.value.contract_file);

    router.post("/landlord/contracts/store-draft", payload, {
        forceFormData: true,
        onSuccess: () => {
            showAddModal.value = false;
            showSuccess(
                "Thành công",
                "Hợp đồng đã được ký kết và kích hoạt thành công!",
            );
        },
        onError: (errs) => {
            showError("Lỗi", Object.values(errs).join("\n"));
        },
    });
};

// Extend Contract State
const extendForm = ref({ new_end_date: "", tenant_cccd: "", notes: "" });
const openExtendModal = (c = null) => {
    //tự đồng lấy hợp đồng từ tham số c hoặc hợp đồng đang chọn
    const target = c && c.status ? c : selectedContract.value;
    if (!target) return;
    selectedContract.value = target;
    const oldEndDateStr =
        target.original_contract?.end_date?.split("T")[0] ||
        (target.end ? target.end.split("T")[0] : "");
    // Tự động tính sẵn ngày hết hạn mới (+6 tháng)
    let suggestedDate = "";
    if (oldEndDateStr) {
        const d = new Date(oldEndDateStr);
        d.setMonth(d.getMonth() + 6);
        suggestedDate = d.toISOString().split("T")[0];
    }
    extendForm.value = {
        new_end_date: suggestedDate,
        tenant_cccd:
            target.tenant_cccd ||
            target.original_contract?.tenant?.cccd_number ||
            "",
        notes: target.original_contract?.cancellation_reason || "",
    };
    showExtendModal.value = true;
};

const submitExtendContract = () => {
    if (!extendForm.value.new_end_date) {
        showWarning("Thiếu thông tin", "Vui lòng chọn ngày hết hạn mới.");
        return;
    }
    if (
        !extendForm.value.tenant_cccd ||
        extendForm.value.tenant_cccd.length !== 12
    ) {
        showWarning(
            "Thiếu thông tin pháp lý",
            "Bắt buộc nhập số CCCD/CMND 12 chữ số của Khách thuê trước khi gia hạn.",
        );
        return;
    }
    router.post(
        `/landlord/contracts/${selectedContract.value.hash_id}/extend`,
        extendForm.value,
        {
            onSuccess: () => {
                showExtendModal.value = false;
                showModal.value = false;
                showSuccess(
                    "Gia hạn thành công",
                    "Đã lưu thời hạn hợp đồng mới và thông tin pháp lý!",
                );
            },
        },
    );
};

// Liquidation State
const liquidationForm = ref({
    deposit_handling: "refund_full",
    deposit_refund_amount: 0,
    notes: "",
});

//tự động tính tiền hoàn cọc khi chủ trọ thay đổi phương án
watch(() =>
    liquidationForm.value.deposit_handling, (newVal) => {
        const deposit = selectedContract.value?.deposit || selectedContract.value?.monthly_rent || 0;
        if (newVal === 'refund_full') {
            liquidationForm.value.deposit_refund_amount = deposit;
        } else if (newVal === 'keep_deposit') {
            liquidationForm.value.deposit_refund_amount = 0;
        }
    });


const openLiquidationModal = (c) => {
    const target = c && c.status ? c : selectedContract.value;
    if (!target) return;

    const allowedStatuses = ["active", "signed", "expiring", "expired", "termination_requested"];
    if (!allowedStatuses.includes(target.status)) {
        showWarning(
            "Chưa thể thanh lý!",
            "Chỉ hợp đồng đang hoạt động hoặc báo hết hạn mới được thực hiện thanh lý.",
        );
        return;
    }

    selectedContract.value = target;
    liquidationForm.value = {
        deposit_handling: "refund_full",
        deposit_refund_amount: target.deposit || target.rent || 0,
        notes: "",
    };
    showLiquidationModal.value = true;
};

const submitLiquidationContract = () => {
    router.post(
        route("landlord.contracts.liquidate", selectedContract.value.hash_id),
        liquidationForm.value,
        {
            onSuccess: () => {
                showLiquidationModal.value = false;
                showModal.value = false;
                showAddModal.value = false;
                showSuccess(
                    "Thành công",
                    "Đã thang lý hợp đồng và giải phóng phòng trọ!",
                );
            },
        },
    );
};

// Manual Scan Action
const isScanning = ref(false);
const triggerScan = () => {
    isScanning.value = true;
    router.post(
        "/landlord/contracts/scan",
        {},
        {
            onFinish: () => {
                isScanning.value = false;
            },
            onSuccess: () => {
                showSuccess(
                    "Thành công",
                    "Đã quét và tự động cập nhật trạng thái hợp đồng mới nhất!",
                );
            },
        },
    );
};

// Mark as Expired Action
const submitMarkExpired = async () => {
    if (!selectedContract.value) return;

    const todayStr = new Date().toISOString().split("T")[0];
    const endDateStr = selectedContract.value.end
        ? selectedContract.value.end.split("T")[0]
        : "";
    const isEarly = endDateStr && endDateStr > todayStr;

    if (isEarly) {
        const result = await CustomSwal.fire({
            icon: "warning",
            title: "Chấm Dứt Hợp Đồng Trước Thời Hạn",
            text:
                "Hợp đồng này chưa đến ngày hết hạn quy định (" +
                formatDate(selectedContract.value.end) +
                "). Vui lòng nhập lý do chấm dứt trước thời hạn:",
            input: "textarea",
            inputPlaceholder: "Nhập lý do chấm dứt sớm...",
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return "Vui lòng nhập lý do chấm dứt trước thời hạn!";
                }
            },
            showCancelButton: true,
            confirmButtonText: "Xác nhận chấm dứt sớm",
            cancelButtonText: "Hủy bỏ",
            reverseButtons: true,
        });

        if (result.isConfirmed && result.value) {
            router.post(
                route(
                    "landlord.contracts.expire",
                    selectedContract.value.hash_id,
                ),
                { reason: result.value },
                {
                    onSuccess: () => {
                        showModal.value = false;
                        showSuccess(
                            "Đã cập nhật",
                            "Đã chấm dứt hợp đồng sớm và chuyển sang trạng thái Hết hạn (Chờ thanh lý).",
                        );
                    },
                },
            );
        }
    } else {
        const confirmed = await showConfirm(
            "Chuyển sang Trạng Thế Hết Hạn",
            "Bạn có chắc muốn chuyển hợp đồng này sang trạng thái Hết Hạn (Expired) để tiến hành Thanh lý hoặc Gia hạn?",
            "Chuyển Hết Hạn",
            "Đóng",
        );
        if (confirmed) {
            router.post(
                `/landlord/contracts/${selectedContract.value.hash_id || selectedContract.value.id}/expire`,
                {},
                {
                    onSuccess: () => {
                        showModal.value = false;
                        showSuccess(
                            "Đã cập nhật",
                            "Hợp đồng đã chuyển sang trạng thái Hết Hạn (expired).",
                        );
                    },
                },
            );
        }
    }
};
// Lọc danh sách lịch hẹn theo nhà trọ (cơ sở) được chọn
const filteredAppointments = computed(() => {
    if (!props.appointments || props.appointments.length === 0) return [];
    if (!selectedBoardingHouseId.value) return props.appointments;
    return props.appointments.filter((apt) => {
        const bhId = apt.room?.boarding_house_id || apt.room?.boardingHouse?.id;
        return !bhId || String(bhId) === String(selectedBoardingHouseId.value);
    });
});

//hàm hiển thị trạng thái tiền cọc
const getDepositBadgeConfig = (c) => {
    const orig = c.original_contract || c;
    const handling = orig.deposit_handling;
    const status = orig.status;
    //trường hợp mất cọc ( Do huỷ HĐ còn hạn hoặc vi phạm)
    if (handling === "keep_deposit") {
        return {
            label: "Mất cọc (tịch thu)",
            cls: "bg-rose-50 text-rose-700 border-rose-200",
        };
    }
    //trường hợp hoàn trả 100% cọc
    if (handling === "refund_full") {
        return {
            label: "Đã trả 100% cọc",
            cls: "bg-emerald-50 text-emerald-700 border-emerald-200",
        };
    }
    //trường hợp khấu trừ cọc
    if (handling === "refund_partial") {
        const refundAmt = orig.deposit_refund_amount ? formatMoney(orig.deposit_refund_amount) : "";
        return {
            label: `Khấu trừ cọc ${refundAmt ? '(' + refundAmt + ')' : ''}`,
            cls: "bg-amber-50 text-amber-800 border-amber-200",
        };
    }
    //trường hợp hợp đồng đang hoạt động
    if (["active", "signed", "expiring", "awaiting_upload"].includes(status)) {
        return {
            label: "Đang giữ cọc",
            cls: "bg-blue-50 text-blue-700 border-blue-200",
        };
    }
    return {
        label: "Đã cọc",
        cls: "bg-slate-50 text-slate-600 border-slate-200",
    };
};
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Hợp đồng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">
                        Quản lý Hợp đồng
                    </h2>
                    <p class="text-xs text-slate-400">
                        Danh sách hợp đồng thuê phòng trực tiếp, kiểm duyệt CCCD
                        và upload hợp đồng giấy đính kèm
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="showPendingRequestsModal = true"
                        class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-amber-500/10 flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-clock-history"></i>
                        <span>Hợp đồng chờ (Khách ưng)</span>
                        <span v-if="props.appointments?.length > 0"
                            class="ml-1 px-1.5 py-0.5 bg-white text-amber-600 rounded-full text-[10px] font-black shadow-xs">
                            {{ props.appointments.length }}
                        </span>
                    </button>
                    <button @click="openAddContract('')"
                        class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                        <i class="bi bi-file-earmark-plus"></i> Ký hợp đồng mới
                    </button>
                    <button @click="showPendingRoommateModal = true"
                        class="relative px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-2xl shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Yêu cầu ở ghép ({{
                            props.pendingRoommateRequests?.length || 0
                            }})</span>
                        <!-- Chấm đỏ thông báo khi có yêu cầu ở ghép đang chờ -->
                        <span v-if="props.pendingRoommateRequests?.length > 0"
                            class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span
                                class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500 border-2 border-white"></span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Expiry Warning Alert -->
            <div v-if="expiringCount > 0"
                class="p-4 bg-amber-50/70 border border-amber-250 rounded-2xl flex items-center gap-3 text-xs text-amber-800 font-semibold shadow-sm">
                <i class="bi bi-clock-history text-lg text-amber-500"></i>
                <p>
                    Hiện đang có
                    <strong class="text-amber-950">{{ expiringCount }}</strong>
                    hợp đồng chuẩn bị hết hạn trong vòng 30 ngày tới. Vui lòng
                    chuyển trạng thái Hết Hạn để Thanh lý hoặc thực hiện Gia
                    hạn.
                </p>
            </div>

            <!-- Stats Deck -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- 1 -->
                <div
                    class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">
                            1. Đang hiệu lực (Signed)
                        </p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">
                            {{
                                contracts.filter(
                                    (c) =>
                                        c.status === "signed" ||
                                        c.status === "active",
                                ).length
                            }}
                        </h3>
                    </div>
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-file-check-fill"></i>
                    </div>
                </div>

                <!-- 2 -->
                <div
                    class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">
                            2. Sắp hết hạn
                        </p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">
                            {{
                                contracts.filter((c) => c.status === "expiring")
                                    .length
                            }}
                        </h3>
                    </div>
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                </div>

                <!-- 3 -->
                <div
                    class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">
                            3. Đã hết hạn (Chờ thanh lý)
                        </p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">
                            {{
                                contracts.filter((c) => c.status === "expired")
                                    .length
                            }}
                        </h3>
                    </div>
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>

                <!-- 4 -->
                <div
                    class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">
                            4. Đã thanh lý / hủy
                        </p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">
                            {{
                                contracts.filter(
                                    (c) =>
                                        c.status === "terminated" ||
                                        c.status === "cancelled",
                                ).length
                            }}
                        </h3>
                    </div>
                    <div
                        class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-check2-all"></i>
                    </div>
                </div>
            </div>

            <!-- Contracts Table (Desktop) -->
            <div class="hidden lg:block bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr
                                class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Mã HĐ</th>
                                <th class="py-3.5 px-4">Phòng</th>
                                <th class="py-3.5 px-4">Đại diện thuê</th>
                                <th class="py-3.5 px-4">Ngày hiệu lực</th>
                                <th class="py-3.5 px-4">Ngày kết thúc</th>
                                <th class="py-3.5 px-4">Đặt cọc</th>
                                <th class="py-3.5 px-4">Trạng thái</th>
                                <th class="py-3.5 px-6 text-right font-bold">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="(c, index) in paginatedContracts" :key="c.id" :class="[
                                'hover:bg-slate-50/40 cursor-pointer',
                                c.status === 'expiring'
                                    ? 'bg-amber-50/10'
                                    : '',
                                c.status === 'expired'
                                    ? 'bg-rose-50/20 font-bold'
                                    : '',
                            ]" @click="openContract(c)">
                                <td class="py-4 px-6 font-bold text-slate-800">
                                    #{{ c.id }}
                                </td>
                                <td class="py-4 px-4 font-bold text-emerald-600">
                                    {{ c.room }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-col">
                                        <span>{{ c.tenant }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">{{ c.phone }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-500">
                                    {{ formatDate(c.start) }}
                                </td>
                                <td class="py-4 px-4 text-slate-500" :class="{
                                    'text-rose-600 font-bold':
                                        c.status === 'expired',
                                }">
                                    {{ formatDate(c.end) }}
                                </td>
                                <td class="py-4 px-4">
                                    <span
                                        :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold border inline-block whitespace-nowrap shadow-xs', getDepositBadgeConfig(c).cls]">
                                        {{ getDepositBadgeConfig(c).label }}
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <span :title="getStatusConfig(c.status).label" :class="[
                                        'px-3 py-1 rounded-lg text-xs font-black border flex items-center gap-1.5 w-fit shadow-xs',
                                        getStatusConfig(c.status).cls,
                                    ]">
                                        <span class="w-2 h-2 rounded-full" :class="getStatusConfig(c.status).dot
                                            "></span>
                                        {{ getStatusConfig(c.status).code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openContract(c)"
                                            class="w-7 h-7 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center transition-colors"
                                            title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button v-if="
                                            c.original_contract
                                                ?.cancellation_reason &&
                                            c.original_contract.cancellation_reason.includes(
                                                'gia hạn',
                                            )
                                        " @click="openExtendModal(c)"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center gap-1 cursor-pointer animate-pulse"
                                            title="Khách đang xin gia hạn hợp đồng này">
                                            <i class="bi bi-arrow-repeat"></i>
                                            Gia hạn ngay
                                        </button>
                                        <a v-if="
                                            c.original_contract
                                                ?.contract_file_path
                                        " :href="`/storage/${c.original_contract.contract_file_path}`" target="_blank"
                                            class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center transition-colors"
                                            title="Tải/Xem File"><i class="bi bi-file-earmark-pdf"></i></a>
                                        <button v-if="
                                            c.status === 'expired' ||
                                            c.status ===
                                            'termination_requested'
                                        " @click="openLiquidationModal(c)"
                                            class="px-2.5 py-1 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center gap-1">
                                            <i class="bi bi-calculator"></i>
                                            Thanh lý
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile List -->
            <div class="block lg:hidden space-y-4">
                <div v-for="c in paginatedContracts" :key="c.id" :class="[
                    'bg-white border border-slate-150 rounded-3xl p-5 shadow-sm space-y-3',
                    c.status === 'expired'
                        ? 'border-rose-200 bg-rose-50/10'
                        : '',
                ]" @click="openContract(c)">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Hợp đồng #{{
                                c.id }}</span>
                            <div class="text-sm font-black text-slate-800 mt-0.5">
                                Phòng {{ c.room }}
                            </div>
                        </div>
                        <span :class="[
                            'px-3 py-1 rounded-lg text-xs font-black border flex items-center gap-1.5 w-fit shadow-xs',
                            getStatusConfig(c.status).cls,
                        ]">
                            <span class="w-2 h-2 rounded-full" :class="getStatusConfig(c.status).dot"></span>
                            {{ getStatusConfig(c.status).code }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs pt-2 border-t border-slate-50 font-semibold text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Người thuê:</span>
                            <span class="text-slate-700 font-bold">{{
                                c.tenant
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">SĐT:</span>
                            <span class="text-slate-700 font-bold">{{
                                c.phone
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Thời hạn:</span>
                            <span>{{ formatDate(c.start) }} -
                                {{ formatDate(c.end) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thanh Phân trang Hợp Đồng -->
            <div v-if="contractTotalPages > 1"
                class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
                <span class="text-[11px] text-slate-400 font-semibold">
                    Hiển thị {{ (contractPage - 1) * contractPageSize + 1 }} -
                    {{
                        Math.min(
                            contractPage * contractPageSize,
                            contracts.length,
                        )
                    }}
                    trong số {{ contracts.length }} hợp đồng
                </span>
                <div class="flex items-center gap-1">
                    <button @click="contractPage = Math.max(1, contractPage - 1)" :disabled="contractPage === 1"
                        class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                    <button v-for="p in getVisiblePages(
                        contractPage,
                        contractTotalPages,
                    )" :key="p" @click="
                        typeof p === 'number' ? (contractPage = p) : null
                        " :class="[
                            'w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all border',
                            contractPage === p
                                ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-500/10'
                                : p === '...'
                                    ? 'border-transparent text-slate-400 cursor-default'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                        ]">
                        {{ p }}
                    </button>
                    <button @click="
                        contractPage = Math.min(
                            contractTotalPages,
                            contractPage + 1,
                        )
                        " :disabled="contractPage === contractTotalPages"
                        class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="space-y-0">
                <!-- Contract Detail Modal -->
                <div v-if="showModal && selectedContract"
                    class="fixed top-0 left-0 right-0 bottom-0 w-screen h-screen bg-slate-800/40 backdrop-blur-xs flex items-center justify-center z-[99999] p-4"
                    @click.self="closeModal">
                    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] shadow-2xl overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <h3 class="text-sm font-bold text-slate-800">
                                Thông tin Hợp đồng #{{ selectedContract.id }}
                            </h3>
                            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div class="p-4 space-y-3 text-xs font-semibold text-slate-600 overflow-y-auto max-h-[70vh]">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Phòng
                                        trọ</span>
                                    <p class="text-sm font-bold text-emerald-600">
                                        Phòng {{ selectedContract.room }}
                                    </p>
                                </div>
                                <div class="space-y-1 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Trạng
                                        thái</span>
                                    <div :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold w-fit border',
                                        getStatusConfig(
                                            selectedContract.status,
                                        ).cls,
                                    ]">
                                        {{
                                            getStatusConfig(
                                                selectedContract.status,
                                            ).label
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 border-t border-slate-100 pt-3">
                                <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
                                    Thông tin Khách Thuê
                                </h4>
                                <div
                                    class="grid grid-cols-2 gap-3 bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100">
                                    <div>
                                        <span class="text-[9px] text-slate-400">Họ tên:</span>
                                        <p class="text-slate-700 font-bold text-xs">
                                            {{ selectedContract.tenant }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400">Điện thoại:</span>
                                        <p class="text-slate-700 font-bold text-xs">
                                            {{ selectedContract.phone }}
                                        </p>
                                    </div>
                                    <div class="col-span-2 pt-1 border-t border-slate-100/50 mt-1">
                                        <span class="text-[9px] text-slate-400">Căn cước công dân (CCCD):</span>
                                        <p class="text-slate-800 font-bold text-xs">
                                            {{
                                                selectedContract.tenant_cccd ||
                                                "⚠️ Chưa cập nhật CCCD"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Thành viên ở ghép (Roommates) -->
                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span
                                        class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                        <i class="bi bi-people-fill text-emerald-600"></i>
                                        Thành viên ở ghép (Roommates)
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="res in filteredContractRoommates" :key="res.id"
                                        class="flex justify-between items-center p-2.5 bg-slate-50 border border-slate-100 rounded-xl hover:bg-slate-100/50 transition-all">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">
                                                {{
                                                    res.user?.name ||
                                                    "Thành viên"
                                                }}
                                            </div>
                                            <div class="text-[10px] font-semibold text-slate-500">
                                                SĐT: {{ res.user?.phone }} -
                                                CCCD:
                                                {{ res.user?.cccd_number }}
                                            </div>
                                            <div class="text-[9px] text-slate-400 font-semibold">
                                                Bắt đầu ở từ:
                                                {{ formatDate(res.start_date) }}
                                            </div>
                                        </div>
                                        <button @click="removeResident(res)"
                                            class="w-7 h-7 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 rounded-lg flex items-center justify-center transition-all cursor-pointer">
                                            <i class="bi bi-trash-fill text-[11px]"></i>
                                        </button>
                                    </div>
                                    <div v-if="
                                        filteredContractRoommates.length ===
                                        0
                                    "
                                        class="text-xs font-semibold text-slate-400 italic bg-slate-50/50 border border-slate-100 rounded-xl p-3 text-center">
                                        Chưa có thành viên ở ghép trong phòng
                                        này
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 border-t border-slate-100 pt-3">
                                <h4 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">
                                    Điều khoản thuê
                                </h4>
                                <div
                                    class="grid grid-cols-3 gap-2 bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100 text-center">
                                    <div>
                                        <span class="text-[9px] text-slate-400">Giá thuê</span>
                                        <p class="text-slate-700 font-bold text-xs mt-0.5">
                                            {{
                                                formatMoney(
                                                    selectedContract.rent,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400">Đặt cọc</span>
                                        <p class="text-slate-700 font-bold text-xs mt-0.5">
                                            {{ formatMoney(selectedContract.deposit) }}
                                        </p>
                                        <span
                                            :class="['px-1.5 py-0.5 rounded text-[9px] font-bold border block mt-1 w-fit mx-auto', getDepositBadgeConfig(selectedContract).cls]">
                                            {{ getDepositBadgeConfig(selectedContract).label }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-[9px] text-slate-400">Kỳ đóng tiền</span>
                                        <p class="text-slate-700 font-bold text-xs mt-0.5">
                                            {{
                                                selectedContract
                                                    .original_contract
                                                    ?.billing_cycle || 1
                                            }}
                                            tháng/lần
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-slate-500 pt-1.5 font-bold">
                                <div>
                                    Ngày hiệu lực:
                                    <span class="text-slate-700 font-bold">{{
                                        formatDate(selectedContract.start)
                                    }}</span>
                                </div>
                                <div>
                                    Ngày kết thúc:
                                    <span class="text-slate-700 font-bold">{{
                                        formatDate(selectedContract.end)
                                    }}</span>
                                </div>
                            </div>

                            <div v-if="
                                selectedContract.original_contract
                                    ?.cancellation_reason
                            " class="p-3 bg-rose-50 border border-rose-100 text-rose-800 rounded-xl">
                                <div class="font-bold text-[10px] uppercase tracking-wider text-rose-950">
                                    Lý do chấm dứt / ghi chú:
                                </div>
                                <p class="mt-0.5 text-xs font-semibold">
                                    {{
                                        selectedContract.original_contract
                                            .cancellation_reason
                                    }}
                                </p>
                            </div>

                            <div v-if="
                                selectedContract.original_contract
                                    ?.contract_file_path
                            " class="pt-2">
                                <a :href="`/storage/${selectedContract.original_contract.contract_file_path}`"
                                    target="_blank"
                                    class="w-full py-2.5 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 rounded-xl border border-slate-200 hover:border-emerald-250 flex items-center justify-center gap-1.5 transition-all text-xs font-bold">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                    Xem file đính kèm hợp đồng
                                </a>
                            </div>
                        </div>

                        <div
                            class="px-4 py-3 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                            <button @click="closeModal"
                                class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl">
                                Đóng
                            </button>

                            <!-- Mark Expired (Active / Expiring -> Expired) -->
                            <button v-if="
                                selectedContract.status === 'signed' ||
                                selectedContract.status === 'active' ||
                                selectedContract.status === 'expiring'
                            " @click="submitMarkExpired"
                                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                {{
                                    selectedContract.end &&
                                        new Date(selectedContract.end) > new Date()
                                        ? "Chấm dứt HĐ trước thời hạn"
                                        : "Chuyển trạng thái Hết Hạn"
                                }}
                            </button>

                            <!-- Extend contract -->
                            <button v-if="
                                selectedContract.status === 'signed' ||
                                selectedContract.status === 'active' ||
                                selectedContract.status === 'expired'
                            " @click="openExtendModal"
                                class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                Gia hạn hợp đồng
                            </button>

                            <!-- Liquidate contract STRICT CHECK -->
                            <button v-if="
                                selectedContract.status === 'expired' ||
                                selectedContract.status ===
                                'termination_requested'
                            " @click="openLiquidationModal"
                                class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md transition-colors flex items-center gap-1">
                                <i class="bi bi-calculator"></i> Thanh lý Hợp
                                Đồng
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Create Contract Modal -->
                <div v-if="showAddModal"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-2 sm:p-4"
                    @click.self="showAddModal = false">
                    <div
                        class="bg-white rounded-t-[32px] sm:rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[88vh] sm:max-h-[92vh] transition-all duration-300 mx-auto">
                        <div
                            class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <div class="space-y-0.5">
                                <h3 class="text-base font-bold text-slate-800">
                                    Tạo hợp đồng thuê mới
                                </h3>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bước {{
                                    activeStep }} / 3</span>
                            </div>
                            <button @click="showAddModal = false"
                                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <!-- Thanh chuyển đổi chế độ ký hợp đồng -->
                        <div class="px-6 pt-3 pb-1 flex bg-slate-50/50 border-b border-slate-50 gap-2">
                            <div
                                class="flex bg-slate-100 p-1 rounded-xl gap-1 text-[11px] font-bold text-slate-500 w-full">
                                <button type="button" @click="creationMode = 'appointment'" :class="creationMode === 'appointment'
                                    ? 'bg-white text-slate-800 shadow-xs'
                                    : 'hover:text-slate-800'
                                    " class="flex-1 py-2 rounded-lg transition-all text-center cursor-pointer">
                                    <i class="bi bi-calendar-event"></i> Ký từ
                                    Lịch hẹn
                                </button>
                                <button type="button" @click="creationMode = 'roommate'" :class="creationMode === 'roommate'
                                    ? 'bg-white text-slate-800 shadow-xs'
                                    : 'hover:text-slate-800'
                                    " class="flex-1 py-2 rounded-lg transition-all text-center cursor-pointer">
                                    <i class="bi bi-people-fill"></i> Ký cho Cư
                                    dân ở ghép
                                </button>
                            </div>
                        </div>

                        <div
                            class="px-6 py-3 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center text-xs font-bold text-slate-400">
                            <button @click="goToStep(1)"
                                class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 1
                                    ? 'text-emerald-600 font-bold'
                                    : 'text-slate-400'
                                    ">
                                <span>1. Khách & Kiểm tra CCCD</span>
                            </button>
                            <i class="bi bi-chevron-right text-slate-300"></i>
                            <button @click="goToStep(2)"
                                class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 2
                                    ? 'text-emerald-600 font-bold'
                                    : 'text-slate-400'
                                    ">
                                <span>2. Điền Hạn & Tiền Cọc</span>
                            </button>
                            <i class="bi bi-chevron-right text-slate-300"></i>
                            <button @click="goToStep(3)"
                                class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 3
                                    ? 'text-emerald-600 font-bold'
                                    : 'text-slate-400'
                                    ">
                                <span>3. Đính kèm Bản Hợp Đồng</span>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 overflow-y-auto flex-1">
                            <!-- Step 1: Chọn khách thuê & Kiểm tra Gate CCCD -->
                            <div v-if="activeStep === 1" class="space-y-4">
                                <!-- LUỒNG A: Ký qua Lịch hẹn -->
                                <div v-if="creationMode === 'appointment'" class="space-y-4">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Chọn lịch hẹn khách ký HĐ
                                            <span class="text-rose-500">*</span></label>
                                        <select v-model="addForm.appointment_id"
                                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                                            <option value="" disabled>
                                                -- Chọn người ký từ lịch hẹn --
                                            </option>
                                            <option v-for="apt in filteredAppointments" :key="apt.id" :value="apt.id">
                                                Phòng
                                                {{ apt.room?.room_number }} -
                                                {{ apt.user?.name }} ({{
                                                    apt.user?.phone
                                                }})
                                            </option>
                                        </select>
                                        <p v-if="
                                            filteredAppointments.length ===
                                            0
                                        "
                                            class="text-[11px] text-amber-600 font-bold bg-amber-50 p-2.5 rounded-xl border border-amber-100 mt-1">
                                            ⚠️ Hiện chưa có Lịch hẹn xem phòng
                                            nào (mà khách đã bấm "ƯNG") đang chờ
                                            tạo hợp đồng tại cơ sở này.
                                        </p>
                                    </div>
                                </div>

                                <!-- LUỒNG B: Ký cho Cư dân ở ghép -->
                                <div v-else-if="creationMode === 'roommate'" class="space-y-4">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Chọn Phòng trọ
                                            <span class="text-rose-500">*</span></label>
                                        <select v-model="addForm.room_id"
                                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                                            <option value="">
                                                -- Chọn phòng trọ --
                                            </option>
                                            <option v-for="r in availableRooms" :key="r.id" :value="r.id">
                                                {{ getRoomStatusLabel(r) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div v-if="addForm.room_id" class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Chọn cư dân ở ghép thăng chức
                                            <span class="text-rose-500">*</span></label>
                                        <select v-model="selectedResidentId"
                                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                                            <option value="">
                                                -- Chọn thành viên đang ở ghép
                                                trong phòng --
                                            </option>
                                            <option v-for="res in activeRoomResidents" :key="res.id" :value="res.id">
                                                {{ res.user?.name || res.name }}
                                                (SĐT:
                                                {{
                                                    res.user?.phone || res.phone
                                                }}
                                                - CCCD:
                                                {{
                                                    res.user?.cccd_number ||
                                                    res.cccd_number
                                                }})
                                            </option>
                                        </select>
                                        <p v-if="
                                            activeRoomResidents.length === 0
                                        "
                                            class="text-[11px] text-amber-600 font-bold bg-amber-50 p-2.5 rounded-xl border border-amber-100 mt-1">
                                            ⚠️ Phòng trọ này hiện chưa ghi nhận
                                            thành viên ở ghép nào được thêm từ
                                            Giai đoạn 1.
                                        </p>
                                    </div>
                                </div>

                                <!-- LUỒNG C: Ký trực tiếp bằng cách tìm tài khoản -->
                                <div v-else class="space-y-4">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Chọn Phòng trọ
                                            <span class="text-rose-500">*</span></label>
                                        <select v-model="addForm.room_id"
                                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                                            <option value="">
                                                -- Chọn phòng trọ --
                                            </option>
                                            <option v-for="r in availableRooms" :key="r.id" :value="r.id">
                                                Phòng {{ r.room_number }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="space-y-1 relative">
                                        <label class="text-xs font-bold text-slate-500">Tìm kiếm tài khoản Khách thuê
                                            <span class="text-rose-500">*</span></label>
                                        <input v-model="searchQuery" type="text"
                                            placeholder="Gõ Số điện thoại, Email hoặc Họ tên..."
                                            @input="performSearchTenant"
                                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white" />

                                        <!-- Dropdown danh sách kết quả tìm kiếm -->
                                        <div v-if="searchResults.length > 0"
                                            class="absolute left-0 right-0 top-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 z-50 max-h-48 overflow-y-auto divide-y divide-slate-100">
                                            <button type="button" v-for="user in searchResults" :key="user.id"
                                                @click="selectDirectUser(user)"
                                                class="w-full text-left px-4 py-2 text-xs hover:bg-slate-50 transition-all block cursor-pointer">
                                                <div class="font-bold text-slate-800">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-[10px] text-slate-500">
                                                    SĐT: {{ user.phone }} -
                                                    CCCD:
                                                    {{
                                                        user.cccd_number ||
                                                        "Chưa cập nhật"
                                                    }}
                                                </div>
                                            </button>
                                        </div>
                                        <p v-if="
                                            searchQuery &&
                                            searchResults.length === 0
                                        " class="text-[10px] text-slate-400 italic mt-0.5">
                                            Không tìm thấy tài khoản phù hợp.
                                        </p>
                                    </div>
                                </div>

                                <!-- KIỂM TRA ĐỊNH DANH CCCD (Chỉ hiện khi đã chọn đối tượng) -->
                                <div v-if="
                                    (creationMode === 'appointment' &&
                                        addForm.appointment_id) ||
                                    (creationMode === 'roommate' &&
                                        selectedResidentId) ||
                                    (creationMode === 'direct' &&
                                        selectedDirectUser)
                                " class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <h4
                                        class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200">
                                        <i class="bi bi-shield-lock-fill text-emerald-600 mr-1 text-base"></i>
                                        Kiểm tra định danh khách thuê
                                    </h4>
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-400 font-bold">Khách thuê:</span>
                                            <p class="font-bold text-slate-700">
                                                {{
                                                    creationMode ===
                                                        "appointment"
                                                        ? selectedAppointment
                                                            ?.user?.name
                                                        : selectedResidentOption?.name
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-slate-400 font-bold">Số điện thoại:</span>
                                            <p class="font-bold text-slate-700">
                                                {{
                                                    creationMode ===
                                                        "appointment"
                                                        ? selectedAppointment
                                                            ?.user?.phone
                                                        : creationMode ===
                                                            "roommate"
                                                            ? selectedResidentOption?.phone
                                                            : selectedDirectUser?.phone
                                                }}
                                            </p>
                                        </div>
                                        <div class="col-span-2 pt-2 border-t border-slate-100">
                                            <span class="text-[10px] text-slate-400 font-bold">Số CCCD:</span>
                                            <div v-if="isCccdValid"
                                                class="flex items-center gap-1.5 text-emerald-600 font-bold mt-0.5">
                                                <i class="bi bi-patch-check-fill text-emerald-500 text-sm"></i>
                                                <span>Hợp lệ ({{
                                                    tenantCccd
                                                    }})</span>
                                            </div>
                                            <div v-else class="flex flex-col gap-1.5 mt-1">
                                                <div class="flex items-center gap-1.5 text-rose-600 font-bold">
                                                    <i
                                                        class="bi bi-exclamation-triangle-fill text-rose-500 text-base"></i>
                                                    <span>{{
                                                        tenantCccd
                                                            ? `Không hợp lệ (${tenantCccd})`
                                                            : "Chưa cập nhật"
                                                    }}</span>
                                                </div>
                                                <p
                                                    class="text-[11px] leading-relaxed text-slate-500 bg-rose-50/50 p-2.5 rounded-xl border border-rose-100 font-medium">
                                                    ⚠️ Khách thuê bắt buộc phải
                                                    có CCCD đúng 12 chữ số. Hãy
                                                    nhắc khách thuê mở app của
                                                    họ lên, vào trang
                                                    <strong>Cá nhân</strong> để
                                                    điền số CCCD chính xác ngay
                                                    lúc này!
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hiển thị Phòng & Số lượng người ở -->
                                <div v-if="addForm.room" class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Phòng đã chọn</label>
                                        <input v-model="addForm.room" readonly
                                            class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-emerald-600 outline-none" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-500">Số lượng người ở
                                            <span class="text-rose-500">*</span></label>
                                        <input v-model.number="addForm.number_of_tenants
                                            " type="number" min="1" max="20" placeholder="1" :disabled="creationMode === 'roommate'
                                                " :class="tenantCountErrorMsg
                                                    ? 'border-2 border-rose-500 bg-rose-50/40 text-rose-900 font-bold'
                                                    : 'border-slate-200 focus:border-emerald-500 font-bold'
                                                    "
                                            class="w-full px-3.5 py-2.5 border rounded-xl text-xs outline-none transition-all disabled:bg-slate-50 disabled:cursor-not-allowed" />
                                    </div>
                                    <p v-if="tenantCountErrorMsg"
                                        class="col-span-2 text-[11px] text-rose-600 font-bold flex items-center gap-1.5 mt-1 p-2 bg-rose-50 border border-rose-200 rounded-xl">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-500"></i>
                                        <span>{{ tenantCountErrorMsg }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Step 2: Nhập thời hạn & Tiền đặt cọc -->
                            <div v-if="activeStep === 2" class="space-y-4">
                                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <h4
                                        class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200 flex items-center gap-1">
                                        <i class="bi bi-calendar-check-fill text-amber-600 text-base"></i>
                                        Thời hạn & Chi phí thuê phòng
                                    </h4>

                                    <!-- Banner thông báo logic dọn vào -->
                                    <div v-if="
                                        creationMode === 'appointment' &&
                                        isSharingRoom
                                    "
                                        class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 mb-3 space-y-1">
                                        <div class="font-bold flex items-center gap-1">
                                            <i class="bi bi-info-circle-fill text-blue-600"></i>
                                            <span>Phòng Ở Ghép (Hiện có
                                                {{ selectedRoomCurrentPeople }}
                                                người ở)</span>
                                        </div>
                                        <p class="leading-relaxed">
                                            Hợp đồng ở ghép sẽ bắt đầu sau 7
                                            ngày ở thử để các thành viên làm
                                            quen. Ngày bắt đầu đã được tự động
                                            lùi lại 7 ngày.
                                        </p>
                                    </div>

                                    <div v-if="creationMode === 'roommate'"
                                        class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 mb-3 space-y-1">
                                        <div class="font-bold flex items-center gap-1">
                                            <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                            <span>Nâng chức chủ hộ trực
                                                tiếp</span>
                                        </div>
                                        <p class="leading-relaxed">
                                            Thành viên ở ghép
                                            {{ selectedResidentOption?.name }}
                                            sẽ thăng chức thành Chủ hợp đồng mới
                                            đại diện phòng.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-slate-500">Ngày bắt đầu hợp đồng
                                                <span class="text-rose-500">*</span></label>
                                            <input v-model="addForm.start_date" type="date" :min="minStartDate"
                                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-emerald-500 bg-white" />
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-slate-500">Ngày kết thúc hợp đồng
                                                <span class="text-rose-500">*</span></label>
                                            <input v-model="addForm.end_date" type="date" :min="minDate"
                                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white" />
                                        </div>
                                        <div class="space-y-1 sm:col-span-2">
                                            <label class="text-xs font-bold text-slate-500">Tiền đặt cọc offline (đ)
                                                <span class="text-rose-500">*</span></label>
                                            <input v-model="displayDeposit" type="text"
                                                placeholder="Nhập số tiền đặt cọc..."
                                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none bg-white" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Direct Upload File -->
                            <div v-if="activeStep === 3" class="space-y-4">
                                <div
                                    class="p-3 bg-blue-50 border border-blue-150 rounded-xl text-xs text-blue-800 font-semibold flex items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-lg text-blue-600"></i>
                                    <span>Chụp ảnh hợp đồng đã ký tay hoặc tải
                                        lên file đính kèm trực tiếp (ảnh hoặc
                                        PDF)</span>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500">Chọn tệp hợp đồng đính kèm
                                        <span class="text-rose-500">*</span></label>
                                    <input type="file" accept="image/*,application/pdf" @change="handleFileSelect"
                                        class="w-full px-3.5 py-3 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs outline-none bg-slate-50 cursor-pointer" />
                                    <p class="text-[10px] text-slate-400 font-semibold">
                                        Chấp nhận file ảnh (.jpg, .jpeg, .png)
                                        hoặc tài liệu PDF dưới 10MB.
                                    </p>
                                </div>

                                <div v-if="addForm.contract_file"
                                    class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 space-y-1">
                                    <div class="font-bold flex items-center gap-1">
                                        <i class="bi bi-file-earmark-check-fill text-emerald-600 text-base"></i>
                                        <span>Tệp đã chọn:</span>
                                    </div>
                                    <p class="font-bold text-slate-700">
                                        {{ addForm.contract_file.name }} ({{
                                            (
                                                addForm.contract_file.size /
                                                1024 /
                                                1024
                                            ).toFixed(2)
                                        }}
                                        MB)
                                    </p>
                                </div>

                                <div class="pt-2">
                                    <label
                                        class="flex items-start gap-2 cursor-pointer p-3 bg-white border border-slate-200 rounded-xl text-[11px] font-semibold text-slate-600 shadow-xs">
                                        <input type="checkbox" required
                                            class="mt-0.5 rounded text-emerald-500 focus:ring-emerald-400" />
                                        <span>Xác nhận thông tin hợp đồng giấy
                                            tải lên trùng khớp với thông tin đã
                                            nhập trên hệ thống.</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-2.5 bg-slate-50/50">
                            <button v-if="activeStep > 1"
                                class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors"
                                @click="activeStep--">
                                Quay lại
                            </button>
                            <div v-else></div>

                            <div class="flex items-center gap-2">
                                <button
                                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors"
                                    @click="showAddModal = false">
                                    Hủy
                                </button>
                                <button v-if="activeStep < 3" :disabled="activeStep === 1 && !isStep1Valid
                                    " :class="[
                                        'px-5 py-2.5 font-bold text-xs rounded-xl transition-all',
                                        activeStep === 1 && !isStep1Valid
                                            ? 'bg-slate-300 text-slate-500 cursor-not-allowed pointer-events-none opacity-60 shadow-none'
                                            : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md cursor-pointer',
                                    ]" @click="goToNextStep">
                                    <span>Tiếp tục</span>
                                </button>
                                <button v-else :disabled="!addForm.contract_file" :class="[
                                    'px-5 py-2.5 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5',
                                    !addForm.contract_file
                                        ? 'bg-slate-300 text-slate-500 cursor-not-allowed pointer-events-none opacity-60 shadow-none'
                                        : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md',
                                ]" @click="submitAddContract">
                                    <i class="bi bi-check-circle-fill text-sm"></i>
                                    <span>Ký kết & Kích hoạt Hợp Đồng</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Danh sách User ấn ưng / Hợp đồng đang chờ -->
                <div v-if="showPendingRequestsModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                    <div
                        class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[85vh]">
                        <div
                            class="p-5 border-b border-slate-100 flex justify-between items-center bg-gradient-to-r from-amber-500 to-orange-500 text-white">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-heart-fill text-xl"></i>
                                <div>
                                    <h3 class="text-sm font-bold">
                                        Danh sách Hợp đồng đang chờ (Khách đã ấn
                                        ưng)
                                    </h3>
                                    <p class="text-[11px] text-amber-100">
                                        Các khách hàng đã nhấn quan tâm / đăng
                                        ký thuê nhưng chưa tạo hợp đồng
                                    </p>
                                </div>
                            </div>
                            <button @click="showPendingRequestsModal = false"
                                class="text-amber-100 hover:text-white transition-colors cursor-pointer">
                                <i class="bi bi-x-lg text-lg"></i>
                            </button>
                        </div>

                        <div class="p-5 overflow-y-auto space-y-3 flex-1">
                            <div v-if="
                                !props.appointments ||
                                props.appointments.length === 0
                            " class="text-center py-8 text-slate-400 space-y-2">
                                <i class="bi bi-inbox text-4xl block text-slate-300"></i>
                                <p class="text-xs font-semibold">
                                    Hiện chưa có khách hàng nào nhấn ưng hoặc
                                    chờ duyệt hợp đồng.
                                </p>
                            </div>
                            <div v-else v-for="apt in props.appointments" :key="apt.id"
                                class="p-4 bg-slate-50 hover:bg-amber-50/40 border border-slate-200 hover:border-amber-300 rounded-2xl flex items-center justify-between transition-all">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-lg">Phòng
                                            {{ apt.room?.room_number }}</span>
                                        <span class="text-xs font-bold text-slate-800">{{
                                            apt.user?.name || "Khách thuê"
                                            }}</span>
                                        <span :class="apt.user?.cccd_number &&
                                            String(apt.user.cccd_number)
                                                .length === 12
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : 'bg-rose-50 text-rose-700 border-rose-200'
                                            "
                                            class="px-2 py-0.5 border text-[10px] font-bold rounded-full inline-flex items-center gap-1 whitespace-nowrap">
                                            <i :class="apt.user?.cccd_number &&
                                                String(apt.user.cccd_number)
                                                    .length === 12
                                                ? 'bi bi-check-circle-fill text-emerald-500'
                                                : 'bi bi-exclamation-triangle-fill text-rose-500'
                                                "></i>
                                            <span>{{
                                                apt.user?.cccd_number &&
                                                    String(apt.user.cccd_number)
                                                        .length === 12
                                                    ? "Đủ CCCD"
                                                    : "Thiếu CCCD"
                                            }}</span>
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-3">
                                        <span><i class="bi bi-telephone"></i>
                                            {{
                                                apt.user?.phone || "Chưa có SĐT"
                                            }}</span>
                                        <span v-if="apt.room?.boarding_house"><i class="bi bi-geo-alt"></i>
                                            {{
                                                apt.room.boarding_house.name
                                            }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button @click="openContractForAppointment(apt)"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                                        <i class="bi bi-file-earmark-plus"></i>
                                        Tạo hợp đồng ngay
                                    </button>
                                    <button @click="rejectAppointment(apt.id)"
                                        class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold text-xs rounded-xl transition-all flex items-center gap-1 cursor-pointer"
                                        title="Hủy / Loại bỏ khỏi danh sách">
                                        <i class="bi bi-x-circle-fill"></i> Hủy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                            <button @click="showPendingRequestsModal = false"
                                class="px-4 py-2 border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl">
                                Đóng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal thêm người ở ghép 2 BƯỚC (Kiểm tra CCCD 12 số -> Nhập ngày & Lưu) -->
        <div v-if="showAddResidentModal"
            class="fixed inset-0 z-55 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
            style="z-index: 60">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-1.5">
                            <i class="bi bi-person-plus-fill text-emerald-600"></i>
                            Thêm thành viên ở ghép vào phòng
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            Bước {{ residentStep }} / 2
                        </span>
                    </div>
                    <button @click="showAddResidentModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <!-- BƯỚC 1: KIỂM TRA THÔNG TIN & GATE CCCD 12 SỐ -->
                <div v-if="residentStep === 1" class="space-y-4">
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold">Họ & Tên:</span>
                            <span class="text-slate-800 font-extrabold">{{
                                selectedRoommateReq?.new_resident_name ||
                                selectedRoommateReq?.tenant?.name ||
                                "Chưa cập nhật"
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold">Số điện thoại:</span>
                            <span class="text-slate-800 font-extrabold">{{
                                residentForm.phone ||
                                selectedRoommateReq?.new_resident_phone ||
                                selectedRoommateReq?.tenant?.phone ||
                                "Chưa cập nhật"
                            }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400 font-bold">Số CCCD (12 số):</span>
                            <span :class="isRoommateCccdValid
                                ? 'text-emerald-600 font-black'
                                : 'text-rose-600 font-black'
                                ">
                                {{
                                    selectedRoommateReq?.new_resident_cccd ||
                                    selectedRoommateReq?.tenant?.cccd_number ||
                                    "Chưa đủ 12 số"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Cảnh báo nếu thiếu CCCD 12 số -->
                    <div v-if="!isRoommateCccdValid"
                        class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-lg shrink-0"></i>
                        <span>Thành viên này chưa cập nhật đủ CCCD 12 số hợp lệ.
                            Vui lòng yêu cầu khách cập nhật thông tin trước khi
                            thêm!</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showAddResidentModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl">
                            Hủy
                        </button>
                        <button type="button" :disabled="!isRoommateCccdValid" @click="residentStep = 2"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1 disabled:cursor-not-allowed cursor-pointer">
                            Thông tin hợp lệ - Qua Bước 2
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- BƯỚC 2: CHỈ CHỌN NGÀY VÀO Ở & XÁC NHẬN LƯU VÀO PHÒNG -->
                <form v-else-if="residentStep === 2" @submit.prevent="submitAddResident" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Số điện thoại thành viên (Đã tự động lấy từ
                            thông
                            tin Khách)</label>
                        <input :value="residentForm.phone" type="text" disabled
                            class="w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-xs font-bold text-slate-600 outline-none cursor-not-allowed" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Ngày bắt đầu vào ở ghép
                            <span class="text-rose-500">*</span></label>
                        <input v-model="residentForm.start_date" type="date" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none" />
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 mt-2">
                        <button type="button" @click="residentStep = 1"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            Quay lại Bước 1
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-check-circle-fill"></i> Xác nhận
                            Thêm vào Phòng
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Liquidation Modal (Biên Bản Thanh Lý Hợp Đồng) -->
        <div v-if="showLiquidationModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-rose-600 flex items-center gap-1.5">
                        <i class="bi bi-file-earmark-x-fill"></i> Biên Bản Thanh
                        Lý Hợp Đồng
                    </h3>
                    <button @click="showLiquidationModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitLiquidationContract" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Phương án xử lý tiền cọc</label>
                        <select v-model="liquidationForm.deposit_handling"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                            <option value="refund_full">
                                Trả cọc (Hoàn trả toàn bộ 100% tiền cọc)
                            </option>
                            <option value="keep_deposit">
                                Mất cọc (Tịch thu toàn bộ cọc do phá hợp đồng)
                            </option>
                            <option value="refund_partial">
                                Khấu trừ cọc (Trừ tiền điện/nước/hư hỏng rồi hoàn lại phần còn lại)
                            </option>
                        </select>

                    </div>

                    <div v-if="
                        liquidationForm.deposit_handling ===
                        'refund_partial'
                    " class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Số tiền cọc hoàn lại (đ)</label>
                        <input v-model.number="liquidationForm.deposit_refund_amount
                            " type="number" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Ghi chú quyết toán (Lý do trả/giữ cọc)</label>
                        <textarea v-model="liquidationForm.notes" rows="3"
                            placeholder="Nhập ghi chú chi tiết bàn giao bàn ghế, thanh toán hóa đơn cuối cùng..."
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs outline-none resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 mt-2">
                        <button type="button" @click="showLiquidationModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            Hủy
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                            Xác nhận thanh lý
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Extend Contract Modal (Gia Hạn Hợp Đồng) -->
        <div v-if="showExtendModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-emerald-600 flex items-center gap-1.5">
                        <i class="bi bi-arrow-repeat"></i> Gia Hạn Hợp Đồng Thuê
                        Trọ
                    </h3>
                    <button @click="showExtendModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitExtendContract" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Chọn ngày hết hạn mới
                            <span class="text-rose-500">*</span></label>
                        <input v-model="extendForm.new_end_date" type="date" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Xác nhận số CCCD/CMND khách thuê (12 số)</label>
                        <input v-model="extendForm.tenant_cccd" type="text" maxlength="12" required
                            placeholder="Nhập đúng 12 số CCCD để lưu gia hạn"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Ghi chú gia hạn</label>
                        <textarea v-model="extendForm.notes" rows="3" placeholder="Nhập ghi chú gia hạn hợp đồng trọ..."
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs outline-none resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 mt-2">
                        <button type="button" @click="showExtendModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            Hủy
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                            Xác nhận gia hạn
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal Danh Sách Yêu Cầu Ở Ghép Chờ Ký Hợp Đồng -->
        <div v-if="showPendingRoommateModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div
                class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-4 border border-slate-100 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-800 flex items-center gap-1.5">
                        <i class="bi bi-person-plus-fill text-amber-500"></i>
                        Yêu cầu ở ghép chờ tạo Hợp Đồng
                    </h3>
                    <button @click="showPendingRoommateModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>
                <div class="space-y-3">
                    <div v-for="req in paginatedRoommateRequests" :key="req.id"
                        class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:bg-slate-100/60 transition-all">
                        <div>
                            <div class="font-extrabold text-slate-800 text-sm">
                                Phòng
                                {{ req.room ? req.room.room_number : "?" }} -
                                Người gửi:
                                {{ req.tenant ? req.tenant.name : "Ẩn danh" }}
                            </div>
                            <div class="text-[11px] text-amber-600 font-bold mt-0.5">
                                Loại yêu cầu:
                                {{
                                    req.type === "stranger"
                                        ? "Tìm người lạ ở ghép"
                                        : "Giới thiệu người quen: " +
                                        req.new_resident_name
                                }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase flex items-center gap-1">
                                <i class="bi bi-clock"></i> Gửi ngày:
                                {{ formatDate(req.created_at) }}
                            </div>
                        </div>
                        <button @click="openAddResidentFromRequest(req)"
                            class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5 self-stretch sm:self-auto justify-center cursor-pointer">
                            <i class="bi bi-person-plus-fill"></i> Duyệt & Thêm
                            người ở ghép
                        </button>
                    </div>
                    <div v-if="
                        !props.pendingRoommateRequests ||
                        props.pendingRoommateRequests.length === 0
                    " class="text-center p-8 text-slate-400 font-bold text-sm">
                        Không có yêu cầu ở ghép nào đang chờ tạo hợp đồng.
                    </div>
                </div>

                <!-- Thanh phân trang Yêu cầu ở ghép -->
                <div v-if="roommateTotalPages > 1"
                    class="flex items-center justify-between border-t border-slate-100 pt-3 mt-2">
                    <span class="text-[11px] text-slate-400 font-semibold">
                        Hiển thị
                        {{ (roommatePage - 1) * roommatePageSize + 1 }} -
                        {{
                            Math.min(
                                roommatePage * roommatePageSize,
                                props.pendingRoommateRequests.length,
                            )
                        }}
                        trên tổng {{ props.pendingRoommateRequests.length }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button @click="
                            roommatePage = Math.max(1, roommatePage - 1)
                            " :disabled="roommatePage === 1"
                            class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 transition-all">
                            <i class="bi bi-chevron-left text-[9px]"></i>
                        </button>
                        <button v-for="p in getVisiblePages(
                            roommatePage,
                            roommateTotalPages,
                        )" :key="p" @click="
                            typeof p === 'number'
                                ? (roommatePage = p)
                                : null
                            " :class="[
                                'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold transition-all border',
                                roommatePage === p
                                    ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm'
                                    : p === '...'
                                        ? 'border-transparent text-slate-400 cursor-default'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                            ]">
                            {{ p }}
                        </button>
                        <button @click="
                            roommatePage = Math.min(
                                roommateTotalPages,
                                roommatePage + 1,
                            )
                            " :disabled="roommatePage === roommateTotalPages"
                            class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 transition-all">
                            <i class="bi bi-chevron-right text-[9px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
