<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const refreshing = ref(false)
const noNewEntreprise = ref(false)

// Au cas où l'utilisateur vient d'être invité : on re-fetch et on file au dashboard.
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
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md text-center">
      <div class="card-body">
        <div class="text-6xl mb-4">📭</div>
        <h1 class="text-2xl font-bold mb-2">Aucune entreprise</h1>
        <p class="text-base-content/70 mb-2">
          Bonjour {{ authStore.user?.displayName }} — ton compte est bien créé,
          mais tu n'es membre d'aucune entreprise pour le moment.
        </p>
        <p class="text-base-content/60 text-sm mb-6">
          Pour accéder à Synkro, demande à un administrateur de t'inviter par e-mail.
          Une fois l'invitation acceptée, ton espace apparaîtra ici.
        </p>

        <p v-if="noNewEntreprise" class="text-warning text-sm mb-4">
          Toujours aucune entreprise. Vérifie que ton invitation a bien été acceptée.
        </p>

        <div class="card-actions justify-center gap-2">
          <button class="btn btn-primary" :disabled="refreshing" @click="refresh">
            <span v-if="refreshing" class="loading loading-spinner loading-sm"></span>
            J'ai été invité·e — rafraîchir
          </button>
          <button class="btn btn-ghost" @click="logout">Se déconnecter</button>
        </div>
      </div>
    </div>
  </div>
</template>
