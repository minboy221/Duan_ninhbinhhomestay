<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Liên Hệ | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Liên Hệ</h1>
                <p>
                <p><a href="index.html">Trang Chủ</a> / Liên Hệ</p>
                </p>
            </div>
        </div>
        <!-- form liên hệ -->
        <section class="contact">
            <!-- phần hiển thị map -->
            <div class="map">
                <iframe
                    :src="$page.props.settings?.contact_map || 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2611.291724627434!2d105.93314109429076!3d20.603915192384463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf62d752dc67%3A0xd79f03899b4e83d8!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIEZQVCBQb2x5dGVjaG5pYyBjxqEgc-G7nyBIw6AgTmFt!5e1!3m2!1svi!2s!4v1774600950495!5m2!1svi!2s'"
                    width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="baolienhe">
                <div class="title">
                    <div class="title-sup">
                        Liên Hệ Chúng Tôi
                    </div>
                    <h2>Liên Hệ</h2>
                    <p>Chúng tôi cam kết sẽ phản hồi lại bạn trong thời gian sớm nhất có thể. Đội ngũ hỗ trợ của
                        chúng tôi luôn sẵn sàng để giúp đỡ bạn với mọi vấn đề liên quan đến dịch vụ của chúng
                        tôi.
                    </p>
                </div>
                <div class="lienhe">
                    <div class="thongtin">
                        <div class="item_contac">
                            <i class="bi bi-geo-alt"></i>
                            <div class="text_contac">
                                <h3>Địa Chỉ</h3>
                                <p>{{ $page.props.settings?.contact_address || 'Duy Tiên, Hà Nam' }}</p>
                            </div>
                        </div>
                        <div class="item_contac">
                            <i class="bi bi-telephone"></i>
                            <div class="text_contac">
                                <h3>Điện Thoại</h3>
                                <p>{{ $page.props.settings?.contact_phone || '(+84) 862931724' }}</p>
                            </div>
                        </div>
                        <div class="item_contac">
                            <i class="bi bi-envelope"></i>
                            <div class="text_contac">
                                <h3>Email</h3>
                                <p>{{ $page.props.settings?.contact_email || 'infor.ninhbinhstayword.gmail.com' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="form_contac">
                        <form action="">
                            <div class="user_contac">
                                <input type="text" placeholder="Họ Tên*">
                                <input type="text" placeholder="Email*">
                            </div>
                            <input type="text" placeholder="Chủ Đề">
                            <textarea cols="40" rows="10" maxlength="2000" aria-invalid="false" name="" id=""
                                placeholder="Nội Dung*"></textarea>
                            <div class="btn_submit">
                                <a href="#">Gửi Liên Hệ</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </MainLayout>
</template>

<style scoped>
@import "../../css/lienhe.css";
@import '../../css/responsive/responsivelienhe.css';
@import '../../css/responsive/responsive.css';
</style>