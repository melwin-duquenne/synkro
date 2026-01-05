<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

const authStore = useAuthStore()
</script>

<template>
  <component :is="authStore.isAuthenticated ? MainLayout : AuthLayout">
    <RouterView />
  </component>
</template>
