<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    reports: Object
})

const showResolveModal = ref(false)
const selectedReport = ref(null)

const form = useForm({
    response_note: '',
    response_evidence: [],
    action: 'target_resolve'
})

const statusText = {
    pending: 'Chờ bạn xử lý',
    investigating: 'Đang thương lượng',
    resolved: 'Đã giải quyết',
    rejected: 'Đã từ chối',
    completed: 'Hoàn thành'
}

const isViewOnly = ref(false)

function openResolveModal(report, viewOnly = false) {
    selectedReport.value = report
    isViewOnly.value = viewOnly
    form.reset()
    form.clearErrors()
    if (viewOnly) {
        form.response_note = report.response_note || ''
    }
    showResolveModal.value = true
}

// Hàm nén ảnh bằng HTML5 Canvas trực tiếp ở trình duyệt
function compressImage(file, { maxWidth = 1200, maxHeight = 1200, quality = 0.7 } = {}) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith("image/")) {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement("canvas");
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
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
                                type: file.type,
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    },
                    file.type,
                    quality
                );
            };
        };
        reader.onerror = (error) => reject(error);
    });
}

async function handleFileChange(event) {
    const files = Array.from(event.target.files)
    const compressed = await Promise.all(
        files.map(file => compressImage(file))
    )
    form.response_evidence = compressed
}

function submitResolution() {
    form.clearErrors()

    if (!form.response_note.trim()) {
        form.setError('response_note', 'Vui lòng nhập nội dung giải trình/khắc phục!')
        return
    }

    form.post(route('reports.self-resolve', selectedReport.value.id), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash && flash.error) {
                alert(flash.error);
                return;
            }
            showResolveModal.value = false
            form.reset()
        },

        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            alert(firstErr || 'Vui lòng kiểm tra lại thông tin gửi đi.');
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
                        <td class="p-3 text-center flex items-center justify-center gap-2">
                            <!-- Nút Xem chi tiết (Luôn hiển thị) -->
                            <button @click="openResolveModal(r, true)"
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-bold transition flex items-center gap-1">
                                <i class="bi bi-eye"></i> Chi tiết
                            </button>

                            <!-- Nút phản hồi (Chỉ hiển thị khi chờ xử lý và chưa quá hạn) -->
                            <button v-if="
                                r.status === 'pending' &&
                                !isExpired(r.negotiation_deadline)
                            " @click="openResolveModal(r, false)"
                                class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition flex items-center gap-1">
                                <i class="bi bi-chat-dots"></i> Giải trình
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="flex justify-center items-center gap-1.5 mt-6 pb-2" v-if="reports.links && reports.links.length > 3">
            <template v-for="(link, index) in reports.links" :key="index">
                <div :class="[
                    'border border-slate-200 rounded-lg overflow-hidden transition-all text-xs font-semibold',
                    {
                        'bg-indigo-600 border-indigo-600 text-white': link.active,
                        'bg-white text-slate-700 hover:bg-slate-50': !link.active && link.url,
                        'bg-slate-50 text-slate-400 cursor-not-allowed': !link.url
                    }
                ]">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3.5 py-2 block text-white" :style="!link.active ? 'color: #334155;' : ''"></Link>
                    <span v-else v-html="link.label" class="px-3.5 py-2 block text-slate-400"></span>
                </div>
            </template>
        </div>

        <!-- Modal chi tiết dành cho chủ trọ-->
        <div v-if="showResolveModal && selectedReport"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-slate-200"
                style="max-height: 85vh; overflow-y: auto;">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-1.5 border-b pb-2">
                    <i class="bi bi-flag-fill text-indigo-500"></i>
                    {{ isViewOnly ? 'Chi Tiết Khiếu Nại' : 'Gửi Phản Hồi Giải Trình' }} #{{ selectedReport.id }}
                </h3>
                <div class="space-y-4">
                    <!-- Khách thuê và lý do -->
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-xs space-y-2">
                        <div><span class="font-bold text-slate-400">Khách thuê:</span> <span
                                class="font-semibold text-slate-800">{{ selectedReport.reporter?.name }} ({{
                                selectedReport.reporter?.email }})</span></div>
                        <div><span class="font-bold text-slate-400">Lý do báo cáo:</span> <span
                                class="px-2 py-0.5 bg-rose-50 text-rose-700 font-bold rounded-md">{{
                                selectedReport.reason }}</span></div>
                        <div><span class="font-bold text-slate-400">Hạn thương lượng:</span> <span
                                class="font-semibold text-indigo-600">{{ selectedReport.negotiation_deadline ? new
                                    Date(selectedReport.negotiation_deadline).toLocaleString('vi-VN') : 'Không có' }}</span>
                        </div>
                    </div>
                    <!-- Mô tả từ khách thuê -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Chi tiết sự việc:</label>
                        <p
                            class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-150 whitespace-pre-line">
                            {{ selectedReport.description || 'Không có mô tả chi tiết.' }}</p>
                    </div>
                    <!-- Bằng chứng khách gửi kèm -->
                    <div v-if="selectedReport.evidence_images && selectedReport.evidence_images.length">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Ảnh bằng chứng của khách
                            thuê:</label>
                        <div class="flex flex-wrap gap-2">
                            <img v-for="(img, idx) in selectedReport.evidence_images" :key="idx"
                                :src="'/storage/' + img"
                                class="w-16 h-16 object-cover rounded-lg border border-slate-200"
                                @click="window.open('/storage/' + img, '_blank')" style="cursor: zoom-in;" />
                        </div>
                    </div>
                    <!-- Ghi chú phản hồi của chủ trọ -->
                    <div class="border-t pt-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                            {{ isViewOnly ? 'Phản hồi giải trình của bạn:' : 'Nhập nội dung giải trình / khắc phục:' }}
                        </label>
                        <textarea v-model="form.response_note" rows="4"
                            class="w-full border border-slate-300 rounded-lg p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="Mô tả phương án bạn đã giải quyết/thương lượng với khách thuê..."
                            :disabled="isViewOnly"></textarea>
                        <p v-if="form.errors.response_note" class="text-rose-500 text-[11px] font-semibold mt-1">
                            {{ form.errors.response_note }}
                        </p>
                    </div>
                    <!-- Chọn ảnh chứng minh của chủ trọ -->
                    <div v-if="!isViewOnly">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                            Ảnh chứng minh khắc phục (nếu có):
                        </label>
                        <input type="file" multiple accept="image/*" @change="handleFileChange"
                            class="w-full text-xs" />
                        <p v-if="form.errors.response_evidence" class="text-rose-500 text-[11px] font-semibold mt-1">
                            {{ form.errors.response_evidence }}
                        </p>
                    </div>
                    <!-- Ảnh chứng minh cũ đã đăng tải -->
                    <div
                        v-if="isViewOnly && selectedReport.response_evidence && selectedReport.response_evidence.length">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Ảnh khắc phục đã
                            gửi:</label>
                        <div class="flex flex-wrap gap-2">
                            <img v-for="(img, idx) in selectedReport.response_evidence" :key="idx"
                                :src="'/storage/' + img"
                                class="w-16 h-16 object-cover rounded-lg border border-slate-200" />
                        </div>
                    </div>
                    <!-- Ghi chú của Admin (nếu có) -->
                    <div v-if="selectedReport.admin_note"
                        class="bg-indigo-50/50 p-3 rounded-lg border border-indigo-100 text-xs">
                        <span class="font-bold text-indigo-800">Kết luận & Ghi chú từ Admin:</span>
                        <p class="text-slate-700 mt-1">{{ selectedReport.admin_note }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6 border-t pt-3">
                    <button @click="showResolveModal = false"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-xs font-bold transition">
                        Đóng
                    </button>
                    <button v-if="!isViewOnly" @click="submitResolution" :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold transition">
                        {{ form.processing ? 'Đang gửi...' : 'Gửi phản hồi' }}
                    </button>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
