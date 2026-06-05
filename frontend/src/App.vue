<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import LandingLayout from '@/layouts/LandingLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'

const authStore = useAuthStore()
const route = useRoute()

const currentLayout = computed(() => {
  if (route.meta.layout === 'landing') {
    return LandingLayout
  }
  // Layout minimal (sans la nav scopée entreprise) pour les écrans hors-entreprise
  if (route.meta.layout === 'blank') {
    return BlankLayout
  }
  return authStore.isAuthenticated ? MainLayout : AuthLayout
})

// Clé unique par entreprise : force le remontage des composants au changement d'entreprise
const routerViewKey = computed(() => route.params.entrepriseSlug as string || route.name as string)
</script>

<template>
  <component :is="currentLayout">
    <RouterView :key="routerViewKey" />
  </component>
</template>
