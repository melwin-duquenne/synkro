<script setup lang="ts">
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const API_URL = import.meta.env.VITE_API_URL || "/api";

const currentEntreprise = authStore.currentEntreprise;

const aiEnabled = ref(currentEntreprise?.aiEnabled ?? false);
const aiMode = ref<"byok" | "platform">(currentEntreprise?.aiMode ?? "byok");
const aiProvider = ref(currentEntreprise?.aiProvider ?? "mistral");
const apiKey = ref("");
const saving = ref(false);
const success = ref<string | null>(null);
const error = ref<string | null>(null);

const providers = [
  { value: "mistral", label: "Mistral AI (recommandé, RGPD EU)" },
];

const tokenUsagePercent = computed(() => {
  const limit = authStore.currentEntreprise?.aiTokensLimit;
  const used = authStore.currentEntreprise?.aiTokensUsed ?? 0;
  if (!limit) return 0;
  return Math.min(100, Math.round((used / limit) * 100));
});

const tokenUsageColor = computed(() => {
  const p = tokenUsagePercent.value;
  if (p >= 90) return "progress-error";
  if (p >= 70) return "progress-warning";
  return "progress-success";
});

async function saveSettings() {
  saving.value = true;
  success.value = null;
  error.value = null;

  try {
    const body: Record<string, unknown> = {
      aiEnabled: aiEnabled.value,
      aiMode: aiMode.value,
      aiProvider: aiProvider.value,
    };
    if (apiKey.value.trim()) {
      body.aiApiKey = apiKey.value.trim();
    }

    const response = await fetch(`${API_URL}/account/entreprise/ai`, {
      method: "PATCH",
      headers: {
        "Content-Type": "application/merge-patch+json",
        ...authStore.getAuthHeaders(),
      },
      body: JSON.stringify(body),
    });

    if (!response.ok) {
      let detail = "Erreur lors de la sauvegarde";
      try {
        const data = await response.json();
        detail = data.detail || data["hydra:description"] || detail;
      } catch {
        /* corps non-JSON */
      }
      throw new Error(detail);
    }

    await authStore.fetchUser();
    if (authStore.currentEntreprise) {
      authStore.setCurrentEntreprise(authStore.currentEntreprise.slug);
    }

    apiKey.value = "";
    success.value = "Paramètres IA sauvegardés.";
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Erreur inconnue";
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen">
    <div class="max-w-2xl mx-auto">
      <h1 class="text-3xl font-bold mb-2 text-white">Paramètres IA</h1>
      <p class="text-gray-400 mb-8">
        Gérez les paramètres d'IA pour votre entreprise
      </p>

      <div
        v-if="success"
        class="alert alert-success mb-4 bg-green-500/20 border border-green-500/50 text-green-300 rounded-lg"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="stroke-current shrink-0 h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ success }}</span>
      </div>
      <div
        v-if="error"
        class="alert alert-error mb-4 bg-red-500/20 border border-red-500/50 text-red-300 rounded-lg"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="stroke-current shrink-0 h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ error }}</span>
      </div>

      <div class="bg-[#0a1628] border border-white/10 rounded-xl shadow-xl">
        <div class="p-8 space-y-8">
          <!-- Activation -->
          <div class="form-control">
            <label class="label cursor-pointer justify-start gap-4 px-0">
              <input
                type="checkbox"
                v-model="aiEnabled"
                class="toggle toggle-primary"
              />
              <span class="label-text text-base font-medium text-white"
                >Activer l'IA pour cette entreprise</span
              >
            </label>
            <p class="text-sm text-gray-400 ml-16">
              Une fois activée, tous les membres pourront utiliser l'assistant
              IA.
            </p>
          </div>

          <div v-if="aiEnabled" class="space-y-8 border-t border-white/10 pt-8">
            <!-- Sélecteur de mode -->
            <div class="form-control">
              <label class="label px-0"
                ><span class="label-text font-medium text-white"
                  >Mode IA</span
                ></label
              >
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="cursor-pointer">
                  <input
                    type="radio"
                    v-model="aiMode"
                    value="byok"
                    class="hidden"
                  />
                  <div
                    class="p-4 rounded-lg border-2 transition-all"
                    :class="
                      aiMode === 'byok'
                        ? 'border-blue-500 bg-blue-500/10'
                        : 'border-white/10 bg-white/5 hover:bg-white/10'
                    "
                  >
                    <div class="font-semibold text-sm mb-1 text-white">
                      Votre clé (BYOK)
                    </div>
                    <div class="text-xs text-gray-400">
                      Utilisez votre propre clé Mistral. Pas de limite de tokens
                      imposée par Synkro.
                    </div>
                  </div>
                </label>
                <label class="cursor-pointer">
                  <input
                    type="radio"
                    v-model="aiMode"
                    value="platform"
                    class="hidden"
                  />
                  <div
                    class="p-4 rounded-lg border-2 transition-all"
                    :class="
                      aiMode === 'platform'
                        ? 'border-blue-500 bg-blue-500/10'
                        : 'border-white/10 bg-white/5 hover:bg-white/10'
                    "
                  >
                    <div class="font-semibold text-sm mb-1 text-white">
                      Clé Synkro (Plan Pro)
                    </div>
                    <div class="text-xs text-gray-400">
                      Synkro gère la clé. Quota mensuel de tokens inclus dans
                      votre plan.
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Quota (mode platform) -->
            <div
              v-if="aiMode === 'platform'"
              class="space-y-3 border-t border-white/10 pt-8"
            >
              <div v-if="authStore.currentEntreprise?.aiTokensLimit">
                <div class="flex justify-between text-sm mb-3">
                  <span class="font-medium text-white"
                    >Tokens utilisés ce mois</span
                  >
                  <span class="tabular-nums text-gray-400">
                    {{
                      authStore.currentEntreprise.aiTokensUsed.toLocaleString(
                        "fr-FR",
                      )
                    }}
                    /
                    {{
                      authStore.currentEntreprise.aiTokensLimit.toLocaleString(
                        "fr-FR",
                      )
                    }}
                  </span>
                </div>
                <progress
                  class="progress w-full h-2 rounded-full"
                  :class="tokenUsageColor"
                  :value="tokenUsagePercent"
                  max="100"
                ></progress>
                <p
                  v-if="tokenUsagePercent >= 90"
                  class="text-xs text-red-400 mt-2"
                >
                  ⚠️ Quota presque épuisé. Il sera réinitialisé le 1er du mois
                  prochain.
                </p>
              </div>
              <div
                v-else
                class="bg-amber-500/20 border border-amber-500/50 text-amber-300 text-sm rounded-lg p-4 flex gap-3"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="stroke-current shrink-0 h-5 w-5 mt-0.5"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
                <span
                  >Le plan Pro n'est pas encore activé pour cette entreprise.
                  Contactez l'équipe Synkro.</span
                >
              </div>
            </div>

            <!-- Fournisseur -->
            <div class="form-control border-t border-white/10 pt-8">
              <label class="label px-0"
                ><span class="label-text font-medium text-white"
                  >Fournisseur IA</span
                ></label
              >
              <select
                v-model="aiProvider"
                class="select border border-white/10 bg-white/5 text-[#e0e0e0] hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-blue-500/50 w-full rounded-lg"
              >
                <option
                  v-for="p in providers"
                  :key="p.value"
                  :value="p.value"
                  class="bg-[#0a1628] text-[#e0e0e0]"
                >
                  {{ p.label }}
                </option>
              </select>
            </div>

            <!-- Clé API (BYOK uniquement) -->
            <div
              v-if="aiMode === 'byok'"
              class="form-control border-t border-white/10 pt-8"
            >
              <label class="label px-0">
                <span class="label-text font-medium text-white">Clé API</span>
                <span class="label-text-alt text-gray-500 text-xs">
                  {{
                    authStore.currentEntreprise?.aiApiKeyConfigured
                      ? "✓ Une clé est déjà configurée"
                      : "○ Aucune clé configurée"
                  }}
                </span>
              </label>
              <input
                v-model="apiKey"
                type="password"
                class="input border border-white/10 bg-white/5 text-[#e0e0e0] placeholder-gray-600 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-blue-500/50 w-full rounded-lg"
                placeholder="Laisser vide pour conserver la clé existante"
              />
              <p class="text-xs text-gray-500 mt-2">
                🔒 Chiffrée en AES-256 avant stockage — jamais renvoyée au
                frontend.
              </p>
            </div>

            <!-- Info RGPD -->
            <div
              class="bg-blue-500/20 border border-blue-500/50 text-blue-300 text-sm rounded-lg p-4 flex gap-3 border-t pt-8"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                class="stroke-current shrink-0 h-5 w-5 mt-0.5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <div>
                <span>
                  ✓ Mistral AI — données hébergées en Europe, conforme RGPD.
                </span>
                <template v-if="aiMode === 'byok'">
                  <div class="mt-1">
                    Obtenez votre clé sur <strong>console.mistral.ai</strong>.
                  </div>
                </template>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-8 border-t border-white/10">
            <button
              class="btn btn-primary px-8"
              @click="saveSettings"
              :disabled="saving"
            >
              <span
                v-if="saving"
                class="loading loading-spinner loading-sm"
              ></span>
              {{ saving ? "Sauvegarde..." : "Sauvegarder" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
