<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, reactive, watch, onMounted, onUnmounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { showSuccess, showError, showWarning, showConfirm, showToast } from '@/Utils/swal'

const props = defineProps({
    invoices: {
        type: Array,
        default: () => []
    },
    archivedInvoices: {
        type: Array,
        default: () => []
    },
    activeContracts: {
        type: Array,
        default: () => []
    },
    services: {
        type: Array,
        default: () => []
    },
    boardingHouses: {
        type: Array,
        default: () => []
    },
    pendingBillingContracts: {
        type: Array,
        default: () => []
    }
})

// Tự động làm mới dữ liệu Hóa đơn mỗi 5s để cập nhật ngay khi khách thanh toán
let autoRefreshTimer = null
onMounted(() => {
    autoRefreshTimer = setInterval(() => {
        if (currentView.value === 'list' && !showViewModal.value) {
            router.reload({ preserveScroll: true, only: ['invoices', 'archivedInvoices'] })
        }
    }, 5000)

    if (billingHousesToAlert.value && billingHousesToAlert.value.length > 0) {
        showBillingReminderModal.value = true
    }
})

onUnmounted(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer)
        autoRefreshTimer = null
    }
})

const currentView = ref('list') // 'list' | 'create' | 'edit'
const selectedContractId = ref(null)
const selectedInvoiceId = ref(null)

const month = ref(new Date().toISOString().substring(0, 7)) // e.g. "2026-06"
const maxAllowedMonth = computed(() => {
    const d = new Date()
    d.setMonth(d.getMonth() + 1)
    return d.toISOString().substring(0, 7)
})
const houseFilter = ref('all')
const startDate = ref('')
const endDate = ref('')
const searchQ = ref('')
const activeTab = ref('all') // 'all' | 'open' | 'completed' | 'archived'

// Meter Photo Files State
const elecMeterFile = ref(null)
const elecOldMeterFile = ref(null)
const waterMeterFile = ref(null)
const waterOldMeterFile = ref(null)

const elecMeterPreview = ref(null)
const elecOldMeterPreview = ref(null)
const waterMeterPreview = ref(null)
const waterOldMeterPreview = ref(null)

// Form state
const invoiceForm = reactive({
    rent: 0,
    elecOld: 0,
    elecNew: 0,
    elecPrice: 3000,
    waterOld: 0,
    waterNew: 0,
    waterPrice: 15000,
    internetQty: 1,
    internetPrice: 50000,
    trashQty: 1,
    trashPrice: 30000,
    parkingQty: 1,
    parkingPrice: 15000,
    management: 0,
    dueDate: new Date().toISOString().split('T')[0]
})

const makeMoneyComputed = (field) => computed({
    get() {
        const val = invoiceForm[field]
        if (val === null || val === undefined || val === '') return ''
        return new Intl.NumberFormat('en-US').format(val)
    },
    set(v) {
        const raw = String(v).replace(/\D/g, '')
        invoiceForm[field] = raw ? parseInt(raw, 10) : 0
    }
})

const displayRentPrice = makeMoneyComputed('rent')
const displayElecPrice = makeMoneyComputed('elecPrice')
const displayWaterPrice = makeMoneyComputed('waterPrice')
const displayInternetPrice = makeMoneyComputed('internetPrice')
const displayTrashPrice = makeMoneyComputed('trashPrice')
const displayParkingPrice = makeMoneyComputed('parkingPrice')
const displayManagementPrice = makeMoneyComputed('management')

// Dynamic active room services based on the selected contract's room configuration
const activeRoomServices = computed(() => {
    const room = selectedContract.value?.room
    if (room && Array.isArray(room.services) && room.services.length > 0) {
        return room.services
    }
    return props.services || []
})

// Find services lookup helper
const elecService = computed(() => activeRoomServices.value.find(s => s.type === 'per_kwh' || (s.name && s.name.toLowerCase().includes('điện'))))
const hasElecService = computed(() => !!elecService.value)

const waterService = computed(() => activeRoomServices.value.find(s => s.type === 'per_m3' || (s.name && s.name.toLowerCase().includes('nước'))))
const hasWaterService = computed(() => !!waterService.value)

const internetService = computed(() => activeRoomServices.value.find(s => s.name && (s.name.toLowerCase().includes('mạng') || s.name.toLowerCase().includes('internet') || s.name.toLowerCase().includes('wifi'))))
const hasInternetService = computed(() => !!internetService.value)

const trashService = computed(() => activeRoomServices.value.find(s => s.name && (s.name.toLowerCase().includes('rác') || s.name.toLowerCase().includes('vệ sinh'))))
const hasTrashService = computed(() => !!trashService.value)

const parkingService = computed(() => activeRoomServices.value.find(s => s.name && (s.name.toLowerCase().includes('xe') || s.name.toLowerCase().includes('gửi xe'))))
const hasParkingService = computed(() => !!parkingService.value)

// Custom services present on the room that are not matched to standard types above
const customRoomServices = computed(() => {
    return activeRoomServices.value.filter(s =>
        s.id !== elecService.value?.id &&
        s.id !== waterService.value?.id &&
        s.id !== internetService.value?.id &&
        s.id !== trashService.value?.id &&
        s.id !== parkingService.value?.id
    )
})
const customServicesPrice = ref({})
const customServicesQty = ref({})

// Helper tính tiền phòng lẻ tháng đầu nếu khách nhận phòng giữa tháng
const selectedContract = computed(() => props.activeContracts.find(c => c.id === selectedContractId.value))
const isFirstMonthInvoice = computed(() => {
    if (!selectedContractId.value) return false
    return !props.invoices.some(i => i.contract_id === selectedContractId.value)
})

const proratedRentInfo = computed(() => {
    const contract = selectedContract.value
    if (!contract || !contract.start_date || !isFirstMonthInvoice.value) return null
    const startDate = new Date(contract.start_date)
    const day = startDate.getDate()
    if (day <= 1) return null

    const year = startDate.getFullYear()
    const month = startDate.getMonth() + 1
    const totalDays = new Date(year, month, 0).getDate()
    const occupiedDays = totalDays - day + 1
    const monthlyRent = Number(contract.room?.price || contract.monthly_rent || 0)
    const suggestedRent = Math.round((monthlyRent / totalDays) * occupiedDays)
    const isGracePeriod = occupiedDays < 7

    return {
        day,
        totalDays,
        occupiedDays,
        monthlyRent,
        suggestedRent,
        isGracePeriod,
        formattedSuggested: new Intl.NumberFormat('vi-VN').format(suggestedRent) + 'đ'
    }
})

const applyProratedRent = () => {
    if (proratedRentInfo.value) {
        invoiceForm.rent = proratedRentInfo.value.suggestedRent
    }
}

// Update form when contract changes
watch(selectedContractId, (newContractId) => {
    if (!newContractId) return
    const contract = props.activeContracts.find(c => c.id === newContractId)
    if (contract) {
        invoiceForm.rent = contract.room?.price || 0

        // Find last invoice for old indexes, photo inheritance and next month suggestion
        const contractInvoices = (props.invoices || [])
            .filter(i => i.contract_id === newContractId)
            .sort((a, b) => (b.billing_month || '').localeCompare(a.billing_month || ''))
        const lastInv = contractInvoices[0]

        // Gợi ý kỳ thanh toán tiếp theo nếu kỳ trước đã lập hóa đơn
        if (lastInv && lastInv.billing_month) {
            const parts = lastInv.billing_month.split('-').map(Number)
            if (parts.length === 2) {
                const nextDate = new Date(parts[0], parts[1], 1)
                const nextMonthStr = `${nextDate.getFullYear()}-${String(nextDate.getMonth() + 1).padStart(2, '0')}`
                if (nextMonthStr <= maxAllowedMonth.value) {
                    month.value = nextMonthStr
                }
            }
        }

        // Electricity
        if (hasElecService.value) {
            const lastElecDetail = lastInv?.details?.find(d => d.item_name.includes('Điện'))
            if (lastElecDetail) {
                invoiceForm.elecOld = lastElecDetail.new_index ?? 0
                elecOldMeterPreview.value = lastElecDetail.meter_image_path || null
            } else if (contract.entry_elec_index !== null && contract.entry_elec_index !== undefined) {
                invoiceForm.elecOld = contract.entry_elec_index
                elecOldMeterPreview.value = contract.entry_elec_image || null
            } else {
                invoiceForm.elecOld = 0
                elecOldMeterPreview.value = null
            }
            invoiceForm.elecNew = ''
            invoiceForm.elecPrice = elecService.value ? Number(elecService.value.price) : 3000
        }

        // Water
        if (hasWaterService.value) {
            const lastWaterDetail = lastInv?.details?.find(d => d.item_name.includes('Nước'))
            if (lastWaterDetail) {
                invoiceForm.waterOld = lastWaterDetail.new_index ?? 0
                waterOldMeterPreview.value = lastWaterDetail.meter_image_path || null
            } else if (contract.entry_water_index !== null && contract.entry_water_index !== undefined) {
                invoiceForm.waterOld = contract.entry_water_index
                waterOldMeterPreview.value = contract.entry_water_image || null
            } else {
                invoiceForm.waterOld = 0
                waterOldMeterPreview.value = null
            }
            invoiceForm.waterNew = ''
            invoiceForm.waterPrice = waterService.value ? Number(waterService.value.price) : 15000
        }

        // Fixed services
        if (hasInternetService.value) {
            invoiceForm.internetPrice = internetService.value ? Number(internetService.value.price) : 50000
            invoiceForm.internetQty = 1
        }
        if (hasTrashService.value) {
            invoiceForm.trashPrice = trashService.value ? Number(trashService.value.price) : 30000
            invoiceForm.trashQty = 1
        }
        if (hasParkingService.value) {
            invoiceForm.parkingPrice = parkingService.value ? Number(parkingService.value.price) : 15000
            invoiceForm.parkingQty = 1
        }

        // Custom services
        customServicesPrice.value = {}
        customServicesQty.value = {}
        customRoomServices.value.forEach(srv => {
            customServicesPrice.value[srv.id] = Number(srv.price || 0)
            customServicesQty.value[srv.id] = 1
        })

        invoiceForm.management = 0
    }
}, { immediate: true })

// Photo handlers
const handlePhotoUpload = (event, type) => {
    const file = event.target.files[0]
    if (!file) return

    if (type === 'elec_new') {
        elecMeterFile.value = file
        elecMeterPreview.value = URL.createObjectURL(file)
        runOcrForField(file, 'elec')
    } else if (type === 'elec_old') {
        elecOldMeterFile.value = file
        elecOldMeterPreview.value = URL.createObjectURL(file)
    } else if (type === 'water_new') {
        waterMeterFile.value = file
        waterMeterPreview.value = URL.createObjectURL(file)
        runOcrForField(file, 'water')
    } else if (type === 'water_old') {
        waterOldMeterFile.value = file
        waterOldMeterPreview.value = URL.createObjectURL(file)
    }
}

// Calculations
const elecDiff = computed(() => {
    if (invoiceForm.elecNew === '' || invoiceForm.elecNew === null || invoiceForm.elecNew === undefined) return 0
    return Math.max(0, Number(invoiceForm.elecNew) - Number(invoiceForm.elecOld))
})
const elecTotal = computed(() => elecDiff.value * invoiceForm.elecPrice)

const waterDiff = computed(() => {
    if (invoiceForm.waterNew === '' || invoiceForm.waterNew === null || invoiceForm.waterNew === undefined) return 0
    return Math.max(0, Number(invoiceForm.waterNew) - Number(invoiceForm.waterOld))
})
const waterTotal = computed(() => waterDiff.value * invoiceForm.waterPrice)

const internetTotal = computed(() => invoiceForm.internetQty * invoiceForm.internetPrice)
const trashTotal = computed(() => invoiceForm.trashQty * invoiceForm.trashPrice)
const parkingTotal = computed(() => invoiceForm.parkingQty * invoiceForm.parkingPrice)

const customTotal = computed(() => {
    let sum = 0
    customRoomServices.value.forEach(srv => {
        const price = Number(customServicesPrice.value[srv.id] ?? srv.price ?? 0)
        const qty = Number(customServicesQty.value[srv.id] ?? 1)
        sum += price * qty
    })
    return sum
})

const formTotal = computed(() => {
    let total = Number(invoiceForm.rent)
    if (hasElecService.value) total += elecTotal.value
    if (hasWaterService.value) total += waterTotal.value
    if (hasInternetService.value) total += internetTotal.value
    if (hasTrashService.value) total += trashTotal.value
    if (hasParkingService.value) total += parkingTotal.value
    total += customTotal.value
    total += Number(invoiceForm.management || 0)
    return total
})

// Filters
const filteredInvoices = computed(() => {
    const sourceList = (activeTab.value === 'archived' ? props.archivedInvoices : props.invoices) || []

    return sourceList.filter(inv => {
        if (!inv) return false

        // Lọc theo Tab trạng thái
        if (activeTab.value !== 'all' && activeTab.value !== 'archived') {
            const isPaid = inv.status === 'paid'
            if (activeTab.value === 'open' && isPaid) return false
            if (activeTab.value === 'completed' && !isPaid) return false
        }
        // Lọc theo Kỳ thanh toán (tháng)
        if (month.value && inv.billing_month) {
            if (inv.billing_month !== month.value) return false
        }
        // Lọc theo Khoảng ngày
        if (startDate.value && inv.created_at) {
            const invDate = String(inv.created_at).substring(0, 10)
            if (invDate < startDate.value) return false
        }
        if (endDate.value && inv.created_at) {
            const invDate = String(inv.created_at).substring(0, 10)
            if (invDate > endDate.value) return false
        }
        // Lọc theo từ khóa tìm kiếm
        if (searchQ.value) {
            const q = searchQ.value.toLowerCase()
            const roomNum = inv.contract?.room?.room_number ? String(inv.contract.room.room_number).toLowerCase() : ''
            const tenantName = inv.contract?.tenant?.name ? String(inv.contract.tenant.name).toLowerCase() : ''
            const code = inv.invoice_code ? String(inv.invoice_code).toLowerCase() : ''
            return roomNum.includes(q) || tenantName.includes(q) || code.includes(q)
        }
        return true
    })
})

const formatMoney = (n) => new Intl.NumberFormat('en-US').format(n || 0) + ' đ'
const formatDate = (d) => {
    if (!d) return ''
    const date = new Date(d)
    if (isNaN(date.getTime())) return String(d)
    return date.toLocaleDateString('vi-VN')
}

// Action triggers
const goCreate = () => {
    currentView.value = 'create'
    selectedContractId.value = props.activeContracts[0]?.id || null
    invoiceForm.dueDate = new Date().toISOString().split('T')[0]
    elecMeterFile.value = null
    elecOldMeterFile.value = null
    waterMeterFile.value = null
    waterOldMeterFile.value = null
    elecMeterPreview.value = null
    elecOldMeterPreview.value = null
    waterMeterPreview.value = null
    waterOldMeterPreview.value = null
}

const goEdit = (inv) => {
    showWarning('Thông báo', 'Hóa đơn sau khi đã tạo không thể chỉnh sửa!')
}

const saveInvoice = async () => {
    if (!selectedContractId.value) {
        showWarning('Thiếu thông tin', 'Vui lòng chọn hợp đồng!')
        return
    }

    const contract = props.activeContracts.find(c => c.id === selectedContractId.value)
    if (contract) {
        if (month.value) {
            const startMonth = contract.start_date.substring(0, 7) // YYYY-MM
            const endMonth = contract.end_date.substring(0, 7) // YYYY-MM
            if (month.value < startMonth || month.value > endMonth) {
                showError('Không hợp lệ', `Kỳ thanh toán (Tháng ${month.value}) nằm ngoài thời hạn hợp đồng (${startMonth} đến ${endMonth})!`)
                return
            }
        }
    }

    // Điện
    if (hasElecService.value) {
        if (invoiceForm.elecNew !== null && invoiceForm.elecNew !== '') {
            if (Number(invoiceForm.elecNew) < Number(invoiceForm.elecOld)) {
                showError('Lỗi nhập liệu', `Chỉ số điện mới (${invoiceForm.elecNew}) không được nhỏ hơn chỉ số cũ (${invoiceForm.elecOld})!`)
                return
            }
        } else {
            showError('Lỗi nhập liệu', 'Vui lòng nhập chỉ số điện mới!')
            return
        }
    }

    // Nước
    if (hasWaterService.value) {
        if (invoiceForm.waterNew !== null && invoiceForm.waterNew !== '') {
            if (Number(invoiceForm.waterNew) < Number(invoiceForm.waterOld)) {
                showError('Lỗi nhập liệu', `Chỉ số nước mới (${invoiceForm.waterNew}) không được nhỏ hơn chỉ số cũ (${invoiceForm.waterOld})!`)
                return
            }
        } else {
            showError('Lỗi nhập liệu', 'Vui lòng nhập chỉ số nước mới!')
            return
        }
    }

    const details = [
        {
            item_name: 'Tiền thuê nhà',
            price: Number(invoiceForm.rent),
            quantity: 1,
            subtotal: Number(invoiceForm.rent),
            service_id: null
        }
    ]

    if (hasElecService.value) {
        details.push({
            item_name: 'Tiền Điện',
            price: Number(invoiceForm.elecPrice),
            quantity: elecDiff.value,
            subtotal: elecTotal.value,
            old_index: Number(invoiceForm.elecOld),
            new_index: Number(invoiceForm.elecNew),
            service_id: elecService.value?.id || null
        })
    }

    if (hasWaterService.value) {
        details.push({
            item_name: 'Tiền Nước',
            price: Number(invoiceForm.waterPrice),
            quantity: waterDiff.value,
            subtotal: waterTotal.value,
            old_index: Number(invoiceForm.waterOld),
            new_index: Number(invoiceForm.waterNew),
            service_id: waterService.value?.id || null
        })
    }

    if (hasInternetService.value) {
        details.push({
            item_name: internetService.value?.name || 'Phí internet / wifi',
            price: Number(invoiceForm.internetPrice),
            quantity: Number(invoiceForm.internetQty),
            subtotal: internetTotal.value,
            service_id: internetService.value?.id || null
        })
    }

    if (hasTrashService.value) {
        details.push({
            item_name: trashService.value?.name || 'Thu gom rác',
            price: Number(invoiceForm.trashPrice),
            quantity: Number(invoiceForm.trashQty),
            subtotal: trashTotal.value,
            service_id: trashService.value?.id || null
        })
    }

    if (hasParkingService.value) {
        details.push({
            item_name: parkingService.value?.name || 'Tiền gửi xe',
            price: Number(invoiceForm.parkingPrice),
            quantity: Number(invoiceForm.parkingQty),
            subtotal: parkingTotal.value,
            service_id: parkingService.value?.id || null
        })
    }

    customRoomServices.value.forEach(srv => {
        const qty = Number(customServicesQty.value[srv.id] || 1)
        const price = Number(customServicesPrice.value[srv.id] ?? srv.price ?? 0)
        details.push({
            item_name: srv.name,
            price: price,
            quantity: qty,
            subtotal: price * qty,
            service_id: srv.id
        })
    })

    if (Number(invoiceForm.management) > 0) {
        details.push({
            item_name: 'Phí dịch vụ khác',
            price: Number(invoiceForm.management),
            quantity: 1,
            subtotal: Number(invoiceForm.management),
            service_id: null
        })
    }

    if (month.value > maxAllowedMonth.value) {
        showError('Lỗi', `Kỳ thanh toán không được tạo vượt quá 1 tháng so với hiện tại (Kỳ tối đa được phép: Tháng ${maxAllowedMonth.value})!`)
        return
    }

    // Tránh trùng hóa đơn trong cùng kỳ thanh toán
    const alreadyExists = (props.invoices || []).some(i => i.contract_id === selectedContractId.value && i.billing_month === month.value)
    if (alreadyExists) {
        showError('Đã tồn tại', `Hóa đơn cho hợp đồng này trong kỳ Tháng ${month.value} đã tồn tại! Vui lòng chọn kỳ tháng tiếp theo hoặc kiểm tra lại danh sách.`)
        return
    }

    const confirmed = await showConfirm(
        'Xác nhận tạo hóa đơn',
        'Lưu ý: Hóa đơn sau khi đã tạo sẽ KHÔNG THỂ CHỈNH SỬA. Bạn đã kiểm tra kỹ các thông tin (chỉ số điện, nước, các khoản phí) chưa?',
        'Xác nhận tạo',
        'Kiểm tra lại'
    )
    if (!confirmed) return

    const form = useForm({
        contract_id: selectedContractId.value,
        billing_month: month.value,
        due_date: invoiceForm.dueDate,
        details: details,
        elec_meter_image: elecMeterFile.value,
        elec_old_meter_image: elecOldMeterFile.value,
        water_meter_image: waterMeterFile.value,
    })

    form.post(route('landlord.invoices.store'), {
        onSuccess: (page) => {
            if (page?.props?.flash?.error) {
                showError('Lỗi', page.props.flash.error)
                return
            }
            showSuccess('Thành công', 'Lưu hóa đơn thành công!')
            currentView.value = 'list'
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0]
            showError('Lỗi', firstErr || 'Không thể tạo hóa đơn, vui lòng kiểm tra lại!')
        }
    })
}

const updateInvoiceStatus = (inv, status) => {
    const statusForm = useForm({ status: status })
    statusForm.patch(route('landlord.invoices.status', inv.id), {
        onSuccess: (page) => {
            if (page?.props?.flash?.error) {
                showError('Lỗi', page.props.flash.error)
                return
            }
            showSuccess('Thành công', 'Cập nhật trạng thái thành công!')
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0]
            showError('Lỗi', firstErr || 'Cập nhật trạng thái thất bại!')
        }
    })
}

const archiveInvoice = (inv) => {
    if (confirm(`Bạn có chắc chắn muốn chuyển hóa đơn mã #${inv.invoice_code} vào Kho lưu trữ?`)) {
        const form = useForm({})
        form.patch(route('landlord.invoices.archive', inv.id), {
            onSuccess: () => {
                alert('Đã chuyển hóa đơn vào kho lưu trữ thành công!')
            }
        })
    }
}

const restoreInvoice = (inv) => {
    const form = useForm({})
    form.patch(route('landlord.invoices.restore', inv.id), {
        onSuccess: () => {
            alert('Đã khôi phục hóa đơn từ kho lưu trữ!')
        }
    })
}

const deleteInvoice = (inv) => {
    if (confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')) {
        const deleteForm = useForm({})
        deleteForm.delete(route('landlord.invoices.delete', inv.id), {
            onSuccess: () => {
                alert('Xóa hóa đơn thành công!')
                if (selectedInvoice.value && selectedInvoice.value.id === inv.id) {
                    closeViewModal()
                }
            }
        })
    }
}

// Modal view detail
const showViewModal = ref(false)
const selectedInvoice = ref(null)

const openViewModal = (inv) => {
    selectedInvoice.value = inv
    showViewModal.value = true
}
const closeViewModal = () => {
    showViewModal.value = false
    selectedInvoice.value = null
}

const copyInvoiceToClipboard = (inv) => {
    if (!inv) return
    let text = `--- HÓA ĐƠN TIỀN NHÀ ---\n`;
    text += `Phòng: ${inv.contract?.room?.room_number || ''}\n`;
    text += `Khách thuê: ${inv.contract?.tenant?.name || ''}\n`;
    text += `Kỳ thanh toán: Tháng ${inv.billing_month || ''}\n`;
    text += `Hạn đóng: ${formatDate(inv.due_date)}\n`;
    text += `-------------------------\n`;
    inv.details?.forEach(d => {
        let calc = '';
        if (d.old_index !== null && d.old_index !== undefined) {
            calc = ` (${d.new_index} - ${d.old_index} = ${d.quantity})`;
        } else if (d.quantity > 1) {
            calc = ` (${d.quantity}x)`;
        }
        text += `${d.item_name}${calc}: ${formatMoney(d.subtotal)}\n`;
    });
    text += `-------------------------\n`;
    text += `TỔNG CỘNG: ${formatMoney(inv.total_amount)}\n`;

    navigator.clipboard.writeText(text).then(() => {
        alert('Đã sao chép nội dung hóa đơn để gửi Zalo/SMS!')
    })
}

const printDisputeReport = (inv) => {
    window.print()
}

// --- BILLING DATE ALERTS & REMINDER ---
const today = new Date().getDate()
const billingHousesToAlert = computed(() => {
    return (props.boardingHouses || []).filter(house => {
        const hasPendingRooms = (props.pendingBillingContracts || []).some(c => c.room?.boarding_house_id === house.id)
        if (!hasPendingRooms) return false

        const billingDay = house.invoice_billing_day || 30
        return today >= billingDay
    })
})

const showBillingReminderModal = ref(false)
const elecOcrLoading = ref(false)
const waterOcrLoading = ref(false)

const runOcrForField = (file, type) => {
    if (type === 'elec') elecOcrLoading.value = true
    if (type === 'water') waterOcrLoading.value = true

    const formData = new FormData()
    formData.append('image', file)

    axios.post(route('landlord.invoices.ocr'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(res => {
        if (res.data && res.data.index !== undefined) {
            if (type === 'elec') {
                invoiceForm.elecNew = res.data.index
                showToast('AI đã nhận dạng số điện: ' + res.data.index, 'success')
            } else {
                invoiceForm.waterNew = res.data.index
                showToast('AI đã nhận dạng số nước: ' + res.data.index, 'success')
            }
        }
    }).catch(err => {
        const msg = err.response?.data?.error || 'Lỗi nhận dạng ảnh. Vui lòng nhập tay!'
        showWarning('Nhận dạng thất bại', msg)
    }).finally(() => {
        if (type === 'elec') elecOcrLoading.value = false
        if (type === 'water') waterOcrLoading.value = false
    })
}

const goToCreateForContract = (contractId) => {
    selectedContractId.value = contractId
    currentView.value = 'create'
    showBillingReminderModal.value = false

    // Reset single invoice creation form files & previews
    invoiceForm.dueDate = new Date().toISOString().split('T')[0]
    elecMeterFile.value = null
    elecOldMeterFile.value = null
    waterMeterFile.value = null
    waterOldMeterFile.value = null
    elecMeterPreview.value = null
    elecOldMeterPreview.value = null
    waterMeterPreview.value = null
    waterOldMeterPreview.value = null
}
</script>

<template>
    <LandlordLayout>
        <!-- LIST VIEW -->
        <div v-if="currentView === 'list'" class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Hóa đơn</span>
            </div>

            <!-- Billing Date Alerts -->
            <div v-if="billingHousesToAlert.length > 0"
                class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-lg flex-shrink-0">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-800 text-xs uppercase tracking-wide">Đến kỳ lập hóa đơn hàng
                            tháng!</h4>
                        <p class="text-xs text-amber-650 mt-0.5">
                            Các cơ sở: <span class="font-bold">{{billingHousesToAlert.map(h => h.name).join(', ')
                                }}</span> đã đến ngày chốt số điện nước.
                            Có tổng cộng <span class="font-bold text-amber-800">{{ pendingBillingContracts.length }}
                                phòng</span> chưa chốt số kỳ này.
                        </p>
                    </div>
                </div>
                <button @click="showBillingReminderModal = true"
                    class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1.5 self-stretch sm:self-auto justify-center cursor-pointer">
                    <i class="bi bi-bell-fill"></i>
                    <span>Xem danh sách chốt</span>
                </button>
            </div>

            <!-- Title Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-slate-800">Hóa đơn</h1>
                <button @click="goCreate"
                    class="px-4 py-2.5 bg-[#0e3b3e] hover:bg-[#09282a] text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                    <i class="bi bi-plus-lg"></i> Thêm hóa đơn mới
                </button>
            </div>

            <!-- Segmented Control Tabs (iOS / Android Native App Style - No Scrollbar) -->
            <div
                class="bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/60 grid grid-cols-4 gap-1 text-center font-bold text-xs shadow-inner">
                <button @click="activeTab = 'all'"
                    :class="['py-2 px-1 rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer', activeTab === 'all' ? 'bg-white text-slate-900 font-black shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                    <span>Tất cả</span>
                    <span
                        :class="['px-1.5 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-600']">{{
                            (invoices || []).length }}</span>
                </button>
                <button @click="activeTab = 'open'"
                    :class="['py-2 px-1 rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer', activeTab === 'open' ? 'bg-white text-emerald-700 font-black shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                    <span>Đang mở</span>
                    <span
                        :class="['px-1.5 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'open' ? 'bg-emerald-600 text-white' : 'bg-emerald-100 text-emerald-700']">{{
                            (invoices || []).filter(i => i.status!=='paid').length }}</span>
                </button>
                <button @click="activeTab = 'completed'"
                    :class="['py-2 px-1 rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer', activeTab === 'completed' ? 'bg-white text-blue-700 font-black shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                    <span>Đã thu</span>
                    <span
                        :class="['px-1.5 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'completed' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700']">{{
                            (invoices || []).filter(i => i.status==='paid').length }}</span>
                </button>
                <button @click="activeTab = 'archived'"
                    :class="['py-2 px-1 rounded-xl transition-all flex items-center justify-center gap-1 cursor-pointer', activeTab === 'archived' ? 'bg-white text-purple-700 font-black shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                    <span>Lưu trữ</span>
                    <span
                        :class="['px-1.5 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'archived' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-700']">{{
                            (archivedInvoices || []).length }}</span>
                </button>
            </div>

            <!-- Filters Bar -->
            <div
                class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Kỳ thanh toán</label>
                    <input type="month" v-model="month"
                        class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/50 cursor-pointer" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Ngày bắt đầu</label>
                    <input type="date" v-model="startDate"
                        class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none text-slate-600 bg-slate-50/50 cursor-pointer" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Ngày kết thúc</label>
                    <input type="date" v-model="endDate"
                        class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none text-slate-600 bg-slate-50/50 cursor-pointer" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Tìm kiếm</label>
                    <div
                        class="flex items-center bg-slate-50/50 border border-slate-200 rounded-xl px-3 py-2 text-slate-400 gap-2">
                        <i class="bi bi-search text-xs"></i>
                        <input v-model="searchQ"
                            class="bg-transparent border-none outline-none text-xs text-slate-700 w-full placeholder-slate-400 font-semibold"
                            placeholder="Tìm theo phòng, khách, mã..." />
                    </div>
                </div>
            </div>

            <!-- Invoices Desktop Table List -->
            <div class="hidden lg:block bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead>
                            <tr
                                class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3.5 px-4">Kỳ thanh toán</th>
                                <th class="py-3.5 px-4">Mã hóa đơn</th>
                                <th class="py-3.5 px-4">Phòng</th>
                                <th class="py-3.5 px-4 text-right">Tổng cộng</th>
                                <th class="py-3.5 px-4 text-center">Tình trạng</th>
                                <th class="py-3.5 px-4">Khách hàng</th>
                                <th class="py-3.5 px-6 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="(inv, idx) in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/40">
                                <td class="py-4 px-4 text-center text-slate-400 font-bold">{{ idx + 1 }}</td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-800">Tháng {{ inv.billing_month }}</div>
                                    <div class="text-[10px] text-slate-400 font-semibold">Hạn: {{
                                        formatDate(inv.due_date) }}</div>
                                </td>
                                <td class="py-4 px-4 text-slate-500 font-mono font-medium">#{{ inv.invoice_code }}</td>
                                <td class="py-4 px-4 text-slate-800 font-bold">Phòng {{ inv.contract?.room?.room_number
                                    }}</td>
                                <td class="py-4 px-4 text-right text-rose-500 font-bold">{{
                                    formatMoney(inv.total_amount) }}</td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex justify-center">
                                        <span v-if="inv.archived_at"
                                            class="px-2 py-0.5 bg-purple-50 text-purple-600 border border-purple-100 rounded-md text-[10px] font-bold flex items-center gap-1">
                                            <i class="bi bi-archive-fill"></i> Đã Lưu Trữ
                                        </span>
                                        <span v-else-if="inv.status === 'paid'"
                                            class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                            Hoàn Thành
                                        </span>
                                        <span v-else
                                            class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                            Đang Mở (Chưa thu)
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-[10px]">
                                            {{ inv.contract?.tenant?.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="text-slate-700 font-semibold">{{ inv.contract?.tenant?.name }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openViewModal(inv)"
                                            class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-lg flex items-center justify-center cursor-pointer"
                                            title="Xem chi tiết hóa đơn & Bằng chứng">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button v-if="!inv.archived_at && inv.status !== 'paid'"
                                            @click="changeStatus(inv, 'paid')"
                                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-lg text-[10px] font-bold flex items-center gap-1 cursor-pointer">
                                            <i class="bi bi-check-lg"></i> Thu tiền
                                        </button>
                                        <button v-if="!inv.archived_at" @click="archiveInvoice(inv)"
                                            class="w-7 h-7 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center cursor-pointer"
                                            title="Đưa vào kho lưu trữ">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                        <button v-else @click="restoreInvoice(inv)"
                                            class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-600 border border-purple-100 rounded-lg text-[10px] font-bold flex items-center gap-1 cursor-pointer">
                                            <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredInvoices.length === 0">
                                <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Không tìm thấy hóa đơn
                                    nào</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices Mobile Card List (Clean & Proven Layout) -->
            <div class="block lg:hidden space-y-4">
                <div v-for="inv in filteredInvoices" :key="inv.id"
                    class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hóa đơn: #{{
                                inv.invoice_code }}</div>
                            <div class="text-sm font-black text-slate-800 mt-1">Phòng {{ inv.contract?.room?.room_number
                                }}</div>
                            <div class="text-xs text-slate-500 font-semibold mt-0.5">Khách: <span
                                    class="text-slate-700 font-bold">{{ inv.contract?.tenant?.name }}</span></div>
                        </div>
                        <div>
                            <span v-if="inv.archived_at"
                                class="px-2 py-0.5 bg-purple-50 text-purple-600 border border-purple-100 rounded-md text-[10px] font-bold">
                                Đã Lưu Trữ
                            </span>
                            <span v-else-if="inv.status === 'paid'"
                                class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                Hoàn Thành
                            </span>
                            <span v-else
                                class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                Đang mở (Chưa thu)
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-slate-50 text-xs">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Kỳ đóng tiền</div>
                            <div class="font-bold text-slate-700 mt-0.5">Tháng {{ inv.billing_month }}</div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Hạn: {{
                                formatDate(inv.due_date) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Tổng thu</div>
                            <div class="font-black text-rose-500 mt-0.5">{{ formatMoney(inv.total_amount) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1.5 pt-3 border-t border-slate-50">
                        <button @click="openViewModal(inv)"
                            class="w-8 h-8 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-xl flex items-center justify-center"
                            title="Xem chi tiết">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button v-if="!inv.archived_at && inv.status !== 'paid'" @click="changeStatus(inv, 'paid')"
                            class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-xl text-xs font-extrabold flex items-center gap-1">
                            <i class="bi bi-check-lg"></i> Thu tiền
                        </button>
                        <button v-if="!inv.archived_at" @click="archiveInvoice(inv)"
                            class="w-8 h-8 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center"
                            title="Lưu trữ">
                            <i class="bi bi-archive"></i>
                        </button>
                        <button v-else @click="restoreInvoice(inv)"
                            class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-600 border border-purple-100 rounded-xl text-xs font-bold flex items-center gap-1">
                            Khôi phục
                        </button>
                    </div>
                </div>
                <div v-if="filteredInvoices.length === 0" class="text-center py-8 text-slate-400 font-bold text-xs">
                    Không tìm thấy hóa đơn nào
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT VIEW -->
        <div v-else class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span class="cursor-pointer hover:text-slate-600" @click="currentView = 'list'">Hóa đơn</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">{{ currentView === 'create' ? 'Tạo hóa đơn' : 'Chỉnh sửa hóa đơn' }}</span>
            </div>

            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-slate-800">{{ currentView === 'create' ? 'Tạo hóa đơn mới' : 'Chỉnh sửa hóa đơn' }}</h1>
                <button @click="currentView = 'list'"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl flex items-center gap-1 transition-colors">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </button>
            </div>

            <!-- Form Container -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-6">
                <!-- House & Contract Select -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Chọn phòng / Hợp đồng <span
                                class="text-rose-500">*</span></label>
                        <select v-model="selectedContractId" :disabled="currentView === 'edit'"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/50">
                            <option v-for="c in props.activeContracts" :key="c.id" :value="c.id">
                                Phòng {{ c.room?.room_number }} - Khách: {{ c.tenant?.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Kỳ thanh toán <span
                                class="text-rose-500">*</span></label>
                        <input type="month" v-model="month" :max="maxAllowedMonth"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/50" />
                    </div>
                </div>

                <!-- Room Price Section (LOCKED FOR ANTI-FRAUD) -->
                <div class="p-4 border border-slate-100 rounded-2xl bg-slate-50/50 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="bi bi-[#0e3b3e] bi-house-door-fill text-emerald-600"></i> Tiền thuê nhà (Khóa cố
                            định theo HĐ)
                        </span>
                        <span class="text-xs font-black text-slate-800">{{ formatMoney(invoiceForm.rent) }}</span>
                    </div>
                    <input type="text" :value="displayRentPrice" readonly
                        class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />

                    <!-- Gợi ý tính tiền phòng lẻ tháng đầu / Quy tắc du di -->
                    <div v-if="proratedRentInfo"
                        class="mt-2 p-3 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 border"
                        :class="proratedRentInfo.isGracePeriod ? 'bg-blue-50/70 border-blue-200 text-blue-900' : 'bg-amber-50/70 border-amber-200 text-amber-900'">
                        <div class="text-xs font-semibold">
                            <i :class="proratedRentInfo.isGracePeriod ? 'bi bi-shield-check text-blue-600' : 'bi bi-calculator-fill text-amber-600'"
                                class="mr-1 text-sm"></i>
                            <span v-if="proratedRentInfo.isGracePeriod">
                                Khách vào ngày <span class="font-bold">{{ proratedRentInfo.day }}</span> (Ở <span
                                    class="font-bold">{{ proratedRentInfo.occupiedDays }} ngày</span>, &lt; 7 ngày du
                                di). Bạn có thể gộp sang kỳ tháng sau hoặc áp dụng giá lẻ:
                            </span>
                            <span v-else>
                                Khách ở thực tế <span class="font-bold">{{ proratedRentInfo.occupiedDays }}/{{
                                    proratedRentInfo.totalDays }} ngày</span> (&ge; 7 ngày chia lẻ). Giá lẻ đề xuất:
                            </span>
                        </div>
                        <button type="button" @click="applyProratedRent"
                            class="px-3 py-1.5 rounded-lg font-bold text-xs transition shadow-xs flex-shrink-0 cursor-pointer text-white"
                            :class="proratedRentInfo.isGracePeriod ? 'bg-blue-600 hover:bg-blue-700' : 'bg-amber-600 hover:bg-amber-700'">
                            Áp dụng {{ proratedRentInfo.formattedSuggested }}
                        </button>
                    </div>
                </div>

                <!-- Services Form -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Chi tiết chỉ số & Dịch vụ
                        </h3>
                        <span v-if="selectedContract?.room"
                            class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100/80 px-2.5 py-1 rounded-xl flex items-center gap-1.5 w-fit">
                            <i class="bi bi-check-circle-fill text-emerald-500"></i>
                            Đã tải {{ activeRoomServices.length }} dịch vụ được đăng ký theo Phòng {{
                            selectedContract.room.room_number }}
                        </span>
                    </div>

                    <!-- Electricity section -->
                    <div v-if="hasElecService" class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                        <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <i class="bi bi-lightning-charge-fill"></i></div>
                            <span class="text-xs font-bold text-slate-700">Tiền điện (chỉ số công tơ)</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 flex items-center justify-between">
                                    <span>Số cũ (Khóa tự động)</span>
                                    <span
                                        v-if="isFirstMonthInvoice && selectedContract?.entry_elec_index !== null && selectedContract?.entry_elec_index !== undefined"
                                        class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">Mốc
                                        bàn giao HĐ</span>
                                </label>
                                <input type="number" v-model.number="invoiceForm.elecOld" readonly
                                    class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                                    Số mới kỳ này
                                    <span v-if="elecOcrLoading"
                                        class="text-[9px] text-amber-500 animate-pulse flex items-center gap-0.5">
                                        <i class="bi bi-arrow-clockwise animate-spin"></i> Đang quét...
                                    </span>
                                </label>
                                <input type="number" :disabled="elecOcrLoading" v-model.number="invoiceForm.elecNew"
                                    :placeholder="elecOcrLoading ? 'Đang đọc...' : ''"
                                    class="w-full px-3 py-1.5 border border-slate-200 focus:border-amber-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Sử dụng (kWh)</label>
                                <div
                                    class="w-full px-3 py-1.5 bg-slate-50 rounded-xl text-xs font-bold text-slate-700 border border-slate-100">
                                    {{ elecDiff }}</div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                <input type="text" :value="displayElecPrice" readonly
                                    class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                            </div>
                            <div class="space-y-1 col-span-2 sm:col-span-1">
                                <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                <div
                                    class="w-full px-3 py-1.5 bg-slate-50 text-amber-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                    {{ formatMoney(elecTotal) }}</div>
                            </div>
                        </div>

                        <!-- Electricity Photo Upload Section -->
                        <div class="pt-2 border-t border-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Ảnh công tơ điện kỳ trước (Kế thừa
                                    tự động)</label>
                                <div v-if="elecOldMeterPreview"
                                    class="relative group w-full h-24 border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <img :src="elecOldMeterPreview" class="w-full h-full object-contain" />
                                </div>
                                <div v-else class="text-[10px] text-slate-400 italic">Không có ảnh kỳ trước</div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Đính kèm ảnh công tơ điện mới kỳ
                                    này</label>
                                <input type="file" @change="e => handlePhotoUpload(e, 'elec_new')" accept="image/*"
                                    class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-50 file:text-amber-700 cursor-pointer" />
                                <div v-if="elecMeterPreview"
                                    class="mt-1 h-20 border rounded-lg overflow-hidden bg-slate-50">
                                    <img :src="elecMeterPreview" class="w-full h-full object-contain" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Water section -->
                    <div v-if="hasWaterService" class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                        <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i
                                    class="bi bi-droplet-fill"></i></div>
                            <span class="text-xs font-bold text-slate-700">Tiền nước (chỉ số đồng hồ)</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 flex items-center justify-between">
                                    <span>Số cũ (Khóa tự động)</span>
                                    <span
                                        v-if="isFirstMonthInvoice && selectedContract?.entry_water_index !== null && selectedContract?.entry_water_index !== undefined"
                                        class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">Mốc
                                        bàn giao HĐ</span>
                                </label>
                                <input type="number" v-model.number="invoiceForm.waterOld" readonly
                                    class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                                    Số mới kỳ này
                                    <span v-if="waterOcrLoading"
                                        class="text-[9px] text-blue-500 animate-pulse flex items-center gap-0.5">
                                        <i class="bi bi-arrow-clockwise animate-spin"></i> Đang quét...
                                    </span>
                                </label>
                                <input type="number" :disabled="waterOcrLoading" v-model.number="invoiceForm.waterNew"
                                    :placeholder="waterOcrLoading ? 'Đang đọc...' : ''"
                                    class="w-full px-3 py-1.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Sử dụng (m³)</label>
                                <div
                                    class="w-full px-3 py-1.5 bg-slate-50 rounded-xl text-xs font-bold text-slate-700 border border-slate-100">
                                    {{ waterDiff }}</div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                <input type="text" :value="displayWaterPrice" readonly
                                    class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                            </div>
                            <div class="space-y-1 col-span-2 sm:col-span-1">
                                <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                <div
                                    class="w-full px-3 py-1.5 bg-slate-50 text-blue-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                    {{ formatMoney(waterTotal) }}</div>
                            </div>
                        </div>

                        <!-- Water Photo Upload Section -->
                        <div class="pt-2 border-t border-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Ảnh đồng hồ nước kỳ trước (Kế thừa
                                    tự động)</label>
                                <div v-if="waterOldMeterPreview"
                                    class="relative group w-full h-24 border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <img :src="waterOldMeterPreview" class="w-full h-full object-contain" />
                                </div>
                                <div v-else class="text-[10px] text-slate-400 italic">Không có ảnh kỳ trước</div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Đính kèm ảnh đồng hồ nước mới kỳ
                                    này</label>
                                <input type="file" @change="e => handlePhotoUpload(e, 'water_new')" accept="image/*"
                                    class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 cursor-pointer" />
                                <div v-if="waterMeterPreview"
                                    class="mt-1 h-20 border rounded-lg overflow-hidden bg-slate-50">
                                    <img :src="waterMeterPreview" class="w-full h-full object-contain" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Internet section -->
                    <div v-if="hasInternetService" class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                        <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i class="bi bi-wifi"></i></div>
                            <span class="text-xs font-bold text-slate-700">Internet / Wifi</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-end">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Số lượng / Số người</label>
                                <input type="number" v-model.number="invoiceForm.internetQty"
                                    class="w-full px-3 py-1.5 border border-slate-200 focus:border-indigo-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                <input type="text" :value="displayInternetPrice" readonly
                                    class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                            </div>
                            <div class="space-y-1 col-span-2 sm:col-span-1">
                                <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                <div
                                    class="w-full px-3 py-1.5 bg-slate-50 text-indigo-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                    {{ formatMoney(internetTotal) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Trash & Parking section -->
                    <div v-if="hasTrashService || hasParkingService" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-if="hasTrashService" class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                                    <i class="bi bi-trash-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Vệ sinh / Rác</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                    <input type="text" :value="displayTrashPrice" readonly
                                        class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div
                                        class="w-full px-3 py-1.5 bg-slate-50 text-rose-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                        {{ formatMoney(trashTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="hasParkingService"
                            class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                    <i class="bi bi-p-square-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Phí gửi xe</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                    <input type="text" :value="displayParkingPrice" readonly
                                        class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div
                                        class="w-full px-3 py-1.5 bg-slate-50 text-purple-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                        {{ formatMoney(parkingTotal) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Services Section -->
                    <div v-if="customRoomServices.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="srv in customRoomServices" :key="srv.id"
                            class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <i :class="['bi', srv.icon || 'bi-box-seam-fill']"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-700">{{ srv.name }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ) (Khóa)</label>
                                    <input type="text" :value="formatMoney(customServicesPrice[srv.id])" readonly
                                        class="w-full px-3 py-1.5 border border-slate-200 rounded-xl text-xs font-bold outline-none bg-slate-100/80 cursor-not-allowed text-slate-500" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div
                                        class="w-full px-3 py-1.5 bg-slate-50 text-emerald-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">
                                        {{ formatMoney((customServicesPrice[srv.id] || 0) * (customServicesQty[srv.id]
                                        || 1)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Management Fee & Due Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700">Phí dịch vụ khác / Quản lý</label>
                            <input type="text" v-model="displayManagementPrice"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40"
                                placeholder="0" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-700">Hạn đóng tiền</label>
                            <input type="date" v-model="invoiceForm.dueDate"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                        </div>
                    </div>
                </div>

                <!-- Total Amount Card -->
                <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block">TỔNG CỘNG HÓA
                            ĐƠN</span>
                        <span class="text-[10px] text-emerald-600">Đã bao gồm tất cả các khoản tiền thuê và dịch
                            vụ</span>
                    </div>
                    <span class="text-xl font-black text-emerald-700">{{ formatMoney(formTotal) }}</span>
                </div>

                <!-- Submit buttons -->
                <div class="flex justify-end gap-3 pt-2">
                    <button @click="currentView = 'list'"
                        class="px-5 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                        Hủy bỏ
                    </button>
                    <button @click="saveInvoice"
                        class="px-6 py-2.5 bg-[#0e3b3e] hover:bg-[#09282a] text-white text-xs font-bold rounded-xl transition-colors shadow-sm cursor-pointer">
                        {{ currentView === 'create' ? 'Lưu & Phát hành hóa đơn' : 'Cập nhật hóa đơn' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- VIEW DETAIL & METER DISPUTE MODAL -->
        <div v-if="showViewModal && selectedInvoice"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden my-8 border border-slate-100">
                <!-- Header -->
                <div class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="font-extrabold text-sm flex items-center gap-2">
                            <span>Chi tiết hóa đơn #{{ selectedInvoice.invoice_code }}</span>
                            <span v-if="selectedInvoice.archived_at"
                                class="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-[10px] font-bold rounded-md border border-purple-400/30">Kho
                                lưu trữ</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Phòng {{
                            selectedInvoice.contract?.room?.room_number }} - {{ selectedInvoice.contract?.tenant?.name
                            }}</p>
                    </div>
                    <button @click="closeViewModal"
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 flex items-center justify-center text-xs transition-colors cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Invoice Summary info -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                        <div>
                            <span class="text-slate-400 text-[10px] font-bold uppercase block">Kỳ thanh toán</span>
                            <span class="font-bold text-slate-700">Tháng {{ selectedInvoice.billing_month }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] font-bold uppercase block">Hạn đóng tiền</span>
                            <span class="font-bold text-slate-700">{{ formatDate(selectedInvoice.due_date) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] font-bold uppercase block">Trạng thái</span>
                            <span v-if="selectedInvoice.status === 'paid'" class="text-blue-600 font-extrabold">Đã thanh
                                toán</span>
                            <span v-else class="text-emerald-600 font-extrabold">Đang mở (Chưa thu)</span>
                        </div>
                    </div>

                    <!-- Items breakdown table -->
                    <div class="border border-slate-100 rounded-2xl overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr
                                    class="bg-slate-50 text-slate-400 font-bold uppercase border-b border-slate-100 text-[10px]">
                                    <th class="py-2.5 px-4">Khoản thu</th>
                                    <th class="py-2.5 px-4 text-center">Chỉ số / Số lượng</th>
                                    <th class="py-2.5 px-4 text-right">Đơn giá</th>
                                    <th class="py-2.5 px-4 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 font-semibold text-slate-700">
                                <tr v-for="d in selectedInvoice.details" :key="d.id" class="hover:bg-slate-50/50">
                                    <td class="py-3 px-4 font-bold">{{ d.item_name }}</td>
                                    <td class="py-3 px-4 text-center text-slate-500">
                                        <span v-if="d.old_index !== null && d.old_index !== undefined"
                                            class="font-mono text-[11px] bg-slate-100 px-2 py-0.5 rounded-md">
                                            {{ d.new_index }} - {{ d.old_index }} = {{ d.quantity }}
                                        </span>
                                        <span v-else>{{ d.quantity }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right text-slate-500">{{ formatMoney(d.price) }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(d.subtotal)
                                        }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Amount -->
                    <div class="flex justify-between items-center pt-2 border-t border-slate-100">
                        <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Tổng cộng</span>
                        <span class="text-lg font-black text-rose-500">{{ formatMoney(selectedInvoice.total_amount)
                            }}</span>
                    </div>

                    <!-- Meter Evidence Dispute Section (Bằng chứng hình ảnh) -->
                    <div class="border border-slate-100 bg-slate-50/50 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-camera-fill text-amber-500"></i> Bằng chứng hình ảnh đối chiếu chỉ số
                                công tơ
                            </h4>
                            <button @click="printDisputeReport(selectedInvoice)"
                                class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold rounded-lg flex items-center gap-1 shadow-sm transition-colors cursor-pointer">
                                <i class="bi bi-printer-fill text-emerald-600"></i> Xuất biên bản đối chiếu
                            </button>
                        </div>

                        <div v-for="dt in selectedInvoice.details.filter(d => d.old_index !== null && d.old_index !== undefined)"
                            :key="dt.id" class="bg-white border border-slate-100 rounded-xl p-4 space-y-3">
                            <div class="flex justify-between items-center border-b border-slate-50 pb-2">
                                <span class="text-xs font-bold text-slate-700">{{ dt.item_name }}</span>
                                <span class="text-xs font-extrabold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md">
                                    {{ dt.new_index }} - {{ dt.old_index }} = {{ dt.quantity }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Old Index Photo -->
                                <div
                                    class="border border-slate-100 rounded-xl p-2.5 text-center bg-slate-50/50 space-y-1.5">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase">Ảnh chỉ số kỳ cũ (Số: {{
                                        dt.old_index }})</div>
                                    <div v-if="dt.old_meter_image_path"
                                        class="overflow-hidden rounded-lg border border-slate-200 bg-black/5">
                                        <img :src="dt.old_meter_image_path" class="w-full h-36 object-contain" />
                                    </div>
                                    <div v-else
                                        class="h-36 flex items-center justify-center text-[11px] text-slate-400 italic bg-white rounded-lg border border-dashed border-slate-200">
                                        Chưa đính kèm ảnh chỉ số cũ
                                    </div>
                                </div>

                                <!-- New Index Photo -->
                                <div
                                    class="border border-slate-100 rounded-xl p-2.5 text-center bg-slate-50/50 space-y-1.5">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase">Ảnh chỉ số kỳ mới (Số:
                                        {{ dt.new_index }})</div>
                                    <div v-if="dt.meter_image_path"
                                        class="overflow-hidden rounded-lg border border-slate-200 bg-black/5">
                                        <img :src="dt.meter_image_path" class="w-full h-36 object-contain" />
                                    </div>
                                    <div v-else
                                        class="h-36 flex items-center justify-center text-[11px] text-slate-400 italic bg-white rounded-lg border border-dashed border-slate-200">
                                        Chưa đính kèm ảnh chỉ số mới
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Foot -->
                <div
                    class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2.5 bg-slate-50/50">
                    <button @click="copyInvoiceText(selectedInvoice)"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                        <i class="bi bi-send-fill text-emerald-600"></i> Gửi Zalo / SMS
                    </button>

                    <button v-if="selectedInvoice.status !== 'paid'"
                        @click="changeStatus(selectedInvoice, 'paid'); closeViewModal()"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors shadow-sm cursor-pointer">
                        <i class="bi bi-check-lg"></i> Xác nhận đã thu
                    </button>
                    <button v-else @click="changeStatus(selectedInvoice, 'unpaid'); closeViewModal()"
                        class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                        Chưa thu tiền
                    </button>

                    <button
                        class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center cursor-pointer"
                        @click="closeViewModal">Đóng</button>
                </div>
            </div>
        </div>

        <!-- BILLING REMINDER MODAL -->
        <div v-if="showBillingReminderModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4">
            <div
                class="bg-white rounded-[32px] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.18)] max-w-xl sm:max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-fade-in border border-slate-100">
                <!-- Head -->
                <div
                    class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent relative rounded-t-[32px]">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-orange-500/20 flex-shrink-0 animate-pulse">
                            <i class="bi bi-bell-fill text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-base tracking-tight uppercase">Đến kỳ lập hóa đơn!
                            </h3>
                            <p class="text-xs text-slate-500 font-semibold mt-0.5">Vui lòng cập nhật chỉ số điện nước &
                                lập hóa đơn cho các cơ sở</p>
                        </div>
                    </div>
                    <button @click="showBillingReminderModal = false"
                        class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors cursor-pointer border border-slate-200/50">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-8 overflow-y-auto space-y-6 flex-1 bg-slate-50/20">
                    <div class="p-4 bg-amber-50/60 border border-amber-100 rounded-2xl flex items-start gap-3">
                        <div class="text-amber-600 mt-0.5"><i class="bi bi-info-circle-fill text-base"></i></div>
                        <p class="text-xs text-slate-600 font-semibold leading-relaxed">
                            Hệ thống đối soát theo ngày chốt đã cấu hình và phát hiện hiện tại có <strong
                                class="text-amber-700 font-black text-sm">{{ pendingBillingContracts.length }}
                                phòng</strong> chưa lập hóa đơn. Bạn có thể chọn phòng bên dưới để chốt số nhanh.
                        </p>
                    </div>

                    <div class="space-y-4 max-h-[45vh] overflow-y-auto pr-1 scrollbar-thin">
                        <div v-for="house in billingHousesToAlert" :key="house.id"
                            class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm space-y-3.5">
                            <!-- House Title & Day config -->
                            <div class="flex justify-between items-center pb-2 border-b border-slate-50">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-sm">
                                        <i class="bi bi-house-door-fill"></i>
                                    </div>
                                    <span class="text-xs font-black text-slate-850 tracking-tight">{{ house.name
                                        }}</span>
                                </div>
                                <span
                                    class="bg-amber-50/80 text-amber-800 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg tracking-wider">
                                    Cấu hình chốt: ngày {{ house.invoice_billing_day || 30 }} hàng tháng
                                </span>
                            </div>

                            <!-- Rooms List -->
                            <div class="grid grid-cols-1 gap-2.5">
                                <div v-for="c in pendingBillingContracts.filter(c => c.room?.boarding_house_id === house.id)"
                                    :key="c.id"
                                    class="group/item flex justify-between items-center bg-slate-50/50 hover:bg-orange-50/20 border border-slate-100/50 hover:border-orange-500/20 p-3 rounded-xl transition-all duration-300">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-white border border-slate-200/60 flex flex-col items-center justify-center shadow-sm">
                                            <span class="text-[8px] text-slate-400 font-bold uppercase">Phòng</span>
                                            <span class="text-xs font-black text-[#0e3b3e] -mt-1">{{ c.room?.room_number
                                                }}</span>
                                        </div>
                                        <div>
                                            <div
                                                class="text-xs font-bold text-slate-700 group-hover/item:text-slate-900 transition-colors">
                                                Khách: {{ c.tenant?.name || 'Chưa cập nhật' }}</div>
                                            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Tiền phòng: {{
                                                formatMoney(c.room?.price || 0) }}</div>
                                        </div>
                                    </div>
                                    <button @click="goToCreateForContract(c.id)"
                                        class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-extrabold text-[10px] rounded-xl transition-all duration-300 shadow-md shadow-orange-500/10 hover:shadow-orange-500/25 hover:-translate-y-0.5 flex items-center gap-1 cursor-pointer">
                                        Lập hóa đơn <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Foot -->
                <div class="px-8 py-5 border-t border-slate-100 flex items-center justify-end bg-slate-50/50">
                    <button @click="showBillingReminderModal = false"
                        class="px-5 py-2.5 bg-white border border-slate-250 hover:bg-slate-50 hover:border-slate-300 text-slate-650 font-bold text-xs rounded-xl transition-all shadow-sm cursor-pointer">
                        Để sau
                    </button>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
