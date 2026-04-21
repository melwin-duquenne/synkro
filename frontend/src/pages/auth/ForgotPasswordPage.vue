<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

const email = ref("");
const sent = ref(false);

async function handleSubmit() {
  const success = await authStore.requestResetPassword(email.value);
  if (success) {
    sent.value = true;
  }
}
</script>

<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title justify-center text-2xl mb-4">
        Mot de passe oublié
      </h2>

      <div v-if="sent" class="alert alert-success">
        <span
          >Si un compte existe avec cet email, un lien de réinitialisation a été
          envoyé.</span
        >
      </div>

      <div v-if="authStore.error" class="alert alert-error mb-4">
        <span>{{ authStore.error }}</span>
      </div>

      <template v-if="!sent">
        <p class="text-base-content/70 mb-4">
          Entrez votre adresse email. Vous recevrez un lien pour réinitialiser
          votre mot de passe.
        </p>

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

          <button
            type="submit"
            class="btn btn-primary w-full"
            :disabled="authStore.loading"
          >
            <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
            {{ authStore.loading ? 'Envoi...' : 'Envoyer le lien' }}
          </button>
        </form>
      </template>

      <div class="divider">OU</div>

      <p class="text-center">
        <router-link to="/login" class="link link-primary"
          >Retour à la connexion</router-link
        >
      </p>
    </div>
  </div>
</template>
