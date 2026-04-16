<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvitationStore } from '@/stores/invitation'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const invitationStore = useInvitationStore()
const authStore = useAuthStore()

const status = ref<'loading' | 'success' | 'login_required' | 'error'>('loading')
const message = ref('')
const entrepriseName = ref('')
const entrepriseSlug = ref('')
const invitationEmail = ref('')

onMounted(async () => {
  const token = route.query.token as string

  if (!token) {
    status.value = 'error'
    message.value = 'Token d\'invitation manquant'
    return
  }

  const result = await invitationStore.acceptInvitation(token)

  if (!result.success) {
    status.value = 'error'
    message.value = invitationStore.error || 'Erreur lors de l\'acceptation de l\'invitation'
    return
  }

  if (result.data?.accepted) {
    status.value = 'success'
    message.value = result.data.message
    entrepriseSlug.value = result.data?.entrepriseSlug || ''
  } else {
    status.value = 'login_required'
    entrepriseName.value = result.data?.entrepriseName || ''
    entrepriseSlug.value = result.data?.entrepriseSlug || ''
    invitationEmail.value = result.data?.email || ''
    message.value = result.data?.message || ''
  }
})

function goToLogin() {
  const token = route.query.token as string
  router.push({ name: 'login', query: { redirect: `/invitation/accept?token=${token}` } })
}

function goToRegister() {
  router.push({ name: 'register' })
}

function goToDashboard() {
  const slug = entrepriseSlug.value || authStore.getFirstEntrepriseSlug()
  router.push(slug ? { name: 'dashboard', params: { entrepriseSlug: slug } } : '/')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-base-200 p-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
      <div class="card-body text-center">
        <!-- Loading -->
        <div v-if="status === 'loading'" class="py-8">
          <span class="loading loading-spinner loading-lg"></span>
          <p class="mt-4 text-base-content/70">Traitement de l'invitation...</p>
        </div>

        <!-- Success -->
        <div v-else-if="status === 'success'">
          <div class="text-5xl mb-4">&#10003;</div>
          <h2 class="card-title justify-center text-xl mb-2">Invitation acceptée</h2>
          <p class="text-base-content/70 mb-6">{{ message }}</p>
          <button class="btn btn-primary w-full" @click="goToDashboard">
            Aller au tableau de bord
          </button>
        </div>

        <!-- Login required -->
        <div v-else-if="status === 'login_required'">
          <h2 class="card-title justify-center text-xl mb-2">Rejoindre {{ entrepriseName }}</h2>
          <p class="text-base-content/70 mb-6">{{ message }}</p>
          <div class="flex flex-col gap-3">
            <button class="btn btn-primary w-full" @click="goToLogin">
              Se connecter
            </button>
            <button class="btn btn-outline w-full" @click="goToRegister">
              Créer un compte
            </button>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="status === 'error'">
          <div class="text-5xl mb-4 text-error">&#10007;</div>
          <h2 class="card-title justify-center text-xl mb-2">Erreur</h2>
          <p class="text-error mb-6">{{ message }}</p>
          <button class="btn btn-ghost w-full" @click="router.push('/')">
            Retour à l'accueil
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
