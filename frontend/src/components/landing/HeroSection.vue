<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <section id="hero" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/10 via-base-100 to-secondary/10 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-20 left-10 w-72 h-72 bg-primary/20 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 right-10 w-96 h-96 bg-secondary/20 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 py-20 relative z-10">
      <div class="max-w-4xl mx-auto text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-base-200 rounded-full mb-8 animate-fade-in">
          <span class="badge badge-primary badge-sm">Nouveau</span>
          <span class="text-sm">Découvrez notre nouveau module Whiteboard</span>
        </div>

        <!-- Titre principal -->
        <h1 class="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent animate-fade-in-up">
          Collaborez en temps réel,<br>créez ensemble
        </h1>

        <!-- Sous-titre -->
        <p class="text-xl md:text-2xl text-base-content/70 mb-12 max-w-2xl mx-auto animate-fade-in-up" style="animation-delay: 0.1s">
          La plateforme tout-en-un pour vos projets d'équipe. Éditeur collaboratif, chat instantané, tableaux blancs et bien plus encore.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 animate-fade-in-up" style="animation-delay: 0.2s">
          <button @click="router.push('/register')" class="btn btn-primary btn-lg gap-2">
            Commencer gratuitement
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </button>
          <button @click="scrollToSection('features')" class="btn btn-outline btn-lg gap-2">
            En savoir plus
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <!-- Illustration / Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-3xl mx-auto mt-20 animate-fade-in-up" style="animation-delay: 0.3s">
          <div class="stat bg-base-200/50 backdrop-blur rounded-xl p-6">
            <div class="stat-value text-primary">100+</div>
            <div class="stat-title">Équipes actives</div>
          </div>
          <div class="stat bg-base-200/50 backdrop-blur rounded-xl p-6">
            <div class="stat-value text-secondary">7</div>
            <div class="stat-title">Modules intégrés</div>
          </div>
          <div class="stat bg-base-200/50 backdrop-blur rounded-xl p-6">
            <div class="stat-value text-accent">99.9%</div>
            <div class="stat-title">Disponibilité</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
      <svg class="w-6 h-6 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
  </section>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.6s ease-out;
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out;
  animation-fill-mode: both;
}
</style>
