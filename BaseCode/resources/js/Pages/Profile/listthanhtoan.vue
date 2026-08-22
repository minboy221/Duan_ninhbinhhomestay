<script setup>
import { ref, computed, onUnmounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { showWarning, showSuccess } from "@/Utils/swal";

const props = defineProps({
    user: Object,
    invoices: {
        type: Array,
        default: () => [],
    },
    reasons: {
        type: Array,
        default: () => [],
    },
});

const activeInvoice = ref(null);
const selectedPaymentMethod = ref("qr");
const showDetailModal = ref(false);
const showMeterProofModal = ref(false);
const showReportModal = ref(false);
const reportText = ref("");
const isSimulating = ref(false);
const autoSuccessMsg = ref("");

let pollTimer = null;

const startPolling = () => {
    stopPolling();
    if (!activeInvoice.value || activeInvoice.value.status === "paid") return;

    pollTimer = setInterval(async () => {
        if (!activeInvoice.value || !showDetailModal.value) {
            stopPolling();
            return;
        }
        try {
            const res = await axios.get(
                `/api/invoices/${activeInvoice.value.id}/status`,
            );
            if (res.data?.success && res.data.status === "paid") {
                activeInvoice.value.status = "paid";
                const invInList = props.invoices.find(
                    (i) => i.id === activeInvoice.value.id,
                );
                if (invInList) invInList.status = "paid";
                autoSuccessMsg.value =
                    "Ngân hàng đã nhận tiền! Hệ thống tự động gạch nợ thành công 🎉";
                stopPolling();
            }
        } catch (e) {
            // quiet poll
        }
    }, 3000);
};

//khởi tạo Inertia form để gửi báo cáo hoá đơn
const reportForm = useForm({
    reportable_type: "Invoice",
    reportable_id: "",
    reason: "",
    resolve_type: 'direct',
    description: "",
    evidence_images: [],
});

//phần nén ảnh
const handleEvidenceChange = (e) => {
    const files = Array.from(e.target.files);
    reportForm.evidence_images = [];

    files.forEach((file) => {
        if (!file.type.startsWith("image/")) {
            reportForm.evidence_images.push(file);
            return;
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement("canvas");
                const max_size = 1200;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > max_size) {
                        height *= max_size / width;
                        width = max_size;
                    }
                } else {
                    if (height > max_size) {
                        width *= max_size / height;
                        height = max_size;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: "image/jpeg",
                                lastModified: Date.now(),
                            });
                            reportForm.evidence_images.push(compressedFile);
                        }
                    },
                    "image/jpeg",
                    0.7,
                );
            };
        };
    });
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const openDetail = (inv) => {
    activeInvoice.value = inv;
    selectedPaymentMethod.value = "qr"; // default method
    paymentSplitMode.value = "full"; // default to full room amount
    showDetailModal.value = true;
    autoSuccessMsg.value = "";
    if (inv.status !== "paid") {
        startPolling();
    }
};

const closeDetail = () => {
    stopPolling();
    showDetailModal.value = false;
    activeInvoice.value = null;
    autoSuccessMsg.value = "";
};

const triggerSimulatedPayment = async () => {
    if (!activeInvoice.value) return;
    isSimulating.value = true;
    try {
        const res = await axios.post("/api/webhooks/simulate-payment", {
            invoice_id: activeInvoice.value.id,
            amount: currentPaymentAmount.value,
        });
        if (res.data?.success) {
            const newStatus = res.data.status || "paid";
            const newPaid = res.data.paid_amount || activeInvoice.value.total_amount;
            activeInvoice.value.status = newStatus;
            activeInvoice.value.paid_amount = newPaid;
            const invInList = props.invoices.find(
                (i) => i.id === activeInvoice.value.id,
            );
            if (invInList) {
                invInList.status = newStatus;
                invInList.paid_amount = newPaid;
            }
            if (newStatus === "partially_paid") {
                autoSuccessMsg.value = `Tín hiệu Webhook Ngân hàng đã khớp! Ghi nhận ĐÃ THANH TOÁN 1 PHẦN (${new Intl.NumberFormat("vi-VN").format(newPaid)}đ) 🟡`;
            } else {
                autoSuccessMsg.value =
                    "Tín hiệu Webhook Ngân hàng đã khớp! Hóa đơn tự động ĐÃ THANH TOÁN HOÀN TẤT 🎉";
                stopPolling();
            }
        }
    } catch (err) {
        alert(
            "Lỗi giả lập thanh toán: " +
            (err.response?.data?.message || err.message),
        );
    } finally {
        isSimulating.value = false;
    }
};

onUnmounted(() => {
    stopPolling();
});

//hàm đóng/ mở submit báo cáo
const openReport = (inv) => {
    activeInvoice.value = inv;
    reportForm.reset();
    reportForm.clearErrors();
    reportForm.reportable_id = inv.id;
    showReportModal.value = true;
};

const closeReport = () => {
    showReportModal.value = false;
    activeInvoice.value = null;
    reportForm.reset();
};

const selectPaymentMethod = (method) => {
    selectedPaymentMethod.value = method;
};

// Format date helper
const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    return d.toLocaleDateString("vi-VN");
};

// Format money helper
const formatMoney = (n) => new Intl.NumberFormat("vi-VN").format(n) + "đ";

// Get specific detail row by keyword
const getDetailByItem = (inv, keyword) => {
    if (!inv || !inv.details) return null;
    return inv.details.find((d) =>
        d.item_name.toLowerCase().includes(keyword.toLowerCase()),
    );
};

const elecDetail = computed(() => getDetailByItem(activeInvoice.value, "Điện"));
const waterDetail = computed(() =>
    getDetailByItem(activeInvoice.value, "Nước"),
);

const zoomedImgUrl = ref(null)
const zoomedImgTitle = ref('')
const zoomImage = (url, title) => {
    zoomedImgUrl.value = getMeterImgUrl(url)
    zoomedImgTitle.value = title
}

const hasMeterImages = computed(() => {
    const eDet = elecDetail.value
    const wDet = waterDetail.value
    return (eDet && (eDet.meter_image_path || eDet.old_meter_image_path)) ||
           (wDet && (wDet.meter_image_path || wDet.old_meter_image_path))
})

const getMeterImgUrl = (path) => {
    if (!path) return null;
    if (path.startsWith("http") || path.startsWith("data:")) return path;
    if (path.startsWith("/storage/")) return path;
    if (path.startsWith("storage/")) return "/" + path;
    if (path.startsWith("/")) return path;
    return "/storage/" + path;
};

const landlordBankInfo = computed(() => {
    if (!activeInvoice.value)
        return { bankName: "", bankAcc: "", bankAccName: "" };
    const landlord =
        activeInvoice.value.contract?.room?.boarding_house?.user ||
        activeInvoice.value.contract?.room?.boardingHouse?.user ||
        {};
    return {
        bankName: landlord.bank_name || "MB Bank",
        bankAcc: landlord.bank_account_no || "0912345678",
        bankAccName: landlord.bank_account_name || "CHỦ TRỌ",
    };
});

const copySuccessMsg = ref("");
const copyToClipboard = (text, label) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copySuccessMsg.value = `Đã sao chép ${label}!`;
    setTimeout(() => {
        copySuccessMsg.value = "";
    }, 2500);
};

// Helper bỏ dấu Tiếng Việt cho nội dung VietQR
const removeVietnameseTones = (str) => {
    if (!str) return "";
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g, "A");
    str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g, "E");
    str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g, "I");
    str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g, "O");
    str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g, "U");
    str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g, "Y");
    str = str.replace(/Đ/g, "D");
    return str.replace(/[^a-zA-Z0-9\s\-_]/g, "").trim();
};

// Cấu trúc Nội dung chuyển khoản: [Số phòng] - [Tên chủ phòng] - [Kỳ thanh toán]
const transferMemo = computed(() => {
    if (!activeInvoice.value) return "";

    const rawRoom =
        activeInvoice.value.contract?.room?.room_number ||
        activeInvoice.value.contract?.room?.name ||
        "";
    const roomStr = rawRoom
        ? rawRoom.toUpperCase().startsWith("P")
            ? rawRoom.replace(/[^a-zA-Z0-9]/g, "")
            : "P" + rawRoom.replace(/[^a-zA-Z0-9]/g, "")
        : "PHONG";

    const rawTenantName =
        activeInvoice.value.contract?.tenant?.name ||
        props.user?.name ||
        "KHACH THUE";
    const tenantNameStr = removeVietnameseTones(rawTenantName).toUpperCase();

    const rawMonth = activeInvoice.value.billing_month || "";
    let monthStr = rawMonth;
    if (rawMonth.includes("-")) {
        const parts = rawMonth.split("-");
        if (parts.length === 2 && parts[0].length === 4) {
            monthStr = `${parts[1]}-${parts[0]}`;
        }
    } else if (rawMonth.includes("/")) {
        monthStr = rawMonth.replace("/", "-");
    }

    let memo = `${roomStr} - ${tenantNameStr} - Thang ${monthStr}`;
    if (memo.length > 50) {
        memo = memo.substring(0, 50);
    }
    return memo;
});

// Tính toán chia tiền phòng ở ghép
const roomOccupantsCount = computed(() => {
    if (!activeInvoice.value) return 1;
    const countFromContract = Number(activeInvoice.value.contract?.number_of_tenants || 0);
    const countFromRoom = Number(activeInvoice.value.contract?.room?.current_occupants || 0);
    const countFromResidents = Array.isArray(activeInvoice.value.contract?.room?.residents) ? activeInvoice.value.contract.room.residents.length : 0;
    return Math.max(countFromContract, countFromRoom, countFromResidents, 1);
});

const isRoomSharing = computed(() => {
    return roomOccupantsCount.value > 1;
});

// Thông tin ngày ở lẻ & tính toán tiền cá nhân cho người dùng hiện tại
const currentUserResidentInfo = computed(() => {
    if (!activeInvoice.value || !props.user) return null;
    const room = activeInvoice.value.contract?.room;
    if (!room || !Array.isArray(room.residents)) return null;

    // Tìm thông tin cư dân của người dùng hiện tại
    const resident = room.residents.find(r => Number(r.user_id) === Number(props.user.id));
    if (!resident || !resident.start_date) return null;

    const startDateStr = String(resident.start_date).substring(0, 10);
    const billingMonthStr = activeInvoice.value.billing_month; // YYYY-MM

    if (startDateStr.substring(0, 7) === billingMonthStr) {
        const parts = startDateStr.split("-");
        const year = Number(parts[0]);
        const month = Number(parts[1]);
        const startDay = Number(parts[2]);

        const totalDaysInMonth = new Date(year, month, 0).getDate();
        const occupiedDays = totalDaysInMonth - startDay + 1;

        return {
            isMidMonth: true,
            startDay,
            occupiedDays,
            totalDaysInMonth,
        };
    }

    return { isMidMonth: false };
});

const perPersonAmount = computed(() => {
    if (!activeInvoice.value || !isRoomSharing.value) return 0;
    const baseShare = activeInvoice.value.total_amount / roomOccupantsCount.value;

    const info = currentUserResidentInfo.value;
    if (info && info.isMidMonth && info.occupiedDays < info.totalDaysInMonth) {
        return Math.round((baseShare / info.totalDaysInMonth) * info.occupiedDays);
    }

    return Math.round(baseShare);
});

// Chế độ thanh toán: 'full' (Toàn bộ hóa đơn) hoặc 'split' (Phần tiền chia đều của tôi)
const paymentSplitMode = ref("full");

const remainingInvoiceAmount = computed(() => {
    if (!activeInvoice.value) return 0;
    const total = Number(activeInvoice.value.total_amount || 0);
    const paid = Number(activeInvoice.value.paid_amount || 0);
    return Math.max(0, total - paid);
});

const currentPaymentAmount = computed(() => {
    if (!activeInvoice.value) return 0;
    const remaining = remainingInvoiceAmount.value;
    if (remaining <= 0) return 0;

    if (paymentSplitMode.value === "split" && isRoomSharing.value) {
        return Math.min(remaining, perPersonAmount.value);
    }
    return Math.round(remaining);
});

// Dynamic VietQR code generation
const qrUrl = computed(() => {
    if (!activeInvoice.value) return "";
    const { bankName, bankAcc, bankAccName } = landlordBankInfo.value;
    const amount = currentPaymentAmount.value;
    const memo = transferMemo.value;

    return `https://img.vietqr.io/image/${bankName}-${bankAcc}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(bankAccName)}`;
});

// Confirm payment submission
const confirmPaymentForm = useForm({
    payment_method: "qr",
});

const confirmPayment = () => {
    if (!activeInvoice.value) return;
    confirmPaymentForm.payment_method = selectedPaymentMethod.value;
    confirmPaymentForm.post(
        route("invoices.notify-payment", activeInvoice.value.id),
        {
            onSuccess: () => {
                closeDetail();
            },
        },
    );
};

// Submit report handler
const submitReport = () => {
    if (!reportForm.reason) {
        showWarning("Thiếu lý do", "Vui lòng chọn lý do báo cáo!");
        return;
    }
    if (reportForm.resolve_type === 'system') {
        if (!reportForm.description || !reportForm.description.trim()) {
            showWarning("Thiếu mô tả", "Vui lòng nhập mô tả chi tiết!");
            return;
        }
    }
    reportForm.post(route("reports.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash && flash.error) {
                showWarning("Không thể gửi", flash.error);
                return;
            }
            showSuccess(
                "Thành công",
                reportForm.resolve_type === 'direct' 
                    ? "Yêu cầu tự giải quyết khiếu nại đã gửi tới chủ trọ!" 
                    : "Báo cáo hóa đơn đã được gửi đi! Hệ thống sẽ kiểm tra và phản hồi sớm nhất."
            );
            closeReport();
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            showWarning("Lỗi gửi báo cáo", firstErr || "Đã có lỗi xảy ra.");
        },
    });
};
</script>

<template>

    <Head title="Lịch Sử Thanh Toán | Ninh Bình HomeStay" />

    <div class="glass-background-zones">
        <div class="zone zone-1"></div>
        <div class="zone zone-2"></div>
        <div class="zone zone-3"></div>
        <div class="zone zone-4"></div>
        <div class="zone zone-5"></div>
        <div class="zone zone-6"></div>
        <div class="zone zone-7"></div>
        <div class="zone zone-8"></div>
    </div>

    <section class="lichsuhoadon">
        <Link :href="route('quanlynoio')" class="back">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </Link>
        <div class="baohoadon">
            <div class="title_hoadon">
                <h2>Trang quản lý hoá đơn trọ</h2>
                <p>Nơi chứa lịch sử giao dịch hàng tháng của khách hàng.</p>
            </div>

            <div class="table_hoadon">
                <table>
                    <thead>
                        <tr>
                            <th>Mã hoá đơn</th>
                            <th>Tháng thanh toán</th>
                            <th>Tổng số tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="inv in invoices" :key="inv.id">
                            <td data-label="Mã hoá đơn">
                                #{{ inv.invoice_code }}
                            </td>
                            <td data-label="Tháng thanh toán">
                                {{ inv.billing_month }}
                            </td>
                            <td data-label="Tổng số tiền">
                                <div class="price">
                                    {{ formatMoney(inv.total_amount) }}
                                </div>
                                <div v-if="(inv.contract?.number_of_tenants || inv.contract?.room?.current_occupants || (inv.contract?.room?.residents && inv.contract.room.residents.length) || 1) > 1" class="text-[11px] text-indigo-600 font-bold mt-0.5">
                                    <i class="bi bi-people-fill"></i> Chia {{ formatMoney(Math.round(inv.total_amount / Math.max(inv.contract?.number_of_tenants || 0, inv.contract?.room?.current_occupants || 0, (inv.contract?.room?.residents?.length || 0), 1))) }}/người
                                </div>
                            </td>
                            <td data-label="Trạng thái">
                                <div v-if="inv.status === 'paid'" class="trangthai">
                                    Đã nhận
                                </div>
                                <div v-else-if="inv.status === 'partially_paid'" class="trangthai warning" style="background: #f59e0b; color: #fff;">
                                    Đã đóng {{ formatMoney(inv.paid_amount || 0) }}
                                </div>
                                <div v-else class="trangthai warning">
                                    Chưa thanh toán
                                </div>
                            </td>
                            <td data-label="Thao tác">
                                <div class="thaotac">
                                    <button class="xemchitiet" @click="openDetail(inv)">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Xem chi tiết</span>
                                    </button>
                                    <button v-if="inv.status !== 'paid'" class="baocao" @click="openReport(inv)">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Báo cáo</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.length === 0">
                            <td colspan="5" class="text-center py-6 text-slate-500 font-semibold">
                                Bạn chưa có hóa đơn nào.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- phần form báo cáo popup -->
    <div id="reportModal" class="modal" :style="{ display: showReportModal ? 'flex' : 'none' }">
        <div class="modal-box small">
            <span class="close" @click="closeReport">&times;</span>
            <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 8px">
                Báo cáo hoá đơn
            </h3>
            <p v-if="activeInvoice" class="text-xs text-slate-500 mb-3 font-semibold"
                style="margin-bottom: 12px; font-size: 12px; color: #64748b">
                Hóa đơn: #{{ activeInvoice.invoice_code }}
            </p>

            <!-- Chọn hình thức xử lý -->
            <div class="mb-3" style="text-align: left; margin-bottom: 12px">
                <label style="
                        display: block;
                        font-size: 11px;
                        font-weight: 700;
                        color: #475569;
                        margin-bottom: 6px;
                    ">Hình thức xử lý khiếu nại <span style="color: #ef4444">*</span></label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <label 
                        style="display: flex; align-items: center; gap: 6px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
                        :style="reportForm.resolve_type === 'direct' ? 'border-color: #4f46e5; background-color: #f5f3ff; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);' : 'background-color: #f8fafc;'"
                    >
                        <input type="radio" v-model="reportForm.resolve_type" value="direct" style="accent-color: #4f46e5;" />
                        <div>
                            <strong style="color: #1e293b; display: block;">Tự giải quyết</strong>
                            <span style="font-size: 9px; color: #64748b;">Thương lượng với chủ trọ</span>
                        </div>
                    </label>
                    <label 
                        style="display: flex; align-items: center; gap: 6px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-size: 11px; transition: all 0.2s;"
                        :style="reportForm.resolve_type === 'system' ? 'border-color: #ef4444; background-color: #fef2f2; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1);' : 'background-color: #f8fafc;'"
                    >
                        <input type="radio" v-model="reportForm.resolve_type" value="system" style="accent-color: #ef4444;" />
                        <div>
                            <strong style="color: #1e293b; display: block;">Báo cáo hệ thống</strong>
                            <span style="font-size: 9px; color: #64748b;">Gửi yêu cầu lên Admin</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Chọn lý do báo cáo động -->
            <div class="mb-3" style="text-align: left; margin-bottom: 12px">
                <label style="
                        display: block;
                        font-size: 11px;
                        font-weight: 700;
                        color: #475569;
                        margin-bottom: 4px;
                    ">Lý do báo cáo</label>
                <select v-model="reportForm.reason" style="
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                        font-size: 12px;
                        outline: none;
                        background-color: #fff;
                    ">
                    <option value="" disabled>-- Chọn lý do báo cáo --</option>
                    <option v-for="(reason, index) in reasons" :key="index" :value="typeof reason === 'object' ? reason.reason : reason">
                        {{ typeof reason === 'object' ? reason.reason : reason }}
                    </option>
                </select>
                <p v-if="reportForm.errors.reason" style="color: #ef4444; font-size: 11px; margin-top: 4px">
                    {{ reportForm.errors.reason }}
                </p>
            </div>

            <!-- Nhập mô tả chi tiết (Chỉ hiển thị khi báo cáo hệ thống) -->
            <div v-if="reportForm.resolve_type === 'system'" class="mb-3" style="text-align: left; margin-bottom: 12px">
                <label style="
                        display: block;
                        font-size: 11px;
                        font-weight: 700;
                        color: #475569;
                        margin-bottom: 4px;
                    ">Mô tả chi tiết <span style="color: #ef4444">*</span></label>
                <textarea v-model="reportForm.description" placeholder="Nhập thêm chi tiết về sai lệch hóa đơn..."
                    style="
                        width: 100%;
                        min-height: 80px;
                        padding: 8px;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                        font-size: 12px;
                        outline: none;
                        box-sizing: border-box;
                        resize: vertical;
                    "></textarea>
                <p v-if="reportForm.errors.description" style="color: #ef4444; font-size: 11px; margin-top: 4px">
                    {{ reportForm.errors.description }}
                </p>
            </div>

            <!-- Upload hình ảnh bằng chứng có nén (Chỉ hiển thị khi báo cáo hệ thống) -->
            <div v-if="reportForm.resolve_type === 'system'" class="mb-3" style="text-align: left; margin-bottom: 16px">
                <label style="
                        display: block;
                        font-size: 11px;
                        font-weight: 700;
                        color: #475569;
                        margin-bottom: 4px;
                    ">Ảnh bằng chứng hóa đơn (Tối đa 5 ảnh) <span style="color: #ef4444">*</span></label>
                <input type="file" @change="handleEvidenceChange" multiple accept="image/*"
                    style="font-size: 11px; display: block; width: 100%" />
                <p v-if="reportForm.errors.evidence_images" style="color: #ef4444; font-size: 11px; margin-top: 4px">
                    {{ reportForm.errors.evidence_images }}
                </p>
            </div>
            <button class="btn-submit" @click="submitReport" :disabled="reportForm.processing">
                {{ reportForm.processing ? "Đang gửi..." : "Gửi báo cáo" }}
            </button>
        </div>
    </div>

    <!-- phần form xem chi tiết popup -->
    <div id="detailModal" class="modal" :style="{ display: showDetailModal ? 'flex' : 'none' }">
        <div class="modal-box" v-if="activeInvoice">
            <span class="close" @click="closeDetail">&times;</span>
            <h3>Chi tiết hoá đơn</h3>

            <div class="cthd">
                <p><strong>Mã:</strong> #{{ activeInvoice.invoice_code }}</p>
                <p><strong>Tháng:</strong> {{ activeInvoice.billing_month }}</p>
                <p v-for="d in activeInvoice.details" :key="d.id">
                    <strong>{{ d.item_name }}
                        <span v-if="d.old_index !== null">({{ d.new_index }} - {{ d.old_index }})</span>:</strong>
                    <span>{{ formatMoney(d.subtotal) }}</span>
                </p>
                <p style="border-top: 1px dashed #cbd5e1; padding-top: 8px; margin-top: 4px">
                    <strong>Tổng tiền phòng:</strong>
                    <span style="color: #059669; font-weight: 800; font-size: 15px">{{ formatMoney(activeInvoice.total_amount) }}</span>
                </p>
            </div>

            <!-- THÔNG TIN PHÒNG Ở GHÉP & CHIA TIỀN -->
            <div v-if="isRoomSharing" style="margin: 12px 0; padding: 12px; background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 12px; font-weight: 700; color: #166534; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-people-fill" style="color: #15803d; font-size: 14px;"></i>
                        Phòng ở ghép ({{ roomOccupantsCount }} người):
                    </span>
                    <span style="font-size: 14px; font-weight: 800; color: #15803d;">
                        {{ formatMoney(perPersonAmount) }} <span style="font-size: 11px; font-weight: normal; color: #166534;">/ phần của bạn</span>
                    </span>
                </div>
                <p v-if="currentUserResidentInfo && currentUserResidentInfo.isMidMonth" style="font-size: 11px; color: #166534; margin: 0; line-height: 1.4;">
                    <i class="bi bi-calendar-check-fill mr-1 text-emerald-600"></i>
                    Bạn dọn vào ở từ ngày <strong>{{ currentUserResidentInfo.startDay }}</strong> trong tháng (ở {{ currentUserResidentInfo.occupiedDays }}/{{ currentUserResidentInfo.totalDaysInMonth }} ngày). Số tiền phần của bạn đã được tự động tính theo số ngày lẻ thực tế!
                </p>
                <p v-else style="font-size: 11px; color: #166534; margin: 0; line-height: 1.4;">
                    Tổng tiền phòng <strong>{{ formatMoney(activeInvoice.total_amount) }}</strong> được chia đều cho <strong>{{ roomOccupantsCount }}</strong> thành viên trong phòng.
                </p>
            </div>

            <!-- HÌNH ẢNH SỐ ĐIỆN NƯỚC -->
            <div class="meter-images" v-if="
                (elecDetail && elecDetail.meter_image_path) ||
                (waterDetail && waterDetail.meter_image_path)
            ">
                <div class="meter-item" v-if="elecDetail && elecDetail.meter_image_path">
                    <p>
                        <i class="bi bi-lightning-charge-fill"></i> Ảnh số điện
                    </p>
                    <div class="img-wrapper">
                        <img :src="getMeterImgUrl(elecDetail.meter_image_path)" alt="Ảnh số điện" />
                    </div>
                </div>
                <div class="meter-item" v-if="waterDetail && waterDetail.meter_image_path">
                    <p><i class="bi bi-droplet-fill"></i> Ảnh số nước</p>
                    <div class="img-wrapper">
                        <img :src="getMeterImgUrl(waterDetail.meter_image_path)" alt="Ảnh số nước" />
                    </div>
                </div>
            </div>

            <!-- HÓA ĐƠN ĐÃ THANH TOÁN -->
            <div v-if="activeInvoice.status === 'paid'"
                class="mt-4 p-6 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl text-center shadow-lg shadow-emerald-500/20">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <h4 class="font-black text-lg mb-1 tracking-wide">
                    HÓA ĐƠN ĐÃ ĐƯỢC THANH TOÁN
                </h4>
                <p class="text-xs text-emerald-50 opacity-90">
                    Hệ thống đã nhận được tiền và tự động gạch nợ thành công.
                </p>
            </div>

            <!-- THANH TOÁN -->
            <div class="thanhtoan" v-else>
                <h4>Phương thức thanh toán</h4>

                <div class="payment-options" style="margin-bottom: 20px;">
                    <div class="payment-item" :class="{ active: selectedPaymentMethod === 'qr' }"
                        @click="selectPaymentMethod('qr')">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Quét mã VietQR Động</span>
                    </div>
                    <div class="payment-item" :class="{ active: selectedPaymentMethod === 'cash' }"
                        @click="selectPaymentMethod('cash')">
                        <i class="bi bi-cash-coin"></i>
                        <span>Tiền mặt trực tiếp</span>
                    </div>
                </div>

                <!-- Toast thông báo tự động -->
                <div v-if="autoSuccessMsg"
                    class="mb-3 p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs rounded-xl text-center font-bold shadow-sm">
                    <i class="bi bi-check-circle-fill text-emerald-600 mr-1"></i>
                    {{ autoSuccessMsg }}
                </div>

                <!-- Toast sao chép thành công -->
                <div v-if="copySuccessMsg"
                    class="mb-3 p-2 bg-blue-50 border border-blue-200 text-blue-700 text-xs rounded-lg text-center font-bold">
                    <i class="bi bi-check-circle-fill mr-1"></i>
                    {{ copySuccessMsg }}
                </div>

                <!-- VietQR Code Dynamic Box -->
                <div class="qr-box p-4 bg-slate-50 border border-slate-200 rounded-2xl text-center space-y-3" :style="{
                    display:
                        selectedPaymentMethod === 'qr' ? 'block' : 'none',
                }">
                    <!-- Lựa chọn mức thanh toán cho phòng ở ghép -->
                    <div v-if="isRoomSharing" style="margin-bottom: 16px; padding: 10px; background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 12px;">
                        <div style="font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 8px; text-align: left; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-people-fill" style="color: #4f46e5;"></i>
                            <span>Chọn mức thanh toán cho mã QR:</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button 
                                type="button" 
                                @click="paymentSplitMode = 'full'"
                                style="padding: 10px; border-radius: 10px; border: 2px solid; cursor: pointer; text-align: center; transition: all 0.2s;"
                                :style="paymentSplitMode === 'full' ? 'border-color: #10b981; background-color: #fff; color: #065f46; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);' : 'border-color: transparent; background-color: rgba(255,255,255,0.6); color: #64748b;'"
                            >
                                <div style="font-size: 11px; font-weight: 600;">
                                    <i class="bi bi-building"></i> Toàn bộ phòng
                                </div>
                                <div style="font-size: 13px; font-weight: 800; color: #059669; margin-top: 2px;">{{ formatMoney(activeInvoice.total_amount) }}</div>
                            </button>
                            <button 
                                type="button" 
                                @click="paymentSplitMode = 'split'"
                                style="padding: 10px; border-radius: 10px; border: 2px solid; cursor: pointer; text-align: center; transition: all 0.2s;"
                                :style="paymentSplitMode === 'split' ? 'border-color: #4f46e5; background-color: #fff; color: #3730a3; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);' : 'border-color: transparent; background-color: rgba(255,255,255,0.6); color: #64748b;'"
                            >
                                <div style="font-size: 11px; font-weight: 600;">
                                    <i class="bi bi-person-fill"></i> Phần của tôi (1/{{ roomOccupantsCount }})
                                </div>
                                <div style="font-size: 13px; font-weight: 800; color: #4f46e5; margin-top: 2px;">{{ formatMoney(perPersonAmount) }}</div>
                            </button>
                        </div>
                    </div>

                    <p class="text-xs font-bold text-slate-600">
                        Quét mã VietQR (Tự động điền Số tiền & Nội dung)
                    </p>

                    <div class="relative inline-block bg-white p-2.5 rounded-2xl shadow-sm border border-slate-100">
                        <img :src="qrUrl" alt="VietQR Code" class="w-52 sm:w-56 h-auto mx-auto object-contain" />
                    </div>

                    <!-- Bảng thông tin tài khoản thụ hưởng & thanh toán (Được thụt lề rộng rãi) -->
                    <div style="margin-top: 14px; text-align: left; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 8px;">
                        
                        <!-- Ngân hàng -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;">
                            <span style="font-size: 12px; font-weight: 600; color: #64748b;">Ngân hàng:</span>
                            <span style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ landlordBankInfo.bankName }}</span>
                        </div>

                        <!-- Chủ tài khoản -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;">
                            <span style="font-size: 12px; font-weight: 600; color: #64748b;">Chủ tài khoản:</span>
                            <span style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">{{ landlordBankInfo.bankAccName }}</span>
                        </div>

                        <!-- Số tài khoản -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px;">
                            <span style="font-size: 12px; font-weight: 600; color: #64748b;">Số tài khoản:</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-family: monospace; font-size: 14px; font-weight: 900; color: #2563eb; letter-spacing: 0.8px;">{{ landlordBankInfo.bankAcc }}</span>
                                <button type="button" @click="copyToClipboard(landlordBankInfo.bankAcc, 'Số tài khoản')"
                                    style="padding: 4px 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>

                        <!-- Số tiền -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 10px;">
                            <span style="font-size: 12px; font-weight: 700; color: #065f46;">Số tiền:</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 15px; font-weight: 900; color: #059669;">
                                    {{ formatMoney(currentPaymentAmount) }}
                                </span>
                                <button type="button" @click="copyToClipboard(currentPaymentAmount, 'Số tiền')"
                                    style="padding: 4px 10px; background: #059669; color: #ffffff; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); transition: all 0.2s;">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>

                        <!-- Nội dung chuyển khoản -->
                        <div style="margin-top: 2px; padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;">
                            <div style="font-size: 11px; font-weight: 700; color: #92400e; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                                <i class="bi bi-chat-left-text-fill" style="color: #f59e0b;"></i> Nội dung chuyển khoản:
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 10px; background: #ffffff; border: 1px dashed #fcd34d; border-radius: 8px;">
                                <span style="font-family: monospace; font-size: 12px; font-weight: 800; color: #1e293b; letter-spacing: 0.5px; user-select: all; word-break: break-all;">
                                    {{ transferMemo }}
                                </span>
                                <button type="button" @click="copyToClipboard(transferMemo, 'Nội dung chuyển khoản')"
                                    style="flex-shrink: 0; padding: 5px 12px; background: #f59e0b; color: #ffffff; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); transition: all 0.2s;">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Thử nghiệm Ngân hàng báo tiền về (Demo Webhook) -->
                    <div class="pt-3 border-t border-slate-200/80">
                        <button type="button" @click="triggerSimulatedPayment" :disabled="isSimulating"
                            class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2">
                            <i class="bi bi-robot text-emerald-400 text-sm"></i>
                            <span>{{
                                isSimulating
                                    ? "Đang gửi tín hiệu Ngân hàng..."
                                    : "⚡ Thử nghiệm Ngân hàng báo tiền về (Demo Auto Payment)"
                            }}</span>
                        </button>
                    </div>
                </div>

                <button
                    class="btn-pay mt-4 w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2"
                    @click="confirmPayment" :disabled="confirmPaymentForm.processing">
                    <i class="bi bi-send-check-fill"></i>
                    <span>{{
                        confirmPaymentForm.processing
                            ? "Đang gửi thông báo..."
                            : "Xác nhận đã chuyển khoản"
                    }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- POPUP XEM CHI TIẾT ẢNH MINH CHỨNG CÔNG TƠ -->
    <div v-if="showMeterProofModal" class="fixed inset-0 z-[10000] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4" @click="showMeterProofModal = false">
        <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full flex flex-col overflow-hidden animate-fade-in border border-slate-100" @click.stop>
            <!-- Head -->
            <div class="px-5 py-4 sm:px-6 sm:py-4.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-black text-slate-800 text-xs sm:text-sm uppercase tracking-wide truncate">Ảnh minh chứng công tơ</h3>
                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5 truncate">Đối soát ảnh chụp chỉ số điện và nước thực tế</p>
                    </div>
                </div>
                <button @click="showMeterProofModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors cursor-pointer flex-shrink-0 ml-2">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-6 overflow-y-auto max-h-[75vh] space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Khung điện -->
                    <div v-if="elecDetail && (elecDetail.meter_image_path || elecDetail.old_meter_image_path)" 
                         class="bg-slate-50 border border-slate-200/70 rounded-2xl p-3.5 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-1 border-b border-slate-200/60 pb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <div class="w-6 h-6 rounded-md bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                                <span class="text-xs font-black text-slate-800 truncate">Chỉ số Điện</span>
                            </div>
                            <span class="text-[10px] text-slate-600 font-bold bg-white px-2 py-0.5 rounded-md border border-slate-200/60 flex-shrink-0">Tiêu thụ: {{ elecDetail.quantity }} kWh</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div v-if="elecDetail.old_meter_image_path" class="space-y-1">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block text-center truncate">Số cũ ({{ elecDetail.old_index }})</span>
                                <div class="relative w-full h-28 sm:h-24 bg-white border border-slate-200 rounded-xl overflow-hidden group/img cursor-zoom-in shadow-xs" @click="zoomImage(elecDetail.old_meter_image_path, `Ảnh điện số cũ: ${elecDetail.old_index}`)">
                                    <img :src="getMeterImgUrl(elecDetail.old_meter_image_path)" class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105" alt="Ảnh điện cũ">
                                </div>
                            </div>
                            <div v-if="elecDetail.meter_image_path" class="space-y-1">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block text-center truncate">Số mới ({{ elecDetail.new_index }})</span>
                                <div class="relative w-full h-28 sm:h-24 bg-white border border-slate-200 rounded-xl overflow-hidden group/img cursor-zoom-in shadow-xs" @click="zoomImage(elecDetail.meter_image_path, `Ảnh điện số mới: ${elecDetail.new_index}`)">
                                    <img :src="getMeterImgUrl(elecDetail.meter_image_path)" class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105" alt="Ảnh điện mới">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Khung nước -->
                    <div v-if="waterDetail && (waterDetail.meter_image_path || waterDetail.old_meter_image_path)" 
                         class="bg-slate-50 border border-slate-200/70 rounded-2xl p-3.5 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-1 border-b border-slate-200/60 pb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <div class="w-6 h-6 rounded-md bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    <i class="bi bi-droplet-fill"></i>
                                </div>
                                <span class="text-xs font-black text-slate-800 truncate">Chỉ số Nước</span>
                            </div>
                            <span class="text-[10px] text-slate-600 font-bold bg-white px-2 py-0.5 rounded-md border border-slate-200/60 flex-shrink-0">Tiêu thụ: {{ waterDetail.quantity }} m³</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div v-if="waterDetail.old_meter_image_path" class="space-y-1">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block text-center truncate">Số cũ ({{ waterDetail.old_index }})</span>
                                <div class="relative w-full h-28 sm:h-24 bg-white border border-slate-200 rounded-xl overflow-hidden group/img cursor-zoom-in shadow-xs" @click="zoomImage(waterDetail.old_meter_image_path, `Ảnh nước số cũ: ${waterDetail.old_index}`)">
                                    <img :src="getMeterImgUrl(waterDetail.old_meter_image_path)" class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105" alt="Ảnh nước cũ">
                                </div>
                            </div>
                            <div v-if="waterDetail.meter_image_path" class="space-y-1">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block text-center truncate">Số mới ({{ waterDetail.new_index }})</span>
                                <div class="relative w-full h-28 sm:h-24 bg-white border border-slate-200 rounded-xl overflow-hidden group/img cursor-zoom-in shadow-xs" @click="zoomImage(waterDetail.meter_image_path, `Ảnh nước số mới: ${waterDetail.new_index}`)">
                                    <img :src="getMeterImgUrl(waterDetail.meter_image_path)" class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105" alt="Ảnh nước mới">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foot -->
            <div class="px-5 py-3.5 sm:px-6 border-t border-slate-100 flex items-center justify-end bg-slate-50/50">
                <button @click="showMeterProofModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <!-- LIGHTBOX IMAGE ZOOM MODAL -->
    <div v-if="zoomedImgUrl" class="fixed inset-0 z-[10050] bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center p-4" @click="zoomedImgUrl = null">
        <div class="relative max-w-3xl w-full max-h-[85vh] flex flex-col items-center" @click.stop>
            <div class="absolute top-2 right-2 z-10 flex gap-2">
                <button @click="zoomedImgUrl = null" class="w-10 h-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/75 cursor-pointer">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>
            <p class="text-white text-xs font-bold bg-black/60 px-4 py-1.5 rounded-full mb-3 shadow-md">{{ zoomedImgTitle }}</p>
            <img :src="zoomedImgUrl" class="max-w-full max-h-[75vh] object-contain rounded-2xl border border-white/10 shadow-2xl" />
        </div>
    </div>
</template>

<style scoped>
@import "../../css/lichsuthanhtoan.css";
@import "../../css/responsive/responsivehoadon.css";
@import "../../css/responsive/responsive.css";
</style>
