<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

</script>

<template>
    <Head title="Trang Quản Lý Nơi Ở | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio">
                    <h2>THÔNG TIN NƠI Ở</h2>
                    <div class="status">
                        <p>Hợp đồng hiệu lực</p>
                    </div>
                </div>
                <form action="">
                    <div class="row">
                        <div class="form-group">
                            <label>Tên phòng/Số phòng</label>
                            <input type="text" placeholder="Phòng số 1">
                        </div>

                        <div class="form-group">
                            <label> Họ tên chủ trọ:</label>
                            <input type="text" placeholder="Nhật Minh">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>SĐT chủ trọ:</label>
                        <input type="text" placeholder="08888888888">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ :</label>
                        <input type="text" placeholder="08888888888">
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Ngày bắt đầu hợp đồng:</label>
                            <input type="text" placeholder="01/01/2026">
                        </div>

                        <div class="form-group">
                            <label>Ngày kêt thúc dự kiến:</label>
                            <input type="text" placeholder="31/12/2026">
                        </div>
                    </div>
                </form>
                <div class="hopdong">
                    <h2>HỢP ĐỒNG THUÊ TRỌ</h2>
                    <button id="openPdf" class="btn-hopdong">Xem trực tiếp hợp đồng tại đây!</button>
                </div>
                <div class="history_thanhtoan">
                    <h2>LỊCH SỬ HOÁ ĐƠN</h2>
                    <Link :href="route('lichsuthanhtoan')" class="btn-hopdong">Xem trực tiếp lịch sử thanh toán</Link>
                </div>
            </div>
        </div>
        <div id="pdfModal" class="modal">
            <span class="close">&times;</span>
            <div class="modal-content">
                <iframe src="Lab 7.pdf"></iframe>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/qlynoio.css";
@import '../../css/responsive/responsiveqlytro.css';
@import '../../css/responsive/responsive.css';
@import '../../css/responsive/responsivetranguser.css';
</style>
