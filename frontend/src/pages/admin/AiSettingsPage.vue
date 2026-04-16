<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const API_URL = import.meta.env.VITE_API_URL || '/api'

const aiEnabled = ref(authStore.currentEntreprise?.aiEnabled ?? false)
const aiProvider = ref(authStore.currentEntreprise?.aiProvider ?? 'mistral')
const apiKey = ref('')
const saving = ref(false)
const success = ref<string | null>(null)
const error = ref<string | null>(null)

const providers = [
  { value: 'mistral', label: 'Mistral AI (recommandé, RGPD EU)' },
]

async function saveSettings() {
  saving.value = true
  success.value = null
  error.value = null

  try {
    const body: Record<string, unknown> = {
      aiEnabled: aiEnabled.value,
      aiProvider: aiProvider.value,
    }
    if (apiKey.value.trim()) {
      body.aiApiKey = apiKey.value.trim()
    }

    const response = await fetch(`${API_URL}/account/entreprise/ai`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
        ...authStore.getAuthHeaders(),
      },
      body: JSON.stringify(body),
    })

    if (!response.ok) {
      let detail = 'Erreur lors de la sauvegarde'
      try {
        const data = await response.json()
        detail = data.detail || data['hydra:description'] || detail
      } catch { /* corps non-JSON, conserver le message par défaut */ }
      throw new Error(detail)
    }

    await authStore.fetchUser()
    if (authStore.currentEntreprise) {
      authStore.setCurrentEntreprise(authStore.currentEntreprise.slug)
    }

    apiKey.value = ''
    success.value = 'Paramètres IA sauvegardés.'
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Erreur inconnue'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Paramètres IA</h1>

    <div v-if="success" class="alert alert-success mb-4"><span>{{ success }}</span></div>
    <div v-if="error" class="alert alert-error mb-4"><span>{{ error }}</span></div>

    <div class="card bg-base-100 shadow-xl">
      <div class="card-body space-y-6">

        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-4">
            <input type="checkbox" v-model="aiEnabled" class="toggle toggle-primary" />
            <span class="label-text text-base font-medium">Activer l'IA pour cette entreprise</span>
          </label>
          <p class="text-sm text-base-content/60 ml-16">
            Une fois activée, tous les membres pourront utiliser l'assistant IA.
          </p>
        </div>

        <div v-if="aiEnabled" class="space-y-4">
          <div class="form-control">
            <label class="label"><span class="label-text font-medium">Fournisseur IA</span></label>
            <select v-model="aiProvider" class="select select-bordered w-full">
              <option v-for="p in providers" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Clé API</span>
              <span class="label-text-alt text-base-content/50">
                {{ authStore.currentEntreprise?.aiApiKeyConfigured ? 'Une clé est déjà configurée' : 'Aucune clé configurée' }}
              </span>
            </label>
            <input
              v-model="apiKey"
              type="password"
              class="input input-bordered w-full"
              placeholder="Laisser vide pour conserver la clé existante"
            />
            <p class="text-xs text-base-content/50 mt-1">
              La clé est chiffrée en AES-256 avant stockage et n'est jamais renvoyée.
            </p>
          </div>

          <div class="alert alert-info text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 h-5 w-5">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Obtenez votre clé sur <strong>console.mistral.ai</strong> — données hébergées en Europe, conforme RGPD.</span>
          </div>
        </div>

        <div class="card-actions justify-end">
          <button class="btn btn-primary" @click="saveSettings" :disabled="saving">
            <span v-if="saving" class="loading loading-spinner loading-sm"></span>
            Sauvegarder
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
