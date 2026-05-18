<script setup>
import UserLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

</script>

<template>

    <Head title="Lịch Sử Thanh Toán | Ninh Bình HomeStay" />

    <div class="glass-background-zones">
        <div class="zone zone-1"></div>
        <div class="zone zone-2"></div>
        <div class="zone zone-3"></div>
        <div class="zone zone-4"></div>
        <div class="zone zone-5"></div>
        <div class="zone zone-6"></div>
        <div class="zone zone-7"></div>
        <div class="zone zone-8"></div>
    </div>
    <section class="lichsuhoadon">
        <Link :href="route('quanlynoio')" class="back">
            <i class="bi bi-arrow-left"></i>
            <span>Quay lại</span>
        </Link>
        <div class="baohoadon">
            <div class="title_hoadon">
                <h2>Trang quản lý hoá đơn trọ</h2>
                <p>Nơi chứa lịch sử giao dịch hàng tháng của khách hàng.</p>
            </div>
            <div class="table_hoadon">
                <table>
                    <thead>
                        <tr>
                            <th>Mã hoá đơn</th>
                            <th>Tháng thanh toán</th>
                            <th>Tổng số tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="Mã hoá đơn">#0002</td>
                            <td data-label="Tháng thanh toán">30/04/2026</td>
                            <td data-label="Tổng số tiền">
                                <div class="price">3.500.000đ</div>
                            </td>
                            <td data-label="Trạng thái">
                                <div class="trangthai warning">
                                    Chưa Nhận
                                </div>
                            </td>
                            <td data-label="Thao tác">
                                <div class="thaotac">
                                    <button class="xemchitiet">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Xem chi tiết</span>
                                    </button>
                                    <button class="baocao">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Báo cáo</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td data-label="Mã hoá đơn">#0001</td>
                            <td data-label="Tháng thanh toán">30/04/2026</td>
                            <td data-label="Tổng số tiền">
                                <div class="price">3.500.000đ</div>
                            </td>
                            <td data-label="Trạng thái">
                                <div class="trangthai success">
                                    Đã nhận
                                </div>
                            </td>
                            <td data-label="Thao tác">
                                <div class="thaotac">
                                    <button class="xemchitiet">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Xem chi tiết</span>
                                    </button>
                                    <button class="baocao">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Báo cáo</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <!-- phần form báo cáo popup -->
    <div id="reportModal" class="modal">
        <div class="modal-box small">
            <span class="close">&times;</span>
            <h3>Báo cáo hoá đơn</h3>

            <textarea placeholder="Nhập lý do báo cáo..."></textarea>

            <button class="btn-submit">Gửi báo cáo</button>
        </div>
    </div>
    <!-- phần form xem chi tiết popup -->
    <div id="detailModal" class="modal">
        <div class="modal-box">
            <span class="close">&times;</span>
            <h3>Chi tiết hoá đơn</h3>

            <div class="cthd">
                <p><strong>Mã:</strong> #0001</p>
                <p><strong>Tháng:</strong> 04/2026</p>
                <p><strong>Tiền phòng:</strong> 3.000.000đ</p>
                <p><strong>Điện:</strong> 300.000đ</p>
                <p><strong>Nước:</strong> 200.000đ</p>
                <p><strong>Tổng:</strong> 3.500.000đ</p>
            </div>

            <!-- HÌNH ẢNH SỐ ĐIỆN NƯỚC -->
            <div class="meter-images">
                <div class="meter-item">
                    <p><i class="bi bi-lightning-charge-fill"></i> Ảnh số điện</p>
                    <div class="img-wrapper">
                        <img src="/anh/sodien.png" alt="Ảnh số điện" onerror="this.src='/anh/phong1.jpg'">
                    </div>
                </div>
                <div class="meter-item">
                    <p><i class="bi bi-droplet-fill"></i> Ảnh số nước</p>
                    <div class="img-wrapper">
                        <img src="/anh/sonuoc.png" alt="Ảnh số nước" onerror="this.src='/anh/phong1.jpg'">
                    </div>
                </div>
            </div>

            <!-- THANH TOÁN -->
            <div class="thanhtoan">
                <h4>Chọn phương thức thanh toán</h4>

                <div class="payment-options">
                    <div class="payment-item" data-type="qr">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>QR Code</span>
                    </div>
                    <div class="payment-item" data-type="cash">
                        <i class="bi bi-cash-coin"></i>
                        <span>Tiền mặt</span>
                    </div>
                </div>

                <!-- QR -->
                <div class="qr-box" id="qrBox">
                    <p>Quét mã để thanh toán</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=ThanhToan3500000">
                </div>

                <button class="btn-pay">Xác nhận thanh toán</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import "../../css/lichsuthanhtoan.css";
@import '../../css/responsive/responsivehoadon.css';
@import '../../css/responsive/responsive.css';
</style>
