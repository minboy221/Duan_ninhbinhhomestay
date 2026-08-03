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
        });
        if (res.data?.success) {
            activeInvoice.value.status = "paid";
            const invInList = props.invoices.find(
                (i) => i.id === activeInvoice.value.id,
            );
            if (invInList) invInList.status = "paid";
            autoSuccessMsg.value =
                "Tín hiệu Webhook Ngân hàng đã khớp! Hóa đơn tự động ĐÃ THANH TOÁN 🎉";
            stopPolling();
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
    return str.replace(/[^a-zA-Z0-9\s]/g, "").trim();
};

// Cấu trúc Nội dung chuyển khoản: P101 [Tên khách] TT thang [Tháng]
const transferMemo = computed(() => {
    if (!activeInvoice.value) return "";

    const rawRoom = activeInvoice.value.contract?.room?.room_number || "";
    const roomStr = rawRoom
        ? rawRoom.toUpperCase().startsWith("P")
            ? rawRoom.replace(/[^a-zA-Z0-9]/g, "")
            : "P" + rawRoom.replace(/[^a-zA-Z0-9]/g, "")
        : "PHONG";

    const rawName =
        activeInvoice.value.contract?.tenant?.name || props.user?.name || "";
    const nameStr = removeVietnameseTones(rawName);

    const monthStr = (activeInvoice.value.billing_month || "").replace(
        "/",
        "-",
    );

    let memo = `${roomStr} ${nameStr} TT thang ${monthStr}`;
    if (memo.length > 50) {
        memo = memo.substring(0, 50);
    }
    return memo;
});

// Dynamic VietQR code generation
const qrUrl = computed(() => {
    if (!activeInvoice.value) return "";
    const { bankName, bankAcc, bankAccName } = landlordBankInfo.value;
    const amount = Math.round(activeInvoice.value.total_amount);
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
                            </td>
                            <td data-label="Trạng thái">
                                <div v-if="inv.status === 'paid'" class="trangthai">
                                    Đã nhận
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
                    <option v-for="(reason, index) in reasons" :key="index" :value="reason.reason">
                        {{ reason.reason }}
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
                <p>
                    <strong>Tổng:</strong>
                    {{ formatMoney(activeInvoice.total_amount) }}
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

                <div class="payment-options">
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
                    <p class="text-xs font-bold text-slate-600">
                        Quét mã VietQR (Tự động điền Số tiền & Nội dung)
                    </p>

                    <div class="relative inline-block bg-white p-2.5 rounded-xl shadow-md border border-slate-100">
                        <img :src="qrUrl" alt="VietQR Code" class="w-56 h-56 mx-auto object-contain" />
                    </div>

                    <!-- Bank Account Info -->
                    <div
                        class="mt-3 text-left bg-white p-3 rounded-xl border border-slate-200 space-y-2 text-xs font-medium text-slate-700">
                        <div class="flex justify-between items-center pb-1 border-b border-slate-100">
                            <span class="text-slate-400">Ngân hàng:</span>
                            <span class="font-bold text-slate-900">{{
                                landlordBankInfo.bankName
                                }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-1 border-b border-slate-100">
                            <span class="text-slate-400">Chủ tài khoản:</span>
                            <span class="font-bold text-slate-900 uppercase">{{
                                landlordBankInfo.bankAccName
                                }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-1 border-b border-slate-100">
                            <span class="text-slate-400">Số tài khoản:</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold font-mono text-blue-600 text-sm">{{ landlordBankInfo.bankAcc
                                    }}</span>
                                <button @click="
                                    copyToClipboard(
                                        landlordBankInfo.bankAcc,
                                        'Số tài khoản',
                                    )
                                    "
                                    class="px-2 py-0.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-[10px] font-bold">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pb-1 border-b border-slate-100">
                            <span class="text-slate-400">Số tiền:</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-emerald-600 text-sm">{{
                                    formatMoney(activeInvoice.total_amount)
                                }}</span>
                                <button @click="
                                    copyToClipboard(
                                        Math.round(
                                            activeInvoice.total_amount,
                                        ),
                                        'Số tiền',
                                    )
                                    "
                                    class="px-2 py-0.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded text-[10px] font-bold">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Nội dung CK:</span>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="font-bold text-slate-800 bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-200 text-xs">{{
                                    transferMemo }}</span>
                                <button @click="
                                    copyToClipboard(
                                        transferMemo,
                                        'Nội dung chuyển khoản',
                                    )
                                    "
                                    class="px-2 py-0.5 bg-amber-100 text-amber-800 hover:bg-amber-200 rounded text-[10px] font-bold">
                                    <i class="bi bi-copy"></i> Chép
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Thử nghiệm Ngân hàng báo tiền về (Demo Webhook) -->
                    <div class="pt-2 border-t border-slate-200">
                        <button type="button" @click="triggerSimulatedPayment" :disabled="isSimulating"
                            class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-2">
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
</template>

<style scoped>
@import "../../css/lichsuthanhtoan.css";
@import "../../css/responsive/responsivehoadon.css";
@import "../../css/responsive/responsive.css";
</style>
