import { messaging, VAPID_KEY, getMessagingInstance } from "@/firebase";
import { getToken, onMessage } from "firebase/messaging";
import { showSuccess } from "@/Utils/swal";
import axios from "axios";

export function useFcm() {
    const registerFcmToken = async () => {
        try {
            if (!("Notification" in window) || !("serviceWorker" in navigator)) {
                return;
            }
            // hỏi xin quyền thông báo trình duyệt điện thoại
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                const messagingObj = messaging || await getMessagingInstance();
                if (!messagingObj) {
                    return;
                }

                // Đăng ký Service Worker cho Firebase Messaging một cách tường minh
                let swRegistration = null;
                try {
                    swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                } catch (swErr) {
                    console.warn('Lỗi đăng ký Service Worker FCM:', swErr);
                }

                // Lấy FCM token
                const tokenOptions = { vapidKey: VAPID_KEY };
                if (swRegistration) {
                    tokenOptions.serviceWorkerRegistration = swRegistration;
                }

                const token = await getToken(messagingObj, tokenOptions);
                if (token) {
                    // Gửi token lên backend để lưu (dùng url an toàn)
                    const targetUrl = typeof route === 'function' ? route('user.update-fcm-token') : '/user/fcm_token';
                    await axios.post(targetUrl, {
                        fcm_token: token
                    });
                    console.log('Đã đăng ký Push Notification thành công cho tài khoản!');
                }

                // Lắng nghe thông báo khi trang web đang mở (Foreground)
                try {
                    onMessage(messagingObj, (payload) => {
                        const title = payload?.notification?.title || payload?.data?.title || 'Thông báo mới';
                        const body = payload?.notification?.body || payload?.data?.body || '';
                        if (title || body) {
                            showSuccess(`${title}: ${body}`);
                        }
                    });
                } catch (msgErr) {}
            }
        } catch (err) {
            console.warn('Lỗi đăng ký FCM Token (Trình duyệt điện thoại không hỗ trợ):', err.message || err);
        }
    };
    return {
        registerFcmToken
    };
}