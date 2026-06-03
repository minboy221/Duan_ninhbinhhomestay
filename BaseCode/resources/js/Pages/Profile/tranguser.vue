<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const { props } = usePage();
const user = computed(() => props.auth.user);

const form = useForm({
    name: user.value.name || '',
    phone: user.value.phone || '',
    address: user.value.address || '',
    job: user.value.job || '',
    dob: user.value.dob || '',
    gender: user.value.gender || '',
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
                    <div v-if="$page.props.flash && $page.props.flash.success" class="text-green-600 mb-4 font-medium">
                        {{ $page.props.flash.success }}
                    </div>
                    <form @submit.prevent="submit">
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và Tên:</label>
                                <input type="text" v-model="form.name" placeholder="Họ và Tên">
                                <span v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</span>
                            </div>

                            <div class="form-group">
                                <label>SĐT:</label>
                                <input type="text" v-model="form.phone" placeholder="Số điện thoại">
                                <span v-if="form.errors.phone" class="text-red-500 text-sm">{{ form.errors.phone }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Địa Chỉ Thường Trú:</label>
                            <input type="text" v-model="form.address" placeholder="Địa chỉ">
                            <span v-if="form.errors.address" class="text-red-500 text-sm">{{ form.errors.address }}</span>
                        </div>

                        <div class="form-group">
                            <label>Nghề Nghiệp Hiện Tại:</label>
                            <input type="text" v-model="form.job" placeholder="Nghề nghiệp">
                            <span v-if="form.errors.job" class="text-red-500 text-sm">{{ form.errors.job }}</span>
                        </div>

                        <div class="form-group">
                            <label>Ngày sinh:</label>
                            <input type="date" v-model="form.dob">
                            <span v-if="form.errors.dob" class="text-red-500 text-sm">{{ form.errors.dob }}</span>
                        </div>
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select v-model="form.gender">
                                <option value="">-- Chọn Giới Tính --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                            <span v-if="form.errors.gender" class="text-red-500 text-sm">{{ form.errors.gender }}</span>
                        </div>
                        <button class="btn_save" type="submit" :disabled="form.processing">
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

</style>