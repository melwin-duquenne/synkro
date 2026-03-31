<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GoogleLogin from '@/components/GoogleLogin.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')

async function handleSubmit() {
  const success = await authStore.login({ email: email.value, password: password.value })
  if (success) {
    const redirect = route.query.redirect as string || '/dashboard'
    router.push(redirect)
  }
}
</script>

<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title justify-center text-2xl mb-4">Connexion</h2>

      <div v-if="authStore.error" class="alert alert-error mb-4">
        <span>{{ authStore.error }}</span>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text">Email</span>
          </label>
          <input
            v-model="email"
            type="email"
            placeholder="votre@email.com"
            class="input input-bordered w-full"
            required
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Mot de passe</span>
          </label>
          <input
            v-model="password"
            type="password"
            placeholder="********"
            class="input input-bordered w-full"
            required
          />
        </div>

        <div class="text-right">
          <router-link to="/forgot-password" class="link link-sm link-primary">Mot de passe oublié ?</router-link>
        </div>

        <button
          type="submit"
          class="btn btn-primary w-full"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
          {{ authStore.loading ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>

      <div class="divider">OU</div>

      <GoogleLogin />

      <p class="text-center">
        Pas encore de compte ?
        <router-link to="/register" class="link link-primary">Créer un compte</router-link>
      </p>
    </div>
  </div>
</template>
