<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { props } = usePage();
const user = computed(() => props.auth.user);
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
                            <span>Đang thuê</span>
                        </div>
                    </div>
                    <div class="item_user3">
                        <div class="infor_tongquan">
                            <p>Trạng thái tài khoản</p>
                            <span>Người thuê uy tín</span>
                        </div>
                    </div>
                </div>
                <div class="noidung_taikhoan">
                    <h2>Thông tin tài khoản</h2>
                    <form>
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và Tên:</label>
                                <input type="text" :value="user.name" placeholder="Họ và Tên">
                            </div>

                            <div class="form-group">
                                <label>SĐT:</label>
                                <input type="text" :value="user.phone || ''" placeholder="Số điện thoại">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Địa Chỉ Thường Trú:</label>
                            <input type="text" placeholder="Địa chỉ">
                        </div>

                        <div class="form-group">
                            <label>Nghề Nghiệp Hiện Tại:</label>
                            <input type="text" placeholder="Nghề nghiệp">
                        </div>

                        <div class="form-group">
                            <label>Ngày sinh:</label>
                            <input type="date">
                        </div>
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select name="" id="">
                                <option value="">-- Chọn Giới Tính --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                            </select>
                        </div>
                        <button class="btn_save" type="submit">
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