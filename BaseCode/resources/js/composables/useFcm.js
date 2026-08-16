import { messaging,VAPID_KEY } from "@/firebase";
import { getToken } from "firebase/messaging";
import axios from "axios";

export function useFcm(){
    const registerFcmToken = async () => {
        try{
            if(!("Notification" in window)) return;
            //hỏi xin quyền thôngg báo trình duyệt điện thoại
            const permission = await Notification.requestPermission();
            if(permission === 'granted'){
                //lấy token từ filebase
                const token = await getToken(messaging,{vapidKey: VAPID_KEY});
                if(token){
                    //gửi token lên backend để lưu
                    await axios.post(route('user.update-fcm-token'),{
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