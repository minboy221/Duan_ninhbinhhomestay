// public/firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyDwxiIdc9_rxqwaEQmfPsaCHkfTKqwHRRQ",
    authDomain: "datn-homestay-app.firebaseapp.com",
    projectId: "datn-homestay-app",
    storageBucket: "datn-homestay-app.firebasestorage.app",
    messagingSenderId: "230354018857",
    appId: "1:230354018857:web:90eb6084acdd5b70fb7238"
});

const messaging = firebase.messaging();

// Xử lý thông báo ngầm từ Google Firebase gửi tới điện thoại
messaging.onBackgroundMessage((payload) => {
    // Nếu payload đã có đối tượng notification, Firebase SDK đã tự động hiển thị 1 thông báo trên điện thoại.
    // Ngắt không gọi showNotification() nữa để tránh bị hiển thị lặp 2 lần!
    if (payload && payload.notification) {
        return;
    }

    const notificationTitle = payload.data?.title || 'Thông báo từ hệ thống';
    const notificationOptions = {
        body: payload.data?.body || '',
        icon: payload.data?.icon || '/anh/logoPWA192x192.png',
        vibrate: [200, 100, 200],
        data: { url: payload.data?.url || '/' }
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Mở web khi bấm vào thông báo điện thoại
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
