<script setup>
import { ref, reactive, nextTick, onMounted, onUnmounted, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Trạng thái mở/đóng popup
const isOpen = ref(false);
const showGreetingBubble = ref(false);
const isTyping = ref(false);
const inputPrompt = ref('');
const messagesContainerRef = ref(null);
const inputRef = ref(null);

// Gợi ý mặc định
const defaultSuggestions = [
    { label: '🏢 Tầng 1 Hoa Lư < 2.5tr', text: 'Tìm phòng tầng 1 quanh khu Hoa Lư, dưới 2.5 triệu' },
    { label: '🌿 Studio gác xép nuôi pet', text: 'Phòng studio có gác xép, cho nuôi thú cưng' },
    { label: '❄️ Có điều hòa & máy giặt', text: 'Phòng trọ có điều hòa, máy giặt, nóng lạnh dưới 3 triệu' },
    { label: '👥 Phòng ghép sinh viên', text: 'Phòng ghép sinh viên giá rẻ dưới 1.5 triệu' },
];

const welcomeMessage = {
    id: 'welcome',
    sender: 'ai',
    text: 'Xin chào bạn! 👋 Mình là **Trợ lý AI Ninh Bình HomeStay**.\nBạn đang tìm phòng trọ như thế nào? Hãy nói cho mình biết khu vực, mức giá hoặc tiện ích mong muốn nhé! ✨',
    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
    rooms: [],
    suggestions: defaultSuggestions.map(s => s.text),
    ai_parsed: null,
};

const messages = ref([welcomeMessage]);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainerRef.value) {
        messagesContainerRef.value.scrollTo({
            top: messagesContainerRef.value.scrollHeight,
            behavior: 'smooth'
        });
    }
};

// 1. TẢI VÀ ĐỒNG BỘ LỊCH SỬ CHAT (7 NGÀY)
const GUEST_STORAGE_KEY = 'ninhbinh_guest_chat_history';

const loadChatHistory = async () => {
    if (user.value) {
        // A. Tài khoản đã đăng nhập: Kiểm tra xem có tin nhắn guest cần đồng bộ không
        const guestData = localStorage.getItem(GUEST_STORAGE_KEY);
        if (guestData) {
            try {
                const guestMsgs = JSON.parse(guestData);
                if (Array.isArray(guestMsgs) && guestMsgs.length > 0) {
                    await axios.post('/api/ai/sync-guest-history', { messages: guestMsgs }).catch(() => {});
                    localStorage.removeItem(GUEST_STORAGE_KEY);
                }
            } catch (e) {}
        }

        // Tải lịch sử 7 ngày từ CSDL Server
        try {
            const res = await axios.get('/api/ai/chat-history');
            if (Array.isArray(res.data) && res.data.length > 0) {
                messages.value = res.data;
            } else {
                messages.value = [welcomeMessage];
            }
        } catch (err) {
            console.error('Lỗi tải lịch sử chat:', err);
            messages.value = [welcomeMessage];
        }
    } else {
        // B. Khách vãng lai (Guest): Đọc từ localStorage và tự động lọc bỏ > 7 ngày
        const raw = localStorage.getItem(GUEST_STORAGE_KEY);
        if (raw) {
            try {
                const parsed = JSON.parse(raw);
                const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;
                const valid = parsed.filter(m => {
                    const t = m.timestamp || (m.created_at ? new Date(m.created_at).getTime() : 0);
                    return t >= cutoff;
                });
                if (valid.length > 0) {
                    messages.value = valid;
                    localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(valid));
                } else {
                    messages.value = [welcomeMessage];
                    localStorage.removeItem(GUEST_STORAGE_KEY);
                }
            } catch (e) {
                messages.value = [welcomeMessage];
            }
        } else {
            messages.value = [welcomeMessage];
        }
    }
    scrollToBottom();
};

// Lưu tin nhắn của khách vào localStorage
const saveGuestMessage = (msg) => {
    if (user.value) return;
    try {
        const raw = localStorage.getItem(GUEST_STORAGE_KEY);
        const list = raw ? JSON.parse(raw) : [];
        list.push(msg);
        // Giữ lại tối đa trong 7 ngày
        const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;
        const valid = list.filter(m => {
            const t = m.timestamp || (m.created_at ? new Date(m.created_at).getTime() : 0);
            return t >= cutoff;
        });
        localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(valid));
    } catch (e) {}
};

// Theo dõi khi người dùng đăng nhập
watch(user, (newUser) => {
    loadChatHistory();
});

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        showGreetingBubble.value = false;
        scrollToBottom();
        nextTick(() => {
            if (inputRef.value) inputRef.value.focus();
        });
    }
};

const closeChat = () => {
    isOpen.value = false;
};

const resetChat = async () => {
    if (user.value) {
        await axios.post('/api/ai/clear-chat-history').catch(() => {});
    } else {
        localStorage.removeItem(GUEST_STORAGE_KEY);
    }
    messages.value = [
        {
            id: 'welcome-' + Date.now(),
            sender: 'ai',
            text: 'Cuộc trò chuyện đã được làm mới! ✨ Bạn muốn mình hỗ trợ tìm phòng trọ ở khu vực nào tại Ninh Bình?',
            time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
            rooms: [],
            suggestions: defaultSuggestions.map(s => s.text),
            ai_parsed: null,
        }
    ];
    scrollToBottom();
};

const dismissGreeting = (e) => {
    e.stopPropagation();
    showGreetingBubble.value = false;
    sessionStorage.setItem('dismissed_ai_greeting', 'true');
};

const sendPromptText = (text) => {
    inputPrompt.value = text;
    sendMessage();
};

const sendMessage = async () => {
    const prompt = inputPrompt.value.trim();
    if (!prompt || isTyping.value) return;

    const now = new Date();
    const timeStr = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

    // 1. Thêm tin nhắn của User
    const userMsg = {
        id: 'user-' + Date.now(),
        sender: 'user',
        text: prompt,
        time: timeStr,
        timestamp: now.getTime(),
        created_at: now.toISOString(),
    };
    messages.value.push(userMsg);
    saveGuestMessage(userMsg);

    inputPrompt.value = '';
    scrollToBottom();

    // 2. Kích hoạt trạng thái AI đang gõ
    isTyping.value = true;

    try {
        const response = await axios.post('/api/ai/chat-assistant', { prompt });
        const data = response.data;

        const aiMsg = {
            id: 'ai-' + Date.now(),
            sender: 'ai',
            text: data.message || 'Dưới đây là 2 phòng trọ phù hợp và mới cập nhật gần đây nhất dành cho bạn:',
            time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
            timestamp: Date.now(),
            created_at: new Date().toISOString(),
            rooms: data.rooms || [],
            total_matches: data.total_matches || 0,
            ai_parsed: data.ai_parsed || null,
            suggestions: data.suggestions || [],
            original_prompt: prompt,
        };
        messages.value.push(aiMsg);
        saveGuestMessage(aiMsg);
    } catch (error) {
        console.error('Lỗi tìm kiếm AI Assistant:', error);
        const errMsg = {
            id: 'ai-err-' + Date.now(),
            sender: 'ai',
            text: 'Rất tiếc, đã có lỗi khi kết nối với máy chủ AI. Bạn vui lòng thử lại sau giây lát nhé! 😥',
            time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
            timestamp: Date.now(),
            created_at: new Date().toISOString(),
            rooms: [],
            suggestions: ['Thử lại', 'Xem tất cả phòng trọ'],
        };
        messages.value.push(errMsg);
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

// Chuyển hướng sang trang chi tiết phòng
const goToRoomDetail = (url) => {
    if (url) {
        router.visit(url);
    }
};

// Chuyển hướng sang trang tìm trọ với bộ lọc AI
const goToSearchPage = (prompt) => {
    isOpen.value = false;
    router.visit('/timtro', {
        data: { ai_prompt: prompt }
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'available': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'rented': return 'bg-rose-100 text-rose-700 border-rose-200';
        case 'deposited': return 'bg-amber-100 text-amber-700 border-amber-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const renderFormattedText = (text) => {
    if (!text) return '';
    const escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    return escaped
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>');
};

// Khởi tạo
onMounted(() => {
    loadChatHistory();

    const dismissed = sessionStorage.getItem('dismissed_ai_greeting');
    if (!dismissed) {
        setTimeout(() => {
            if (!isOpen.value) {
                showGreetingBubble.value = true;
            }
        }, 2000);
    }
});
</script>

<template>
    <div class="ai-assistant-root">
        <!-- 1. GREETING SPEECH BUBBLE (Bóng thoại mời chào) -->
        <Transition name="fade-slide">
            <div 
                v-if="showGreetingBubble && !isOpen" 
                class="ai-greeting-bubble"
                @click="toggleChat"
            >
                <div class="greeting-content">
                    <span class="greeting-sparkle">✨</span>
                    <p class="greeting-text">
                        <strong>Cần tìm phòng trọ?</strong><br>
                        Nhắn cho mình để AI gợi ý nhé!
                    </p>
                </div>
                <button 
                    class="greeting-close-btn" 
                    @click.stop="dismissGreeting" 
                    title="Đóng"
                >
                    <i class="bi bi-x"></i>
                </button>
                <div class="greeting-arrow"></div>
            </div>
        </Transition>

        <!-- 2. FLOATING MASCOT BUTTON (Nút nhân vật nổi) -->
        <div class="ai-mascot-wrapper" @click="toggleChat" :class="{ 'is-active': isOpen }">
            <div class="mascot-pulse-ring"></div>
            <div class="mascot-button" :title="isOpen ? 'Thu nhỏ chat AI' : 'Mở Trợ lý AI Tìm Trọ'">
                <img 
                    src="/anh/popup_character.png" 
                    alt="AI Mascot Assistant" 
                    class="mascot-img"
                />
                <!-- Online Green Dot Badge -->
                <span class="mascot-online-dot"></span>
                <!-- Sparkle Badge -->
                <span class="mascot-sparkle-badge">
                    <i class="bi bi-stars"></i>
                </span>
            </div>
        </div>

        <!-- 3. INTERACTIVE CHAT POPUP (Cửa sổ chatbox) -->
        <Transition name="chat-scale">
            <div v-if="isOpen" class="ai-chatbox-window">
                <!-- Header -->
                <div class="chatbox-header">
                    <div class="chatbox-header-info">
                        <div class="header-avatar-wrap">
                            <img src="/anh/popup_character.png" alt="AI Avatar" class="header-avatar-img" />
                            <span class="header-online-dot"></span>
                        </div>
                        <div class="header-title-wrap">
                            <h3 class="header-title">Trợ Lý AI Ninh Bình</h3>
                            <p class="header-status">
                                <i class="bi bi-stars text-cyan-300 mr-1"></i>
                                Sẵn sàng tìm phòng giúp bạn
                            </p>
                        </div>
                    </div>
                    <div class="chatbox-header-actions">
                        <button 
                            @click="resetChat" 
                            class="header-btn" 
                            title="Làm mới cuộc trò chuyện"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button 
                            @click="closeChat" 
                            class="header-btn header-btn-close" 
                            title="Đóng chatbox"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="chatbox-body custom-scrollbar" ref="messagesContainerRef">
                    <div 
                        v-for="msg in messages" 
                        :key="msg.id" 
                        :class="['chat-msg-row', msg.sender === 'user' ? 'msg-user' : 'msg-ai']"
                    >
                        <!-- AI Avatar -->
                        <div v-if="msg.sender === 'ai'" class="msg-avatar">
                            <img src="/anh/popup_character.png" alt="AI" />
                        </div>

                        <div class="msg-bubble-wrap">
                            <!-- Main Text Bubble -->
                            <div class="msg-bubble">
                                <div class="msg-text-content" v-html="renderFormattedText(msg.text)"></div>
                                <span class="msg-time">{{ msg.time }}</span>
                            </div>

                            <!-- AI Detected Filter Tags -->
                            <div v-if="msg.ai_parsed && (msg.ai_parsed.area_name || msg.ai_parsed.price_max || msg.ai_parsed.amenity_names?.length)" class="ai-filter-tags">
                                <span class="filter-tags-label"><i class="bi bi-funnel-fill"></i> Đã lọc:</span>
                                <span v-if="msg.ai_parsed.area_name" class="filter-tag-pill">
                                    <i class="bi bi-geo-alt-fill text-blue-500"></i> {{ msg.ai_parsed.area_name }}
                                </span>
                                <span v-if="msg.ai_parsed.price_max" class="filter-tag-pill">
                                    <i class="bi bi-tag-fill text-emerald-500"></i> ≤ {{ new Intl.NumberFormat('vi-VN').format(msg.ai_parsed.price_max) }} đ
                                </span>
                                <span v-if="msg.ai_parsed.floor_number" class="filter-tag-pill">
                                    <i class="bi bi-layers-fill text-indigo-500"></i> Tầng {{ msg.ai_parsed.floor_number }}
                                </span>
                                <span v-for="(am, amIdx) in (msg.ai_parsed.amenity_names || []).slice(0, 2)" :key="amIdx" class="filter-tag-pill">
                                    <i class="bi bi-check-circle-fill text-teal-500"></i> {{ am }}
                                </span>
                            </div>

                            <!-- Room Cards List (Top 2 Closest Matches) -->
                            <div v-if="msg.rooms && msg.rooms.length > 0" class="room-cards-list">
                                <div 
                                    v-for="(room, rIdx) in msg.rooms.slice(0, 2)" 
                                    :key="room.id" 
                                    class="chat-room-card"
                                    @click="goToRoomDetail(room.url)"
                                >
                                    <div class="card-thumb-wrap">
                                        <img 
                                            :src="room.image" 
                                            :alt="room.title" 
                                            class="card-thumb-img"
                                            @error="$event.target.src = '/anh/banner_tro.png'"
                                        />
                                        <!-- Badge trạng thái: Đã có X người ở HOẶC Còn phòng -->
                                        <span 
                                            v-if="room.current_people > 0" 
                                            class="card-status-badge card-status-residents"
                                        >
                                            <i class="bi bi-person-check-fill mr-0.5"></i> Đã có {{ room.current_people }} người ở
                                        </span>
                                        <span 
                                            v-else 
                                            class="card-status-badge" 
                                            :class="getStatusBadgeClass(room.status)"
                                        >
                                            {{ room.status_label || 'Còn phòng' }}
                                        </span>

                                        <span v-if="rIdx === 0" class="card-match-badge">
                                            <i class="bi bi-patch-check-fill"></i> {{ room.badge_label || (msg.total_matches > 0 ? 'Phù hợp nhất' : 'Giá sát nhất') }}
                                        </span>
                                    </div>
                                    <div class="card-info-wrap">
                                        <h4 class="card-room-title" :title="room.title">{{ room.title }}</h4>
                                        <div class="card-price-row">
                                            <span class="card-price">{{ room.price_formatted }}</span>
                                            <div class="flex items-center gap-1">
                                                <span v-if="room.floor" class="card-floor-tag">
                                                    <i class="bi bi-layers-fill"></i> {{ room.floor }}
                                                </span>
                                                <span v-if="room.area" class="card-area">{{ room.area }} m²</span>
                                            </div>
                                        </div>
                                        <p class="card-address" :title="room.address">
                                            <i class="bi bi-geo-alt-fill text-blue-500"></i>
                                            <span>{{ room.address }}</span>
                                        </p>
                                        <div class="card-action-row">
                                            <span class="card-btn-view">
                                                Xem phòng <i class="bi bi-arrow-right"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action: Xem toàn bộ trên trang Tìm Trọ -->
                                <button 
                                    v-if="msg.original_prompt"
                                    @click="goToSearchPage(msg.original_prompt)"
                                    class="chat-view-all-btn"
                                >
                                    <i class="bi bi-search"></i>
                                    <span>Xem tất cả {{ msg.total_matches > 2 ? msg.total_matches + ' phòng' : 'trên trang Tìm Trọ' }}</span>
                                    <i class="bi bi-arrow-right ml-auto"></i>
                                </button>
                            </div>

                            <!-- Follow-up Suggestion Chips -->
                            <div v-if="msg.suggestions && msg.suggestions.length > 0" class="msg-suggestions">
                                <button 
                                    v-for="(sug, sIdx) in msg.suggestions" 
                                    :key="sIdx" 
                                    @click="sendPromptText(sug)"
                                    class="sug-chip-btn"
                                >
                                    {{ sug }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="chat-msg-row msg-ai">
                        <div class="msg-avatar">
                            <img src="/anh/popup_character.png" alt="AI" />
                        </div>
                        <div class="msg-bubble-wrap">
                            <div class="msg-bubble msg-typing-bubble">
                                <div class="typing-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <span class="typing-text">AI đang phân tích & tìm kiếm...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Input Area -->
                <div class="chatbox-footer">
                    <form @submit.prevent="sendMessage" class="chat-input-form">
                        <input 
                            ref="inputRef"
                            v-model="inputPrompt" 
                            type="text" 
                            placeholder="Nhập yêu cầu: Tầng 1 Hoa Lư < 2.5tr..." 
                            class="chat-input-field border-0 focus:ring-0 focus:border-0 outline-none"
                            :disabled="isTyping"
                        />
                        <button 
                            type="submit" 
                            class="chat-send-btn" 
                            :disabled="!inputPrompt.trim() || isTyping"
                            title="Gửi tin nhắn"
                        >
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* =============================================
   ROOT & POSITIONING
   ============================================= */
.ai-assistant-root {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99990;
    font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* =============================================
   1. GREETING SPEECH BUBBLE
   ============================================= */
.ai-greeting-bubble {
    position: absolute;
    bottom: 82px;
    right: 6px;
    background: #ffffff;
    border-radius: 16px;
    padding: 12px 32px 12px 14px;
    box-shadow: 0 12px 30px -4px rgba(16, 42, 109, 0.2), 0 4px 10px rgba(0, 0, 0, 0.05);
    border: 1.5px solid #dbeafe;
    cursor: pointer;
    min-width: 220px;
    max-width: 260px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    animation: gentleBob 3s ease-in-out infinite;
}

.ai-greeting-bubble:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 36px -4px rgba(16, 42, 109, 0.25);
    border-color: #93c5fd;
}

.greeting-content {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.greeting-sparkle {
    font-size: 16px;
    line-height: 1;
}

.greeting-text {
    font-size: 12.5px;
    line-height: 1.4;
    color: #1e293b;
    margin: 0;
}

.greeting-text strong {
    color: #2563eb;
}

.greeting-close-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    color: #94a3b8;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: 0.2s;
}

.greeting-close-btn:hover {
    background: #f1f5f9;
    color: #ef4444;
}

.greeting-arrow {
    position: absolute;
    bottom: -8px;
    right: 28px;
    width: 14px;
    height: 14px;
    background: #ffffff;
    border-right: 1.5px solid #dbeafe;
    border-bottom: 1.5px solid #dbeafe;
    transform: rotate(45deg);
}

/* =============================================
   2. FLOATING MASCOT BUTTON
   ============================================= */
.ai-mascot-wrapper {
    position: relative;
    width: 68px;
    height: 68px;
    cursor: pointer;
    user-select: none;
}

.mascot-pulse-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.4) 0%, rgba(6, 182, 212, 0) 70%);
    animation: pulseGlow 2.5s infinite;
    pointer-events: none;
}

.mascot-button {
    position: relative;
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
    box-shadow: 
        0 10px 25px -3px rgba(37, 99, 235, 0.35),
        0 4px 12px rgba(0, 0, 0, 0.1),
        inset 0 2px 4px rgba(255, 255, 255, 0.9),
        inset 0 -2px 4px rgba(37, 99, 235, 0.2);
    border: 2.5px solid #60a5fa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    animation: mascotFloat 3.5s ease-in-out infinite;
}

.ai-mascot-wrapper:hover .mascot-button {
    transform: scale(1.1) rotate(4deg);
    border-color: #3b82f6;
    box-shadow: 0 15px 30px -3px rgba(37, 99, 235, 0.5);
}

.ai-mascot-wrapper.is-active .mascot-button {
    transform: scale(0.95);
    border-color: #2563eb;
}

.mascot-img {
    width: 62px;
    height: 62px;
    object-fit: cover;
    border-radius: 50%;
    pointer-events: none;
    transform: translateY(2px);
}

.mascot-online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 15px;
    height: 15px;
    background: #22c55e;
    border: 2.5px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
}

.mascot-sparkle-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 22px;
    height: 22px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    animation: spinSlow 8s linear infinite;
}

/* =============================================
   3. CHATBOX POPUP WINDOW
   ============================================= */
.ai-chatbox-window {
    position: absolute;
    bottom: 84px;
    right: 0;
    width: 390px;
    height: 590px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 
        0 20px 50px -10px rgba(15, 23, 42, 0.25),
        0 8px 20px -4px rgba(15, 23, 42, 0.1);
    border: 1.5px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 99995;
    transform-origin: bottom right;
}

/* Header */
.chatbox-header {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #0284c7 100%);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #ffffff;
    position: relative;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.chatbox-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-avatar-wrap {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffffff;
    padding: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.header-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.header-online-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 11px;
    height: 11px;
    background: #22c55e;
    border: 2px solid #ffffff;
    border-radius: 50%;
}

.header-title-wrap {
    display: flex;
    flex-direction: column;
}

.header-title {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    line-height: 1.3;
    color: #ffffff;
    letter-spacing: -0.2px;
}

.header-status {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.85);
    margin: 2px 0 0 0;
    display: flex;
    align-items: center;
}

.chatbox-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.header-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.header-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.header-btn-close:hover {
    background: rgba(239, 68, 68, 0.8);
}

/* Body / Message stream */
.chatbox-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chat-msg-row {
    display: flex;
    gap: 10px;
    max-width: 100%;
}

.chat-msg-row.msg-user {
    justify-content: flex-end;
}

.chat-msg-row.msg-ai {
    justify-content: flex-start;
}

.msg-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ffffff;
    border: 1.5px solid #dbeafe;
    overflow: hidden;
    flex-shrink: 0;
    padding: 1px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.msg-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.msg-bubble-wrap {
    display: flex;
    flex-direction: column;
    max-width: 82%;
}

.chat-msg-row.msg-user .msg-bubble-wrap {
    align-items: flex-end;
}

.msg-bubble {
    padding: 12px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    position: relative;
    word-break: break-word;
}

.chat-msg-row.msg-user .msg-bubble {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.chat-msg-row.msg-ai .msg-bubble {
    background: #ffffff;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.msg-text-content p {
    margin: 0;
}

.msg-time {
    display: block;
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.65;
    text-align: right;
}

/* Filter tags */
.ai-filter-tags {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
}

.filter-tags-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-right: 2px;
}

.filter-tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 7px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    color: #334155;
}

/* Room Cards Inside Chat */
.room-cards-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
    width: 100%;
}

.chat-room-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
}

.chat-room-card:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
}

.card-thumb-wrap {
    position: relative;
    width: 100%;
    height: 105px;
    background: #f1f5f9;
    overflow: hidden;
}

.card-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.chat-room-card:hover .card-thumb-img {
    transform: scale(1.04);
}

.card-status-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    border: 1px solid;
    backdrop-filter: blur(4px);
}

.card-status-residents {
    background: #dbeafe !important;
    color: #1d4ed8 !important;
    border-color: #93c5fd !important;
}

.card-floor-tag {
    font-size: 11px;
    font-weight: 600;
    background: #ede9fe;
    color: #6d28d9;
    padding: 2px 6px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.card-match-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.card-info-wrap {
    padding: 10px 12px;
}

.card-room-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.card-price {
    font-size: 13.5px;
    font-weight: 800;
    color: #e11d48;
}

.card-area {
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    padding: 2px 6px;
    border-radius: 6px;
}

.card-address {
    font-size: 11.5px;
    color: #64748b;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-action-row {
    border-top: 1px dashed #f1f5f9;
    padding-top: 6px;
    display: flex;
    justify-content: flex-end;
}

.card-btn-view {
    font-size: 11.5px;
    font-weight: 700;
    color: #2563eb;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: gap 0.2s ease;
}

.chat-room-card:hover .card-btn-view {
    gap: 7px;
    color: #1d4ed8;
}

.chat-view-all-btn {
    width: 100%;
    padding: 9px 12px;
    background: #eff6ff;
    border: 1.5px dashed #93c5fd;
    border-radius: 10px;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.chat-view-all-btn:hover {
    background: #dbeafe;
    border-color: #3b82f6;
}

/* Suggestion Chips */
.msg-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.sug-chip-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 5px 10px;
    border-radius: 14px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.sug-chip-btn:hover {
    background: #eff6ff;
    border-color: #60a5fa;
    color: #2563eb;
    transform: translateY(-1px);
}

/* Typing Bubble */
.msg-typing-bubble {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
}

.typing-dots {
    display: flex;
    gap: 4px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background: #3b82f6;
    border-radius: 50%;
    animation: typingBlink 1.4s infinite ease-in-out both;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

.typing-text {
    font-size: 11.5px;
    color: #64748b;
    font-style: italic;
}

/* Footer / Input Area */
.chatbox-footer {
    padding: 12px 14px 14px 14px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}

.chat-input-form {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    border: 1.5px solid #cbd5e1;
    border-radius: 24px;
    padding: 4px 6px 4px 14px;
    transition: all 0.2s;
}

.chat-input-form:focus-within {
    background: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* INPUT HOÀN TOÀN KHÔNG VIỀN, KHÔNG VUÔNG BÊN TRONG */
.chat-input-field {
    flex: 1;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    -webkit-appearance: none !important;
    font-size: 13px;
    color: #1e293b;
    font-family: inherit;
    padding: 6px 0 !important;
}

.chat-input-field:focus {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.chat-input-field::placeholder {
    color: #94a3b8;
    font-size: 12.5px;
}

.chat-send-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.chat-send-btn:hover:not(:disabled) {
    transform: scale(1.08);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
}

.chat-send-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.7;
}

/* =============================================
   ANIMATIONS & TRANSITIONS
   ============================================= */
@keyframes mascotFloat {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-6px);
    }
}

@keyframes gentleBob {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

@keyframes pulseGlow {
    0% {
        transform: scale(0.95);
        opacity: 0.8;
    }
    50% {
        transform: scale(1.15);
        opacity: 0.3;
    }
    100% {
        transform: scale(0.95);
        opacity: 0.8;
    }
}

@keyframes spinSlow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes typingBlink {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1.0); }
}

/* Chat Scale Transition */
.chat-scale-enter-active,
.chat-scale-leave-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.chat-scale-enter-from,
.chat-scale-leave-to {
    opacity: 0;
    transform: scale(0.6) translateY(40px);
}

/* Fade Slide Transition */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Custom Scrollbar inside chat */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Mobile */
@media (max-width: 768px) {
    .ai-assistant-root {
        bottom: 85px;
        right: 12px;
    }
    .ai-chatbox-window {
        width: calc(100vw - 24px);
        height: calc(100vh - 110px);
        max-height: 520px;
        bottom: 74px;
        right: 0;
    }
    .ai-greeting-bubble {
        right: 0;
        bottom: 76px;
        max-width: 230px;
    }
}
</style>
