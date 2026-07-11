<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

const { props } = usePage();
const user = computed(() => props.auth.user);

// Tính toán trạng thái có thể cập nhật thông tin từ Backend truyền xuống
const pageProps = computed(() => usePage().props);
const canUpdateProfile = computed(() => pageProps.value.canUpdateProfile !== false);
const daysUntilNextUpdate = computed(() => pageProps.value.daysUntilNextUpdate || 0);

const form = useForm({
    name: user.value.name || '',
    phone: user.value.phone || '',
    address: user.value.address || '',
    job: user.value.job || '',
    dob: user.value.dob || '',
    gender: user.value.gender || '',
});

const provinces = ref([]);
const wards = ref([]);
const selectedProvinceCode = ref('');
const selectedWardCode = ref('');
const addressDetail = ref('');

const fetchProvinces = async () => {
    try {
        const res = await fetch('https://provinces.open-api.vn/api/v2/p/');
        provinces.value = await res.json();
        
        // Phân tách địa chỉ cũ để tự động map vào dropdown
        if (form.address) {
            const parts = form.address.split(',').map(s => s.trim());
            if (parts.length >= 2) {
                const provName = parts[parts.length - 1];
                const matchedProv = provinces.value.find(p => p.name === provName);
                if (matchedProv) {
                    selectedProvinceCode.value = matchedProv.code;
                    await fetchWards(matchedProv.code);
                    
                    const wardName = parts[parts.length - 2];
                    const matchedWard = wards.value.find(w => w.name === wardName);
                    if (matchedWard) {
                        selectedWardCode.value = matchedWard.code;
                    }
                    
                    addressDetail.value = parts.slice(0, parts.length - 2).join(', ');
                } else {
                    addressDetail.value = form.address;
                }
            } else {
                addressDetail.value = form.address;
            }
        }
    } catch (e) {
        console.error('Lỗi tải danh sách Tỉnh/Thành:', e);
    }
};

const fetchWards = async (provinceCode) => {
    try {
        const res = await fetch(`https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`);
        const data = await res.json();
        wards.value = data.wards || [];
    } catch (e) {
        console.error('Lỗi tải danh sách Phường/Xã:', e);
    }
};

const onProvinceChange = async () => {
    selectedWardCode.value = '';
    wards.value = [];
    if (selectedProvinceCode.value) {
        await fetchWards(selectedProvinceCode.value);
    }
    updateAddressField();
};

const updateAddressField = () => {
    const prov = provinces.value.find(p => p.code === selectedProvinceCode.value);
    const ward = wards.value.find(w => w.code === selectedWardCode.value);
    
    if (prov && ward) {
        form.address = `${addressDetail.value ? addressDetail.value + ', ' : ''}${ward.name}, ${prov.name}`;
    } else if (prov) {
        form.address = `${addressDetail.value ? addressDetail.value + ', ' : ''}${prov.name}`;
    } else {
        form.address = addressDetail.value;
    }
};

onMounted(() => {
    fetchProvinces();
});

const submit = () => {
    form.post(route('tranguser.update'), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Trang Cá Nhân | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="tongquan_user">
                    <div class="item_user1">
                        <div class="infor_tongquan">
                            <p>Đã tham gia vào ngày</p>
                            <span>{{ new Date(user.created_at).toLocaleDateString('vi-VN') }}</span>
                        </div>
                    </div>
                    <div class="item_user2">
                        <div class="infor_tongquan">
                            <p>Trạng thái thuê trọ</p>
                            <span>{{ $page.props.rentalStatus }}</span>
                        </div>
                    </div>
                    <div class="item_user3">
                        <div class="infor_tongquan">
                            <p>Trạng thái tài khoản</p>
                            <span>{{ $page.props.accountStatus }}</span>
                        </div>
                    </div>
                </div>
                <div class="noidung_taikhoan">
                    <h2>Thông tin tài khoản</h2>
                    
                    <!-- Banner hiển thị thông báo lỗi lưu hành động từ Backend -->
                    <div v-if="form.errors.profile" class="text-red-600 mb-4 font-medium alert-profile-error">
                        {{ form.errors.profile }}
                    </div>

                    <!-- Banner cảnh báo giới hạn thay đổi thông tin 15 ngày -->
                    <div v-if="!canUpdateProfile" class="alert-info-15days">
                        <svg xmlns="http://www.w3.org/2000/svg" class="alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="alert-content">
                            <span class="alert-title">Giới hạn cập nhật thông tin cá nhân</span>
                            <p class="alert-desc">
                                Bạn chỉ có thể cập nhật thông tin cá nhân 1 lần mỗi 15 ngày. Lần cập nhật tiếp theo khả dụng sau <strong class="text-yellow-600 font-bold">{{ daysUntilNextUpdate }} ngày</strong> nữa.
                            </p>
                        </div>
                    </div>

                    <div v-if="$page.props.flash && $page.props.flash.success" class="text-green-600 mb-4 font-medium">
                        {{ $page.props.flash.success }}
                    </div>
                    <form @submit.prevent="submit">
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và Tên:</label>
                                <input type="text" v-model="form.name" placeholder="Họ và Tên" :disabled="!canUpdateProfile">
                                <span v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</span>
                            </div>

                            <div class="form-group">
                                <label>SĐT {{ user.phone ? '(Không thể thay đổi)' : '(Chỉ được nhập 1 lần)' }}:</label>
                                <input type="text" v-model="form.phone" placeholder="Số điện thoại" :disabled="!!user.phone">
                                <span v-if="form.errors.phone" class="text-red-500 text-sm">{{ form.errors.phone }}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Tỉnh / Thành phố:</label>
                                <select v-model="selectedProvinceCode" @change="onProvinceChange" :disabled="!canUpdateProfile">
                                    <option value="">-- Chọn Tỉnh / Thành phố --</option>
                                    <option v-for="prov in provinces" :key="prov.code" :value="prov.code">
                                        {{ prov.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Phường / Xã / Thị trấn:</label>
                                <select v-model="selectedWardCode" @change="updateAddressField" :disabled="!canUpdateProfile || !selectedProvinceCode">
                                    <option value="">-- Chọn Phường / Xã / Thị trấn --</option>
                                    <option v-for="w in wards" :key="w.code" :value="w.code">
                                        {{ w.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Thôn / Xóm / Số nhà / Đường:</label>
                            <input type="text" v-model="addressDetail" @input="updateAddressField" placeholder="Nhập thôn, xóm, số nhà, tên đường..." :disabled="!canUpdateProfile">
                            <span v-if="form.errors.address" class="text-red-500 text-sm">{{ form.errors.address }}</span>
                        </div>

                        <div class="form-group">
                            <label>Nghề Nghiệp Hiện Tại:</label>
                            <input type="text" v-model="form.job" placeholder="Nghề nghiệp" :disabled="!canUpdateProfile">
                            <span v-if="form.errors.job" class="text-red-500 text-sm">{{ form.errors.job }}</span>
                        </div>

                        <div class="form-group">
                            <label>Ngày sinh:</label>
                            <input type="date" v-model="form.dob" :disabled="!canUpdateProfile">
                            <span v-if="form.errors.dob" class="text-red-500 text-sm">{{ form.errors.dob }}</span>
                        </div>
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select v-model="form.gender" :disabled="!canUpdateProfile">
                                <option value="">-- Chọn Giới Tính --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                            <span v-if="form.errors.gender" class="text-red-500 text-sm">{{ form.errors.gender }}</span>
                        </div>
                        <button class="btn_save" type="submit" :disabled="form.processing || !canUpdateProfile">
                            Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </UserLayout>
</template>
<style scoped>
@import "../../css/user.css";
@import '../../css/responsive/responsivetranguser.css';

.alert-info-15days {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background-color: #fff9db;
    border-left: 4px solid #fcc419;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.alert-icon {
    width: 24px;
    height: 24px;
    color: #fcc419;
    flex-shrink: 0;
}

.alert-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.alert-title {
    font-size: 14px;
    font-weight: 700;
    color: #f59f00;
}

.alert-desc {
    font-size: 13px;
    color: #666;
    margin: 0;
}

.alert-profile-error {
    background-color: #fff5f5;
    border-left: 4px solid #ff8787;
    border-radius: 6px;
    padding: 12px 16px;
    color: #e03131;
}

input:disabled, select:disabled {
    background-color: #f1f3f5;
    color: #868e96;
    cursor: not-allowed;
    border-color: #e9ecef;
}

.btn_save:disabled {
    background: #ced4da;
    cursor: not-allowed;
    transform: none;
}
</style>