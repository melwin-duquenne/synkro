<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const token = computed(() => (route.query.token as string) || "");
const confirmed = ref(false);
const deleted = ref(false);

async function handleConfirm() {
  if (!token.value) return;

  const ok = await authStore.confirmDeleteAccount(token.value);
  if (ok) {
    deleted.value = true;
    setTimeout(() => {
      router.push("/login");
    }, 3000);
  }
}
</script>

<template>
  <div class="min-h-screen bg-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-primary">Synkro</h1>
      </div>

      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title justify-center text-2xl mb-4">
            Suppression de compte
          </h2>

          <div v-if="deleted" class="alert alert-success">
            <span
              >Votre compte a été supprimé. Redirection vers la page de
              connexion...</span
            >
          </div>

          <div v-else-if="!token" class="alert alert-error">
            <span
              >Lien invalide. Veuillez utiliser le lien reçu par email.</span
            >
          </div>

          <template v-else>
            <div v-if="authStore.error" class="alert alert-error mb-4">
              <span>{{ authStore.error }}</span>
            </div>

            <div v-if="!confirmed" class="space-y-4">
              <div class="alert alert-warning">
                <span
                  >Cette action est irréversible. Toutes vos données seront
                  supprimées définitivement.</span
                >
              </div>

              <div class="flex gap-2 justify-center">
                <router-link
                  to="/dashboard"
                  class="btn btn-ghost bg-white text-black border-0"
                  >Annuler</router-link
                >
                <button
                  class="btn btn-error bg-white text-black border-0"
                  @click="confirmed = true"
                >
                  Je comprends, continuer
                </button>
              </div>
            </div>

            <div v-else class="space-y-4">
              <p class="text-center text-base-content/70">
                Cliquez sur le bouton ci-dessous pour confirmer la suppression
                définitive de votre compte.
              </p>

              <button
                class="btn btn-error w-full"
                :disabled="authStore.loading"
                @click="handleConfirm"
              >
                <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
                {{ authStore.loading ? 'Suppression...' : 'Supprimer définitivement mon compte' }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>
