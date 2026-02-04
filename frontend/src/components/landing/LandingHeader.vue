<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)
const activeSection = ref('')

const navItems = [
  { id: 'features', label: 'Fonctionnalités' },
  { id: 'team', label: 'Équipe' },
  { id: 'changelog', label: 'Changelog' },
  { id: 'contact', label: 'Contact' }
]

function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    isMobileMenuOpen.value = false
  }
}

function handleScroll() {
  isScrolled.value = window.scrollY > 50

  // Scroll spy - détecte la section active
  const sections = navItems.map(item => item.id)
  for (const sectionId of sections) {
    const element = document.getElementById(sectionId)
    if (element) {
      const rect = element.getBoundingClientRect()
      if (rect.top <= 150 && rect.bottom >= 150) {
        activeSection.value = sectionId
        break
      }
    }
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

function goToLogin() {
  router.push('/login')
}

function goToRegister() {
  router.push('/register')
}
</script>

<template>
  <header 
    class="sticky top-0 z-50 transition-all duration-300"
    :class="isScrolled ? 'bg-base-100 shadow-lg backdrop-blur-lg bg-opacity-90' : 'bg-transparent'"
  >
    <nav class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-2 cursor-pointer" @click="scrollToSection('hero')">
          <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span class="text-2xl font-bold">Synkro</span>
        </div>

        <!-- Navigation Desktop -->
        <div class="hidden md:flex items-center gap-8">
          <a
            v-for="item in navItems"
            :key="item.id"
            @click="scrollToSection(item.id)"
            class="cursor-pointer transition-colors hover:text-primary font-medium"
            :class="activeSection === item.id ? 'text-primary' : 'text-base-content'"
          >
            {{ item.label }}
          </a>
        </div>

        <!-- Actions Desktop -->
        <div class="hidden md:flex items-center gap-3">
          <button 
            v-if="!authStore.isAuthenticated"
            @click="goToLogin" 
            class="btn btn-ghost btn-sm"
          >
            Se connecter
          </button>
          <button 
            v-if="!authStore.isAuthenticated"
            @click="goToRegister" 
            class="btn btn-primary btn-sm"
          >
            Commencer
          </button>
          <button 
            v-else
            @click="router.push('/rooms')" 
            class="btn btn-primary btn-sm"
          >
            Accéder à l'app
          </button>
        </div>

        <!-- Burger Menu Mobile -->
        <button 
          class="btn btn-ghost btn-square md:hidden"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Menu Mobile -->
      <div 
        v-if="isMobileMenuOpen"
        class="md:hidden mt-4 pb-4 space-y-2 animate-fade-in"
      >
        <a
          v-for="item in navItems"
          :key="item.id"
          @click="scrollToSection(item.id)"
          class="block py-2 px-4 rounded-lg cursor-pointer transition-colors hover:bg-base-200"
          :class="activeSection === item.id ? 'bg-primary text-primary-content' : ''"
        >
          {{ item.label }}
        </a>
        <div class="pt-2 space-y-2">
          <button 
            v-if="!authStore.isAuthenticated"
            @click="goToLogin" 
            class="btn btn-ghost btn-block btn-sm"
          >
            Se connecter
          </button>
          <button 
            v-if="!authStore.isAuthenticated"
            @click="goToRegister" 
            class="btn btn-primary btn-block btn-sm"
          >
            Commencer
          </button>
          <button 
            v-else
            @click="router.push('/rooms')" 
            class="btn btn-primary btn-block btn-sm"
          >
            Accéder à l'app
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>
