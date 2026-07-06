<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    appointments: { type: Array, default: () => [] },
    favoriteRoomIds: { type: Array, default: () => [] }
});

const favoritedRoomIds = ref([...props.favoriteRoomIds]);
import { router } from '@inertiajs/vue3';

const isRoomFavorited = (roomId) => {
    return favoritedRoomIds.value.includes(roomId);
};

const toggleFavorite = (roomId) => {
    router.post(route('rooms.favorite', roomId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const idx = favoritedRoomIds.value.indexOf(roomId);
            if (idx > -1) {
                favoritedRoomIds.value.splice(idx, 1);
            } else {
                favoritedRoomIds.value.push(roomId);
            }
        }
    });
};

const statusMap = {
    pending:  { label: 'Chờ Duyệt',  cls: 'bg-amber-50 text-amber-600 border-amber-100', dot: 'bg-amber-500' },
    approved: { label: 'Đã Duyệt',   cls: 'bg-emerald-50 text-emerald-600 border-emerald-100', dot: 'bg-emerald-500' },
    rejected: { label: 'Từ Chối',    cls: 'bg-slate-50 text-slate-500 border-slate-100', dot: 'bg-slate-500' },
};

// Map state
const activeMapAppointment = ref(null);
const userLocation = ref(null);
const isMapLoading = ref(false);
let leafletMapInstance = null;

// Load Leaflet dynamically
function loadLeaflet(callback) {
    if (window.L) {
        callback();
        return;
    }
    
    // Inject Leaflet CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);

    // Inject Leaflet JS
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = () => {
        callback();
    };
    document.body.appendChild(script);
}

function showMap(appointment) {
    activeMapAppointment.value = appointment;
    isMapLoading.value = true;
    
    // Get user GPS position
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                initMap(appointment);
            },
            (error) => {
                console.warn('Geolocation error, using fallback location', error);
                // Fallback position (center of Ninh Bình city)
                userLocation.value = { lat: 20.2526, lng: 105.9749 };
                initMap(appointment);
            }
        );
    } else {
        // Geolocation not supported
        userLocation.value = { lat: 20.2526, lng: 105.9749 };
        initMap(appointment);
    }
}

function initMap(appointment) {
    loadLeaflet(() => {
        isMapLoading.value = false;
        
        setTimeout(() => {
            // Clean up existing map
            if (leafletMapInstance) {
                leafletMapInstance.remove();
                leafletMapInstance = null;
            }

            // Get room destination coordinates
            // Look up boardingHouse GPS coordinates first
            const bh = appointment.room?.property?.landlord?.boarding_house;
            const destLat = bh?.latitude ? parseFloat(bh.latitude) : 20.2506; // Fallback Hoa Lư Ninh Bình
            const destLng = bh?.longitude ? parseFloat(bh.longitude) : 105.9739;
            const address = appointment.room?.address || appointment.room?.property?.address || 'Ninh Bình';

            // Initialize map centered at destination
            leafletMapInstance = window.L.map('leaflet-map-container').setView([destLat, destLng], 14);

            // Add OpenStreetMap tile layer
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(leafletMapInstance);

            // House icon (Destination)
            const destIcon = window.L.divIcon({
                html: '<div style="background-color: #ef4444; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"><i class="bi bi-house-door-fill"></i></div>',
                className: 'custom-leaflet-icon',
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });

            // Person icon (Guest Location)
            const guestIcon = window.L.divIcon({
                html: '<div style="background-color: #3b82f6; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"><i class="bi bi-person-fill"></i></div>',
                className: 'custom-leaflet-icon',
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });

            // Add destination marker
            window.L.marker([destLat, destLng], { icon: destIcon })
                .addTo(leafletMapInstance)
                .bindPopup(`<strong>Điểm hẹn: Phòng ${appointment.room?.room_number}</strong><br/>Địa chỉ: ${address}`)
                .openPopup();

            // Add user guest marker
            if (userLocation.value) {
                window.L.marker([userLocation.value.lat, userLocation.value.lng], { icon: guestIcon })
                    .addTo(leafletMapInstance)
                    .bindPopup('<strong>Vị trí của bạn</strong>');

                // Draw route line
                const polyline = window.L.polyline([
                    [userLocation.value.lat, userLocation.value.lng],
                    [destLat, destLng]
                ], { color: '#3b82f6', weight: 4, dashArray: '8, 8' }).addTo(leafletMapInstance);

                // Fit map bounds to show both points
                const bounds = window.L.latLngBounds([
                    [userLocation.value.lat, userLocation.value.lng],
                    [destLat, destLng]
                ]);
                leafletMapInstance.fitBounds(bounds, { padding: [40, 40] });
            }
        }, 100);
    });
}

function getGoogleMapsUrl(appointment) {
    const bh = appointment.room?.property?.landlord?.boarding_house;
    const destLat = bh?.latitude ? parseFloat(bh.latitude) : 20.2506;
    const destLng = bh?.longitude ? parseFloat(bh.longitude) : 105.9739;
    const address = appointment.room?.address || appointment.room?.property?.address || '';
    
    if (userLocation.value) {
        return `https://www.google.com/maps/dir/?api=1&origin=${userLocation.value.lat},${userLocation.value.lng}&destination=${destLat},${destLng}&travelmode=driving`;
    }
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

const isToday = (dateStr) => {
    const today = new Date().toISOString().split('T')[0];
    return dateStr === today;
};
</script>

<template>
    <Head title="Lịch Hẹn Xem Phòng | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio">
                    <h2>LỊCH HẸN XEM PHÒNG</h2>
                    <p class="text-xs text-slate-400">Danh sách lịch hẹn xem phòng của bạn với các chủ trọ</p>
                </div>

                <!-- Appointments Table -->
                <div class="table-container" style="margin-top: 20px; overflow-x: auto;">
                    <table class="lichhen-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                                <th style="padding: 12px 16px;">Phòng đặt</th>
                                <th style="padding: 12px 16px;">Thời gian</th>
                                <th style="padding: 12px 16px;">Chủ trọ</th>
                                <th style="padding: 12px 16px;">Trạng thái</th>
                                <th style="padding: 12px 16px; text-align: center;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody style="color: #334155;">
                            <tr v-if="appointments.length === 0">
                                <td colspan="5" style="padding: 32px; text-align: center; color: #94a3b8;">
                                    <i class="bi bi-calendar-x" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                    Bạn chưa có lịch hẹn xem phòng nào.
                                </td>
                            </tr>
                            <tr v-for="apt in appointments" :key="apt.id" style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 600; color: #1e293b;">
                                        Phòng {{ apt.room?.room_number }}
                                    </div>
                                    <div style="font-size: 11.5px; color: #64748b;">
                                        {{ apt.room?.property?.name }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 550;">
                                        {{ new Date(apt.date).toLocaleDateString('vi-VN') }}
                                    </div>
                                    <div style="font-size: 11px; color: #64748b;">
                                        Lúc {{ apt.time.substring(0, 5) }}
                                        <span v-if="isToday(apt.date)" class="today-badge">Hôm nay!</span>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div>{{ apt.room?.property?.landlord?.name }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">
                                        SĐT: {{ apt.room?.property?.landlord?.phone }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span :class="['status-badge', statusMap[apt.status].cls]" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid;">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[apt.status].dot" style="width:6px; height:6px; border-radius:50%;"></span>
                                        {{ statusMap[apt.status].label }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <Link :href="route('chitiettro', apt.room_id)" class="btn-action btn-view" title="Xem phòng">
                                            <i class="bi bi-eye-fill"></i>
                                        </Link>
                                        <button @click="toggleFavorite(apt.room_id)" 
                                                class="btn-action btn-fav" 
                                                :title="isRoomFavorited(apt.room_id) ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'">
                                            <i :class="['bi', isRoomFavorited(apt.room_id) ? 'bi-heart-fill text-red' : 'bi-heart']"></i>
                                        </button>
                                        <button v-if="apt.status === 'approved'" 
                                                @click="showMap(apt)" 
                                                class="btn-action btn-map" 
                                                :title="isToday(apt.date) ? 'Đến ngày! Chỉ đường Google Maps' : 'Xem bản đồ'">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Leaflet Routing Map container -->
                <div v-if="activeMapAppointment" class="map-card-wrapper animate-fadeIn" style="margin-top: 30px;">
                    <div class="map-card-header" style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 20px; border-top-left-radius: 12px; border-top-right-radius: 12px; border: 1px solid #e2e8f0; border-bottom: none;">
                        <div>
                            <h3 style="font-size: 14px; font-weight: 700; color: #1e293b; margin: 0;">
                                <i class="bi bi-map-fill text-blue-500"></i> Lộ trình xem phòng: Phòng {{ activeMapAppointment.room?.room_number }}
                            </h3>
                            <p style="font-size: 11.5px; color: #64748b; margin: 2px 0 0;">
                                Địa chỉ: {{ activeMapAppointment.room?.address || activeMapAppointment.room?.property?.address }}
                            </p>
                        </div>
                        <button class="map-close-btn" @click="activeMapAppointment = null" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #94a3b8;">&times;</button>
                    </div>

                    <div style="position: relative; border: 1px solid #e2e8f0; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; overflow: hidden; background: #fff;">
                        <!-- Khảo sát quan tâm -->
                        <div class="survey-prompt-card" :class="{ favorited: isRoomFavorited(activeMapAppointment.room_id) }">
                            <div class="survey-body">
                                <div class="survey-text-wrapper">
                                    <i :class="['bi', isRoomFavorited(activeMapAppointment.room_id) ? 'bi-heart-fill text-red' : 'bi-heart-pulse-fill text-red pulse-icon']"></i>
                                    <span v-if="isRoomFavorited(activeMapAppointment.room_id)">Bạn đã lưu phòng trọ này vào danh sách quan tâm!</span>
                                    <span v-else>Bạn đã đến địa chỉ hoặc kết thúc buổi xem phòng? Bạn có quan tâm đến trọ này không?</span>
                                </div>
                                <button @click="toggleFavorite(activeMapAppointment.room_id)" class="btn-survey-favorite" :class="{ active: isRoomFavorited(activeMapAppointment.room_id) }">
                                    <i :class="['bi', isRoomFavorited(activeMapAppointment.room_id) ? 'bi-heart-fill' : 'bi-heart']"></i>
                                    {{ isRoomFavorited(activeMapAppointment.room_id) ? 'Đã Lưu (Bỏ Thích)' : 'Thả Tim (Quan Tâm)' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="isMapLoading" class="map-loading-overlay" style="position: absolute; inset: 0; background: rgba(255,255,255,0.8); z-index: 10; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #64748b;">
                            <span class="spinner" style="margin-right: 8px;"></span> Đang tải vị trí của bạn...
                        </div>
                        <div id="leaflet-map-container" style="height: 350px; width: 100%;"></div>

                        <!-- Directions Actions -->
                        <div style="padding: 16px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; md-flex-direction: row; justify-content: space-between; align-items: center; gap: 12px;">
                            <div style="font-size: 12px; color: #475569; font-weight: 550;">
                                <i class="bi bi-info-circle-fill" style="color: #3b82f6;"></i>
                                <span v-if="isToday(activeMapAppointment.date)"> Lịch hẹn hôm nay! Vui lòng di chuyển tới phòng trọ theo lộ trình.</span>
                                <span v-else> Lịch hẹn ngày {{ new Date(activeMapAppointment.date).toLocaleDateString('vi-VN') }}. Bản đồ giúp bạn khảo sát vị trí trước.</span>
                            </div>
                            <a :href="getGoogleMapsUrl(activeMapAppointment)" 
                               target="_blank" 
                               class="btn-directions-google" 
                               style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background-color: #166ea9; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 13px; transition: background 0.2s;">
                                <i class="bi bi-signpost-2-fill"></i>
                                <span>Chỉ Đường Bằng Google Maps</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/user.css";
@import '../../css/responsive/responsivetranguser.css';

.status-badge {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.today-badge {
    background-color: #ef4444;
    color: white;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 9.5px;
    font-weight: 700;
    margin-left: 4px;
    display: inline-block;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-view {
    background-color: #f1f5f9;
    color: #475569;
}

.btn-view:hover {
    background-color: #cbd5e1;
    color: #1e293b;
}

.btn-map {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.btn-map:hover {
    background-color: #2563eb;
    color: #ffffff;
}

.btn-directions-google:hover {
    background-color: #0f4f7a;
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Spinner styling */
.spinner {
    border: 3px solid #cbd5e1;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
    display: inline-block;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Favorites styles */
.btn-fav {
    background-color: #fff1f2;
    color: #f43f5e;
    border: 1px solid #fecdd3;
}

.btn-fav:hover {
    background-color: #f43f5e;
    color: #ffffff;
}

.text-red {
    color: #ef4444 !important;
}

/* Survey Card styling */
.survey-prompt-card {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    transition: all 0.3s;
}

.survey-prompt-card.favorited {
    background-color: #fff1f2;
    border-bottom-color: #ffe4e6;
}

.survey-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.survey-text-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
}

.survey-prompt-card.favorited .survey-text-wrapper {
    color: #9f1239;
}

.pulse-icon {
    animation: heartbeat 1.5s infinite;
}

@keyframes heartbeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

.btn-survey-favorite {
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #fecdd3;
    background: #fff;
    color: #f43f5e;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-survey-favorite:hover {
    background: #fff1f2;
}

.btn-survey-favorite.active {
    background: #f43f5e;
    color: #fff;
    border-color: #f43f5e;
}

.btn-survey-favorite.active:hover {
    background: #e11d48;
}
</style>
