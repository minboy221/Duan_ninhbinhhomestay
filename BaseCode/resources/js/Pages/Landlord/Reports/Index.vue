```vue
<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    reports: Object
})

const showResolveModal = ref(false)
const selectedReport = ref(null)

const form = useForm({
    response_note: '',
    response_evidence: []
})

const statusText = {
    pending: 'Chờ bạn xử lý',
    investigating: 'Đang thương lượng',
    resolved: 'Đã giải quyết',
    rejected: 'Đã từ chối',
    completed: 'Hoàn thành'
}

function openResolveModal(report) {
    selectedReport.value = report
    form.reset()
    form.clearErrors()
    showResolveModal.value = true
}

function handleFileChange(event) {
    form.response_evidence = Array.from(event.target.files)
}

function submitResolution() {
    if (!form.response_note.trim()) {
        alert('Vui lòng nhập nội dung giải trình/khắc phục!')
        return
    }

    form.post(route('reports.self-resolve', selectedReport.value.id), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            alert('Gửi phản hồi thành công! Chờ khách thuê xác nhận.')
            showResolveModal.value = false
            form.reset()
        },

        onError: () => {
            console.log(form.errors)
        }
    })
}

function isExpired(deadline) {
    if (!deadline) return false
    return new Date() > new Date(deadline)
}
</script>

<template>

    <Head title="Quản Lý Khiếu Nại | Chủ Trọ" />

    <LandlordLayout>
        <div class="p-6 bg-white rounded-xl shadow border border-slate-200">
            <h2 class="text-xl font-bold text-slate-800 mb-2">
                Danh Sách Khiếu Nại & Báo Cáo
            </h2>

            <p class="text-sm text-slate-500 mb-6">
                Tiếp nhận và phản hồi các khiếu nại trực tiếp từ khách thuê trước khi
                Admin can thiệp.
            </p>

            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-3 font-semibold text-slate-700">
                            Khách thuê
                        </th>

                        <th class="p-3 font-semibold text-slate-700">
                            Lý do khiếu nại
                        </th>

                        <th class="p-3 font-semibold text-slate-700">
                            Thời hạn thương lượng
                        </th>

                        <th class="p-3 font-semibold text-slate-700 text-center">
                            Trạng thái
                        </th>

                        <th class="p-3 font-semibold text-slate-700 text-center">
                            Hành động
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="reports.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-slate-400">
                            Không có khiếu nại nào dành cho bạn.
                        </td>
                    </tr>

                    <tr v-for="r in reports.data" :key="r.id" class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="p-3">
                            <p class="font-semibold text-slate-800">
                                {{ r.reporter?.name }}
                            </p>

                            <p class="text-xs text-slate-400">
                                {{ r.reporter?.email }}
                            </p>
                        </td>

                        <td class="p-3">
                            <p class="font-medium text-slate-800">
                                {{ r.reason }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                {{ r.description }}
                            </p>
                        </td>

                        <td class="p-3 text-xs text-slate-500">
                            {{
                                r.negotiation_deadline
                                    ? new Date(
                                        r.negotiation_deadline
                                    ).toLocaleString('vi-VN')
                                    : 'N/A'
                            }}

                            <span v-if="
                                r.status === 'pending' &&
                                isExpired(r.negotiation_deadline)
                            " class="block text-rose-600 font-bold mt-1">
                                (Quá hạn - Chờ Admin can thiệp)
                            </span>
                        </td>

                        <td class="p-3 text-center">
                            <span :class="[
                                'px-2.5 py-1 rounded-full text-xs font-semibold',
                                {
                                    'bg-amber-100 text-amber-800':
                                        r.status === 'pending',

                                    'bg-sky-100 text-sky-800':
                                        r.status === 'investigating',

                                    'bg-green-100 text-green-800':
                                        r.status === 'resolved',

                                    'bg-red-100 text-red-800':
                                        r.status === 'rejected',

                                    'bg-slate-100 text-slate-700':
                                        ![
                                            'pending',
                                            'investigating',
                                            'resolved',
                                            'rejected'
                                        ].includes(r.status)
                                }
                            ]">
                                {{ statusText[r.status] ?? r.status }}
                            </span>
                        </td>

                        <td class="p-3 text-center">
                            <button v-if="
                                r.status === 'pending' &&
                                !isExpired(r.negotiation_deadline)
                            " @click="openResolveModal(r)"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition">
                                Giải trình / Khắc phục
                            </button>

                            <span v-else class="text-xs text-slate-400 font-medium">
                                Không thể thao tác
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showResolveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Gửi Phản Hồi Giải Trình
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                            Nội dung giải trình
                        </label>

                        <textarea v-model="form.response_note" rows="5"
                            class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="Nhập nội dung giải trình..."></textarea>

                        <p v-if="form.errors.response_note" class="text-red-500 text-xs mt-1">
                            {{ form.errors.response_note }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                            Ảnh bằng chứng (nếu có)
                        </label>

                        <input type="file" multiple accept="image/*" @change="handleFileChange"
                            class="w-full text-sm" />

                        <p v-if="form.errors.response_evidence" class="text-red-500 text-xs mt-1">
                            {{ form.errors.response_evidence }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showResolveModal = false" class="px-4 py-2 bg-slate-200 rounded-lg text-sm">
                        Hủy
                    </button>

                    <button @click="submitResolution" :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-sm">
                        {{
                            form.processing
                                ? 'Đang gửi...'
                                : 'Gửi phản hồi'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
