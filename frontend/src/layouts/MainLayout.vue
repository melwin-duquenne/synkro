<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import AiFloatingChat from '@/components/ai/AiFloatingChat.vue'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || ''

const avatarUrl = computed(() => {
  if (authStore.user?.avatarUrl) {
    return `${API_BASE}${authStore.user.avatarUrl}`
  }
  return null
})

const currentSlug = computed(() => route.params.entrepriseSlug as string | undefined)
const isAdmin = computed(() => authStore.currentEntreprise?.role === 'admin')
const hasEntreprises = computed(() => (authStore.user?.entreprises?.length ?? 0) > 0)

function handleSwitchEntreprise(slug: string) {
  if (slug === currentSlug.value) return
  router.push({ name: 'dashboard', params: { entrepriseSlug: slug } })
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-base-200">
    <!-- Navbar -->
    <div class="navbar bg-base-100 shadow-lg">
      <div class="flex-1">
        <router-link :to="currentSlug ? { name: 'dashboard', params: { entrepriseSlug: currentSlug } } : '/'" class="btn btn-ghost text-xl">Synkro</router-link>
      </div>
      <div class="flex-none gap-2">
        <ul class="menu menu-horizontal px-1">
          <li><router-link :to="{ name: 'dashboard', params: { entrepriseSlug: currentSlug } }">Dashboard</router-link></li>
          <li><router-link :to="{ name: 'rooms', params: { entrepriseSlug: currentSlug } }">Rooms</router-link></li>
          <li><router-link :to="{ name: 'calendar', params: { entrepriseSlug: currentSlug } }">Calendrier</router-link></li>
        </ul>
        <!-- Sélecteur d'entreprise -->
        <div v-if="hasEntreprises" class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-1">
            <span class="max-w-32 truncate">{{ authStore.currentEntreprise?.name ?? authStore.user?.entreprises[0]?.name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
          <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-56">
            <li v-for="ue in authStore.user?.entreprises" :key="ue.id">
              <a @click="handleSwitchEntreprise(ue.slug)" class="flex justify-between items-center">
                <span :class="ue.slug === currentSlug ? 'font-semibold' : ''">
                  {{ ue.slug === currentSlug ? '✓ ' : '' }}{{ ue.name }}
                </span>
                <span class="badge badge-sm badge-ghost">{{ ue.role }}</span>
              </a>
            </li>
            <li class="border-t border-base-300 mt-1 pt-1">
              <router-link :to="{ name: 'profile', params: { entrepriseSlug: currentSlug } }" class="text-primary">+ Créer une entreprise</router-link>
            </li>
          </ul>
        </div>
        <div class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
            <div class="rounded-full w-10" :class="avatarUrl ? '' : 'bg-primary text-primary-content'">
              <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar" class="rounded-full" />
              <span v-else>{{ authStore.user?.displayName?.charAt(0)?.toUpperCase() || 'U' }}</span>
            </div>
          </div>
          <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
            <li class="menu-title">
              <span>{{ authStore.user?.displayName }}</span>
              <span class="text-xs opacity-60">{{ authStore.user?.email }}</span>
            </li>
            <li><router-link :to="{ name: 'profile', params: { entrepriseSlug: currentSlug } }">Profil</router-link></li>
            <li v-if="isAdmin"><router-link :to="{ name: 'admin-users', params: { entrepriseSlug: currentSlug } }">Gestion utilisateurs</router-link></li>
            <li v-if="isAdmin">
              <router-link :to="{ name: 'admin-ai-settings', params: { entrepriseSlug: currentSlug } }">
                Paramètres IA
              </router-link>
            </li>
            <li><a @click="handleLogout">Déconnexion</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <main class="container mx-auto p-4">
      <slot />
    </main>

    <AiFloatingChat />
  </div>
</template>
