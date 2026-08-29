import './bootstrap';
import './css/app.css';
import './css/main.css';
import './css/responsive/responsive.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { vClickOutside } from './Directives/vClickOutside';

// const appName = import.meta.env.VITE_APP_NAME || 'null';

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.directive('click-outside', vClickOutside);
        app.config.errorHandler = (err, instance, info) => {
            console.error('Vue Error:', err, info);
        };
        return app.use(plugin).use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Tự động hủy đăng ký toàn bộ Service Worker cũ trên điện thoại người dùng để xóa sạch bộ nhớ đệm
if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        for (let registration of registrations) {
            registration.unregister();
            console.log('Đã tự động gỡ bỏ Service Worker cũ:', registration);
        }
    });
}

//hàm xin quyền hiển thị thông báo trên thiết bị điện thoại
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log("Người dùng đã cho phép nhận thông báo!");
            }
        });
    }
}

//hàm âm thanh khi có thông báo mới
window.playNotificationSound = function () {
    const audio = new Audio('/sounds/thongbao.mp3');
    audio.play().catch(err => {
        console.log('Tự động phát âm thanh bị chặn bởi trình duyệt, cần người dùng tương tác trước.', err);
    });
};

//gọi xin quyền khi ứng dụng khởi chạy
if (typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        requestNotificationPermission();
    });
}