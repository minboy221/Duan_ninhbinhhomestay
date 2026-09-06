<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, usePage, useForm, router } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";

const { props } = usePage();
const user = computed(() => props.auth.user);

// Tính toán trạng thái có thể cập nhật thông tin từ Backend truyền xuống
const pageProps = computed(() => usePage().props);
const canUpdateProfile = computed(
    () => pageProps.value.canUpdateProfile !== false,
);
const daysUntilNextUpdate = computed(
    () => pageProps.value.daysUntilNextUpdate || 0,
);

// Biển kiểm tra trạng thái khoá
const isProfileLocked = computed(() => {
    return !!user.value?.last_profile_update_at;
});

import { showSuccess, showError } from "@/Utils/swal";

const showRequestModal = ref(false);
const requestReason = ref("");
const requestProcessing = ref(false);

const submitUnlockRequest = () => {
    if (!requestReason.value || requestReason.value.trim().length < 10) {
        showError(
            "Thiếu thông tin",
            "Vui lòng nhập lý do cụ thể (tối thiểu 10 ký tự)!",
        );
        return;
    }
    requestProcessing.value = true;
    router.post(
        route("profile.request-unlock"),
        {
            reason: requestReason.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRequestModal.value = false;
                requestReason.value = "";
                requestProcessing.value = false;
                showSuccess(
                    "Thành công",
                    "Đã gửi yêu cầu xin mở khóa chỉnh sửa thông tin tới Admin!",
                );
            },
            onError: (err) => {
                requestProcessing.value = false;
                const firstErr = Object.values(err)[0];
                showError(
                    "Lỗi",
                    firstErr || "Không thể gửi yêu cầu. Vui lòng thử lại!",
                );
            },
        },
    );
};

// Kiểm tra xem CCCD đã được cập nhật chưa (Khóa nếu đã có 12 số)
const hasCccd = computed(() => {
    return !!(
        user.value?.cccd_number &&
        String(user.value.cccd_number).trim().length === 12
    );
});

const form = useForm({
    name: user.value.name || "",
    phone: user.value.phone || "",
    address: user.value.address || "",
    job: user.value.job || "",
    dob: user.value.dob || "",
    gender: user.value.gender || "",
    cccd_number: user.value.cccd_number || "",
});

const provinces = ref([]);
const wards = ref([]);
const selectedProvinceCode = ref("");
const selectedWardCode = ref("");
const addressDetail = ref("");

const fetchProvinces = async () => {
    try {
        const res = await fetch("https://provinces.open-api.vn/api/v2/p/");
        provinces.value = await res.json();

        // Phân tách địa chỉ cũ để tự động map vào dropdown
        if (form.address) {
            const parts = form.address.split(",").map((s) => s.trim());
            if (parts.length >= 2) {
                const provName = parts[parts.length - 1];
                const matchedProv = provinces.value.find(
                    (p) => p.name === provName,
                );
                if (matchedProv) {
                    selectedProvinceCode.value = matchedProv.code;
                    await fetchWards(matchedProv.code);

                    const wardName = parts[parts.length - 2];
                    const matchedWard = wards.value.find(
                        (w) => w.name === wardName,
                    );
                    if (matchedWard) {
                        selectedWardCode.value = matchedWard.code;
                    }

                    addressDetail.value = parts
                        .slice(0, parts.length - 2)
                        .join(", ");
                } else {
                    addressDetail.value = form.address;
                }
            } else {
                addressDetail.value = form.address;
            }
        }
    } catch (e) {
        console.error("Lỗi tải danh sách Tỉnh/Thành:", e);
    }
};

const fetchWards = async (provinceCode) => {
    try {
        const res = await fetch(
            `https://provinces.open-api.vn/api/v2/p/${provinceCode}?depth=2`,
        );
        const data = await res.json();
        wards.value = data.wards || [];
    } catch (e) {
        console.error("Lỗi tải danh sách Phường/Xã:", e);
    }
};

const onProvinceChange = async () => {
    selectedWardCode.value = "";
    wards.value = [];
    if (selectedProvinceCode.value) {
        await fetchWards(selectedProvinceCode.value);
    }
    updateAddressField();
};

const updateAddressField = () => {
    const prov = provinces.value.find(
        (p) => p.code === selectedProvinceCode.value,
    );
    const ward = wards.value.find((w) => w.code === selectedWardCode.value);

    if (prov && ward) {
        form.address = `${addressDetail.value ? addressDetail.value + ", " : ""}${ward.name}, ${prov.name}`;
    } else if (prov) {
        form.address = `${addressDetail.value ? addressDetail.value + ", " : ""}${prov.name}`;
    } else {
        form.address = addressDetail.value;
    }
};

onMounted(() => {
    fetchProvinces();
});

const submit = () => {
    // 1. Nếu người dùng nhập CCCD nhưng chưa điền SĐT -> Cảnh báo yêu cầu nhập SĐT
    if (!form.phone) {
        form.setError(
            "phone",
            "Số điện thoại là bắt buộc. Vui lòng nhập Số điện thoại của bạn!",
        );
        return;
    }

    // 2. Nếu chưa điền CCCD -> Cảnh báo yêu cầu nhập CCCD
    if (!form.cccd_number) {
        form.setError(
            "cccd_number",
            "Số CCCD (12 chữ số) là bắt buộc. Vui lòng nhập CCCD của bạn!",
        );
        return;
    }

    form.post(route("tranguser.update"), {
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
                            <span>{{
                                new Date(user.created_at).toLocaleDateString(
                                    "vi-VN",
                                )
                            }}</span>
                        </div>
                    </div>
                    <div class="item_user2" :class="{
                        'is-renting':
                            $page.props.rentalStatus !== 'Chưa thuê trọ',
                    }">
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

                    <div v-if="$page.props.flash && $page.props.flash.success" class="text-green-600 mb-4 font-medium">
                        {{ $page.props.flash.success }}
                    </div>

                    <!-- Banner Cảnh báo Khóa thông tin cá nhân -->
                    <div v-if="isProfileLocked"
                        class="!p-4 bg-amber-50 border border-amber-200 rounded-2xl text-amber-900 text-xs font-bold flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5 shadow-xs">
                        <div class="flex items-start gap-2.5">
                            <i class="bi bi-lock-fill text-amber-600 text-lg shrink-0 mt-0.5"></i>
                            <div>
                                <p class="font-bold text-amber-900">
                                    Thông tin cá nhân của bạn đã được khóa (phục vụ tính pháp lý của hợp đồng thuê trọ).
                                </p>
                                <p v-if="user.profile_unlock_reason"
                                    class="text-[12px] text-amber-700 italic mt-1 font-semibold flex items-center gap-1">
                                    <i class="bi bi-hourglass-split text-amber-600"></i>
                                    Yêu cầu xin sửa thông tin đang chờ Admin duyệt:
                                    <span class="font-extrabold text-amber-900">"{{ user.profile_unlock_reason
                                        }}"</span>
                                </p>
                            </div>
                        </div>
                        <button v-if="!user.profile_unlock_reason" type="button" @click="showRequestModal = true"
                            class="!px-3.5 !py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl transition-all shadow-xs shrink-0 flex items-center gap-1.5 cursor-pointer self-stretch sm:self-auto justify-center">
                            <i class="bi bi-pencil-square"></i>
                            <span>Xin chỉnh sửa thông tin</span>
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và Tên:</label>
                                <input type="text" v-model="form.name" placeholder="Họ và Tên"
                                    :disabled="isProfileLocked" />
                                <span v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</span>
                            </div>

                            <div class="form-group">
                                <label>SĐT
                                    {{
                                        isProfileLocked || user.phone
                                            ? "(Không thể thay đổi)"
                                            : "(Chỉ được nhập 1 lần duy nhất)"
                                    }}:</label>
                                <input type="text" v-model="form.phone" :disabled="isProfileLocked" />
                                <span v-if="form.errors.phone" class="text-red-500 text-sm">{{ form.errors.phone
                                }}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group" style="margin-bottom: 20px">
                                <label>Tỉnh / Thành phố:</label>
                                <select v-model="selectedProvinceCode" @change="onProvinceChange"
                                    :disabled="isProfileLocked">
                                    <option value="">
                                        -- Chọn Tỉnh / Thành phố --
                                    </option>
                                    <option v-for="prov in provinces" :key="prov.code" :value="prov.code">
                                        {{ prov.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px">
                                <label>Phường / Xã / Thị trấn:</label>
                                <select v-model="selectedWardCode" @change="updateAddressField" :disabled="isProfileLocked || !selectedProvinceCode
                                    ">
                                    <option value="">
                                        -- Chọn Phường / Xã / Thị trấn --
                                    </option>
                                    <option v-for="w in wards" :key="w.code" :value="w.code">
                                        {{ w.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px">
                            <label>Thôn / Xóm / Số nhà / Đường:</label>
                            <input type="text" v-model="addressDetail" @input="updateAddressField"
                                placeholder="Nhập thôn, xóm, số nhà, tên đường..." :disabled="isProfileLocked" />
                            <span v-if="form.errors.address" class="text-red-500 text-sm">{{ form.errors.address
                            }}</span>
                        </div>

                        <!-- Trường nhập số CCCD 12 số của Khách thuê -->
                        <div class="form-group" style="margin-bottom: 20px">
                            <label>Số Căn cước công dân (CCCD - 12 chữ số)
                                {{
                                    isProfileLocked || hasCccd
                                        ? "(Không thể thay đổi)"
                                        : "(Chỉ được nhập 1 lần duy nhất)"
                                }}
                                <span class="text-red-500">*</span>:</label>
                            <input type="text" v-model="form.cccd_number" maxlength="12" :disabled="isProfileLocked" />
                            <span v-if="form.errors.cccd_number" class="text-red-500 text-sm">{{ form.errors.cccd_number
                            }}</span>
                        </div>
                        <div class="row">
                            <div class="form-group" style="margin-bottom: 20px">
                                <label>Nghề Nghiệp Hiện Tại:</label>
                                <input type="text" v-model="form.job" placeholder="Nghề nghiệp"
                                    :disabled="isProfileLocked" />
                                <span v-if="form.errors.job" class="text-red-500 text-sm">{{ form.errors.job }}</span>
                            </div>
                            <div class="form-group" style="margin-bottom: 20px">
                                <label>Ngày sinh:</label>
                                <input type="date" v-model="form.dob" :disabled="isProfileLocked" />
                                <span v-if="form.errors.dob" class="text-red-500 text-sm">{{ form.errors.dob }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select v-model="form.gender" :disabled="isProfileLocked">
                                <option value="">-- Chọn Giới Tính --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                            </select>
                            <span v-if="form.errors.gender" class="text-red-500 text-sm">{{ form.errors.gender }}</span>
                        </div>
                        <button v-if="!isProfileLocked" class="btn_save" type="submit" :disabled="form.processing">
                            Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal Xin mở khóa chỉnh sửa thông tin cá nhân -->
        <Teleport to="body">
            <div v-if="showRequestModal" class="unlock-modal-overlay" @click.self="showRequestModal = false">
                <div class="unlock-modal-box">
                    <div class="unlock-modal-header">
                        <h3 class="unlock-modal-title">
                            <i class="bi bi-shield-lock-fill" style="color: #d97706; font-size: 18px;"></i>
                            Xin mở khóa chỉnh sửa thông tin
                        </h3>
                        <button @click="showRequestModal = false" class="unlock-modal-close">&times;</button>
                    </div>
                    <p class="unlock-modal-desc">
                        Vui lòng nhập lý do cụ thể cần thay đổi thông tin (VD: Nhầm số CCCD, thay đổi địa chỉ sinh
                        sống...) để gửi tới Ban Quản Trị (Admin) xem xét phê duyệt:
                    </p>
                    <div style="margin-bottom: 12px;">
                        <textarea v-model="requestReason" rows="3"
                            placeholder="Nhập lý do chi tiết (tối thiểu 10 ký tự)..."
                            class="unlock-modal-textarea"></textarea>
                        <p class="unlock-modal-hint">
                            Admin sẽ nhận được thông báo và xem xét lý do của bạn.
                        </p>
                    </div>
                    <div class="unlock-modal-footer">
                        <button @click="showRequestModal = false" class="btn-modal-cancel">
                            Hủy
                        </button>
                        <button @click="submitUnlockRequest" :disabled="requestProcessing" class="btn-modal-submit">
                            <i class="bi bi-send-fill"></i>
                            <span>Gửi yêu cầu</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </UserLayout>
</template>

<style scoped>
@import "../../css/user.css";
@import "../../css/responsive/responsivetranguser.css";
</style>
