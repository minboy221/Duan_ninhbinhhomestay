<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Tìm Phòng Trọ | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Trọ</h1>
                <p>
                <p><a href="index.html">Trang Chủ</a> / Tìm Trọ</p>
                </p>
            </div>
        </div>
        <!-- phần chia layout -->
        <div class="layout">
            <!-- phần bộ lọc tìm kiếm -->
            <section class="filter">
                <div class="baofilter">
                    <h2>Bộ Lọc Tìm Kiếm</h2>
                    <div class="select_box">
                        <div class="select">
                            <span class="selected"><i class="bi bi-geo-alt"></i> Địa điểm</span>
                            <span class="arrow"><i class="bi bi-caret-down"></i></span>
                        </div>

                        <div class="dropdown">
                            <div class="dropdown-header">
                                <span>Địa điểm:</span>
                            </div>

                            <ul>
                                <li class="active">Ninh Bình</li>
                                <li>Duy Tiên</li>
                                <li>Đồng Văn</li>
                            </ul>
                        </div>
                    </div>
                    <!-- khoảng giá -->
                    <div class="select_option">
                        <h3>Khoảng giá:</h3>
                        <div class="price_list">
                            <label><input type="radio" name="price"> Dưới 1 triệu</label>
                            <label><input type="radio" name="price"> 1 - 2 triệu</label>
                            <label><input type="radio" name="price"> 2 - 3 triệu</label>
                            <label><input type="radio" name="price"> Trên 3 triệu</label>
                        </div>
                    </div>
                    <!-- khoảng giá -->
                    <div class="select_option">
                        <h3>Diện Tích:</h3>
                        <div class="price_list">
                            <label><input type="radio" name="dientich">Dưới 20m<sup>2</sup></label>
                            <label><input type="radio" name="dientich">Dưới 20m<sup>2</sup></label>
                            <label><input type="radio" name="dientich">Dưới 20m<sup>2</sup></label>
                            <label><input type="radio" name="dientich">Dưới 20m<sup>2</sup></label>
                        </div>
                    </div>
                    <!-- tiện ích -->
                    <div class="select_option">
                        <h3>Tiện ích:</h3>
                        <div class="feature_list">
                            <label><input type="checkbox"> Wifi</label>
                            <label><input type="checkbox"> Điều hòa</label>
                            <label><input type="checkbox"> Gác xép</label>
                            <label><input type="checkbox"> Nóng lạnh</label>
                            <label><input type="checkbox"> Gần trung tâm</label>
                        </div>
                    </div>
                    <div class="bao_btn">
                        <button class="btn_filter">Tìm kiếm</button>
                        <button class="btn_filter_mic"><i class="bi bi-mic"></i>
                        </button>
                    </div>
                </div>
            </section>
            <!-- phần hiển thị phòng -->
            <section class="room">
                <div class="baoroom">
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                    <div class="item_room">
                        <img src="/anh/banner_tro.png" alt="">
                        <div class="infor_room">
                            <div class="title_room">
                                <h2>Cho thuê trọ</h2>
                            </div>
                            <div class="infor">
                                <p>2 triệu/tháng</p>
                                <p>20m<sup>2</sup>
                                </p>
                                <p><span><i class="bi bi-geo-alt"></i></span>Duy Tiên, Hà Nam</p>
                                <div class="about_room">
                                    <p>Phòng xịn hẹ hẹ hẹ hẹ.</p>
                                </div>
                            </div>
                            <div class="user_room">
                                <img src="/anh/banner.png" alt="">
                                <h4>Chủ trọ</h4>
                                <p>cập nhật 2 giờ trước</p>
                            </div>
                        </div>
                        <a class="btn" href="#">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
                <div class="phantrang">
                    <div class="baophantrang">
                        <div class="so_trang">
                            <a href="">
                                <p>1</p>
                            </a>
                        </div>
                        <div class="so_trang">
                            <a href="">
                                <p>2</p>
                            </a>
                        </div>
                        <div class="so_trang">
                            <a href="">
                                <p>3</p>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/timtro.css";
@import '../../css/responsive/responsivetimtro.css';
@import '../../css/responsive/responsive.css';
</style>