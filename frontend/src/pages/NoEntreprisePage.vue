<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const refreshing = ref(false)
const noNewEntreprise = ref(false)

async function refresh() {
  refreshing.value = true
  noNewEntreprise.value = false
  await authStore.fetchUser()
  const slug = authStore.getFirstEntrepriseSlug()
  if (slug) {
    router.push({ name: 'dashboard', params: { entrepriseSlug: slug } })
  } else {
    noNewEntreprise.value = true
  }
  refreshing.value = false
}

function logout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div
    class="min-h-screen bg-linear-to-br from-[#0a1628] to-[#0f1f35] flex items-center justify-center p-4 relative overflow-hidden"
  >
    <!-- Background decorative blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-[#408ed6]/20 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-[#2a5aa8]/20 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#1f4b7c]/15 rounded-full blur-3xl"></div>
      <div class="absolute -top-32 left-1/4 w-64 h-64 bg-[#408ed6]/15 rounded-full blur-2xl"></div>
      <div class="absolute -bottom-32 right-1/4 w-72 h-72 bg-[#2a5aa8]/15 rounded-full blur-2xl"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
      <!-- Branding -->
      <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-[#e7f0fa] tracking-tight">Synkro</h1>
        <p class="text-[#8ea4be] mt-2 text-sm">Plateforme collaborative temps réel</p>
      </div>

      <!-- Card -->
      <div class="rounded-2xl border border-white/10 bg-[#0f1f35]/80 backdrop-blur-sm shadow-2xl p-8 space-y-6">
        <!-- Icon -->
        <div class="flex justify-center">
          <div class="w-16 h-16 rounded-full bg-[#408ed6]/15 border border-[#408ed6]/30 flex items-center justify-center">
            <svg class="w-8 h-8 text-[#408ed6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
        </div>

        <!-- Heading -->
        <div class="text-center space-y-2">
          <h2 class="text-2xl font-bold text-[#e7f0fa]">Aucune entreprise</h2>
          <p class="text-[#8ea4be] text-sm leading-relaxed">
            Bonjour <span class="text-[#dbe6f2] font-medium">{{ authStore.user?.displayName }}</span> —
            ton compte est bien créé, mais tu n'es membre d'aucune entreprise pour le moment.
          </p>
        </div>

        <!-- Info box -->
        <div class="rounded-lg border border-white/10 bg-[#0a1628]/50 p-4">
          <div class="flex gap-3">
            <svg class="w-5 h-5 text-[#408ed6] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-[#8ea4be] text-sm leading-relaxed">
              Pour accéder à Synkro, demande à un administrateur de t'inviter par e-mail.
              Une fois l'invitation acceptée, ton espace apparaîtra ici.
            </p>
          </div>
        </div>

        <!-- Warning -->
        <div
          v-if="noNewEntreprise"
          class="rounded-lg border border-amber-400/30 bg-amber-500/10 p-3 flex gap-2 items-start"
        >
          <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd" />
          </svg>
          <p class="text-amber-200 text-sm">
            Toujours aucune entreprise. Vérifie que ton invitation a bien été acceptée.
          </p>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
          <button
            class="w-full py-3 px-4 rounded-lg font-medium text-sm text-white bg-[#408ed6] hover:bg-[#5ba3e8] disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            :disabled="refreshing"
            @click="refresh"
          >
            <span v-if="refreshing" class="loading loading-spinner loading-sm"></span>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            J'ai été invité·e — rafraîchir
          </button>

          <button
            class="w-full py-3 px-4 rounded-lg font-medium text-sm text-[#8ea4be] hover:text-[#dbe6f2] border border-white/10 hover:border-white/20 bg-transparent transition-colors"
            @click="logout"
          >
            Se déconnecter
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
