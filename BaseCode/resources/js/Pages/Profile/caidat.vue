<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { props } = usePage();
const user = computed(() => props.auth.user);
</script>

<template>

    <Head title="Cài Đặt Tài Khoản | Ninh Bình HomeStay" />
    <UserLayout>
        <!--phần hiển thị phần cài đặt -->
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio">
                    <h2>CÀI ĐẶT TÀI KHOẢN</h2>
                    <p>Quản lý bảo mật,thông báo và quyền riêng tư của bạn.</p>
                </div>
                <div class="password-form">
                    <h4>Đổi nhật mật khẩu</h4>
                    <form action="">
                        <div class="row">
                            <div class="form-group">
                                <label>Mật khẩu hiện tại:</label>
                                <input type="text" placeholder="Đào Nhật Minh">
                            </div>

                            <div class="form-group">
                                <label>Mật khẩu mới:</label>
                                <input type="text" placeholder="098xxxxxxx">
                            </div>
                        </div>
                        <button class="btn_save" type="submit">
                            Cập nhật mật khẩu
                        </button>
                    </form>
                </div>
                <!-- phần upload ảnh cccd -->
                <div class="upload_cccd">
                    <h3>Xác thực thông tin (CCCD)</h3>
                    <p class="upload-desc">Vui lòng tải lên ảnh rõ nét của hai mặt thẻ Căn cước công dân để xác
                        thực
                        tài khoản.</p>

                    <div class="cccd-grid">
                        <!-- MẶT TRƯỚC -->
                        <div class="cccd-item">
                            <div class="cccd-card">
                                <div class="card-header">
                                    <p>Mặt trước CCCD</p>
                                </div>
                                <div class="upload-container" id="containerFront">
                                    <input type="file" id="cccdFront" accept="image/*" hidden>
                                    <label for="cccdFront" class="upload-label">
                                        <div class="upload-icon-box">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="main-text">Tải ảnh mặt trước</span>
                                            <span class="sub-text">Hỗ trợ JPG, PNG, WEBP</span>
                                        </div>
                                    </label>
                                    <div class="preview-box" id="previewBoxFront">
                                        <img id="previewFront" src="" alt="Mặt trước CCCD">
                                        <div class="preview-actions">
                                            <button type="button" class="btn-remove" data-target="cccdFront"
                                                title="Xóa ảnh">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <label for="cccdFront" class="btn-change" title="Đổi ảnh">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- MẶT SAU -->
                        <div class="cccd-item">
                            <div class="cccd-card">
                                <div class="card-header">
                                    <p>Mặt sau CCCD</p>
                                </div>
                                <div class="upload-container" id="containerBack">
                                    <input type="file" id="cccdBack" accept="image/*" hidden>
                                    <label for="cccdBack" class="upload-label">
                                        <div class="upload-icon-box">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="main-text">Tải ảnh mặt sau</span>
                                            <span class="sub-text">Hỗ trợ JPG, PNG, WEBP</span>
                                        </div>
                                    </label>
                                    <div class="preview-box" id="previewBoxBack">
                                        <img id="previewBack" src="" alt="Mặt sau CCCD">
                                        <div class="preview-actions">
                                            <button type="button" class="btn-remove" data-target="cccdBack"
                                                title="Xóa ảnh">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <label for="cccdBack" class="btn-change" title="Đổi ảnh">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn_save" type="submit">
                        Lưu CCCD
                    </button>
                </div>
            </div>
            <!-- phần xem thiết bị đã đăng nhập -->
            <div class="bao_thietbi">
                <h4>Thiết bị đã đăng nhập</h4>
                <div class="thietbi_item">
                    <i class="bi bi-phone"></i>
                    <div class="infor_thietbi">
                        <p>Iphone 15 Pro Max - Ninh Bình</p>
                        <span>2 ngày trước / APP</span>
                    </div>
                </div>
            </div>
            <!-- phần xoá tài khoản -->
            <div class="bao_delete">
                <div class="title_delete">
                    <span><i class="bi bi-x-circle-fill"></i></span>
                    <h4>Xoá tài khoản</h4>
                </div>
                <div class="item_delete">
                    <div class="infor-delete">
                        <h2>Khu vực nguy hiểm</h2>
                        <p>Hành động này không thể hoàn tác. Vui lòng cân nhắc kỹ trước khi xoá</p>
                    </div>
                    <button class="delete_btn">
                        Xoá tài khoản
                    </button>
                </div>
            </div>
        </div>
    </UserLayout>
</template>
<style scoped>
@import "../../css/caidat.css";
@import '../../css/responsive/responsivetranguser.css';
</style>