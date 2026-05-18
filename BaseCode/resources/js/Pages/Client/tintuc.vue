<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Tin Tức | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Tin Tức</h1>
                <p>
                <p><a href="index.html">Trang Chủ</a> / Tin Tức</p>
                </p>
            </div>
        </div>
        <!-- phần layout bao tin tức -->
        <div class="layout">
            <section class="left_section">
                <div class="search">
                    <div class="bao_search">
                        <input type="text" placeholder="Tìm kiếm...">
                        <button class="btn_search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <!-- danh mục -->
                <div class="category">
                    <div class="baodanhmuc">
                        <h2>Danh Mục Bài Viết</h2>
                        <div class="danhmuc">
                            <div class="left">
                                <i class="bi bi-folder"></i>
                                <span>Tin Tức</span>
                            </div>
                            <div class="soluong">(1)</div>
                        </div>
                        <div class="danhmuc">
                            <div class="left">
                                <i class="bi bi-folder"></i>
                                <span>Việc Làm</span>
                            </div>
                            <div class="soluong">(5)</div>
                        </div>
                    </div>
                </div>
                <!-- bài viết mới -->
                <div class="baivietmoi">
                    <div class="baonewtintuc">
                        <h2>Bài Viết Mới</h2>
                        <div class="baoinfor_tintuc">
                            <div class="infor_tintucnew">
                                <a href="#">
                                    Bài Viết Mới Nhất Bài Viết Mới Nhất
                                    Bài Viết Mới Nhất
                                </a>
                            </div>
                            <div class="infor_tintucnew">
                                <a href="#">
                                    Bài Viết Mới Nhất Bài Viết Mới Nhất
                                    Bài Viết Mới Nhất
                                </a>
                            </div>
                            <div class="infor_tintucnew">
                                <a href="#">
                                    Bài Viết Mới Nhất Bài Viết Mới Nhất
                                    Bài Viết Mới Nhất
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- tag -->
                <div class="tags">
                    <div class="bao_tag">
                        <h2>Tag</h2>
                        <div class="baotag_tintuc">
                            <div class="item_tag">
                                <a href="#">Việc Làm</a>
                            </div>
                            <div class="item_tag">
                                <a href="#">Tìm Trọ</a>
                            </div>
                            <div class="item_tag">
                                <a href="#">Giá Rẻ</a>
                            </div>
                            <div class="item_tag">
                                <a href="#">Sinh Viên</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="right_section">
                <div class="tintuc">
                    <div class="baotintuc">
                        <div class="image_tintuc">
                            <img src="/anh/banner_tro.png" alt="">
                        </div>
                        <div class="infor_tintuc">
                            <a class="title_tintuc" href="#">
                                <h2>Phòng trọ đẹp nhất</h2>
                            </a>
                            <div class="thongtin">
                                <div class="date">
                                    <i class="bi bi-calendar"></i>
                                    <span>28/03/2026</span>
                                </div>
                                <div class="category_tintuc">
                                    <i class="bi bi-folder"></i>
                                    <span>
                                        <a href="">
                                            Tin Tức
                                        </a>
                                    </span>
                                </div>
                                <div class="user_tintuc">
                                    <i class="bi bi-person"></i>
                                    <span><a href="">
                                            Admin
                                        </a></span>
                                </div>
                                <div class="comment_tintuc">
                                    <i class="bi bi-chat-dots"></i>
                                    <span>
                                        <a href="">
                                            80 Bình Luận
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="bio">
                                <div class="bio_tintuc">
                                    <p>hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ
                                        hẹ
                                        hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹ</p>
                                </div>
                                <a class="btn_tintuc" href="#">
                                    Xem Thêm
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="baotintuc">
                        <div class="image_tintuc">
                            <img src="/anh/banner_tro.png" alt="">
                        </div>
                        <div class="infor_tintuc">
                            <a class="title_tintuc" href="#">
                                <h2>Phòng trọ đẹp nhất</h2>
                            </a>
                            <div class="thongtin">
                                <div class="date">
                                    <i class="bi bi-calendar"></i>
                                    <span>28/03/2026</span>
                                </div>
                                <div class="category_tintuc">
                                    <i class="bi bi-folder"></i>
                                    <span>
                                        <a href="">
                                            Tin Tức
                                        </a>
                                    </span>
                                </div>
                                <div class="user_tintuc">
                                    <i class="bi bi-person"></i>
                                    <span><a href="">
                                            Admin
                                        </a></span>
                                </div>
                                <div class="comment_tintuc">
                                    <i class="bi bi-chat-dots"></i>
                                    <span>
                                        <a href="">
                                            80 Bình Luận
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="bio">
                                <div class="bio_tintuc">
                                    <p>hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ
                                        hẹ
                                        hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹ</p>
                                </div>
                                <a class="btn_tintuc" href="#">
                                    Xem Thêm
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="baotintuc">
                        <div class="image_tintuc">
                            <img src="/anh/banner_tro.png" alt="">
                        </div>
                        <div class="infor_tintuc">
                            <a class="title_tintuc" href="#">
                                <h2>Phòng trọ đẹp nhất</h2>
                            </a>
                            <div class="thongtin">
                                <div class="date">
                                    <i class="bi bi-calendar"></i>
                                    <span>28/03/2026</span>
                                </div>
                                <div class="category_tintuc">
                                    <i class="bi bi-folder"></i>
                                    <span>
                                        <a href="">
                                            Tin Tức
                                        </a>
                                    </span>
                                </div>
                                <div class="user_tintuc">
                                    <i class="bi bi-person"></i>
                                    <span><a href="">
                                            Admin
                                        </a></span>
                                </div>
                                <div class="comment_tintuc">
                                    <i class="bi bi-chat-dots"></i>
                                    <span>
                                        <a href="">
                                            80 Bình Luận
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="bio">
                                <div class="bio_tintuc">
                                    <p>hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ
                                        hẹ
                                        hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹ</p>
                                </div>
                                <a class="btn_tintuc" href="#">
                                    Xem Thêm
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="baotintuc">
                        <div class="image_tintuc">
                            <img src="/anh/banner_tro.png" alt="">
                        </div>
                        <div class="infor_tintuc">
                            <a class="title_tintuc" href="#">
                                <h2>Phòng trọ đẹp nhất</h2>
                            </a>
                            <div class="thongtin">
                                <div class="date">
                                    <i class="bi bi-calendar"></i>
                                    <span>28/03/2026</span>
                                </div>
                                <div class="category_tintuc">
                                    <i class="bi bi-folder"></i>
                                    <span>
                                        <a href="">
                                            Tin Tức
                                        </a>
                                    </span>
                                </div>
                                <div class="user_tintuc">
                                    <i class="bi bi-person"></i>
                                    <span><a href="">
                                            Admin
                                        </a></span>
                                </div>
                                <div class="comment_tintuc">
                                    <i class="bi bi-chat-dots"></i>
                                    <span>
                                        <a href="">
                                            80 Bình Luận
                                        </a>
                                    </span>
                                </div>
                            </div>
                            <div class="bio">
                                <div class="bio_tintuc">
                                    <p>hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ
                                        hẹ
                                        hẹhẹ hẹ hẹ hẹ hẹhẹ hẹ hẹ hẹ hẹ</p>
                                </div>
                                <a class="btn_tintuc" href="#">
                                    Xem Thêm
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="phantrang_joob">
            <div class="baophantrangjoob">
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
    </MainLayout>
</template>

<style scoped>
@import "../../css/tintuc.css";
@import '../../css/responsive/responsivetintuc.css';
@import '../../css/responsive/responsive.css';
</style>