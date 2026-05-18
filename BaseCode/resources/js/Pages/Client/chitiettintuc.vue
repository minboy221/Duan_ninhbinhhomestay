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
                <a href="#">Tin Tức</a> /
                <a href="#">Nội dung của tin tức</a>
            </div>
        </div>
        <!-- phần layout bao chi tiết tin tức -->
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
                <div class="chitiettintuc">
                    <div class="baoitem_tintuc">
                        <div class="title_tintuc">
                            <h2>Phòng Trọ Đẹp Nhất</h2>
                        </div>
                        <div class="image_tintuc">
                            <img src="anh/banner_tro.png" alt="">
                        </div>
                        <div class="thongtin">
                            <div class="date">
                                <i class="bi bi-calendar"></i>
                                <span>28/03/2026</span>
                            </div>
                            <div class="user_tintuc">
                                <i class="bi bi-person"></i>
                                <span><a href="">
                                        Admin
                                    </a></span>
                            </div>
                        </div>
                        <div class="baiviet_tintuc">
                            <p>Nulla porttitor accumsan tincidunt. Curabitur aliquet quam id dui posuere blandit.
                                Vestibulum
                                ac diam sit amet for a quam vehicula elementum sed sit amet dui. Donec sollicitudin
                                molestie
                                malesuada. Donec sollicitudin molestie malesuada. Proin eget tortor risus. Quisque velit
                                nisi, pretium ut lacinia in, elementum id enim. Pellentesque in ipsum id orci porta
                                dapibus.
                                Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Lorem ipsum dolor
                                sit
                                amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut lacinia in, elementum
                                id
                                enim. Pellentesque in ipsum id orci porta dapibus. Vestibulum ac diam sit amet quam
                                vehicula
                                elementum sed sit amet dui. Lorem ipsum dolor sit amet, consectetur adipiscing ipsum
                                dolor
                                sit amet, consectetur elit.
                                <br>
                                Vestibulum ac diam sit amet for a quam vehicula elementum sed sit amet dui. Donec
                                sollicitudin molestie malesuada. Donec sollicitudin molestie malesuada. Proin eget
                                tortor
                                risus. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Pellentesque in
                                ipsum
                                id orci porta dapibus. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet
                                dui.
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut
                                lacinia in, elementum id enim. Quisque velit nisi, pretium ut lacinia in, elementum id
                                enim.
                                <br>
                                Lorem ipsum dolor sit amet consectetur.
                                Vestibulum ac diam sit amet for a quam vehicula elementum sed sit amet dui. Donec
                                sollicitudin molestie malesuada. Donec sollicitudin molestie malesuada. Proin eget
                                tortor
                                risus. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Pellentesque in
                                ipsum
                                id orci porta dapibus. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet
                                dui.
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque velit nisi, pretium ut
                                lacinia in, elementum id enim. Quisque velit nisi, pretium ut lacinia in, elementum id
                                enim.
                                Vestibulum ac diam sit amet for a quam vehicula elementum sed sit amet dui. Donec
                                sollicitudin molestie malesuada. Donec sollicitudin molestie malesuada. Proin eget
                                tortor
                                risus. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Pellentesque in
                                ipsum
                                id orci porta dapibus.
                            </p>
                            <div class="hastag">
                                <div class="baohastag">
                                    <h3>Từ Khoá:</h3>
                                    <div class="item_hastag">
                                        <a href="#">#Timtro</a>
                                        <a href="#">#Timtro</a>
                                        <a href="#">#Timtro</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettintuc.css";
@import '../../css/responsive/responsivechitiettintuc.css';
@import '../../css/responsive/responsive.css';
</style>