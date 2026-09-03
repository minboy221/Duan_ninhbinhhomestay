import './bootstrap';
import './css/app.css';
import './css/main.css';
import './css/responsive/responsive.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// const appName = import.meta.env.VITE_APP_NAME || 'null';

// Hiển thị khung thông báo lỗi trực tiếp trên màn hình di động (giúp phát hiện chính xác nguyên nhân nếu bị trắng màn hình)
if (typeof window !== 'undefined') {
    const showDebugError = (title, message) => {
        const msgStr = String(message?.message || message || '');
        // Bỏ qua các lỗi không thuộc về app (từ tiện ích mở rộng Chrome/trình duyệt, Vue DevTools hoặc script ngoài)
        if (
            msgStr.includes('M_ID') ||
            msgStr.includes('chrome-extension') ||
            msgStr.includes('moz-extension') ||
            msgStr.includes('ResizeObserver') ||
            msgStr.includes('WebSocket') ||
            msgStr.includes('localhost:undefined')
        ) {
            console.warn('Bỏ qua lỗi từ tiện ích mở rộng trình duyệt:', message);
            return;
        }

        let errDiv = document.getElementById('mobile-debug-error');
        if (!errDiv) {
            errDiv = document.createElement('div');
            errDiv.id = 'mobile-debug-error';
            errDiv.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:999999;background:#dc2626;color:white;padding:12px 16px;font-size:13px;word-break:break-all;box-shadow:0 4px 12px rgba(0,0,0,0.5);font-family:monospace;display:flex;align-items:center;justify-content:space-between;gap:12px;';
            document.body.appendChild(errDiv);
        }
        errDiv.innerHTML = `
            <div style="flex:1;">
                <strong>${title}:</strong><br>${message}
            </div>
            <button onclick="this.parentElement.remove()" style="background:rgba(255,255,255,0.25);border:none;color:white;padding:4px 10px;border-radius:6px;cursor:pointer;font-weight:bold;white-space:nowrap;">✕ Đóng</button>
        `;
    };

    window.addEventListener('error', (event) => {
        showDebugError('LỖI TRÊN ĐIỆN THOẠI', `${event.message} <br><small>tại ${event.filename}:${event.lineno}</small>`);
    });

    window.addEventListener('unhandledrejection', (event) => {
        showDebugError('LỖI PROMISE / MODULE TRÊN ĐIỆN THOẠI', event.reason);
    });
}

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.errorHandler = (err, instance, info) => {
            console.error('Vue Error:', err, info);
            showDebugError('LỖI VUE COMPONENT', err.message || err);
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