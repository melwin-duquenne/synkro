<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Feature {
  icon: string
  title: string
  description: string
  color: string
}

const features = ref<Feature[]>([
  {
    icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    title: 'Éditeur collaboratif',
    description: 'Écrivez ensemble en temps réel avec synchronisation instantanée. Voyez les curseurs de vos collègues.',
    color: 'primary'
  },
  {
    icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    title: 'Chat instantané',
    description: 'Communiquez rapidement avec votre équipe. Messages synchronisés en temps réel sur tous les appareils.',
    color: 'secondary'
  },
  {
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    title: 'Gestion des tâches',
    description: 'Organisez votre travail avec des tâches partagées. Drag & drop, statuts, assignations et bien plus.',
    color: 'accent'
  },
  {
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    title: 'Calendrier partagé',
    description: 'Planifiez vos réunions et événements. Vue synchronisée pour toute l\'équipe avec notifications.',
    color: 'info'
  },
  {
    icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    title: 'Tableau blanc',
    description: 'Dessinez, schématisez et brainstormez ensemble sur un canvas collaboratif infini.',
    color: 'success'
  },
  {
    icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    title: 'Partage de fichiers',
    description: 'Centralisez vos documents. Upload, partage et gestion des fichiers en toute sécurité.',
    color: 'warning'
  }
])

const visibleCards = ref<boolean[]>([])

onMounted(() => {
  visibleCards.value = new Array(features.value.length).fill(false)
  
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const index = parseInt(entry.target.getAttribute('data-index') || '0')
          setTimeout(() => {
            visibleCards.value[index] = true
          }, index * 100)
        }
      })
    },
    { threshold: 0.1 }
  )

  document.querySelectorAll('.feature-card').forEach((card) => {
    observer.observe(card)
  })
})
</script>

<template>
  <section id="features" class="py-20 lg:py-32 bg-base-100">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4">
          Tout ce dont vous avez besoin
        </h2>
        <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
          Une suite complète d'outils pour booster la productivité de votre équipe
        </p>
      </div>

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <div
          v-for="(feature, index) in features"
          :key="index"
          :data-index="index"
          class="feature-card card bg-base-200 hover:bg-base-300 shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer group"
          :class="{ 'opacity-0 translate-y-10': !visibleCards[index], 'opacity-100 translate-y-0': visibleCards[index] }"
          style="transition: opacity 0.6s ease-out, transform 0.6s ease-out;"
        >
          <div class="card-body">
            <!-- Icon -->
            <div 
              class="w-14 h-14 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform"
              :class="`bg-${feature.color}/20`"
            >
              <svg 
                class="w-8 h-8"
                :class="`text-${feature.color}`"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  :d="feature.icon" 
                />
              </svg>
            </div>

            <!-- Content -->
            <h3 class="card-title text-xl mb-2">{{ feature.title }}</h3>
            <p class="text-base-content/70">{{ feature.description }}</p>

            <!-- Arrow indicator on hover -->
            <div class="mt-4 flex items-center gap-2 text-primary opacity-0 group-hover:opacity-100 transition-opacity">
              <span class="text-sm font-medium">En savoir plus</span>
              <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom CTA -->
      <div class="text-center mt-16">
        <div class="inline-flex items-center gap-2 px-6 py-3 bg-primary/10 rounded-full">
          <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span class="font-medium">Et bien plus de fonctionnalités à venir !</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.feature-card {
  transform-origin: center;
}
</style>
