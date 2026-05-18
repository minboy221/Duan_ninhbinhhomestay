<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import HomePopup from '@/Components/HomePopup.vue';

</script>
<template>

    <Head title="Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tìm Phòng Và Nhà Trọ Phù Hợp</h1>
                <p>
                    Hệ thống tìm kiếm và quản lý phòng trọ thông minh số 1 tại Ninh Bình.
                </p>
            </div>
        </div>
        <!-- phần tìm kiếm -->
        <div class="boloc">
            <div class="search">
                <div class="location">
                    <label for="">Địa Chỉ:</label>
                    <input type="text" placeholder="Nhập nơi bạn muốn tìm?">
                </div>
                <div class="price_select">
                    <label for="">Chọn Mức Giá:</label>
                    <select name="" id="">
                        <option value="1">--Chọn Giá--</option>
                    </select>
                </div>
                <div class="roomtype_select">
                    <label for="">Chọn Loại Phòng:</label>
                    <select name="" id="">
                        <option value="1">--Chọn Loại Phòng--</option>
                    </select>
                </div>
                <button class="login-btn"><i class="bi bi-search"></i> <span>Tìm Kiếm</span></button>
            </div>
        </div>
        <section class="section_noidung">
            <div class="image_noidung">
                <img src="/anh/image_noidung.png" alt="">
            </div>
            <div class="noidung">
                <div class="text_support">
                    <p>TÌM HIỂU</p>
                </div>
                <div class="title">
                    <h2>Ninh Bình <span>HomeStay.</span></h2>
                </div>
                <p>Ninh Bình HomeStay là nền tảng chuyên biệt giúp bạn tìm kiếm và quản lý nhà trọ một cách minh bạch,
                    an
                    toàn tại Ninh Bình. chúng tôi kết nối người thuê với hệ thống chủ trọ uy tín, đồng thời cung cấp
                    giải
                    pháp quản lý vận hành thông minh cho các hộ kinh doanh lưu trú.
                    <br>
                    Với giao diện thân thiện, công cụ tìm kiếm thông minh và thông tin được cập nhật liên tục, Ninh Bình
                    HomeStay giúp bạn dễ dàng tìm được nơi ở phù hợp trong thời gian ngắn nhất.
                </p>
                <button class="btn"> Xem Thêm <i class="bi bi-arrow-right"></i></button>

            </div>
        </section>
        <section class="phongtro">
            <div class="pt-header">
                <p class="text_support">KHÁM PHÁ</p>
                <h2>Phòng Trọ <span>Nổi Bật</span></h2>
            </div>

            <!-- Slider fullscreen background -->
            <div class="pt-slider" id="ptSlider">

                <!-- Slide 1 -->
                <div class="pt-slide active" style="background-image: url('anh/phong1.jpg')">
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Nổi Bật</span>
                        <h3 class="pt-name">Phòng số 1</h3>
                        <p class="pt-addr"><i class="bi bi-geo-alt-fill"></i> Duy Tiên, Ninh Bình</p>
                        <div class="pt-meta">
                            <span class="pt-price">1.500.000 <small>/Tháng</small></span>
                            <span class="pt-area"><i class="bi bi-aspect-ratio"></i> 20m²</span>
                        </div>
                        <a class="pt-btn" href="#">Xem Chi Tiết <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="pt-slide" style="background-image: url('anh/phong1.jpg')">
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Hot</span>
                        <h3 class="pt-name">Phòng số 2</h3>
                        <p class="pt-addr"><i class="bi bi-geo-alt-fill"></i> Hoa Lư, Ninh Bình</p>
                        <div class="pt-meta">
                            <span class="pt-price">2.000.000 <small>/Tháng</small></span>
                            <span class="pt-area"><i class="bi bi-aspect-ratio"></i> 25m²</span>
                        </div>
                        <a class="pt-btn" href="#">Xem Chi Tiết <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="pt-slide" style="background-image: url('anh/phong1.jpg')">
                    <div class="pt-overlay"></div>
                    <div class="pt-info">
                        <span class="pt-badge">Mới</span>
                        <h3 class="pt-name">Phòng số 3</h3>
                        <p class="pt-addr"><i class="bi bi-geo-alt-fill"></i> Gia Viễn, Ninh Bình</p>
                        <div class="pt-meta">
                            <span class="pt-price">1.800.000 <small>/Tháng</small></span>
                            <span class="pt-area"><i class="bi bi-aspect-ratio"></i> 22m²</span>
                        </div>
                        <a class="pt-btn" href="#">Xem Chi Tiết <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Nút điều hướng -->
                <button class="pt-nav pt-prev" id="ptPrev"><i class="bi bi-chevron-left"></i></button>
                <button class="pt-nav pt-next" id="ptNext"><i class="bi bi-chevron-right"></i></button>

                <!-- Dots -->
                <div class="pt-dots" id="ptDots">
                    <span class="pt-dot active" data-index="0"></span>
                    <span class="pt-dot" data-index="1"></span>
                    <span class="pt-dot" data-index="2"></span>
                </div>
            </div>
        </section>

        <!-- phần cam kết -->
        <section class="camket">
            <div class="baocamket">
                <div class="about_camket">
                    <div class="text_support">
                        <p>CAM KẾT</p>
                    </div>
                    <div class="title">
                        <h2>Ninh Bình Home Stay <span>Cam Kết.</span></h2>
                    </div>
                    <div class="timeline">
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconhome.png" alt="">
                            </div>
                            <div class="content">
                                <h2>Thông Tin Minh Bạch</h2>
                                <p>Tất cả thông tin nhà trọ và việc làm đều được đăng tải rõ ràng, giúp người dùng dễ
                                    dàng
                                    tìm
                                    hiểu và lựa chọn.</p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconsearch.png" alt="">
                            </div>
                            <div class="content">
                                <h2>Tìm Kiếm Nhanh Chóng</h2>
                                <p>Hệ thống tìm kiếm thông minh giúp bạn nhanh chóng tìm được phòng trọ phù hợp
                                    với mọi nhu cầu.</p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconhandshake.png" alt="">
                            </div>
                            <div class="content">
                                <h2>Kết Nối Uy Tín</h2>
                                <p>Nền tảng kết nối minh bạch giữa người tìm trọ và chủ cho thuê. Hệ thống quản lý thông
                                    minh giúp tối ưu hóa việc tìm kiếm nơi ở và vận hành nhà trọ chuyên nghiệp.</p>
                            </div>
                        </div>
                        <div class="item">
                            <div class="icon">
                                <img src="anh/iconstar.png" alt="">
                            </div>
                            <div class="content">
                                <h2>Trải Nghiệm Tiện Lợi</h2>
                                <p>Giao diện thân thiện, dễ sử dụng giúp người dùng tìm kiếm và tiếp cận thông tin một
                                    cách
                                    nhanh chóng.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="img_camket">
                    <img src="anh/camket.png" alt="">
                </div>
                <div class="house_3d">
                    <img src="anh/3d-house.png" alt="">
                </div>
            </div>
        </section>
        <!-- phần thông số người dùng -->
        <section class="thongso">
            <div class="baothongso">
                <div class="infor_thongso">
                    <div class="item_thongso">
                        <h2>100+</h2>
                        <p>Người Đã Tìm Được Phòng Ưng Ý</p>
                    </div>
                    <div class="item_thongso">
                        <h2>100+</h2>
                        <p>Chủ trọ uy tín và chuyên nghiệp</p>
                    </div>
                    <div class="item_thongso">
                        <h2>4.9+</h2>
                        <p>Điếm Đánh Giá Từ Người Dùng</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- phần đánh giá -->
        <section class="review">
            <div class="title">
                <p class="sub">Đánh Giá Dịch Vụ</p>
                <h2>ĐÁNH GIÁ KHÁCH HÀNG</h2>
                <p class="desc">Chúng tôi tự hào khi nhận được phản hồi tích cực từ khách hàng</p>
            </div>

            <div class="review-container">
                <div class="review-track">

                    <!-- card -->
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ chuyên nghiệp.</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Rất tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>Trải nghiệm tuyệt vời! Tôi rất hài lòng với dịch vụ chuyên nghiệp.</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>Phúc Phúc</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Tuyệt vời</h3>
                        <div class="stars">★★★★★</div>
                        <p>Dịch vụ chu đáo, tư vấn tận tâm. Tôi sẽ quay lại!</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>Lê Hào</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>Trải nghiệm ổn định, giao diện dễ sử dụng.</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>An An</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>Trải nghiệm ổn định, giao diện dễ sử dụng.</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>An An</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>Tốt</h3>
                        <div class="stars">★★★★★</div>
                        <p>Trải nghiệm ổn định, giao diện dễ sử dụng.</p>
                        <div class="user">
                            <img src="anh/banner.png">
                            <div>
                                <b>An An</b>
                                <span>Khách hàng</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- nút -->
            <div class="btns">
                <button id="prev">❮</button>
                <button id="next">❯</button>
            </div>
        </section>
        <HomePopup />
    </MainLayout>
</template>