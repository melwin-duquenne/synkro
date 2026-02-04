<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const mobileMenuOpen = ref(false)

function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    mobileMenuOpen.value = false
  }
}

function goToLogin() {
  router.push('/login')
}

function goToRegister() {
  router.push('/register')
}
</script>

<template>
  <div class="min-h-screen bg-base-100">
    <!-- Navbar Sticky -->
    <nav class="navbar bg-base-100 shadow-lg sticky top-0 z-50 border-b border-base-300">
      <div class="container mx-auto flex items-center justify-between flex-nowrap">
        <div class="flex-none">
          <a href="#hero" @click.prevent="scrollToSection('hero')" class="btn btn-ghost text-xl font-bold">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Synkro
          </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center gap-2 flex-none">
          <a @click.prevent="scrollToSection('features')" class="btn btn-ghost btn-sm">Fonctionnalités</a>
          <a @click.prevent="scrollToSection('pricing')" class="btn btn-ghost btn-sm">Tarifs</a>
          <a @click.prevent="scrollToSection('team')" class="btn btn-ghost btn-sm">Équipe</a>
          <a @click.prevent="scrollToSection('changelog')" class="btn btn-ghost btn-sm">Changelog</a>
          <a @click.prevent="scrollToSection('contact')" class="btn btn-ghost btn-sm">Contact</a>
          <div class="divider divider-horizontal mx-2"></div>
          <button @click="goToLogin" class="btn btn-ghost btn-sm">Se connecter</button>
          <button @click="goToRegister" class="btn btn-primary btn-sm">Commencer</button>
        </div>

        <!-- Mobile Menu Button -->
        <div class="lg:hidden">
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="btn btn-ghost btn-square">
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </nav>

    <!-- Mobile Menu Dropdown -->
    <div v-if="mobileMenuOpen" class="lg:hidden bg-base-200 border-b border-base-300 shadow-lg">
      <div class="container mx-auto py-4 px-4 space-y-2">
        <a @click.prevent="scrollToSection('features')" class="block btn btn-ghost btn-block justify-start">Fonctionnalités</a>
        <a @click.prevent="scrollToSection('pricing')" class="block btn btn-ghost btn-block justify-start">Tarifs</a>
        <a @click.prevent="scrollToSection('team')" class="block btn btn-ghost btn-block justify-start">Équipe</a>
        <a @click.prevent="scrollToSection('changelog')" class="block btn btn-ghost btn-block justify-start">Changelog</a>
        <a @click.prevent="scrollToSection('contact')" class="block btn btn-ghost btn-block justify-start">Contact</a>
        <div class="divider my-2"></div>
        <button @click="goToLogin" class="btn btn-ghost btn-block">Se connecter</button>
        <button @click="goToRegister" class="btn btn-primary btn-block">Commencer</button>
      </div>
    </div>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-base-200 border-t border-base-300 mt-20">
      <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Logo & Description -->
          <div class="md:col-span-1">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <span class="text-xl font-bold">Synkro</span>
            </div>
            <p class="text-base-content/70 text-sm">
              Collaborez mieux ensemble
            </p>
          </div>

          <!-- Produit -->
          <div>
            <h3 class="font-semibold mb-3">Produit</h3>
            <ul class="space-y-2 text-sm">
              <li><a @click.prevent="scrollToSection('features')" class="link link-hover text-base-content/70">Fonctionnalités</a></li>
              <li><a href="#" class="link link-hover text-base-content/70">Tarifs</a></li>
              <li><a @click.prevent="scrollToSection('changelog')" class="link link-hover text-base-content/70">Changelog</a></li>
            </ul>
          </div>

          <!-- Support -->
          <div>
            <h3 class="font-semibold mb-3">Support</h3>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="link link-hover text-base-content/70">Documentation</a></li>
              <li><a href="#" class="link link-hover text-base-content/70">FAQ</a></li>
              <li><a @click.prevent="scrollToSection('contact')" class="link link-hover text-base-content/70">Contact</a></li>
            </ul>
          </div>

          <!-- Légal -->
          <div>
            <h3 class="font-semibold mb-3">Légal</h3>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="link link-hover text-base-content/70">CGU</a></li>
              <li><a href="#" class="link link-hover text-base-content/70">Confidentialité</a></li>
              <li><a href="#" class="link link-hover text-base-content/70">Cookies</a></li>
            </ul>
          </div>
        </div>

        <!-- Bottom Footer -->
        <div class="divider my-8"></div>
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-base-content/60">
          <p>© 2026 Synkro. Tous droits réservés.</p>
          <div class="flex gap-4">
            <a href="#" class="link link-hover">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
            </a>
            <a href="#" class="link link-hover">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </a>
            <a href="#" class="link link-hover">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
