// resources/js/composables/main.js
// Xử lý các logic chung cho giao diện (Back to Top, Dropdown, Mobile Menu)

import { ref, onMounted, onUnmounted } from 'vue'

// Composable xử lý nút Back to Top
export function useBackToTop() {
    const showBtn = ref(false)

    function onScroll() {
        showBtn.value = window.scrollY > 200
    }

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    onMounted(() => window.addEventListener('scroll', onScroll))
    onUnmounted(() => window.removeEventListener('scroll', onScroll))

    return { showBtn, scrollToTop }
}

// Composable xử lý Dropdown và Thông báo
export function useDropdownMenu() {
    const showDropdown = ref(false)
    const showNotification = ref(false)

    function toggleDropdown() {
        showDropdown.value = !showDropdown.value
        if (showDropdown.value) showNotification.value = false
    }

    function toggleNotification() {
        showNotification.value = !showNotification.value
        if (showNotification.value) showDropdown.value = false
    }

    // Đóng khi click ra ngoài
    function closeAll() {
        showDropdown.value = false
        showNotification.value = false
    }

    onMounted(() => {
        window.addEventListener('click', closeAll)
    })

    onUnmounted(() => {
        window.removeEventListener('click', closeAll)
    })

    return { 
        showDropdown, 
        showNotification, 
        toggleDropdown, 
        toggleNotification,
        closeAll
    }
}

// Composable xử lý Mobile Menu (Drawer)
export function useMobileDrawer() {
    const isOpen = ref(false)

    function toggleDrawer() {
        isOpen.value = !isOpen.value
    }

    function closeDrawer() {
        isOpen.value = false
    }

    return { isOpen, toggleDrawer, closeDrawer }
}
