<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, Link, useForm, usePage, router } from "@inertiajs/vue3";
import { ref, computed, Teleport } from "vue";
import { showSuccess, showError, showConfirm } from "@/Utils/swal";
import axios from "axios";
import { compressMultipleImages } from "@/Utils/compressor";

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
        (res) => String(res.user_id) !== String(primaryTenantId),
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
    entryForm.post(
        route("profile.entry-readings.submit", props.contract.hash_id || props.contract.id),
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
    if (props.contract?.contract_file_url) return props.contract.contract_file_url;
    const path = props.contract?.contract_file_path || props.contract?.signed_contract_image;
    if (!path) return null;
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const filename = path.split("/").pop();
    return `/files/private/contracts/${filename}`;
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
const showAcquaintanceModal = ref(false);
const acquaintanceForm = useForm({
    new_resident_name: "",
    new_resident_phone: "",
    new_resident_email: "",
    new_resident_cccd: "",
});

const isCheckingRoommateUser = ref(false);
const roommateUserCheckResult = ref(null);
let roommateCheckTimeout = null;

const checkRoommateUserInfo = () => {
    if (roommateCheckTimeout) clearTimeout(roommateCheckTimeout);
    const phone = acquaintanceForm.new_resident_phone?.trim() || "";
    const email = acquaintanceForm.new_resident_email?.trim() || "";
    const cccd = acquaintanceForm.new_resident_cccd?.trim() || "";

    if (!phone && !email && !cccd) {
        roommateUserCheckResult.value = null;
        return;
    }

    roommateCheckTimeout = setTimeout(async () => {
        isCheckingRoommateUser.value = true;
        try {
            const res = await axios.post(route("profile.roommate.check_user"), {
                phone,
                email,
                cccd,
            });
            roommateUserCheckResult.value = res.data;
            if (res.data?.exists && res.data?.user) {
                if (!acquaintanceForm.new_resident_name && res.data.user.name) {
                    acquaintanceForm.new_resident_name = res.data.user.name;
                }
                if (!acquaintanceForm.new_resident_phone && res.data.user.phone) {
                    acquaintanceForm.new_resident_phone = res.data.user.phone;
                }
                if (!acquaintanceForm.new_resident_email && res.data.user.email) {
                    acquaintanceForm.new_resident_email = res.data.user.email;
                }
                if (!acquaintanceForm.new_resident_cccd && res.data.user.cccd_number) {
                    acquaintanceForm.new_resident_cccd = res.data.user.cccd_number;
                }
            }
        } catch (e) {
            roommateUserCheckResult.value = null;
        } finally {
            isCheckingRoommateUser.value = false;
        }
    }, 400);
};

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
    if (roommateUserCheckResult.value?.is_renting_elsewhere) {
        showError(
            "Không thể gửi",
            roommateUserCheckResult.value.message || "Thành viên này hiện đang thuê trọ ở nơi khác!",
        );
        return;
    }
    acquaintanceForm.post(route("profile.roommate.request_acquaintance"), {
        onSuccess: () => {
            showAcquaintanceModal.value = false;
            acquaintanceForm.reset();
            roommateUserCheckResult.value = null;
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
const isSubmittingExtension = ref(false);

const submitExtendRequest = () => {
    if (!props.contract?.id) {
        showError("Lỗi", "Không tìm thấy thông tin hợp đồng.");
        return;
    }

    isSubmittingExtension.value = true;

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
            onFinish: () => {
                isSubmittingExtension.value = false;
            },
        },
    );
};

const handleViewPdf = () => {
    if (!props.contract || (!props.contract.contract_file_path && !props.contract.contract_file_url)) {
        showError("Thông báo Hợp đồng", "File hợp đồng của phòng hiện chưa được chủ trọ tải lên.");
        return;
    }
    // Cho phép cả Chủ hợp đồng lẫn Thành viên ở ghép đều xem được file PDF hợp đồng của phòng
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
const handleEvidenceImages = async (e) => {
    const files = Array.from(e.target.files);
    const compressedFiles = await compressMultipleImages(files);
    reportForm.evidence_images = compressedFiles;
    previewEvidenceImages.value = compressedFiles.map((file) =>
        URL.createObjectURL(file),
    );
};

const submitReport = () => {
    reportForm.post(route("reports.store"), {
        forceFormData: true,
        onSuccess: () => {
            showReportModal.value = false;
            showSuccess(
                "Thành công",
                "Đã gửi báo cáo thành công! Hệ thống sẽ hỗ trợ bạn xử lý.",
            );
        },
        onError: () => { },
    });
};
</script>

<template>

    <Head title="Trang Quản Lý Nơi Ở | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div v-if="!contract" class="alert-no-contract">
                    <i class="bi bi-exclamation-triangle-fill"></i> Bạn hiện
                    chưa có hợp đồng thuê trọ nào có hiệu lực.
                </div>

                <div v-else-if="contract?.status === 'termination_requested'" class="alert-no-contract alert-no-contract-warning">
                    <i class="bi bi-clock-history"></i> Bạn đã gửi yêu cầu chấm
                    dứt hợp đồng này. Chủ trọ đang xem xét và tiến hành thủ tục
                    thanh lý.
                </div>

                <div class="title_noio">
                    <h2>THÔNG TIN NƠI Ở</h2>
                    <div class="status" :style="{ background: getStatusBg }">
                        <p>{{ getStatusLabel }}</p>
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
                    <div v-if="filteredRoommates && filteredRoommates.length > 0" class="roommates-section">
                        <h3 class="roommates-title">
                            <i class="bi bi-people-fill roommates-title-icon"></i>
                            DANH SÁCH THÀNH VIÊN Ở GHÉP TRONG PHÒNG ({{
                                filteredRoommates?.length || 0
                            }}
                            người)
                        </h3>
                        <div class="roommates-list">
                            <div v-for="res in filteredRoommates" :key="res.id" class="roommate-item">
                                <div>
                                    <strong class="roommate-name">{{
                                        res.user?.name || "Thành viên"
                                    }}</strong>
                                    <span class="roommate-phone">SĐT:
                                        {{ res.user?.phone || "Chưa có" }}</span>
                                </div>
                                <span class="roommate-badge">
                                    <i class="bi bi-person-check-fill"></i> Đang
                                    ở ghép
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
                        <button v-if="
                            props.isPrimaryTenant &&
                            contract.room?.capacity > 1 &&
                            (contract.room?.current_people || 1) <
                            contract.room?.capacity
                        " type="button" @click="submitStrangerRequest" class="btn-roommate-stranger">
                            <i class="bi bi-people-fill"></i> Tìm người ở ghép
                        </button>

                        <!-- Nút Giới thiệu bạn bè: Chỉ hiện khi là Chủ hợp đồng, phòng > 1 người và chưa đầy -->
                        <button v-if="
                            props.isPrimaryTenant &&
                            contract.room?.capacity > 1 &&
                            (contract.room?.current_people || 1) <
                            contract.room?.capacity
                        " type="button" @click="showAcquaintanceModal = true" class="btn-roommate-acquaintance">
                            <i class="bi bi-person-plus-fill"></i> Giới thiệu
                            người vào ở
                        </button>

                        <!-- Nút Chấm dứt HĐ: Chỉ hiện dành cho Chủ hợp đồng -->
                        <button v-if="props.isPrimaryTenant" type="button" @click="showTerminateModal = true"
                            class="btn-terminate">
                            <i class="bi bi-x-circle-fill"></i>
                            {{ terminateButtonText }}
                        </button>
                    </div>
                </form>

                <div class="hopdong">
                    <h2>HỢP ĐỒNG THUÊ TRỌ</h2>
                    <div class="hopdong-actions">
                        <!-- Nút Yêu cầu gia hạn HĐ: CHỈ HIỆN DÀNH CHO CHỦ HỢP ĐỒNG -->
                        <button v-if="
                            props.isPrimaryTenant && !hasRequestedExtension
                        " @click="showExtendRequestModal = true" class="btn-hopdong btn-extend-request">
                            <i class="bi bi-arrow-repeat"></i> Yêu cầu gia hạn
                            HĐ
                        </button>
                        <span v-else-if="
                            props.isPrimaryTenant && hasRequestedExtension
                        " class="badge-requested-extension">
                            <i class="bi bi-clock-history"></i> Đã gửi yêu cầu
                            gia hạn
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

        <!-- Modal Yêu cầu Chấm dứt Hợp đồng cho Client -->
        <Teleport to="body">
            <div v-if="showTerminateModal" class="terminate-modal-overlay">
                <div class="terminate-modal-card">
                    <div class="terminate-modal-header">
                        <h3 class="terminate-modal-title">
                            <i class="bi bi-exclamation-octagon-fill"></i> Yêu Cầu
                            Chấm Dứt Hợp Đồng
                        </h3>
                        <button @click="showTerminateModal = false" class="terminate-modal-close">
                            &times;
                        </button>
                    </div>

                    <p class="terminate-modal-desc">
                        Bạn đang chuẩn bị gửi yêu cầu chấm dứt/thanh lý hợp đồng sớm
                        cho chủ trọ. Vui lòng nhập rõ lý do bên dưới để chủ trọ tiếp
                        nhận và xử lý.
                    </p>

                    <div class="terminate-modal-field">
                        <label class="terminate-modal-label">Lý do chấm dứt hợp đồng <span class="text-danger-star">(*)</span>:</label>
                        <textarea v-model="terminateForm.reason" rows="4"
                            placeholder="Ví dụ: Chuyển nơi công tác / Trả phòng do hết nhu cầu thuê..." class="terminate-modal-textarea"></textarea>
                    </div>

                    <div class="terminate-modal-footer">
                        <button @click="showTerminateModal = false" class="btn-cancel">
                            Hủy bỏ
                        </button>
                        <button @click="submitTerminateRequest" :disabled="terminateForm.processing" class="btn-submit-danger">
                            <i class="bi bi-send-fill"></i> Gửi Yêu Cầu
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Xem PDF / Ảnh Hợp Đồng -->
        <Teleport to="body">
            <div v-if="showPdfModal" class="contract-modal-overlay">
                <div class="contract-modal">
                    <!-- HEADER -->
                    <div class="contract-modal-header">
                        <div class="contract-modal-title">
                            <div class="contract-modal-icon">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div>
                                <h3>Hợp đồng thuê trọ</h3>
                                <p>Bản ghi điện tử chính thức đã ký kết</p>
                            </div>
                        </div>
                        <button type="button" @click="showPdfModal = false" class="contract-modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <!-- BODY -->
                    <div class="contract-modal-body">
                        <!-- Không có file -->
                        <div v-if="!contract?.contract_file_path && !contract?.contract_file_url"
                            class="contract-empty">
                            <div class="contract-empty-icon">
                                <i class="bi bi-file-earmark-x"></i>
                            </div>
                            <p>
                                Chưa có tệp hợp đồng được tải lên.
                            </p>
                        </div>
                        <!-- Ảnh hợp đồng -->
                        <div v-else-if="isImage(contract?.contract_file_path || contract?.contract_file_url)"
                            class="contract-image-viewer">
                            <img :src="getContractUrl()" alt="Hợp đồng thuê trọ" class="contract-image" />
                        </div>
                        <!-- PDF -->
                        <iframe v-else :src="getContractUrl()" class="contract-pdf"></iframe>
                    </div>
                    <!-- FOOTER -->
                    <div class="contract-modal-footer">
                        <a v-if="getContractUrl()" :href="getContractUrl()" target="_blank" download
                            class="contract-download-btn">
                            <i class="bi bi-arrows-fullscreen"></i>
                            <span>Xem ảnh gốc / Tải tệp</span>
                        </a>
                        <div v-else></div>
                        <button type="button" @click="showPdfModal = false" class="contract-close-btn">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL GIỚI THIỆU NGƯỜI QUEN VÀO Ở GHÉP -->
        <Teleport to="body">
            <div v-if="showAcquaintanceModal" class="modal-overlay modal-overlay-top">
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
                            <label class="form-field-label">Số điện thoại <span class="text-danger-star">(*)</span></label>
                            <input type="text" v-model="acquaintanceForm.new_resident_phone"
                                @input="checkRoommateUserInfo" placeholder="Ví dụ: 0987654321..."
                                class="form-field-input" />
                        </div>

                        <div class="form-field-group">
                            <label class="form-field-label">Email liên hệ <span class="text-danger-star">(*)</span></label>
                            <input type="email" v-model="acquaintanceForm.new_resident_email"
                                @input="checkRoommateUserInfo" placeholder="Nhập địa chỉ email..."
                                class="form-field-input" />
                        </div>

                        <div class="form-field-group">
                            <label class="form-field-label">Số CCCD/CMND (12 chữ số) <span class="text-danger-star">(*)</span></label>
                            <input type="text" v-model="acquaintanceForm.new_resident_cccd"
                                @input="checkRoommateUserInfo" placeholder="Đúng 12 chữ số..." maxlength="12"
                                class="form-field-input" />
                        </div>

                        <div class="form-field-group">
                            <label class="form-field-label">Họ và tên <span class="text-danger-star">(*)</span></label>
                            <input type="text" v-model="acquaintanceForm.new_resident_name" placeholder="Nhập họ tên..."
                                class="form-field-input" />
                        </div>

                        <!-- Real-time check feedback badge -->
                        <div v-if="isCheckingRoommateUser" class="roommate-check-loading">
                            <i class="bi bi-arrow-repeat animate-spin"></i> Đang kiểm tra thông tin tài khoản trên hệ
                            thống...
                        </div>
                        <div v-else-if="roommateUserCheckResult" class="roommate-check-result"
                            :class="{
                                'check-renting-elsewhere': roommateUserCheckResult.exists && roommateUserCheckResult.is_renting_elsewhere,
                                'check-exists': roommateUserCheckResult.exists && !roommateUserCheckResult.is_renting_elsewhere,
                                'check-new': !roommateUserCheckResult.exists
                            }">
                            <i :class="roommateUserCheckResult.exists
                                ? (roommateUserCheckResult.is_renting_elsewhere ? 'bi bi-exclamation-triangle-fill' : 'bi bi-check-circle-fill')
                                : 'bi bi-info-circle-fill'" class="roommate-check-icon"></i>
                            <div>
                                <div>{{ roommateUserCheckResult.message }}</div>
                                <div v-if="roommateUserCheckResult.exists && roommateUserCheckResult.user" class="roommate-check-subtext">
                                    Thành viên: <strong>{{ roommateUserCheckResult.user.name }}</strong> (SĐT: {{
                                        roommateUserCheckResult.user.phone || 'Chưa cập nhật' }})
                                </div>
                            </div>
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
        </Teleport>
    </UserLayout>

    <!-- Modal Yêu cầu Gia hạn Hợp đồng cho Client -->
    <div v-if="showExtendRequestModal" class="extend-modal-overlay">
        <div class="extend-modal-card">
            <!-- Header -->
            <div class="extend-modal-header">
                <h3 class="extend-modal-title">
                    <i class="bi bi-arrow-repeat"></i> Yêu Cầu Gia Hạn Hợp Đồng
                </h3>
                <button @click="showExtendRequestModal = false" class="extend-modal-close">
                    &times;
                </button>
            </div>

            <!-- Description -->
            <p class="extend-modal-desc">
                Bạn đang gửi yêu cầu tiếp tục gia hạn hợp đồng thuê phòng. Vui lòng chọn thời gian và nhập ghi chú (nếu
                có) để chủ trọ xét duyệt.
            </p>

            <!-- Form Body -->
            <form @submit.prevent="submitExtendRequest">
                <!-- Chọn thời gian gia hạn -->
                <div class="extend-modal-field">
                    <label class="extend-modal-label">Số tháng muốn gia hạn thêm <span class="text-danger-star">(*)</span>:</label>
                    <select v-model="extendRequestForm.desired_months" class="extend-modal-select">
                        <option :value="3">3 Tháng</option>
                        <option :value="6">6 Tháng (Nửa năm)</option>
                        <option :value="12">12 Tháng (1 Năm)</option>
                        <option :value="24">24 Tháng (2 Năm)</option>
                    </select>
                </div>

                <!-- Ghi chú -->
                <div class="extend-modal-field-lg">
                    <label class="extend-modal-label">Ghi chú gửi Chủ trọ:</label>
                    <textarea v-model="extendRequestForm.note" rows="3"
                        placeholder="Nhập nguyện vọng hoặc đề xuất thêm của bạn..." class="extend-modal-textarea"></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="extend-modal-footer">
                    <button type="button" @click="showExtendRequestModal = false" class="btn-cancel">
                        Hủy bỏ
                    </button>
                    <button type="submit" :disabled="isSubmittingExtension" class="btn-submit-success">
                        <i v-if="isSubmittingExtension" class="bi bi-arrow-repeat animate-spin"></i>
                        <i v-else class="bi bi-send-fill"></i> Gửi Yêu Cầu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Báo cáo Sự cố và Vi phạm cho Client -->
    <div v-if="showReportModal" class="report-modal-overlay">
        <div class="report-modal-card">

            <!-- Header -->
            <div class="report-modal-header">
                <h3 class="report-modal-title">
                    <i class="bi bi-exclamation-octagon-fill"></i> Báo Cáo Sự Cố / Vi Phạm
                </h3>
                <button type="button" @click="showReportModal = false" class="report-modal-close">
                    &times;
                </button>
            </div>

            <!-- Description -->
            <p class="report-modal-desc">
                Vui lòng cung cấp đầy đủ thông tin chi tiết và hình ảnh minh chứng để chủ trọ kịp thời nắm bắt và xử lý
                sự cố.
            </p>

            <!-- Form Body -->
            <form @submit.prevent="submitReport">

                <!-- Phân loại báo cáo -->
                <div class="report-modal-field">
                    <label class="report-modal-label">Loại báo cáo <span class="text-danger-star">(*)</span>:</label>
                    <select v-model="reportForm.reason" class="report-modal-select">
                        <option value="" disabled>-- Chọn phân loại báo cáo --</option>
                        <option v-for="(r, idx) in props.reasons" :key="r.id || idx"
                            :value="typeof r === 'object' ? r.reason : r">
                            {{ typeof r === 'object' ? r.reason : r }}
                        </option>
                        <option v-if="!props.reasons || props.reasons.length === 0" value="Khác">
                            Lý do khác
                        </option>
                    </select>
                    <p v-if="reportForm.errors?.reason" class="report-field-error">
                        {{ reportForm.errors.reason }}
                    </p>
                </div>

                <!-- Mô tả chi tiết -->
                <div class="report-modal-field">
                    <label class="report-modal-label">Mô tả chi tiết <span class="text-danger-star">(*)</span>:</label>
                    <textarea v-model="reportForm.description" rows="4"
                        placeholder="Mô tả cụ thể vị trí, tình trạng hỏng hóc hoặc vấn đề cần xử lý..." class="report-modal-textarea"></textarea>
                    <p v-if="reportForm.errors?.description" class="report-field-error">
                        {{ reportForm.errors.description }}
                    </p>
                </div>

                <!-- Hình ảnh minh chứng -->
                <div class="report-modal-field-lg">
                    <label class="report-modal-label">Hình ảnh minh chứng:</label>

                    <div class="evidence-upload-grid">
                        <!-- Camera -->
                        <label class="evidence-upload-btn evidence-upload-btn-camera">
                            <div class="evidence-icon-box">
                                <i class="bi bi-camera-fill evidence-icon-camera"></i>
                            </div>
                            <div>
                                <span class="evidence-btn-title text-danger">Chụp ảnh</span>
                                <span class="evidence-btn-sub text-danger-sub">Mở camera</span>
                            </div>
                            <input type="file" accept="image/*" capture="environment" class="hidden-file-input"
                                @change="handleEvidenceImages" />
                        </label>

                        <!-- Bộ sưu tập -->
                        <label class="evidence-upload-btn evidence-upload-btn-gallery">
                            <div class="evidence-icon-box">
                                <i class="bi bi-images evidence-icon-gallery"></i>
                            </div>
                            <div>
                                <span class="evidence-btn-title text-slate">Bộ sưu tập</span>
                                <span class="evidence-btn-sub text-slate-sub">Chọn ảnh có sẵn</span>
                            </div>
                            <input type="file" multiple accept="image/*,.heic,.heif" class="hidden-file-input"
                                @change="handleEvidenceImages" />
                        </label>
                    </div>

                    <!-- Preview ảnh -->
                    <div v-if="previewEvidenceImages && previewEvidenceImages.length > 0" class="evidence-preview-grid">
                        <div v-for="(img, idx) in previewEvidenceImages" :key="idx">
                            <img :src="img" class="evidence-preview-img" />
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="report-modal-footer">
                    <button type="button" @click="showReportModal = false" class="btn-cancel">
                        Hủy bỏ
                    </button>
                    <button type="submit" :disabled="reportForm.processing" class="btn-submit-danger">
                        <i v-if="reportForm.processing" class="bi bi-arrow-repeat animate-spin"></i>
                        <i v-else class="bi bi-send-fill"></i>
                        <span>{{ reportForm.processing ? "Đang gửi..." : "Gửi Báo Cáo" }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
@import "../../css/qlynoio.css";
@import "../../css/responsive/responsiveqlytro.css";
@import "../../css/responsive/responsive.css";
@import "../../css/responsive/responsivetranguser.css";
</style>
