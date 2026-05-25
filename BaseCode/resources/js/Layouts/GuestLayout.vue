<template>
    <div class="guest-layout">
        <slot />
    </div>
</template>

<style scoped>
.guest-layout {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
}
</style>
