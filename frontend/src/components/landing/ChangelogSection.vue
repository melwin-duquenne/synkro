<script setup lang="ts">
import { ref } from 'vue'

interface Change {
  type: 'feature' | 'improvement' | 'fix'
  text: string
}

interface ChangelogEntry {
  version: string
  date: string
  isNew?: boolean
  changes: Change[]
}

const changelogs = ref<ChangelogEntry[]>([
  {
    version: '2.1.0',
    date: '26 janvier 2026',
    isNew: true,
    changes: [
      { type: 'feature', text: 'Nouveau module Whiteboard collaboratif' },
      { type: 'feature', text: 'Chat flottant avec notifications' },
      { type: 'improvement', text: 'Amélioration des performances du temps réel' },
      { type: 'improvement', text: 'Interface utilisateur rafraîchie' },
      { type: 'fix', text: 'Correction du bug de synchronisation du calendrier' }
    ]
  },
  {
    version: '2.0.0',
    date: '15 décembre 2025',
    changes: [
      { type: 'feature', text: 'Refonte complète de l\'interface utilisateur' },
      { type: 'feature', text: 'Support du mode sombre' },
      { type: 'feature', text: 'Nouveau système de layouts personnalisables' },
      { type: 'improvement', text: 'Performance de l\'éditeur améliorée de 300%' },
      { type: 'fix', text: 'Corrections de bugs critiques' }
    ]
  },
  {
    version: '1.5.0',
    date: '3 novembre 2025',
    changes: [
      { type: 'feature', text: 'Module de gestion des fichiers' },
      { type: 'feature', text: 'Intégration OAuth Google' },
      { type: 'improvement', text: 'Amélioration de la gestion des permissions' },
      { type: 'fix', text: 'Corrections diverses' }
    ]
  },
  {
    version: '1.0.0',
    date: '1 octobre 2025',
    changes: [
      { type: 'feature', text: 'Lancement de Synkro 🎉' },
      { type: 'feature', text: 'Éditeur collaboratif temps réel' },
      { type: 'feature', text: 'Chat instantané' },
      { type: 'feature', text: 'Gestion des tâches' },
      { type: 'feature', text: 'Calendrier partagé' }
    ]
  }
])

function getChangeIcon(type: string): string {
  const icons = {
    feature: 'M13 10V3L4 14h7v7l9-11h-7z',
    improvement: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    fix: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
  }
  return icons[type as keyof typeof icons] || icons.feature
}

function getChangeBadgeClass(type: string): string {
  const classes = {
    feature: 'badge-primary',
    improvement: 'badge-info',
    fix: 'badge-success'
  }
  return classes[type as keyof typeof classes] || 'badge-ghost'
}

function getChangeLabel(type: string): string {
  const labels = {
    feature: 'Nouveau',
    improvement: 'Amélioration',
    fix: 'Correction'
  }
  return labels[type as keyof typeof labels] || 'Nouveau'
}
</script>

<template>
  <section id="changelog" class="py-20 lg:py-32 bg-base-100">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4">
          Changelog
        </h2>
        <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
          Découvrez les dernières améliorations et nouveautés de Synkro
        </p>
      </div>

      <!-- Timeline -->
      <div class="max-w-4xl mx-auto">
        <div class="space-y-8">
          <div
            v-for="(entry, index) in changelogs"
            :key="index"
            class="relative"
          >
            <!-- Timeline line -->
            <div 
              v-if="index < changelogs.length - 1"
              class="absolute left-6 top-14 bottom-0 w-0.5 bg-base-300"
            ></div>

            <!-- Entry Card -->
            <div class="flex gap-6">
              <!-- Timeline dot -->
              <div class="flex-none relative z-10">
                <div 
                  class="w-12 h-12 rounded-full flex items-center justify-center"
                  :class="entry.isNew ? 'bg-primary text-primary-content ring-4 ring-primary/20' : 'bg-base-300'"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 pb-8">
                <div class="card bg-base-200 shadow-lg hover:shadow-xl transition-shadow">
                  <div class="card-body">
                    <!-- Header -->
                    <div class="flex items-center gap-3 mb-4">
                      <h3 class="card-title text-2xl">v{{ entry.version }}</h3>
                      <div 
                        v-if="entry.isNew"
                        class="badge badge-primary badge-lg gap-1"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Nouveau
                      </div>
                      <div class="text-base-content/60 text-sm ml-auto">
                        {{ entry.date }}
                      </div>
                    </div>

                    <!-- Changes -->
                    <div class="space-y-2">
                      <div
                        v-for="(change, changeIndex) in entry.changes"
                        :key="changeIndex"
                        class="flex items-start gap-3 p-2 rounded-lg hover:bg-base-300 transition-colors"
                      >
                        <!-- Icon -->
                        <div class="flex-none mt-0.5">
                          <svg 
                            class="w-5 h-5"
                            :class="{
                              'text-primary': change.type === 'feature',
                              'text-info': change.type === 'improvement',
                              'text-success': change.type === 'fix'
                            }"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                          >
                            <path 
                              stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="2" 
                              :d="getChangeIcon(change.type)" 
                            />
                          </svg>
                        </div>
                        
                        <!-- Text -->
                        <div class="flex-1">
                          <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-sm" :class="getChangeBadgeClass(change.type)">
                              {{ getChangeLabel(change.type) }}
                            </span>
                            <span>{{ change.text }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- View all link -->
        <div class="text-center mt-12">
          <button class="btn btn-outline gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Voir tous les changelogs
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
