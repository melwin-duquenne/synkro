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
  <div
    class="rounded-2xl border border-white/10 bg-[#0f1f35]/80 backdrop-blur-sm shadow-2xl p-8 space-y-6"
  >
    <!-- Success state -->
    <template v-if="sent">
      <div class="flex justify-center">
        <div
          class="w-16 h-16 rounded-full bg-emerald-500/15 border border-emerald-400/30 flex items-center justify-center"
        >
          <svg
            class="w-8 h-8 text-emerald-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
        </div>
      </div>

      <div class="text-center space-y-2">
        <h2 class="text-2xl font-bold text-[#e7f0fa]">Email envoyé</h2>
        <p class="text-[#8ea4be] text-sm leading-relaxed">
          Si un compte existe avec cet email, un lien de réinitialisation a été
          envoyé. Vérifie ta boîte de réception.
        </p>
      </div>

      <div class="text-center">
        <router-link
          to="/login"
          class="text-sm text-[#408ed6] hover:text-[#5ba3e8] transition-colors"
        >
          ← Retour à la connexion
        </router-link>
      </div>
    </template>

    <!-- Form state -->
    <template v-else>
      <!-- Icon -->
      <div class="flex justify-center">
        <div
          class="w-16 h-16 rounded-full bg-[#408ed6]/15 border border-[#408ed6]/30 flex items-center justify-center"
        >
          <svg
            class="w-8 h-8 text-[#408ed6]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
            />
          </svg>
        </div>
      </div>

      <!-- Heading -->
      <div class="text-center space-y-2">
        <h2 class="text-2xl font-bold text-[#e7f0fa]">Mot de passe oublié</h2>
        <p class="text-[#8ea4be] text-sm leading-relaxed">
          Entrez votre adresse email. Vous recevrez un lien pour réinitialiser
          votre mot de passe.
        </p>
      </div>

      <!-- Error -->
      <div
        v-if="authStore.error"
        class="rounded-lg border border-red-400/30 bg-red-500/15 p-3 flex gap-2 items-start"
      >
        <svg
          class="w-5 h-5 text-red-400 shrink-0"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="text-red-200 text-sm">{{ authStore.error }}</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="space-y-2">
          <label class="label">
            <span class="label-text text-[#dbe6f2] font-medium text-sm">Email</span>
          </label>
          <input
            v-model="email"
            type="email"
            placeholder="votre@email.com"
            class="w-full px-4 py-3 rounded-lg border border-white/10 bg-[#0a1628]/50 text-[#e0e0e0] placeholder-[#5a7a98] focus:outline-none focus:ring-2 focus:ring-[#408ed6] focus:border-transparent transition-all"
            required
          />
        </div>

        <button
          type="submit"
          class="w-full py-3 px-4 rounded-lg font-medium text-sm text-white bg-[#408ed6] hover:bg-[#5ba3e8] disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          :disabled="authStore.loading"
        >
          <span
            v-if="authStore.loading"
            class="loading loading-spinner loading-sm"
          ></span>
          {{ authStore.loading ? "Envoi en cours..." : "Envoyer le lien" }}
        </button>
      </form>

      <!-- Divider -->
      <div class="relative">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-3 bg-[#0f1f35]/80 text-[#8ea4be]">OU</span>
        </div>
      </div>

      <p class="text-center text-sm text-[#8ea4be]">
        <router-link
          to="/login"
          class="text-[#408ed6] font-medium hover:text-[#5ba3e8] transition-colors"
        >
          ← Retour à la connexion
        </router-link>
      </p>
    </template>
  </div>
</template>
