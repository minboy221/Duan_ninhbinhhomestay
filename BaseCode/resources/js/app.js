import './bootstrap';
import './css/app.css';
import './css/main.css';
import './css/responsive/responsive.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// const appName = import.meta.env.VITE_APP_NAME || 'null';

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => {
                console.log('Service Worker đã chạy kích hoạt thành công với scope: ', reg.scope);
            })
            .catch(err => {
                console.log('Đăng ký Service Worker thất bại: ', err);
            });
    });
}

//hàm xin quyền hiển thị thông báo trên thiết bị điện thoại
function requestNotificationPermission(){
    if('Notification' in window && Notification.permission === 'default'){
        Notification.requestPermission().then(permission => {
            if(permission === 'granted'){
                console.log("Người dùng đã cho phép nhận thông báo!");
            }
        });
    }
}

//hàm âm thanh khi có thông báo mới
window.playNotificationSound = function(){
    const audio = new Audio('/sounds/thongbao.mp3');
    audio.play().catch(err=>{
        console.log('Tự động phát âm thanh bị chặn bởi trình duyệt, cần người dùng tương tác trước.', err);
    });
};

//gọi xin quyền khi ứng dụng khởi chạy
if(typeof window !== 'undefined'){
    window.addEventListener('load',() => {
        requestNotificationPermission();
    });
}