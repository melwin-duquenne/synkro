<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

function goToFirstEntreprise() {
  const slug = authStore.getFirstEntrepriseSlug()
  if (slug) {
    router.push({ name: 'dashboard', params: { entrepriseSlug: slug } })
  } else {
    router.push('/')
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md text-center">
      <div class="card-body">
        <div class="text-6xl mb-4">🔒</div>
        <h1 class="text-2xl font-bold mb-2">Accès refusé</h1>
        <p class="text-base-content/70 mb-6">
          Vous n'êtes pas membre de cette entreprise ou elle n'existe pas.
        </p>
        <div class="card-actions justify-center gap-2">
          <button class="btn btn-primary" @click="goToFirstEntreprise">
            Aller à mon entreprise
          </button>
          <button class="btn btn-ghost" @click="authStore.logout(); $router.push('/login')">
            Se déconnecter
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
