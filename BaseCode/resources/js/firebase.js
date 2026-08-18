// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
import { getMessaging } from "firebase/messaging";
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
export const messaging = getMessaging(app);
export const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY || "";