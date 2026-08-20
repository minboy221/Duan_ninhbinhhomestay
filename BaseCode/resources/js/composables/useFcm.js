import { messaging,VAPID_KEY } from "@/firebase";
import { getToken } from "firebase/messaging";
import axios from "axios";

export function useFcm(){
    const registerFcmToken = async () => {
        try{
            if(!("Notification" in window)) return;
            //hỏi xin quyền thôngg báo trình duyệt điện thoại
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                // Đăng ký Service Worker cho Firebase Messaging một cách tường minh
                let swRegistration = null;
                if ('serviceWorker' in navigator) {
                    try {
                        swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                    } catch (swErr) {
                        console.warn('Lỗi đăng ký Service Worker FCM:', swErr);
                    }
                }

                // Lấy FCM token
                const tokenOptions = { vapidKey: VAPID_KEY };
                if (swRegistration) {
                    tokenOptions.serviceWorkerRegistration = swRegistration;
                }

                const token = await getToken(messaging, tokenOptions);
                if (token) {
                    // Gửi token lên backend để lưu
                    await axios.post(route('user.update-fcm-token'), {
                        fcm_token: token
                    });
                    console.log('Đã đăng ký Push Notification thành công cho tài khoản!');
                }
            }
        }catch(err){
            console.error('lỗi đăng ký FCM Token:' , err);
        }
    };
    return{
        registerFcmToken
    };
}