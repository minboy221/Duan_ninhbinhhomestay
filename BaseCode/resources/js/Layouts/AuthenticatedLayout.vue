<script setup>
// Alias cho MainLayout - dùng cho các page đã đăng nhập
</script>

<template>
    <MainLayoutComponent>
        <slot />
    </MainLayoutComponent>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    components: { MainLayoutComponent: MainLayout }
}
</script>
