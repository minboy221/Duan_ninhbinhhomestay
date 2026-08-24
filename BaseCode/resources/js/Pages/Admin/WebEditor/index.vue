<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    initialSettings: {
        type: Object,
        default: () => ({}),
    },
});

// Khởi tạo form lưu trữ toàn bộ cấu hình
const form = useForm({
    hero_title:
        props.initialSettings.hero_title || "Tìm Phòng Và Nhà Trọ Phù Hợp",
    hero_subtitle:
        props.initialSettings.hero_subtitle ||
        "Hệ thống tìm kiếm và quản lý phòng trọ thông minh số 1 tại Ninh Bình.",
    contact_phone: props.initialSettings.contact_phone || "0912 345 678",
    contact_email:
        props.initialSettings.contact_email || "contact@ninhbinhhomestay.vn",
    contact_address:
        props.initialSettings.contact_address || "Ninh Bình, Việt Nam",
    contact_map: props.initialSettings.contact_map || "",
    banners: props.initialSettings.banners || [
        {
            id: 1,
            title: "Banner chính trang chủ",
            img: "/anh/banner.png",
            active: true,
            order: 1,
        },
    ],
    report_negotiation_days: props.initialSettings.report_negotiation_days || 7,
    warning_electricity_price:
        props.initialSettings.warning_electricity_price || 8000,
    warning_water_price: props.initialSettings.warning_water_price || 40000,
    warning_invoice_amount:
        props.initialSettings.warning_invoice_amount || 10000000,
    warning_monthly_rent:
        props.initialSettings.warning_monthly_rent || 15000000,
    not_interested_reasons: Array.isArray(props.initialSettings.not_interested_reasons)
        ? props.initialSettings.not_interested_reasons
        : (typeof props.initialSettings.not_interested_reasons === 'object' && props.initialSettings.not_interested_reasons !== null
            ? Object.values(props.initialSettings.not_interested_reasons)
            : [
                "Giá thuê quá cao so với chất lượng",
                "Phòng thực tế khác nhiều so với ảnh",
                "Cơ sở vật chất xuống cấp, vệ sinh kém",
                "Thái độ của chủ trọ không tốt",
                "Vị trí không thuận tiện đi lại",
                "Lý do khác",
            ]),
});

// Quản lý Tab hiện tại: 'banners', 'hero', 'contact'
const savedTab =
    localStorage.getItem("admin_webeditor_active_tab") || "banners";
const activeTab = ref(savedTab);
const saved = ref(false);
const fileInputs = ref([]);
const dropzoneInput = ref(null);

function save() {
    form.post(route("admin.website.update"), {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => {
                saved.value = false;
            }, 3000);
        },
    });
}

function toggleBanner(b) {
    b.active = !b.active;
}

function updateOrder(b, val) {
    b.order = parseInt(val) || 0;
}
function switchTab(tabName) {
    activeTab.value = tabName;
    localStorage.setItem("admin_webeditor_active_tab", tabName);
}

function addBanner() {
    form.banners.push({
        id: Date.now(),
        title: "Banner mới",
        img: "",
        file: null,
        active: true,
        order: form.banners.length + 1,
    });
}

function delBanner(b) {
    form.banners = form.banners.filter((x) => x.id !== b.id);
}

function triggerUpload(index) {
    const el = fileInputs.value[index];
    if (el) {
        el.click();
    }
}

function onFileChange(e, index) {
    const file = e.target.files[0];
    if (file) {
        form.banners[index].file = file;
        form.banners[index].img = URL.createObjectURL(file);
    }
}

// Xử lý kéo thả / Click để thêm ảnh trực tiếp ở Dropzone mới
function handleDropzoneClick() {
    if (dropzoneInput.value) {
        dropzoneInput.value.click();
    }
}

function onDropzoneFileChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.banners.push({
            id: Date.now(),
            title: "Banner " + (form.banners.length + 1),
            img: URL.createObjectURL(file),
            file: file,
            active: true,
            order: form.banners.length + 1,
        });
    }
}

//phần crud lý do
const newReasonText = ref("");

function ensureReasonsArray() {
    if (!Array.isArray(form.not_interested_reasons)) {
        form.not_interested_reasons = typeof form.not_interested_reasons === "object" && form.not_interested_reasons !== null
            ? Object.values(form.not_interested_reasons)
            : [];
    }
}

function addReason() {
    if (!newReasonText.value.trim()) return;
    ensureReasonsArray();
    form.not_interested_reasons.push(newReasonText.value.trim());
    newReasonText.value = "";
}
function removeReason(index) {
    ensureReasonsArray();
    form.not_interested_reasons.splice(index, 1);
}
</script>

<template>

    <Head title="Admin - Chỉnh Sửa Giao Diện" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="header-title">Chỉnh Sửa Website</h1>
                <p class="text-xs text-gray-500 mt-1">
                    Tùy biến banner, nội dung trang chủ và thông tin liên hệ
                    động
                </p>
            </div>
        </template>

        <!-- Trình quản trị kiểu Tabbed Workspace -->
        <div class="premium-workspace">
            <div class="workspace-main">
                <!-- Navigation Tabs -->
                <div class="tab-navbar">
                    <div class="tab-nav-links">
                        <button @click="activeTab = 'banners'" :class="[
                            'tab-link',
                            activeTab === 'banners' ? 'active' : '',
                        ]">
                            <i class="bi bi-images"></i> Quản Lý Banner
                        </button>
                        <button @click="activeTab = 'hero'" :class="[
                            'tab-link',
                            activeTab === 'hero' ? 'active' : '',
                        ]">
                            <i class="bi bi-type-h1"></i> Tiêu Đề Hero
                        </button>
                        <button @click="activeTab = 'contact'" :class="[
                            'tab-link',
                            activeTab === 'contact' ? 'active' : '',
                        ]">
                            <i class="bi bi-telephone-fill"></i> Liên Hệ & Bản
                            Đồ
                        </button>
                        <button @click="activeTab = 'thresholds'" :class="[
                            'tab-link',
                            activeTab === 'thresholds' ? 'active' : '',
                        ]">
                            <i class="bi bi-shield-fill-exclamation"></i> Cấu
                            Hình Ngưỡng Cảnh Báo
                        </button>
                        <button @click="activeTab = 'feedback'" :class="[
                            'tab-link',
                            activeTab === 'feedback' ? 'active' : '',
                        ]">
                            <i class="bi bi-patch-question-fill"></i> Lý Do Không Ưng
                        </button>
                    </div>
                    <div class="tab-actions">
                        <button @click="save" :disabled="form.processing"
                            :class="['save-btn-premium', saved ? 'saved' : '']">
                            <i :class="[
                                'bi',
                                saved
                                    ? 'bi-check-circle-fill'
                                    : form.processing
                                        ? 'spinner'
                                        : 'bi-send-fill',
                            ]"></i>
                            <span>{{
                                saved
                                    ? "Đã lưu!"
                                    : form.processing
                                        ? "Đang lưu..."
                                        : "Lưu Thay Đổi"
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content: Banners -->
                <div v-if="activeTab === 'banners'" class="tab-pane fade-in">
                    <div class="pane-header">
                        <h3 class="pane-title">Cấu hình Banner Trượt</h3>
                        <p class="pane-sub">
                            Kéo thả hoặc nhấn vào ô trống bên dưới để tải ảnh
                            banner mới.
                        </p>
                    </div>

                    <div class="banners-grid">
                        <!-- Banner Cards -->
                        <div v-for="(b, index) in form.banners" :key="b.id" class="banner-card">
                            <div class="card-image-wrap">
                                <img v-if="b.img" :src="b.img" alt="banner preview" class="card-img" />
                                <div v-else class="card-img-placeholder">
                                    <i class="bi bi-image"></i>
                                    <span>Chưa có ảnh</span>
                                </div>
                                <div class="card-image-overlay">
                                    <button type="button" @click="triggerUpload(index)" class="overlay-btn"
                                        title="Đổi ảnh">
                                        <i class="bi bi-camera-fill"></i> Đổi
                                        ảnh
                                    </button>
                                    <input type="file" :ref="(el) => {
                                        fileInputs[index] = el;
                                    }
                                        " @change="onFileChange($event, index)" style="display: none"
                                        accept="image/*" />
                                </div>
                            </div>
                            <div class="card-details">
                                <input v-model="b.title" class="card-title-input" placeholder="Tiêu đề banner..." />
                                <div class="card-meta">
                                    <div class="order-input-group">
                                        <span class="label">Vị trí:</span>
                                        <input type="number" :value="b.order" @input="
                                            updateOrder(
                                                b,
                                                $event.target.value,
                                            )
                                            " class="small-number-input" />
                                    </div>
                                    <div class="card-actions-row">
                                        <button type="button" @click="toggleBanner(b)" :class="[
                                            'card-btn',
                                            b.active
                                                ? 'active-green'
                                                : 'inactive-gray',
                                        ]" :title="b.active
                                            ? 'Click để ẩn'
                                            : 'Click để hiển thị'
                                            ">
                                            <i :class="[
                                                'bi',
                                                b.active
                                                    ? 'bi-eye-fill'
                                                    : 'bi-eye-slash-fill',
                                            ]"></i>
                                            <span>{{
                                                b.active ? "Hiển thị" : "Đã ẩn"
                                            }}</span>
                                        </button>
                                        <button type="button" @click="delBanner(b)" class="card-btn delete-red"
                                            title="Xóa banner">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone card for adding new banner -->
                        <div @click="handleDropzoneClick" class="banner-card dropzone-card">
                            <i class="bi bi-cloud-arrow-up-fill icon-upload"></i>
                            <span class="title">Tải Ảnh Mới</span>
                            <span class="sub">Nhấn vào đây để chọn ảnh</span>
                            <input type="file" ref="dropzoneInput" @change="onDropzoneFileChange" style="display: none"
                                accept="image/*" />
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Hero Text -->
                <div v-if="activeTab === 'hero'" class="tab-pane fade-in">
                    <div class="pane-header">
                        <h3 class="pane-title">Nội dung Trang Chủ</h3>
                        <p class="pane-sub">
                            Thay đổi tiêu đề lớn và lời giới thiệu ở đầu trang
                            chủ.
                        </p>
                    </div>

                    <div class="card-form">
                        <div class="form-group-premium">
                            <label class="label-premium">Tiêu Đề Lớn (H1)</label>
                            <input v-model="form.hero_title" class="input-premium"
                                placeholder="Ví dụ: Tìm Phòng Và Nhà Trọ Phù Hợp" />
                            <small class="help-text">Nên giữ độ dài khoảng 30 - 60 ký tự để hiển thị
                                đẹp nhất.</small>
                        </div>
                        <div class="form-group-premium mt-4">
                            <label class="label-premium">Lời Mô Tả Phụ (Subtitle)</label>
                            <textarea v-model="form.hero_subtitle" class="textarea-premium" rows="4"
                                placeholder="Nhập mô tả ngắn về website của bạn..."></textarea>
                            <small class="help-text">Mô tả ngắn gọn, súc tích về Ninh Bình
                                Homestay.</small>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Contact & Map -->
                <div v-if="activeTab === 'contact'" class="tab-pane fade-in">
                    <div class="pane-header">
                        <h3 class="pane-title">Thông Tin Liên Hệ & Bản Đồ</h3>
                        <p class="pane-sub">
                            Quản lý các thông tin liên hệ và link nhúng Google
                            Map hiển thị ở trang Liên Hệ.
                        </p>
                    </div>

                    <div class="card-form">
                        <div class="grid-2-cols">
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-telephone"></i> Số Điện
                                    Thoại</label>
                                <input v-model="form.contact_phone" class="input-premium"
                                    placeholder="Ví dụ: 0912 345 678" />
                            </div>
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-envelope"></i> Email Liên
                                    Hệ</label>
                                <input v-model="form.contact_email" class="input-premium"
                                    placeholder="Ví dụ: contact@staywork.com" />
                            </div>
                        </div>

                        <div class="form-group-premium mt-4">
                            <label class="label-premium"><i class="bi bi-geo-alt"></i> Địa Chỉ</label>
                            <input v-model="form.contact_address" class="input-premium"
                                placeholder="Nhập địa chỉ văn phòng đại diện..." />
                        </div>

                        <div class="tab-nav-links">
                            <button @click="activeTab = 'banners'" :class="[
                                'tab-link',
                                activeTab === 'banners' ? 'active' : '',
                            ]">
                                <i class="bi bi-images"></i> Quản Lý Banner
                            </button>
                            <button @click="activeTab = 'hero'" :class="[
                                'tab-link',
                                activeTab === 'hero' ? 'active' : '',
                            ]">
                                <i class="bi bi-type-h1"></i> Tiêu Đề Hero
                            </button>
                            <button @click="activeTab = 'contact'" :class="[
                                'tab-link',
                                activeTab === 'contact' ? 'active' : '',
                            ]">
                                <i class="bi bi-telephone-fill"></i> Liên Hệ &
                                Bản Đồ
                            </button>
                            <button @click="activeTab = 'thresholds'" :class="[
                                'tab-link',
                                activeTab === 'thresholds' ? 'active' : '',
                            ]">
                                <i class="bi bi-shield-fill-exclamation"></i>
                                Cấu Hình Ngưỡng Cảnh Báo
                            </button>
                        </div>

                        <div class="form-group-premium mt-4">
                            <label class="label-premium"><i class="bi bi-map"></i> Đường dẫn nhúng Bản
                                đồ (Google Map Embed Link)</label>
                            <textarea v-model="form.contact_map" class="textarea-premium" rows="4"
                                placeholder="Dán link src trong thẻ iframe nhúng từ Google Maps..."></textarea>
                            <div class="map-guide mt-2">
                                <span class="badge">Hướng dẫn lấy link:</span>
                                <ul>
                                    <li>
                                        Vào Google Maps > Tìm địa chỉ cần hiển
                                        thị > Bấm <strong>Chia sẻ</strong>.
                                    </li>
                                    <li>
                                        Chọn tab <strong>Nhúng bản đồ</strong> >
                                        Copy đường dẫn nằm trong thuộc tính
                                        <code>src="..."</code> của thẻ iframe.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Tab Content: Threshold Settings -->
                <!-- Tab Content: Threshold Settings (Sửa đổi hiển thị lỗi validation) -->
                <div v-if="activeTab === 'thresholds'" class="tab-pane fade-in">
                    <div class="pane-header">
                        <h3 class="pane-title">
                            Ngưỡng Cảnh Báo Hành Vi Bất Thường
                        </h3>
                        <p class="pane-sub">
                            Thiết lập giới hạn tối đa. Vượt quá mức này, log
                            hoạt động của chủ trọ sẽ tự động bị gắn cờ Nhạy cảm.
                        </p>
                    </div>

                    <div class="card-form">
                        <div class="grid-2-cols" style="
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 16px;
                            ">
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-lightning-charge-fill"
                                        style="color: #eab308"></i>
                                    Giá Điện Cảnh Báo (VND/kWh)</label>
                                <input type="number" v-model="form.warning_electricity_price" class="input-premium" />
                                <!-- THÀNH PHẦN HIỂN THỊ LỖI GIÁ ĐIỆN -->
                                <span v-if="form.errors.warning_electricity_price"
                                    class="text-xs text-red-500 mt-1 block">
                                    {{ form.errors.warning_electricity_price }}
                                </span>
                            </div>
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-droplet-fill" style="color: #3b82f6"></i>
                                    Giá Nước Cảnh Báo (VND/m³)</label>
                                <input type="number" v-model="form.warning_water_price" class="input-premium" />
                                <!-- THÀNH PHẦN HIỂN THỊ LỖI GIÁ NƯỚC -->
                                <span v-if="form.errors.warning_water_price" class="text-xs text-red-500 mt-1 block">
                                    {{ form.errors.warning_water_price }}
                                </span>
                            </div>
                        </div>

                        <div class="grid-2-cols mt-4" style="
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 16px;
                                margin-top: 16px;
                            ">
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-wallet2" style="color: #10b981"></i>
                                    Tổng Hóa Đơn Cảnh Báo (VND/Tháng)</label>
                                <input type="number" v-model="form.warning_invoice_amount" class="input-premium" />
                                <!-- THÀNH PHẦN HIỂN THỊ LỖI TỔNG TIỀN HÓA ĐƠN -->
                                <span v-if="form.errors.warning_invoice_amount" class="text-xs text-red-500 mt-1 block">
                                    {{ form.errors.warning_invoice_amount }}
                                </span>
                            </div>
                            <div class="form-group-premium">
                                <label class="label-premium"><i class="bi bi-file-earmark-text-fill"
                                        style="color: #8b5cf6"></i>
                                    Giá Thuê Cảnh Báo (VND/Tháng)</label>
                                <input type="number" v-model="form.warning_monthly_rent" class="input-premium" />
                                <!-- THÀNH PHẦN HIỂN THỊ LỖI GIÁ THUÊ -->
                                <span v-if="form.errors.warning_monthly_rent" class="text-xs text-red-500 mt-1 block">
                                    {{ form.errors.warning_monthly_rent }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Lý do không ưng -->
                <div v-if="activeTab === 'feedback'" class="tab-pane fade-in">
                    <div class="pane-header">
                        <h3 class="pane-title">Cấu hình lý do "Không ưng" phòng</h3>
                        <p class="pane-sub">Danh sách các lý do gợi ý hiển thị cho khách thuê chọn khi không muốn thuê phòng.</p>
                    </div>

                    <div class="pane-body" style="margin-top: 20px;">
                        <!-- Ô nhập lý do mới -->
                        <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                            <input v-model="newReasonText" type="text" placeholder="Nhập lý do không ưng mới..." 
                                   style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none;"
                                   @keyup.enter="addReason" />
                            <button @click="addReason" type="button" 
                                    style="background: #4f46e5; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                                Thêm lý do
                            </button>
                        </div>

                        <!-- Danh sách lý do hiện tại -->
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div v-for="(reason, idx) in form.not_interested_reasons" :key="idx" 
                                 style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                    <span style="color: #94a3b8; font-weight: 700; font-size: 12px;">#{{ idx + 1 }}</span>
                                    <input v-model="form.not_interested_reasons[idx]" type="text" 
                                           style="border: none; background: transparent; font-size: 13px; font-weight: 500; color: #334155; width: 80%; outline: none;" />
                                </div>
                                <button @click="removeReason(idx)" type="button" 
                                        style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 12px; font-weight: 700;">
                                    Xóa
                                </button>
                            </div>
                        </div>

                        <p v-if="form.not_interested_reasons.length === 0" style="text-align: center; color: #94a3b8; font-style: italic; font-size: 12px; margin-top: 20px;">
                            Chưa có lý do nào được cấu hình.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Toàn bộ giao diện layout cao cấp */
.header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.page-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.page-sub {
    font-size: 13px;
    color: #64748b;
    margin: 3px 0 0;
}

/* Nút lưu thay đổi cao cấp */
.save-btn-premium {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.25s ease;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
}

.save-btn-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(79, 70, 229, 0.35);
}

.save-btn-premium:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.save-btn-premium.saved {
    background: #10b981;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
}

.premium-workspace {
    display: block;
    margin-top: 20px;
}

.workspace-main {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}

/* Thanh Tabs Navbar phẳng và đẹp */
.tab-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 16px;
}

.tab-nav-links {
    display: flex;
}

.tab-actions {
    padding: 6px 0;
}

.tab-link {
    padding: 14px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.tab-link:hover {
    color: #0f172a;
}

.tab-link.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
    font-weight: 700;
}

/* Các vùng hiển thị nội dung từng tab */
.tab-pane {
    padding: 24px;
}

.pane-header {
    margin-bottom: 20px;
}

.pane-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.pane-sub {
    font-size: 12px;
    color: #64748b;
    margin: 4px 0 0;
}

/* Grid hiển thị danh sách Banners */
.banners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px;
}

/* Từng card banner trực quan */
.banner-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
}

.banner-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.card-image-wrap {
    height: 130px;
    width: 100%;
    position: relative;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-img-placeholder {
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.card-img-placeholder i {
    font-size: 32px;
}

.card-img-placeholder span {
    font-size: 11px;
}

/* Lớp phủ hover trên ảnh để thay ảnh nhanh */
.card-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.card-image-wrap:hover .card-image-overlay {
    opacity: 1;
}

.overlay-btn {
    background: #ffffff;
    color: #0f172a;
    border: none;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-details {
    padding: 12px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.card-title-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    outline: none;
    box-sizing: border-box;
}

.card-title-input:focus {
    border-color: #4f46e5;
}

.card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
}

.order-input-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.order-input-group .label {
    font-size: 11px;
    color: #64748b;
}

.small-number-input {
    width: 45px;
    padding: 3px 5px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 11px;
    text-align: center;
}

.card-actions-row {
    display: flex;
    gap: 4px;
}

.card-btn {
    padding: 5px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}

.active-green {
    background: #ecfdf5;
    color: #059669;
}

.active-green:hover {
    background: #059669;
    color: #fff;
}

.inactive-gray {
    background: #f1f5f9;
    color: #64748b;
}

.inactive-gray:hover {
    background: #64748b;
    color: #fff;
}

.delete-red {
    background: #fef2f2;
    color: #ef4444;
}

.delete-red:hover {
    background: #ef4444;
    color: #fff;
}

/* Card Dropzone để kéo thả và thêm ảnh nhanh */
.dropzone-card {
    border: 2px dashed #cbd5e1;
    background: #ffffff;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    gap: 6px;
}

.dropzone-card:hover {
    border-color: #4f46e5;
    background: #f8fafc;
}

.icon-upload {
    font-size: 32px;
    color: #94a3b8;
    transition: color 0.2s ease;
}

.dropzone-card:hover .icon-upload {
    color: #4f46e5;
}

.dropzone-card .title {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.dropzone-card .sub {
    font-size: 11px;
    color: #94a3b8;
}

/* Các trường input form cao cấp */
.card-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group-premium {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.label-premium {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 6px;
}

.label-premium i {
    color: #4f46e5;
}

.input-premium {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
}

.input-premium:focus {
    border-color: #4f46e5;
}

.textarea-premium {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    resize: none;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
}

.textarea-premium:focus {
    border-color: #4f46e5;
}

.help-text {
    font-size: 11px;
    color: #94a3b8;
}

.grid-2-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 600px) {
    .grid-2-cols {
        grid-template-columns: 1fr;
    }
}

.fade-in {
    animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
