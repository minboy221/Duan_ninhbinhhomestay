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
                        <form @submit.prevent="submit">
                            <div class="user_contac">
                                <div class="w-full">
                                    <input type="text" v-model="form.name" placeholder="Họ Tên">
                                    <div v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</div>
                                </div>
                                <div class="w-full">
                                    <input type="email" v-model="form.email" placeholder="Email">
                                    <div v-if="form.errors.email" class="text-rose-500 text-[10px] mt-1">{{ form.errors.email }}</div>
                                </div>
                            </div>
                            <div class="w-full">
                                <input type="text" v-model="form.subject" placeholder="Chủ Đề">
                                <div v-if="form.errors.subject" class="text-rose-500 text-[10px] mt-1">{{ form.errors.subject }}</div>
                            </div>
                            <div class="w-full">
                                <textarea cols="40" rows="10" maxlength="2000" v-model="form.message" placeholder="Nội Dung"></textarea>
                                <div v-if="form.errors.message" class="text-rose-500 text-[10px] mt-1">{{ form.errors.message }}</div>
                            </div>
                            <div class="btn_submit">
                                <button type="submit" :disabled="form.processing" class="w-full py-3 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 button-gradient" style="border: none; cursor: pointer;">
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

    <!-- Success Modal Popup -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="isSubmitted" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 20px;">
                <div class="modal-content" style="background: white; border-radius: 20px; width: 450px; max-width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; overflow: hidden; position: relative;">
                    <div style="height: 6px; background: linear-gradient(90deg, #102a6d, #45abe6);"></div>
                    <div style="padding: 30px; text-align: center;">
                        <div style="width: 60px; height: 60px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0284c7; margin: 0 auto 20px auto;">
                            <i class="bi bi-envelope-check-fill" style="font-size: 28px;"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 10px; font-family: 'Segoe UI', sans-serif;">Gửi liên hệ thành công!</h3>
                        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 25px; font-family: 'Segoe UI', sans-serif;">
                            Cảm ơn bạn đã liên hệ với Ninh Bình HomeStay. Yêu cầu của bạn đã được gửi thành công đến chúng tôi. Chúng tôi sẽ phản hồi bạn sớm nhất có thể.
                        </p>
                        <button @click="isSubmitted = false" class="button-gradient" style="color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 120px;">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
@import "../../css/lienhe.css";
@import '../../css/responsive/responsivelienhe.css';
@import '../../css/responsive/responsive.css';

.button-gradient {
    background: linear-gradient(90deg, #102a6d, #45abe6);
    transition: 0.3s;
}
.button-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.modal-content {
    animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes scaleUp {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>