<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, Link, useForm, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { showSuccess, showError, showConfirm } from "@/Utils/swal";
import axios from "axios";

const props = defineProps({
    user: Object,
    contract: Object,
    isPrimaryTenant: Boolean,
    reasons: Array,
});

const showPdfModal = ref(false);
const showEntryModal = ref(false);
const showTerminateModal = ref(false);

// Bộ lọc danh sách người ở ghép (Loại trừ Chủ hợp đồng chính)
const filteredRoommates = computed(() => {
    const list = props.contract?.room?.residents;
    if (!Array.isArray(list)) return [];
    const primaryTenantId = props.contract?.tenant_id;
    return list.filter(
        (res) => String(res.user_id) !== String(primaryTenantId)
    );
});

const entryForm = useForm({
    entry_elec_index: props.contract?.entry_elec_index || "",
    entry_water_index: props.contract?.entry_water_index || "",
    entry_elec_image: null,
    entry_water_image: null,
});

const terminateForm = useForm({
    reason: "",
});

const elecImgPreview = ref(null);
const waterImgPreview = ref(null);

const handleElecImg = (e) => {
    const file = e.target.files[0];
    if (file) {
        entryForm.entry_elec_image = file;
        elecImgPreview.value = URL.createObjectURL(file);
    }
};

const handleWaterImg = (e) => {
    const file = e.target.files[0];
    if (file) {
        entryForm.entry_water_image = file;
        waterImgPreview.value = URL.createObjectURL(file);
    }
};

const submitEntryReadings = () => {
    if (entryForm.entry_elec_index === "" || entryForm.entry_elec_index === null || entryForm.entry_water_index === "" || entryForm.entry_water_index === null) {
        showError("Lỗi nhập liệu", "Vui lòng nhập đầy đủ chỉ số điện và chỉ số nước bàn giao!");
        return;
    }
    entryForm.post(
        route("profile.contract.submit-entry-readings", props.contract.hash_id),
        {
            forceFormData: true,
            onSuccess: () => {
                showEntryModal.value = false;
                showSuccess(
                    "Thành công",
                    "Đã cập nhật chỉ số điện nước bàn giao thành công!",
                );
            },
            onError: (err) => {
                showError(
                    "Lỗi",
                    err.message || "Không thể lưu thông tin. Vui lòng thử lại.",
                );
            },
        },
    );
};

const submitTerminateRequest = async () => {
    if (!terminateForm.reason.trim()) {
        showError("Lỗi", "Vui lòng nhập lý do chấm dứt hợp đồng.");
        return;
    }

    //chờ user xác nhận
    const isConfirmed = await showConfirm(
        "Xác nhận gửi yêu cầu",
        "Bạn có chắc chắn muốn gửi yêu cầu chấm dứt hợp đồng trọ này không?",
    );

    //nếu bấm đồng ý
    if (isConfirmed) {
        terminateForm.post(
            route("contracts.request-termination", props.contract.hash_id),
            {
                onSuccess: () => {
                    showTerminateModal.value = false;
                    showSuccess(
                        "Thành công",
                        "Yêu cầu chấm dứt hợp đồng đã được gửi đến chủ trọ.",
                    );
                },
            },
        );
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    return d.toLocaleDateString("vi-VN");
};

const getContractUrl = () => {
    if (!props.contract?.contract_file_path) return null;
    return "/storage/" + props.contract.contract_file_path;
};

const isImage = (path) => {
    if (!path) return false;
    return path.match(/\.(jpeg|jpg|gif|png)$/i) != null;
};

const getStatusLabel = computed(() => {
    if (!props.contract) return "Không hoạt động";
    switch (props.contract.status) {
        case "signed":
        case "active":
            return "Hợp đồng hiệu lực";
        case "termination_requested":
            return "Yêu cầu chấm dứt (Chờ duyệt)";
        case "terminated":
            return "Đã thanh lý";
        case "expired":
            return "Đã hết hạn";
        default:
            return "Chưa ký";
    }
});

const getStatusBg = computed(() => {
    if (!props.contract) return "#ef4444";
    switch (props.contract.status) {
        case "signed":
        case "active":
            return "#22c55e";
        case "termination_requested":
            return "#f97316";
        case "terminated":
            return "#64748b";
        case "expired":
            return "#ef4444";
        default:
            return "#ef4444";
    }
});

const terminateButtonText = computed(() => {
    if (!props.contract?.end_date) return "Chấm dứt hợp đồng";

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const endDate = new Date(props.contract.end_date);
    endDate.setHours(0, 0, 0, 0);

    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 3) {
        return "Chấm dứt HĐ trước thời hạn";
    } else {
        return "Chấm dứt hợp đồng";
    }
});
//trạng thái modal và form gửi yêu cầu ở ghép
const showAcquaintanceModal = ref(false);
const acquaintanceForm = useForm({
    new_resident_name: "",
    new_resident_phone: "",
    new_resident_email: "",
    new_resident_cccd: "",
});
//gửi yêu cầu tìm người lạ ở ghép
//gửi yêu cầu tìm người lạ ở ghép
const submitStrangerRequest = async () => {
    const isConfirmed = await showConfirm(
        "Xác nhận yêu cầu",
        "Hệ thống sẽ gửi yêu cầu đăng tin tìm người ở ghép (người lạ) tới chủ trọ. Bạn có chắc chắn muốn gửi không?",
    );

    if (isConfirmed) {
        router.post(
            route("profile.roommate.request_stranger"),
            {},
            {
                onSuccess: () => {
                    showSuccess(
                        "Thành công",
                        "Đã gửi yêu cầu tìm người ở ghép tới chủ trọ thành công!",
                    );
                },
                onError: (err) => {
                    showError("Lỗi", err.message || "Không thể gửi yêu cầu.");
                },
            },
        );
    }
};

//gửi yêu cầu giới thiệu người quen vào ở ghép
const submitAcquaintanceRequest = () => {
    if (!acquaintanceForm.new_resident_name.trim() || !acquaintanceForm.new_resident_phone.trim()) {
        showError("Lỗi nhập liệu", "Vui lòng nhập đầy đủ tên và số điện thoại của người ở ghép!");
        return;
    }
    acquaintanceForm.post(route("profile.roommate.request_acquaintance"), {
        onSuccess: () => {
            showAcquaintanceModal.value = false;
            acquaintanceForm.reset();
            showSuccess(
                "Thành công",
                "Đã gửi thông báo giới thiệu bạn bè vào ở ghép thành công!",
            );
        },
        onError: (err) => {
            showError("Lỗi", Object.values(err).join("\n"));
        },
    });
};

//gửi yêu cầu gia hạn
const showExtendRequestModal = ref(false);
const hasRequestedExtension = computed(() => {
    return (
        props.contract?.cancellation_reason &&
        props.contract.cancellation_reason.includes("gia hạn")
    );
});

const extendRequestForm = ref({
    desired_months: 6,
    note: "",
});

const submitExtendRequest = () => {
    if (!props.contract?.id) {
        showError("Lỗi", "Không tìm thấy thông tin hợp đồng.");
        return;
    }

    router.post(
        route("profile.contracts.request-extension", props.contract.hash_id),
        extendRequestForm.value,
        {
            preserveScroll: true, // Giữ nguyên vị trí cuộn trang, tránh làm đổi phương thức
            onSuccess: () => {
                showExtendRequestModal.value = false;
                showSuccess(
                    "Thành công",
                    "Đã gửi yêu cầu gia hạn hợp đồng tới Chủ trọ!",
                );
            },
            onError: (errs) => {
                showError("Lỗi", Object.values(errs).join("\n"));
            },
        },
    );
};

const handleViewPdf = () => {
    if (!props.isPrimaryTenant) {
        showError(
            "Thông báo Hợp đồng",
            "Bạn là thành viên ở ghép trong phòng. Hợp đồng chính do Chủ hợp đồng (" + (props.contract?.tenant?.name || "đại diện") + ") đứng tên ký kết."
        );
        return;
    }
    showPdfModal.value = true;
};

//state & form cho modal báo cáo
const showReportModal = ref(false);
const previewEvidenceImages = ref([]);

const reportForm = useForm({
    reportable_type: "Room",
    reportable_id: null,
    resolve_type: "direct",
    reason: "",
    description: "",
    evidence_images: [],
});

// Hàm mở Modal Báo cáo
const openReportModal = () => {
    if (!props.contract || !props.contract.room_id) {
        showError("Lỗi", "Không tìm thấy thông tin phòng trọ để báo cáo.");
        return;
    }
    reportForm.reset();
    reportForm.reportable_type = "Room";
    reportForm.reportable_id = props.contract.room_id;
    previewEvidenceImages.value = [];
    showReportModal.value = true;
};

const handleEvidenceImages = (e) => {
    const files = Array.from(e.target.files);
    reportForm.evidence_images = files;
    previewEvidenceImages.value = files.map((file) => URL.createObjectURL(file));
};

const submitReport = () => {
    if (!reportForm.reason || !reportForm.description.trim()) {
        showError("Lỗi nhập liệu", "Vui lòng chọn lý do và nhập mô tả chi tiết báo cáo!");
        return;
    }
    reportForm.post(route("reports.store"), {
        forceFormData: true,
        onSuccess: () => {
            showReportModal.value = false;
            showSuccess("Thành công", "Đã gửi báo cáo thành công! Hệ thống sẽ hỗ trợ bạn xử lý.");
        },
        onError: (errs) => {
            showError("Lỗi", Object.values(errs).join("\n"));
        },
    });
};
</script>

<template>

    <Head title="Trang Quản Lý Nơi Ở | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div v-if="!contract" class="alert-no-contract" style="
                        margin-bottom: 20px;
                        padding: 15px;
                        border-radius: 8px;
                        background: rgba(239, 68, 68, 0.1);
                        border: 1px solid rgba(239, 68, 68, 0.2);
                        color: #ef4444;
                        font-weight: 600;
                    ">
                    <i class="bi bi-exclamation-triangle-fill"></i> Bạn hiện
                    chưa có hợp đồng thuê trọ nào có hiệu lực.
                </div>

                <div v-else-if="contract?.status === 'termination_requested'" class="alert-no-contract" style="
                        margin-bottom: 20px;
                        padding: 15px;
                        border-radius: 8px;
                        background: #fff7ed;
                        border: 1px solid #ffedd5;
                        color: #ea580c;
                        font-weight: 600;
                    ">
                    <i class="bi bi-clock-history"></i> Bạn đã gửi yêu cầu chấm
                    dứt hợp đồng này. Chủ trọ đang xem xét và tiến hành thủ tục
                    thanh lý.
                </div>

                <div class="title_noio" style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                    <h2>THÔNG TIN NƠI Ở</h2>
                    <div class="status" :style="{ background: getStatusBg }">
                        <p>{{ getStatusLabel }}</p>
                    </div>
                </div>

                <!-- THÔNG BÁO / KHỐI CHỐT SỐ ĐIỆN NƯỚC LÚC BÀN GIAO PHÒNG -->
                <div v-if="contract" class="entry-meter-card" :style="{
                    margin: '15px 0 25px 0',
                    padding: '14px 18px',
                    borderRadius: '12px',
                    border: contract.entry_readings_submitted_at
                        ? '1px solid #a7f3d0'
                        : '1px solid #fde68a',
                    background: contract.entry_readings_submitted_at
                        ? '#ecfdf5'
                        : '#fffbeb',
                    boxShadow: '0 2px 6px rgba(0,0,0,0.03)',
                    width: '100%',
                    boxSizing: 'border-box',
                }">
                    <div style="
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            flex-wrap: wrap;
                            gap: 12px;
                        ">
                        <div style="
                                display: flex;
                                align-items: center;
                                gap: 12px;
                                min-width: 250px;
                            ">
                            <div :style="{
                                width: '38px',
                                height: '38px',
                                borderRadius: '10px',
                                background:
                                    contract.entry_readings_submitted_at
                                        ? '#10b981'
                                        : '#f59e0b',
                                color: '#fff',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '18px',
                                flexShrink: 0,
                            }">
                                <i :class="contract.entry_readings_submitted_at
                                    ? 'bi bi-check-circle-fill'
                                    : 'bi bi-lightning-charge-fill'
                                    "></i>
                            </div>
                            <div>
                                <h4 style="
                                        font-weight: 700;
                                        font-size: 14px;
                                        color: #1e293b;
                                        margin: 0 0 2px 0;
                                    ">
                                    {{
                                        contract.entry_readings_submitted_at
                                            ? "Chỉ số điện/nước lúc nhận phòng"
                                            : "⚡ Chưa chốt chỉ số điện/nước lúc nhận phòng"
                                    }}
                                </h4>
                                <p v-if="contract.entry_readings_submitted_at" style="
                                        font-size: 12px;
                                        color: #475569;
                                        margin: 0;
                                    ">
                                    Điện:
                                    <strong style="color: #059669">{{
                                        contract.entry_elec_index
                                        }}
                                        kWh</strong>
                                    | Nước:
                                    <strong style="color: #2563eb">{{
                                        contract.entry_water_index
                                        }}
                                        m³</strong>
                                    <span style="
                                            font-size: 11px;
                                            color: #94a3b8;
                                            margin-left: 8px;
                                        ">(Xác nhận:
                                        {{
                                            formatDate(
                                                contract.entry_readings_submitted_at,
                                            )
                                        }})</span>
                                </p>
                                <p v-else style="
                                        font-size: 12px;
                                        color: #b45309;
                                        margin: 0;
                                    ">
                                    Vui lòng nhập chỉ số điện, nước & ảnh chụp
                                    lúc mới nhận phòng để tránh thiệt thòi tháng
                                    đầu.
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="showEntryModal = true" :style="{
                            padding: '8px 16px',
                            fontSize: '12px',
                            fontWeight: '700',
                            borderRadius: '8px',
                            border: contract.entry_readings_submitted_at
                                ? '1px solid #059669'
                                : 'none',
                            background: contract.entry_readings_submitted_at
                                ? '#ffffff'
                                : '#d97706',
                            color: contract.entry_readings_submitted_at
                                ? '#059669'
                                : '#ffffff',
                            cursor: 'pointer',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                            transition: 'all 0.2s',
                            flexShrink: 0,
                        }">
                            <i class="bi bi-camera"></i>
                            <span>{{
                                contract.entry_readings_submitted_at
                                    ? "Xem / Cập nhật lại"
                                    : "Cập nhật chỉ số ngay"
                            }}</span>
                        </button>
                    </div>
                </div>

                <form action="" @submit.prevent>
                    <div class="row">
                        <div class="form-group">
                            <label>Tên phòng/Số phòng</label>
                            <input type="text" :value="contract?.room?.room_number ||
                                'Chưa có phòng'
                                " disabled />
                        </div>

                        <div class="form-group">
                            <label> Họ tên chủ trọ:</label>
                            <input type="text" :value="contract?.room?.boarding_house?.user
                                ?.name ||
                                contract?.room?.boardingHouse?.user?.name ||
                                'Chưa xác định'
                                " disabled />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>SĐT chủ trọ:</label>
                        <input type="text" :value="contract?.room?.boarding_house?.user?.phone ||
                            contract?.room?.boardingHouse?.user?.phone ||
                            'Chưa xác định'
                            " disabled />
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ :</label>
                        <input type="text" :value="contract?.room?.boarding_house
                            ?.address_detail ||
                            contract?.room?.boardingHouse?.address_detail ||
                            contract?.room?.address ||
                            'Chưa xác định'
                            " disabled />
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Ngày bắt đầu hợp đồng:</label>
                            <input type="text" :value="formatDate(contract?.start_date)" disabled />
                        </div>

                        <div class="form-group">
                            <label>Ngày kêt thúc dự kiến:</label>
                            <input type="text" :value="formatDate(contract?.end_date)" disabled />
                        </div>
                    </div>
                    <!-- KHỐI HIỂN THỊ DANH SÁCH THÀNH VIÊN Ở GHÉP TRONG PHÒNG -->
                    <div v-if="filteredRoommates && filteredRoommates.length > 0"
                        style="margin: 20px 0; padding: 16px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px;">
                        <h3
                            style="font-size: 14px; font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-people-fill" style="color: #10b981; font-size: 16px;"></i>
                            DANH SÁCH THÀNH VIÊN Ở GHÉP TRONG PHÒNG ({{ filteredRoommates?.length || 0 }} người)
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div v-for="res in filteredRoommates" :key="res.id"
                                style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px;">
                                <div>
                                    <strong style="font-size: 13px; color: #0f172a;">{{ res.user?.name || 'Thành viên'
                                        }}</strong>
                                    <span style="font-size: 11px; color: #64748b; margin-left: 8px;">SĐT: {{
                                        res.user?.phone || 'Chưa có' }}</span>
                                </div>
                                <span
                                    style="font-size: 11px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 4px 10px; border-radius: 8px; border: 1px solid #a7f3d0;">
                                    <i class="bi bi-person-check-fill"></i> Đang ở ghép
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Cụm nút quản lý ở ghép & chấm dứt hợp đồng -->
                    <div v-if="
                        contract &&
                        ['active', 'signed', 'expiring'].includes(
                            contract.status,
                        )
                    " class="roommate-actions-container">
                        <!-- Nút Tìm người ở ghép: Chỉ hiện khi là Chủ hợp đồng, phòng > 1 người và chưa đầy -->
                        <button
                            v-if="props.isPrimaryTenant && (contract.room?.capacity > 1) && ((contract.room?.current_people || 1) < contract.room?.capacity)"
                            type="button" @click="submitStrangerRequest" class="btn-roommate-stranger">
                            <i class="bi bi-people-fill"></i> Tìm người ở ghép
                        </button>

                        <!-- Nút Giới thiệu bạn bè: Chỉ hiện khi là Chủ hợp đồng, phòng > 1 người và chưa đầy -->
                        <button
                            v-if="props.isPrimaryTenant && (contract.room?.capacity > 1) && ((contract.room?.current_people || 1) < contract.room?.capacity)"
                            type="button" @click="showAcquaintanceModal = true" class="btn-roommate-acquaintance">
                            <i class="bi bi-person-plus-fill"></i> Giới thiệu người vào ở
                        </button>

                        <!-- Nút Chấm dứt HĐ: Chỉ hiện dành cho Chủ hợp đồng -->
                        <button v-if="props.isPrimaryTenant" type="button" @click="showTerminateModal = true"
                            class="btn-terminate">
                            <i class="bi bi-x-circle-fill"></i>
                            {{ terminateButtonText }}
                        </button>
                    </div>
                </form>

                <div class="hopdong" style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: 10px;
                        flex-wrap: wrap;
                    ">
                    <h2>HỢP ĐỒNG THUÊ TRỌ</h2>
                    <div style="display: flex; gap: 10px; align-items: center">
                        <!-- Nút Yêu cầu gia hạn HĐ: CHỈ HIỆN DÀNH CHO CHỦ HỢP ĐỒNG -->
                        <button v-if="props.isPrimaryTenant && !hasRequestedExtension"
                            @click="showExtendRequestModal = true" class="btn-hopdong"
                            style="background: #10b981; color: #fff; cursor: pointer;">
                            <i class="bi bi-arrow-repeat"></i> Yêu cầu gia hạn HĐ
                        </button>
                        <span v-else-if="props.isPrimaryTenant && hasRequestedExtension" style="
                                font-size: 12px;
                                font-weight: 700;
                                color: #d97706;
                                background: #fffbeb;
                                padding: 6px 12px;
                                border-radius: 8px;
                                border: 1px solid #fde68a;
                            ">
                            <i class="bi bi-clock-history"></i> Đã gửi yêu cầu gia hạn
                        </span>

                        <button id="openPdf" class="btn-hopdong" @click="handleViewPdf" :disabled="!contract">
                            Xem trực tiếp hợp đồng tại đây!
                        </button>
                    </div>
                </div>

                <div class="history_thanhtoan">
                    <h2>LỊCH SỬ HOÁ ĐƠN</h2>
                    <Link :href="route('lichsuthanhtoan')" class="btn-hopdong">Xem trực tiếp lịch sử thanh toán</Link>
                </div>
                <!-- Nút Báo cáo sự cố / vi phạm -->
                <button @click="openReportModal" class="btn-bao-cao">
                    <i class="bi bi-flag-fill"></i>
                    Báo cáo sự cố / vi phạm
                </button>
            </div>
        </div>

        <!-- Modal Xem PDF / Ảnh Hợp Đồng -->
        <div id="pdfModal" class="modal" :style="{ display: showPdfModal ? 'flex' : 'none' }">
            <div class="modal-content" style="
                    max-height: 90vh;
                    overflow-y: auto;
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    position: relative;
                    width: 80%;
                    max-width: 800px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                ">
                <span @click="showPdfModal = false"
                    class="absolute top-2 right-4 text-slate-400 hover:text-slate-600 cursor-pointer" style="
                        position: absolute;
                        top: 10px;
                        right: 15px;
                        font-size: 32px;
                        line-height: 1;
                        z-index: 50;
                    ">&times;</span>

                <h3 style="
                        margin-bottom: 15px;
                        color: #10b981;
                        font-weight: bold;
                    ">
                    HỢP ĐỒNG THUÊ TRỌ
                </h3>
                <div v-if="!contract?.contract_file_path" class="p-6 text-center text-slate-500 font-semibold mt-4"
                    style="color: #64748b; font-size: 1.1rem">
                    Chưa có tệp hợp đồng được tải lên.
                </div>
                <div v-else-if="isImage(contract.contract_file_path)" class="text-center p-4" style="width: 100%">
                    <img :src="getContractUrl()" alt="Hợp đồng" style="
                            max-width: 100%;
                            height: auto;
                            border-radius: 8px;
                        " />
                </div>
                <iframe v-else :src="getContractUrl()" style="
                        width: 100%;
                        height: 70vh;
                        border: none;
                        border-radius: 8px;
                    "></iframe>
            </div>
        </div>

        <!-- MODAL CẬP NHẬT CHỈ SỐ ĐIỆN NƯỚC NHẬN PHÒNG -->
        <div v-if="showEntryModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-800 text-base flex items-center gap-2">
                        <i class="bi bi-speedometer2 text-emerald-600"></i>
                        <span>Chỉ số điện / nước khi nhận phòng</span>
                    </h3>
                    <button @click="showEntryModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitEntryReadings" class="space-y-4">
                    <!-- Khối Điện -->
                    <div class="p-3 bg-amber-50/50 border border-amber-200/60 rounded-xl space-y-3">
                        <label class="block text-xs font-bold text-amber-900 flex items-center gap-1.5">
                            <i class="bi bi-lightning-charge-fill text-amber-500"></i>
                            <span>Chỉ số ĐIỆN ban đầu (kWh)</span>
                        </label>
                        <input type="number" min="0" v-model="entryForm.entry_elec_index"
                            placeholder="Ví dụ: 1250"
                            class="w-full px-3 py-2 text-sm border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white" />
                        <div>
                            <span class="text-[11px] text-slate-500 font-semibold block mb-1">Ảnh chụp công tơ điện lúc
                                nhận phòng:</span>
                            <input type="file" accept="image/*" @change="handleElecImg"
                                class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-white hover:file:bg-amber-600 cursor-pointer" />
                            <div v-if="
                                elecImgPreview || contract?.entry_elec_image
                            " class="mt-2 w-28 h-28 rounded-lg overflow-hidden border border-amber-200 shadow-xs">
                                <img :src="elecImgPreview ||
                                    contract?.entry_elec_image
                                    " class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    <!-- Khối Nước -->
                    <div class="p-3 bg-blue-50/50 border border-blue-200/60 rounded-xl space-y-3">
                        <label class="block text-xs font-bold text-blue-900 flex items-center gap-1.5">
                            <i class="bi bi-droplet-fill text-blue-500"></i>
                            <span>Chỉ số NƯỚC ban đầu (m³)</span>
                        </label>
                        <input type="number" min="0" v-model="entryForm.entry_water_index"
                            placeholder="Ví dụ: 85"
                            class="w-full px-3 py-2 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white" />
                        <div>
                            <span class="text-[11px] text-slate-500 font-semibold block mb-1">Ảnh chụp công tơ nước lúc
                                nhận phòng:</span>
                            <input type="file" accept="image/*" @change="handleWaterImg"
                                class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600 cursor-pointer" />
                            <div v-if="
                                waterImgPreview ||
                                contract?.entry_water_image
                            " class="mt-2 w-28 h-28 rounded-lg overflow-hidden border border-blue-200 shadow-xs">
                                <img :src="waterImgPreview ||
                                    contract?.entry_water_image
                                    " class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="showEntryModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition">
                            Hủy
                        </button>
                        <button type="submit" :disabled="entryForm.processing"
                            class="px-5 py-2 text-xs font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition shadow-md flex items-center gap-1.5">
                            <i v-if="entryForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                            <span>Lưu thông tin</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Yêu cầu Chấm dứt Hợp đồng cho Client -->
        <div v-if="showTerminateModal" class="modal" style="
                display: flex;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                justify-content: center;
                align-items: center;
            ">
            <div class="modal-content" style="
                    background: white;
                    padding: 24px;
                    border-radius: 16px;
                    height: 400px;
                    width: 90%;
                    max-width: 500px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                ">
                <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 16px;
                    ">
                    <h3 style="
                            font-size: 18px;
                            font-weight: bold;
                            color: #dc2626;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        ">
                        <i class="bi bi-exclamation-octagon-fill"></i> Yêu Cầu
                        Chấm Dứt Hợp Đồng
                    </h3>
                    <button @click="showTerminateModal = false" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            color: #94a3b8;
                            cursor: pointer;
                        ">
                        &times;
                    </button>
                </div>

                <p style="
                        font-size: 13.5px;
                        color: #475569;
                        margin-bottom: 16px;
                        line-height: 1.5;
                    ">
                    Bạn đang chuẩn bị gửi yêu cầu chấm dứt/thanh lý hợp đồng sớm
                    cho chủ trọ. Vui lòng nhập rõ lý do bên dưới để chủ trọ tiếp
                    nhận và xử lý.
                </p>

                <div style="margin-bottom: 20px">
                    <label style="
                            display: block;
                            font-size: 13px;
                            font-weight: 600;
                            color: #334155;
                            margin-bottom: 6px;
                        ">Lý do chấm dứt hợp đồng (*):</label>
                    <textarea v-model="terminateForm.reason" rows="4"
                        placeholder="Ví dụ: Chuyển nơi công tác / Trả phòng do hết nhu cầu thuê..." style="
                            width: 100%;
                            padding: 10px 12px;
                            border: 1px solid #cbd5e1;
                            border-radius: 8px;
                            font-size: 13.5px;
                            outline: none;
                            box-sizing: border-box;
                        "></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px">
                    <button @click="showTerminateModal = false" style="
                            padding: 9px 16px;
                            background: #f1f5f9;
                            color: #475569;
                            border: none;
                            border-radius: 8px;
                            font-weight: 600;
                            cursor: pointer;
                        ">
                        Hủy bỏ
                    </button>
                    <button @click="submitTerminateRequest" :disabled="terminateForm.processing" style="
                            padding: 9px 18px;
                            background: #dc2626;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            font-weight: 600;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                        ">
                        <i class="bi bi-send-fill"></i> Gửi Yêu Cầu
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL GIỚI THIỆU NGƯỜI QUEN VÀO Ở GHÉP -->
        <div v-if="showAcquaintanceModal" class="modal-overlay">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="bi bi-person-plus-fill"></i> GIỚI THIỆU THÀNH
                        VIÊN Ở GHÉP
                    </h3>
                    <button type="button" @click="showAcquaintanceModal = false" class="modal-close-btn">
                        &times;
                    </button>
                </div>

                <p class="modal-body-desc">
                    Vui lòng nhập chính xác thông tin thành viên bạn giới thiệu
                    vào phòng. Yêu cầu sẽ được gửi tới chủ nhà phê duyệt để thêm
                    vào cư dân phòng.
                </p>

                <form @submit.prevent="submitAcquaintanceRequest">
                    <div class="form-field-group">
                        <label class="form-field-label">Họ và tên (*)</label>
                        <input type="text" v-model="acquaintanceForm.new_resident_name"
                            placeholder="Nhập họ tên..." class="form-field-input" />
                    </div>

                    <div class="form-field-group">
                        <label class="form-field-label">Số điện thoại (*)</label>
                        <input type="text" v-model="acquaintanceForm.new_resident_phone"
                            placeholder="Ví dụ: 0987654321..." class="form-field-input" />
                    </div>

                    <div class="form-field-group">
                        <label class="form-field-label">Email liên hệ (*)</label>
                        <input type="email" v-model="acquaintanceForm.new_resident_email"
                            placeholder="Nhập địa chỉ email..." class="form-field-input" />
                    </div>

                    <div class="form-field-group">
                        <label class="form-field-label">Số CCCD/CMND (12 chữ số) (*)</label>
                        <input type="text" v-model="acquaintanceForm.new_resident_cccd"
                            placeholder="Đúng 12 chữ số..." maxlength="12" class="form-field-input" />
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showAcquaintanceModal = false" class="modal-cancel-btn">
                            Hủy
                        </button>
                        <button type="submit" :disabled="acquaintanceForm.processing" class="modal-submit-btn">
                            <i v-if="acquaintanceForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                            <i v-else class="bi bi-send-fill"></i> Gửi yêu cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal Yêu cầu gia hạn hợp đồng phía Khách -->
        <div v-if="showExtendRequestModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-emerald-600 flex items-center gap-1.5">
                        <i class="bi bi-arrow-repeat"></i> Gửi Yêu Cầu Gia Hạn
                        Hợp Đồng
                    </h3>
                    <button @click="showExtendRequestModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitExtendRequest" novalidate class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Số tháng muốn gia hạn thêm
                            <span class="text-rose-500">*</span></label>
                        <select v-model="extendRequestForm.desired_months"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white">
                            <option :value="3">3 Tháng</option>
                            <option :value="6">6 Tháng (Nửa năm)</option>
                            <option :value="12">12 Tháng (1 Năm)</option>
                            <option :value="24">24 Tháng (2 Năm)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Ghi chú gửi Chủ trọ</label>
                        <textarea v-model="extendRequestForm.note" rows="3"
                            placeholder="Nhập nguyện vọng hoặc đề xuất của bạn..."
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs outline-none resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 mt-2">
                        <button type="button" @click="showExtendRequestModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            Hủy
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                            Gửi Yêu Cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL BÁO CÁO SỰ CỐ VÀ VI PHẠM -->
        <div v-if="showReportModal"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div
                class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-5 border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-extrabold text-rose-600 flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-rose-500"></i>
                        <span>Gửi Báo Cáo Sự Cố / Vi Phạm</span>
                    </h3>
                    <button @click="showReportModal = false"
                        class="text-slate-400 hover:text-slate-600 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitReport" novalidate class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-600">Loại báo cáo <span
                                class="text-rose-500">*</span></label>
                        <select v-model="reportForm.reason"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-rose-500 rounded-xl text-xs font-semibold outline-none bg-white">
                            <option value="" disabled>--Chọn phân loại báo cáo--</option>
                            <option v-for="(r, idx) in props.reasons" :key="r.id || idx" :value="typeof r === 'object' ? r.reason : r">
                                {{ typeof r === 'object' ? r.reason : r }}
                            </option>
                            <option v-if="!props.reasons || props.reasons.length === 0" value="Khác">
                                Lý do khác
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-600">Mô tả chi tiết sự cố <span
                                class="text-rose-500">*</span></label>
                        <textarea v-model="reportForm.description" rows="4"
                            placeholder="Mô tả cụ thể vị trí, tình trạng hỏng hóc hoặc vấn đề vi phạm cần xử lý..."
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-rose-500 rounded-xl text-xs outline-none resize-none"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-600">Hình ảnh minh chứng (nếu có)</label>
                        <input type="file" multiple accept="image/*" @change="handleEvidenceImages"
                            class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer" />
                        <div v-if="previewEvidenceImages.length > 0" class="flex flex-wrap gap-2 pt-2">
                            <img v-for="(img, idx) in previewEvidenceImages" :key="idx" :src="img"
                                class="w-16 h-16 object-cover rounded-xl border border-slate-200 shadow-2xs" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 mt-2">
                        <button type="button" @click="showReportModal = false"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            Hủy
                        </button>
                        <button type="submit" :disabled="reportForm.processing"
                            class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i v-if="reportForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                            <i v-else class="bi bi-send-fill"></i>
                            <span>Gửi Báo Cáo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/qlynoio.css";
@import "../../css/responsive/responsiveqlytro.css";
@import "../../css/responsive/responsive.css";
@import "../../css/responsive/responsivetranguser.css";
</style>
