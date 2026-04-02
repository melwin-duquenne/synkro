<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const token = computed(() => (route.query.token as string) || '')
const password = ref('')
const passwordConfirm = ref('')
const success = ref(false)
const validationError = ref<string | null>(null)

async function handleSubmit() {
  validationError.value = null

  if (!token.value) {
    validationError.value = 'Token manquant. Utilisez le lien reçu par email.'
    return
  }

  if (password.value !== passwordConfirm.value) {
    validationError.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  if (password.value.length < 6) {
    validationError.value = 'Le mot de passe doit contenir au moins 6 caractères.'
    return
  }

  const ok = await authStore.confirmResetPassword(token.value, password.value)
  if (ok) {
    success.value = true
    setTimeout(() => {
      router.push('/login')
    }, 3000)
  }
}
</script>

<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title justify-center text-2xl mb-4">Nouveau mot de passe</h2>

      <div v-if="success" class="alert alert-success">
        <span>Mot de passe réinitialisé avec succès. Redirection vers la connexion...</span>
      </div>

      <div v-if="authStore.error" class="alert alert-error mb-4">
        <span>{{ authStore.error }}</span>
      </div>

      <div v-if="validationError" class="alert alert-warning mb-4">
        <span>{{ validationError }}</span>
      </div>

      <template v-if="!success">
        <div v-if="!token" class="alert alert-error">
          <span>Lien invalide. Veuillez utiliser le lien reçu par email.</span>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Nouveau mot de passe</span>
            </label>
            <input
              v-model="password"
              type="password"
              placeholder="********"
              class="input input-bordered w-full"
              minlength="6"
              required
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Confirmer le mot de passe</span>
            </label>
            <input
              v-model="passwordConfirm"
              type="password"
              placeholder="********"
              class="input input-bordered w-full"
              minlength="6"
              required
            />
          </div>

          <button
            type="submit"
            class="btn btn-primary w-full"
            :disabled="authStore.loading"
          >
            <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
            {{ authStore.loading ? 'Réinitialisation...' : 'Réinitialiser le mot de passe' }}
          </button>
        </form>
      </template>

      <div class="divider">OU</div>

      <p class="text-center">
        <router-link to="/login" class="link link-primary">Retour à la connexion</router-link>
      </p>
    </div>
  </div>
</template>
