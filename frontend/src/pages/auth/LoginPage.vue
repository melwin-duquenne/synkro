<script setup lang="ts">
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import GoogleLogin from "@/components/GoogleLogin.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const email = ref("");
const password = ref("");

async function handleSubmit() {
  const success = await authStore.login({
    email: email.value,
    password: password.value,
  });
  if (success) {
    const slug = authStore.getFirstEntrepriseSlug();
    const redirect = route.query.redirect as string;
    router.push(
      redirect ||
        (slug ? { name: "dashboard", params: { entrepriseSlug: slug } } : "/"),
    );
  }
}
</script>

<template>
  <div
    class="rounded-2xl border border-white/10 bg-[#0f1f35]/80 backdrop-blur-sm shadow-2xl p-8 space-y-6"
  >
    <!-- Title -->
    <div class="text-center">
      <h2 class="text-2xl font-bold text-[#e7f0fa]">
        Connexion à votre compte
      </h2>
      <p class="text-[#8ea4be] text-sm mt-2">
        Entrez vos identifiants pour continuer
      </p>
    </div>

    <!-- Error Alert -->
    <div
      v-if="authStore.error"
      class="alert bg-red-500/15 border border-red-400/30 rounded-lg p-3"
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
        ></path>
      </svg>
      <span class="text-red-200 text-sm">{{ authStore.error }}</span>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Email -->
      <div class="space-y-2">
        <label class="label">
          <span class="label-text text-[#dbe6f2] font-medium text-sm"
            >Email</span
          >
        </label>
        <input
          v-model="email"
          type="email"
          placeholder="votre@email.com"
          class="w-full px-4 py-3 rounded-lg border border-white/10 bg-[#0a1628]/50 text-[#e0e0e0] placeholder-[#5a7a98] focus:outline-none focus:ring-2 focus:ring-[#408ed6] focus:border-transparent transition-all"
          required
        />
      </div>

      <!-- Password -->
      <div class="space-y-2">
        <label class="label">
          <span class="label-text text-[#dbe6f2] font-medium text-sm"
            >Mot de passe</span
          >
        </label>
        <input
          v-model="password"
          type="password"
          placeholder="••••••••"
          class="w-full px-4 py-3 rounded-lg border border-white/10 bg-[#0a1628]/50 text-[#e0e0e0] placeholder-[#5a7a98] focus:outline-none focus:ring-2 focus:ring-[#408ed6] focus:border-transparent transition-all"
          required
        />
      </div>

      <!-- Forgot Password Link -->
      <div class="flex justify-end">
        <router-link
          to="/forgot-password"
          class="text-xs text-[#408ed6] hover:text-[#5ba3e8] transition-colors"
        >
          Mot de passe oublié ?
        </router-link>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="btn w-full mt-2"
        :class="{ loading: authStore.loading }"
        :disabled="authStore.loading"
      >
        <span
          v-if="authStore.loading"
          class="loading loading-spinner loading-sm"
        ></span>
        {{ authStore.loading ? "Connexion..." : "Se connecter" }}
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

    <!-- Google Login -->
    <GoogleLogin />

    <!-- Sign Up Link -->
    <p class="text-center text-[#8ea4be] text-sm">
      Pas encore de compte ?
      <router-link
        to="/register"
        class="text-[#408ed6] font-medium hover:text-[#5ba3e8] transition-colors"
      >
        Créer un compte
      </router-link>
    </p>
  </div>
</template>
