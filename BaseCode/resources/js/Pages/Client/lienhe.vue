<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

const isSubmitted = ref(false);

const submit = () => {
    form.post(route('client.contact.store'), {
        onSuccess: () => {
            form.reset();
            isSubmitted.value = true;
            setTimeout(() => {
                isSubmitted.value = false;
            }, 6000);
        }
    });
};
</script>

<template>
    <Head title="Liên Hệ | Ninh Bình HomeStay" />
    <MainLayout>
        <!-- BANNER -->
        <div class="banner">
            <img src="/anh/banner.png" alt="banner">
            <div class="banner-text">
                <h1>Liên Hệ</h1>
                <p><a href="/">Trang Chủ</a> / Liên Hệ</p>
            </div>
        </div>
        <!-- form liên hệ -->
        <section class="contact">
            <!-- phần hiển thị map -->
            <div class="map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2611.291724627434!2d105.93314109429076!3d20.603915192384463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf62d752dc67%3A0xd79f03899b4e83d8!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIEZQVCBQb2x5dGVjaG5pYyBjxqEgc-G7nyBIw6AgTmFt!5e1!3m2!1svi!2s!4v1774600950495!5m2!1svi!2s"
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
                                <p>Duy Tiên, Hà Nam</p>
                            </div>
                        </div>
                        <div class="item_contac">
                            <i class="bi bi-telephone"></i>
                            <div class="text_contac">
                                <h3>Điện Thoại</h3>
                                <p>(+84) 862931724</p>
                            </div>
                        </div>
                        <div class="item_contac">
                            <i class="bi bi-envelope"></i>
                            <div class="text_contac">
                                <h3>Email</h3>
                                <p>infor.ninhbinhstayword.gmail.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="form_contac">
                        <!-- Success Message -->
                        <div v-if="isSubmitted" class="p-4 mb-4 text-xs text-emerald-800 bg-emerald-50 rounded-xl border border-emerald-200">
                            <strong>Thành công!</strong> Gửi liên hệ thành công! Chúng tôi sẽ phản hồi bạn sớm nhất có thể.
                        </div>

                        <form @submit.prevent="submit">
                            <div class="user_contac">
                                <div class="w-full">
                                    <input type="text" v-model="form.name" placeholder="Họ Tên*" required>
                                    <div v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</div>
                                </div>
                                <div class="w-full">
                                    <input type="email" v-model="form.email" placeholder="Email*" required>
                                    <div v-if="form.errors.email" class="text-rose-500 text-[10px] mt-1">{{ form.errors.email }}</div>
                                </div>
                            </div>
                            <div class="w-full">
                                <input type="text" v-model="form.subject" placeholder="Chủ Đề">
                                <div v-if="form.errors.subject" class="text-rose-500 text-[10px] mt-1">{{ form.errors.subject }}</div>
                            </div>
                            <div class="w-full">
                                <textarea cols="40" rows="10" maxlength="2000" v-model="form.message" placeholder="Nội Dung*" required></textarea>
                                <div v-if="form.errors.message" class="text-rose-500 text-[10px] mt-1">{{ form.errors.message }}</div>
                            </div>
                            <div class="btn_submit">
                                <button type="submit" :disabled="form.processing" class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                                    <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    Gửi Liên Hệ
                                </button>
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