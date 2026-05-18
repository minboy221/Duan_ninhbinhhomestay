<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';

</script>

<template>

    <Head title="Xem Chi Tiết Phòng | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- điều hướng -->
        <div class="dieuhuong">
            <div class="baodieuhuong">
                <a href="#">Trang Chủ</a> /
                <a href="#">Tìm Phòng Trọ</a> /
                <a href="#">Xem Chi Tiết Phòng</a>
            </div>
        </div>
        <!-- phần chi tiết phòng -->
        <div class="layout">
            <section class="room_detail">
                <div class="container">
                    <div class="gallery">
                        <!-- Ảnh lớn -->
                        <div class="main-image">
                            <button class="prev">&#10094;</button>
                            <img id="currentImage" src="anh/banner.png">
                            <button class="next">&#10095;</button>
                        </div>

                        <!-- Thumbnail -->
                        <div class="thumbnails">
                            <img src="anh/banner.png" class="thumb active">
                            <img src="anh/banner.png" class="thumb">
                            <img src="anh/banner.png" class="thumb">
                            <img src="anh/banner.png" class="thumb">
                            <img src="anh/banner_tro.png" class="thumb">
                        </div>
                    </div>
                    <div class="detail_tro">
                        <div class="theloai">
                            <i class="bi bi-award"></i>
                            <span>ĐÃ ĐƯỢC KIỂM CHỨNG</span>
                        </div>
                        <div class="tieude">
                            <h2>Phòng trọ xịn</h2>
                        </div>
                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>Địa điểm: Duy Tiên, Ninh Bình</span>
                        </div>
                        <div class="infor_tro">
                            <div class="price_tro">
                                <p>1.5 Triệu/Tháng</p>
                            </div>
                            <div class="trangthai">
                                <p>Cập nhật: 1 ngày trước</p>
                            </div>
                        </div>
                        <div class="thongtin_tro">
                            <h4>Thông tin mô tả:</h4>
                            <p>Cho thuê phòng trọ mới xây dạng chung cư mini cao cấp</p>
                            <div class="tienich">
                                <div class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Đầy đủ nội thất</span>
                                </div>
                                <div class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Có kệ bếp</span>
                                </div>
                                <div class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Có chỗ để xe</span>
                                </div>
                                <div class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Không chung chủ</span>
                                </div>
                                <div class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Wifi</span>
                                </div>
                            </div>
                            <div class="bando">
                                <h2>Vị trí & Bản Đồ</h2>
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2611.291724627434!2d105.93314109429076!3d20.603915192384463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf62d752dc67%3A0xd79f03899b4e83d8!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIEZQVCBQb2x5dGVjaG5pYyBjxqEgc-G7nyBIw6AgTmFt!5e1!3m2!1svi!2s!4v1774600950495!5m2!1svi!2s"
                                    width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="info_chutro">
                <div class="baochutro">
                    <div class="avatar1">
                        <div class="avatar-img">
                            <img src="anh/banner_tro.png" alt="">
                            <span class="status1 online"></span>
                        </div>
                        <div class="name_chutro">
                            <h3>Nhật Minh</h3>
                            <div class="kiem_chung">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Đã Được Xác Thực</span>
                            </div>
                        </div>
                    </div>
                    <div class="content_chutro">
                        <div class="phone">
                            <a class="btn_content" href="">
                                <i class="bi bi-telephone"></i>
                                <span>0862931722</span>
                            </a>
                        </div>
                        <div class="nhantin_chutro">
                            <a class="btn_mess" href="">
                                <i class="bi bi-chat-dots-fill"></i>
                                <span>Nhắn Tin</span>
                            </a>
                        </div>
                        <div class="warning">
                            <a class="btn_waring" href="">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Báo Xấu</span>
                            </a>
                        </div>
                    </div>
                    <div class="luu_y">
                        <h5>Lưu ý an toàn</h5>
                        <div class="warning_content">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Không đặt cọc nếu chưa xem phòng</span>
                        </div>
                        <div class="success_content">
                            <i class="bi bi-check-circle"></i>
                            <span>Kiểm tra giấy tờ chính chủ</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- phần bài đăng liên quan -->
        <section class="tindang_tro">
            <h2>Tin đăng tương tự</h2>

            <div class="bao_tindang">

                <div class="item_tindang">
                    <div class="img">
                        <img src="anh/banner.png">
                        <span class="count"><i class="bi bi-camera"></i> 6</span>
                    </div>
                    <div class="content">
                        <h3>Căn hộ mini full nội thất gần ĐH Sư Phạm Kỹ Thuật</h3>
                        <p class="dientich">Diện tích: 10 m²</p>
                        <p class="diachi"><i class="bi bi-geo-alt"></i> Phủ Vân, Ninh Bình</p>
                    </div>
                </div>

                <div class="item_tindang">
                    <div class="img">
                        <img src="anh/banner.png">
                        <span class="count"><i class="bi bi-camera"></i> 4</span>
                    </div>
                    <div class="content">
                        <h3>Phòng trọ gác lửng đường Hoàng Diệu 2, an ninh tốt</h3>
                        <p class="dientich">Diện tích: 30 m²</p>
                        <p class="diachi"><i class="bi bi-geo-alt"></i> Phủ Vân, Ninh Bình</p>
                    </div>
                </div>

                <div class="item_tindang">
                    <div class="img">
                        <img src="anh/banner_tro.png">
                        <span class="count"><i class="bi bi-camera"></i> 3</span>
                    </div>
                    <div class="content">
                        <h3>Tìm nam ở ghép chung cư Moonlight, tiện ích hồ bơi</h3>
                        <p class="dientich">Diện tích: 30 m²</p>
                        <p class="diachi"><i class="bi bi-geo-alt"></i> Phủ Vân, Ninh Bình</p>
                    </div>
                </div>
            </div>
        </section>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettro.css";
@import '../../css/responsive/responsivechitiettro.css';
@import '../../css/responsive/responsive.css';
</style>