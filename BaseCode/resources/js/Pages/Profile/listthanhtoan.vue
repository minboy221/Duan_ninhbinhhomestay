<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    user: Object,
    invoices: {
        type: Array,
        default: () => []
    }
})

const activeInvoice = ref(null)
const selectedPaymentMethod = ref('qr')
const showDetailModal = ref(false)
const showReportModal = ref(false)
const reportText = ref('')

const openDetail = (inv) => {
    activeInvoice.value = inv
    selectedPaymentMethod.value = 'qr' // default method
    showDetailModal.value = true
}

const closeDetail = () => {
    showDetailModal.value = false
    activeInvoice.value = null
}

const openReport = (inv) => {
    activeInvoice.value = inv
    reportText.value = ''
    showReportModal.value = true
}

const closeReport = () => {
    showReportModal.value = false
    activeInvoice.value = null
}

const selectPaymentMethod = (method) => {
    selectedPaymentMethod.value = method
}

// Format date helper
const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('vi-VN')
}

// Format money helper
const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'

// Get specific detail row by keyword
const getDetailByItem = (inv, keyword) => {
    if (!inv || !inv.details) return null
    return inv.details.find(d => d.item_name.toLowerCase().includes(keyword.toLowerCase()))
}

const elecDetail = computed(() => getDetailByItem(activeInvoice.value, 'Điện'))
const waterDetail = computed(() => getDetailByItem(activeInvoice.value, 'Nước'))

// Dynamic VietQR code generation
const qrUrl = computed(() => {
    if (!activeInvoice.value) return ''
    const landlord = activeInvoice.value.contract?.room?.boarding_house?.user 
        || activeInvoice.value.contract?.room?.boardingHouse?.user 
        || {}
        
    const bankName = landlord.bank_name || 'MB'
    const bankAcc = landlord.bank_account_no || '0912345678'
    const bankAccName = landlord.bank_account_name || 'NGUYEN VAN CHU TRO'
    const amount = Math.round(activeInvoice.value.total_amount)
    const memo = activeInvoice.value.invoice_code
    
    return `https://img.vietqr.io/image/${bankName}-${bankAcc}-compact2.png?amount=${amount}&addInfo=${memo}&accountName=${encodeURIComponent(bankAccName)}`
})

// Confirm payment submission
const confirmPaymentForm = useForm({
    payment_method: 'qr'
})

const confirmPayment = () => {
    if (!activeInvoice.value) return
    confirmPaymentForm.payment_method = selectedPaymentMethod.value
    confirmPaymentForm.post(route('invoices.notify-payment', activeInvoice.value.id), {
        onSuccess: () => {
            closeDetail()
        }
    })
}

// Submit report handler
const submitReport = () => {
    if (!reportText.value.trim()) {
        alert('Vui lòng nhập lý do báo cáo!')
        return
    }
    alert('Báo cáo của bạn đã được ghi nhận. Chủ trọ sẽ liên hệ lại với bạn sớm nhất.')
    closeReport()
}
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
                            <td data-label="Mã hoá đơn">#{{ inv.invoice_code }}</td>
                            <td data-label="Tháng thanh toán">{{ inv.billing_month }}</td>
                            <td data-label="Tổng số tiền">
                                <div class="price">{{ formatMoney(inv.total_amount) }}</div>
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
                                    <button class="baocao" @click="openReport(inv)">
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
            <h3>Báo cáo hoá đơn</h3>
            <p v-if="activeInvoice" class="text-xs text-slate-500 mb-3 font-semibold">Hóa đơn: #{{ activeInvoice.invoice_code }}</p>

            <textarea v-model="reportText" placeholder="Nhập lý do báo cáo..."></textarea>

            <button class="btn-submit" @click="submitReport">Gửi báo cáo</button>
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
                    <strong>{{ d.item_name }} <span v-if="d.old_index !== null">({{ d.new_index }} - {{ d.old_index }})</span>:</strong>
                    <span>{{ formatMoney(d.subtotal) }}</span>
                </p>
                <p><strong>Tổng:</strong> {{ formatMoney(activeInvoice.total_amount) }}</p>
            </div>

            <!-- HÌNH ẢNH SỐ ĐIỆN NƯỚC -->
            <div class="meter-images" v-if="elecDetail?.meter_image_path || waterDetail?.meter_image_path">
                <div class="meter-item" v-if="elecDetail?.meter_image_path">
                    <p><i class="bi bi-lightning-charge-fill"></i> Ảnh số điện</p>
                    <div class="img-wrapper">
                        <img :src="'/storage/' + elecDetail.meter_image_path" alt="Ảnh số điện" onerror="this.src='/anh/sodien.png'">
                    </div>
                </div>
                <div class="meter-item" v-if="waterDetail?.meter_image_path">
                    <p><i class="bi bi-droplet-fill"></i> Ảnh số nước</p>
                    <div class="img-wrapper">
                        <img :src="'/storage/' + waterDetail.meter_image_path" alt="Ảnh số nước" onerror="this.src='/anh/sonuoc.png'">
                    </div>
                </div>
            </div>

            <!-- THANH TOÁN -->
            <div class="thanhtoan" v-if="activeInvoice.status !== 'paid'">
                <h4>Chọn phương thức thanh toán</h4>

                <div class="payment-options">
                    <div class="payment-item" :class="{ active: selectedPaymentMethod === 'qr' }" @click="selectPaymentMethod('qr')">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>QR Code</span>
                    </div>
                    <div class="payment-item" :class="{ active: selectedPaymentMethod === 'cash' }" @click="selectPaymentMethod('cash')">
                        <i class="bi bi-cash-coin"></i>
                        <span>Tiền mặt</span>
                    </div>
                </div>

                <!-- QR -->
                <div class="qr-box" :style="{ display: selectedPaymentMethod === 'qr' ? 'block' : 'none' }">
                    <p>Quét mã để thanh toán</p>
                    <img :src="qrUrl" alt="VietQR Code" class="mx-auto">
                </div>

                <button class="btn-pay" @click="confirmPayment" :disabled="confirmPaymentForm.processing">
                    {{ confirmPaymentForm.processing ? 'Đang gửi...' : 'Xác nhận thanh toán' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import "../../css/lichsuthanhtoan.css";
@import '../../css/responsive/responsivehoadon.css';
@import '../../css/responsive/responsive.css';
</style>
