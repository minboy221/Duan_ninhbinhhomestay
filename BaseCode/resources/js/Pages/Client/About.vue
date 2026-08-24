<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Về Chúng Tôi | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Giới Thiệu</h1>
                <p><a href="index.html">Trang Chủ</a> / Giới Thiệu</p>
            </div>
        </div>
        <div class="baonoidung">
            <!-- phần nội dung -->
            <section class="hero">
                <div class="container_hero">
                    <!-- LEFT -->
                    <div class="left">
                        <img src="/anh/noidung.png" class="main-img" alt="travel">

                        <div class="badge badge-top">
                            <h2>1 Năm</h2>
                            <p>Kinh nghiệm</p>
                        </div>

                        <div class="circle-img">
                            <img src="/anh/noidung2.png" alt="traveler">
                        </div>

                        <div class="badge badge-bottom">
                            <h2>20+</h2>
                            <p>Địa Điểm</p>
                        </div>
                    </div>
                    <!-- RIGHT -->
                    <div class="right">
                        <span class="sub-title">Chúng Tôi Là Ninh Bình HomeStay</span>
                        <h1>Ninh Bình HomeStay là giải pháp hoàn hảo cho không gian sống của bạn.</h1>
                        <p class="desc">
                            Nền tảng chuyên biệt giúp bạn tìm kiếm phòng trọ ưng ý và hỗ trợ chủ trọ quản lý vận hành
                            thông
                            minh
                            –
                            tối ưu, an toàn và minh bạch.
                        </p>
                        <div class="features">
                            <ul>
                                <li>Hệ thống vị trí chi tiết</li>
                                <li>Đánh giá & Nhận xét thực tế</li>
                                <li>Thông tin phòng trọ xác thực</li>
                                <li>Hỗ trợ bằng giọng nói</li>
                            </ul>
                            <ul>
                                <li>Quản lý hóa đơn tự động</li>
                                <li>Thanh toán tiện lợi</li>
                                <li>Theo dõi trạng thái phòng</li>
                                <li>Kết nối chủ trọ uy tín</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            <!-- phần slogan -->
            <section class="slogan">
                <div class="baoslogan">
                    <img src="/anh/banner_tro.png" alt="">
                    <div class="vertical-text">HOMESTAY</div>
                    <div class="infor-left">
                        <span class="text_support">KHÁM PHÁ</span>
                        <h2>Câu Chuyện & Sứ Mệnh</h2>
                        <p>
                            Ninh Bình HomeStay được hình thành từ mong muốn giải quyết một vấn đề nan giải tại cố đô:
                            sự thiếu kết nối và minh bạch trong thị trường nhà trọ.
                            <br><br>
                            Chúng tôi hiểu rằng, tìm kiếm một nơi ở ổn định tại một vùng đất mới thường gây mất nhiều
                            thời
                            gian.
                            Nhiều người, từ sinh viên đến người lao động, đã từng phải đối mặt với thông tin phòng không
                            chính
                            xác,
                            giá cả mập mờ hoặc khó khăn trong việc thanh toán và trao đổi với chủ nhà.
                            <br><br>
                            Chính vì vậy, Ninh Bình HomeStay ra đời để trở thành một hệ sinh thái lưu trú toàn diện.
                            Nơi người thuê có thể tìm thấy không gian sống xác thực, và chủ trọ có thể quản lý vận hành
                            một cách chuyên nghiệp thông qua công nghệ quản lý tự động và thanh toán trực tuyến an toàn.
                            <br><br>
                        </p>
                    </div>
                </div>
            </section>
            <!-- phần tính năng -->
            <section class="why-us">
                <div class="container">
                    <div class="center-text">
                        <p class="sub">WHY US?</p>
                        <h2>Sự Độc Đáo Từ</h2>
                        <h3>Ninh Bình Stay</h3>
                    </div>

                    <div class="hex hex1">
                        <img src="/anh/anhabout_1.png">
                        <span>HỖ TRỢ ĐA ĐỐI TƯỢNG</span>
                    </div>


                    <div class="hex hex2">
                        <img src="/anh/anhabout_6.png">
                        <span>THÔNG TIN XÁC THỰC</span>
                    </div>

                    <div class="hex hex3">
                        <img src="/anh/anhabout_5.png">
                        <span>QUẢN LÝ HÓA ĐƠN TỰ ĐỘNG</span>
                    </div>

                    <div class="hex hex4">
                        <img src="/anh/anhabout_2.png">
                        <span>TRẢI NGHIỆM CÁ NHÂN HÓA</span>
                    </div>
                    <div class="hex hex5">
                        <img src="/anh/anhabout_4.png">
                        <span>THANH TOÁN AN TOÀN</span>
                    </div>
                    <div class="hex hex6">
                        <img src="/anh/anhabout_3.png">
                        <span>HỖ TRỢ ĐA DẠNG</span>
                    </div>
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/about.css";
@import '../../css/responsive/responsivegioithieu.css';
@import '../../css/responsive/responsive.css';
</style>