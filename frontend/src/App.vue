<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import LandingLayout from '@/layouts/LandingLayout.vue'

const authStore = useAuthStore()
const route = useRoute()

const currentLayout = computed(() => {
  // Si on est sur la landing page
  if (route.meta.layout === 'landing') {
    return LandingLayout
  }
  // Si connecté → MainLayout, sinon → AuthLayout
  return authStore.isAuthenticated ? MainLayout : AuthLayout
})
</script>

<template>
  <component :is="currentLayout">
    <RouterView />
  </component>
</template>
