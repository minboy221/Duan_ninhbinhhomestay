<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

const form = useForm({
    name: page.props.auth?.user?.name || '',
    email: page.props.auth?.user?.email || '',
    phone: page.props.auth?.user?.phone || '',
    category: 'general',
    subject: '',
    message: '',
    website_hp: '', // Honeypot field for spam prevention
});

const isSubmitted = ref(false);
const ticketCode = ref('');
const cooldownSeconds = ref(0);
let timer = null;

const startCooldown = () => {
    cooldownSeconds.value = 60;
    if (timer) clearInterval(timer);
    timer = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value--;
        } else {
            clearInterval(timer);
        }
    }, 1000);
};

const submit = () => {
    if (cooldownSeconds.value > 0) return;
    form.post(route('client.contact.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            ticketCode.value = page.props.flash?.ticket_code || '';
            form.reset('subject', 'message', 'website_hp');
            isSubmitted.value = true;
            startCooldown();
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
                    :src="$page.props.settings?.contact_map || 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2611.291724627434!2d105.93314109429076!3d20.603915192384463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf62d752dc67%3A0xd79f03899b4e83d8!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIEZQVCBQb2x5dGVjaG5pYyBjxqEgc-G7nyBIw6AgNam!5e1!3m2!1svi!2s!4v1774600950495!5m2!1svi!2s'"
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
                        <form @submit.prevent="submit">
                            <!-- Honeypot input to trap spam bots -->
                            <div style="display: none !important;">
                                <input type="text" v-model="form.website_hp" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="user_contac">
                                <div class="w-full">
                                    <input type="text" v-model="form.name" placeholder="Họ Tên">
                                    <div v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.name }}</div>
                                </div>
                                <div class="w-full">
                                    <input type="email" v-model="form.email" placeholder="Email">
                                    <div v-if="form.errors.email" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.email }}</div>
                                </div>
                            </div>
                            <div class="user_contac" style="margin-top: 15px;">
                                <div class="w-full">
                                    <input type="text" v-model="form.phone" placeholder="Số Điện Thoại">
                                    <div v-if="form.errors.phone" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.phone }}</div>
                                </div>
                                <div class="w-full">
                                    <select v-model="form.category" style="width: 100%; height: 50px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0 15px; background: #fff; font-size: 14px; color: #334155; outline: none; transition: border-color 0.2s;">
                                        <option value="general">Góp ý / Yêu cầu chung</option>
                                        <option value="consultation">Tư vấn & Đặt phòng trọ</option>
                                        <option value="technical">Báo lỗi & Hỗ trợ kỹ thuật</option>
                                        <option value="partnership">Hợp tác / Cho thuê nhà trọ</option>
                                    </select>
                                    <div v-if="form.errors.category" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.category }}</div>
                                </div>
                            </div>
                            <div class="w-full" style="margin-top: 15px;">
                                <input type="text" v-model="form.subject" placeholder="Chủ Đề">
                                <div v-if="form.errors.subject" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.subject }}</div>
                            </div>
                            <div class="w-full" style="margin-top: 15px;">
                                <textarea cols="40" rows="10" maxlength="2000" v-model="form.message" placeholder="Nội Dung"></textarea>
                                <div v-if="form.errors.message" class="text-rose-500 text-[10px] mt-1 font-semibold">{{ form.errors.message }}</div>
                            </div>
                            <div class="btn_submit" style="margin-top: 10px;">
                                <button type="submit" :disabled="form.processing || cooldownSeconds > 0" class="w-full text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 button-gradient" :class="{'opacity-60 cursor-not-allowed': cooldownSeconds > 0}" style="height: 52px; border: none; font-size: 15px; letter-spacing: 0.5px;">
                                    <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <span v-if="cooldownSeconds > 0">Gửi lại sau ({{ cooldownSeconds }}s)</span>
                                    <span v-else>Gửi Liên Hệ</span>
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
                        
                        <div v-if="ticketCode" style="margin-bottom: 15px; background: #f0f9ff; border: 1px dashed #0284c7; border-radius: 10px; padding: 8px 12px; display: inline-block;">
                            <span style="font-size: 13px; color: #0369a1; font-weight: 600;">Mã tra cứu của bạn: </span>
                            <span style="font-size: 14px; color: #0284c7; font-weight: 700; font-family: monospace;">{{ ticketCode }}</span>
                        </div>

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