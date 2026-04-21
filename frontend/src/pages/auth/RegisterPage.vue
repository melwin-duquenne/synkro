<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const email = ref("");
const password = ref("");
const displayName = ref("");

async function handleSubmit() {
  const success = await authStore.register({
    email: email.value,
    password: password.value,
    displayName: displayName.value,
  });
  if (success) {
    const slug = authStore.getFirstEntrepriseSlug()
    router.push(slug ? { name: 'dashboard', params: { entrepriseSlug: slug } } : '/')
  }
}
</script>

<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title justify-center text-2xl mb-4">Créer un compte</h2>

      <div v-if="authStore.error" class="alert alert-error mb-4">
        <span>{{ authStore.error }}</span>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text">Nom d'affichage</span>
          </label>
          <input
            v-model="displayName"
            type="text"
            placeholder="Votre nom"
            class="input input-bordered w-full"
            required
          />
        </div>

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
          {{ authStore.loading ? 'Création...' : 'Créer mon compte' }}
        </button>
      </form>

      <div class="divider">OU</div>

      <p class="text-center">
        Déjà un compte ?
        <router-link to="/login" class="link link-primary"
          >Se connecter</router-link
        >
      </p>
    </div>
  </div>
</template>
