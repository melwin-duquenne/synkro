<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || ''

const avatarUrl = computed(() => {
  if (authStore.user?.avatarUrl) {
    return `${API_BASE}${authStore.user.avatarUrl}`
  }
  return null
})

const isAdmin = computed(() => authStore.user?.role === 'admin')

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
        <router-link to="/dashboard" class="btn btn-ghost text-xl">Synkro</router-link>
      </div>
      <div class="flex-none gap-2">
        <ul class="menu menu-horizontal px-1">
          <li><router-link to="/dashboard">Dashboard</router-link></li>
          <li><router-link to="/rooms">Rooms</router-link></li>
          <li><router-link to="/calendar">Calendrier</router-link></li>
        </ul>
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
            <li><router-link to="/profile">Profil</router-link></li>
            <li v-if="isAdmin"><router-link to="/admin/users">Gestion utilisateurs</router-link></li>
            <li><a @click="handleLogout">Déconnexion</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <main class="container mx-auto p-4">
      <slot />
    </main>
  </div>
</template>
