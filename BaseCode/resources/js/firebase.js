// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getMessaging, isSupported } from "firebase/messaging";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyDwxiIdc9_rxqwaEQmfPsaCHkfTKqwHRRQ",
  authDomain: "datn-homestay-app.firebaseapp.com",
  projectId: "datn-homestay-app",
  storageBucket: "datn-homestay-app.firebasestorage.app",
  messagingSenderId: "230354018857",
  appId: "1:230354018857:web:90eb6084acdd5b70fb7238",
  measurementId: "G-V3XSDWF28H"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

let messagingSync = null;
try {
  if (typeof window !== "undefined" && "Notification" in window && "serviceWorker" in navigator) {
    messagingSync = getMessaging(app);
  }
} catch (e) {
  console.warn("Trình duyệt điện thoại không hỗ trợ Firebase Messaging:", e.message);
}

export const getMessagingInstance = async () => {
  try {
    const supported = await isSupported();
    if (supported) {
      return getMessaging(app);
    }
  } catch (err) {
    console.warn("Lỗi kiểm tra hỗ trợ Firebase Messaging:", err);
  }
  return null;
};

export const messaging = messagingSync;
export const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY || "BI4oQirK1z-KOrTARFVW3MGns6MfSqu6hchiA5fnAA55zQhkbQFF6ZV8jWljiOyNy8pfX1Xeb-9y9Qc84QyAxBY";
export { app };