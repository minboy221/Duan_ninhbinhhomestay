<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, reactive } from 'vue'

const form = reactive({
    title: '',
    room: '',
    price: '',
    area: '',
    address: '',
    description: '',
    elecPrice: 3500,
    waterPrice: 15000,
    amenities: [],
    images: [],
})

const amenityOptions = [
    { key: 'wifi',      label: 'WiFi',         icon: 'bi-wifi' },
    { key: 'ac',        label: 'Máy lạnh',     icon: 'bi-thermometer-snow' },
    { key: 'parking',   label: 'Bãi xe',       icon: 'bi-bicycle' },
    { key: 'wm',        label: 'Máy giặt',     icon: 'bi-bag-dash' },
    { key: 'fridge',    label: 'Tủ lạnh',      icon: 'bi-snow' },
    { key: 'security',  label: 'Camera an ninh', icon: 'bi-camera-video' },
    { key: 'toilet',    label: 'WC riêng',     icon: 'bi-droplet' },
    { key: 'balcony',   label: 'Ban công',     icon: 'bi-door-open' },
]

const toggleAmenity = (key) => {
    const idx = form.amenities.indexOf(key)
    if (idx > -1) form.amenities.splice(idx, 1)
    else form.amenities.push(key)
}

const handleImages = (e) => {
    const files = Array.from(e.target.files)
    files.forEach(f => {
        const reader = new FileReader()
        reader.onload = (ev) => form.images.push(ev.target.result)
        reader.readAsDataURL(f)
    })
}

const removeImage = (i) => form.images.splice(i, 1)

const formatMoney = (n) => n ? new Intl.NumberFormat('vi-VN').format(n) + 'đ' : '—'
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Đăng Tin Cho Thuê</h1></template>

        <div class="create-wrap">
            <div class="create-cols">
                <!-- Form -->
                <div class="form-col">
                    <!-- Basic info -->
                    <div class="form-card">
                        <h3 class="fc-title"><i class="bi bi-info-circle-fill"></i> Thông Tin Cơ Bản</h3>
                        <div class="form-group">
                            <label class="form-label">Tiêu đề tin đăng *</label>
                            <input v-model="form.title" class="form-input" placeholder="VD: Phòng trọ sạch sẽ thoáng mát trung tâm..." />
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Phòng *</label>
                                <select v-model="form.room" class="form-input">
                                    <option value="">Chọn phòng</option>
                                    <option>Phòng 103</option>
                                    <option>Phòng 204</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Diện tích (m²) *</label>
                                <input type="number" v-model.number="form.area" class="form-input" placeholder="20" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Địa chỉ *</label>
                            <input v-model="form.address" class="form-input" placeholder="Số nhà, đường, phường, thành phố..." />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mô tả phòng</label>
                            <textarea v-model="form.description" class="form-input form-textarea" rows="4" placeholder="Mô tả chi tiết về phòng trọ, tiện ích, vị trí..."></textarea>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="form-card">
                        <h3 class="fc-title"><i class="bi bi-cash-coin"></i> Giá Thuê & Phí Dịch Vụ</h3>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Giá thuê (đ/tháng) *</label>
                                <input type="number" v-model.number="form.price" class="form-input" placeholder="3000000" />
                                <span class="form-hint" v-if="form.price">{{ formatMoney(form.price) }}</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Giá điện (đ/kWh)</label>
                                <input type="number" v-model.number="form.elecPrice" class="form-input" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Giá nước (đ/m³)</label>
                                <input type="number" v-model.number="form.waterPrice" class="form-input" />
                            </div>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="form-card">
                        <h3 class="fc-title"><i class="bi bi-stars"></i> Tiện Ích</h3>
                        <div class="amenity-grid">
                            <button
                                v-for="a in amenityOptions"
                                :key="a.key"
                                type="button"
                                :class="['amenity-btn', form.amenities.includes(a.key) ? 'amenity-active' : '']"
                                @click="toggleAmenity(a.key)"
                            >
                                <i :class="['bi', a.icon]"></i>
                                <span>{{ a.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: images + map + preview -->
                <div class="right-col">
                    <!-- Images -->
                    <div class="form-card">
                        <h3 class="fc-title"><i class="bi bi-images"></i> Hình Ảnh Phòng</h3>
                        <label class="img-upload-area">
                            <input type="file" multiple accept="image/*" @change="handleImages" style="display:none" />
                            <i class="bi bi-cloud-upload"></i>
                            <span>Nhấn để chọn ảnh</span>
                            <span class="img-hint">JPG, PNG tối đa 5MB mỗi ảnh</span>
                        </label>
                        <div class="img-preview-grid" v-if="form.images.length > 0">
                            <div v-for="(src, i) in form.images" :key="i" class="img-preview-item">
                                <img :src="src" :alt="`Ảnh ${i+1}`" />
                                <button class="img-remove" @click="removeImage(i)"><i class="bi bi-x"></i></button>
                                <span v-if="i === 0" class="img-main-badge">Ảnh chính</span>
                            </div>
                        </div>
                    </div>

                    <!-- Map placeholder -->
                    <div class="form-card">
                        <h3 class="fc-title"><i class="bi bi-geo-alt-fill"></i> Vị Trí Trên Bản Đồ</h3>
                        <div class="map-placeholder">
                            <i class="bi bi-map"></i>
                            <span>Bản đồ sẽ hiển thị ở đây</span>
                            <span class="map-hint">Tính năng Google Maps sẽ được tích hợp</span>
                        </div>
                        <input v-model="form.address" class="form-input mt-10" placeholder="Nhập địa chỉ để tìm trên bản đồ..." />
                    </div>

                    <!-- Preview -->
                    <div class="preview-card">
                        <h3 class="fc-title"><i class="bi bi-eye-fill"></i> Xem Trước Tin Đăng</h3>
                        <div class="preview-box">
                            <div class="prev-title">{{ form.title || 'Tiêu đề tin đăng...' }}</div>
                            <div class="prev-price">{{ formatMoney(form.price) }}</div>
                            <div class="prev-meta">
                                <span v-if="form.area"><i class="bi bi-rulers"></i> {{ form.area }} m²</span>
                                <span v-if="form.address"><i class="bi bi-geo-alt"></i> {{ form.address.substring(0,30) }}...</span>
                            </div>
                            <div class="prev-amenities" v-if="form.amenities.length > 0">
                                <span v-for="key in form.amenities.slice(0,4)" :key="key" class="prev-amenity">
                                    {{ amenityOptions.find(a=>a.key===key)?.label }}
                                </span>
                                <span v-if="form.amenities.length > 4" class="prev-amenity">+{{ form.amenities.length - 4 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="submit-bar">
                <a href="/landlord/listings" class="btn-cancel">Hủy</a>
                <button class="btn-draft"><i class="bi bi-save"></i> Lưu Nháp</button>
                <button class="btn-submit"><i class="bi bi-send-fill"></i> Đăng Tin</button>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.create-wrap { display: flex; flex-direction: column; gap: 20px; }
.create-cols { display: grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: flex-start; }
.form-col, .right-col { display: flex; flex-direction: column; gap: 16px; }

.form-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0fdf4; display: flex; flex-direction: column; gap: 14px; }
.fc-title { font-size: 14px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }

.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-row-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.form-label { font-size: 12px; font-weight: 600; color: #374151; }
.form-input { padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; outline: none; width: 100%; box-sizing: border-box; }
.form-input:focus { border-color: #0f766e; }
.form-textarea { resize: vertical; font-family: inherit; }
.form-hint { font-size: 12px; color: #0f766e; font-weight: 600; }
.mt-10 { margin-top: 10px; }

/* Amenities */
.amenity-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.amenity-btn {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    padding: 10px 6px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #6b7280;
    font-size: 11px; font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}
.amenity-btn i { font-size: 18px; }
.amenity-active { border-color: #0f766e !important; background: #f0fdf4 !important; color: #0f766e !important; }
.amenity-btn:hover:not(.amenity-active) { border-color: #d1fae5; background: #f0fdf4; color: #374151; }

/* Images */
.img-upload-area {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    border: 2px dashed #d1fae5; border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: border-color 0.15s;
    color: #6b7280;
}
.img-upload-area:hover { border-color: #0f766e; }
.img-upload-area i { font-size: 32px; color: #0f766e; }
.img-upload-area span { font-size: 14px; font-weight: 600; }
.img-hint { font-size: 11px; color: #9ca3af; font-weight: 400; }

.img-preview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
.img-preview-item { position: relative; border-radius: 8px; overflow: hidden; aspect-ratio: 1; }
.img-preview-item img { width: 100%; height: 100%; object-fit: cover; }
.img-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.5); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.img-main-badge { position: absolute; bottom: 4px; left: 4px; background: #0f766e; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 100px; }

/* Map */
.map-placeholder {
    background: #f0fdf4; border-radius: 10px;
    height: 160px; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 8px; color: #6b7280;
}
.map-placeholder i { font-size: 36px; color: #6ee7b7; }
.map-hint { font-size: 11px; color: #9ca3af; }

/* Preview */
.preview-card { background: #f0fdf4; border: 1.5px solid #d1fae5; border-radius: 16px; padding: 18px; }
.preview-box { background: #fff; border-radius: 12px; padding: 14px; margin-top: 10px; }
.prev-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.prev-price { font-size: 20px; font-weight: 800; color: #0f766e; margin-bottom: 8px; }
.prev-meta { display: flex; gap: 12px; font-size: 12px; color: #6b7280; margin-bottom: 8px; flex-wrap: wrap; }
.prev-meta span { display: flex; align-items: center; gap: 4px; }
.prev-amenities { display: flex; gap: 6px; flex-wrap: wrap; }
.prev-amenity { padding: 3px 10px; background: #f0fdf4; color: #0f766e; border-radius: 100px; font-size: 11px; font-weight: 600; }

/* Submit bar */
.submit-bar {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 10px;
    background: #fff; border-radius: 16px; padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.btn-cancel  { padding: 10px 20px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-draft   { padding: 10px 20px; background: #fef9c3; color: #854d0e; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-submit  { padding: 10px 24px; background: #0f766e; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 7px; }
.btn-submit:hover { background: #0d9488; }

@media (max-width: 1100px) {
    .create-cols { grid-template-columns: 1fr; }
    .amenity-grid { grid-template-columns: repeat(4, 1fr); }
}
</style>
