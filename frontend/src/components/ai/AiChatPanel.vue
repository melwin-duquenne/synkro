<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAi } from '@/composables/useAi'

const props = defineProps<{
  module?: string
  placeholder?: string
  selectedText?: string
  documentContent?: string
}>()

const emit = defineEmits<{
  (e: 'insert', text: string): void
}>()

const { chat, loading, error, isAvailable } = useAi()

const prompt = ref('')
const response = ref<string | null>(null)

const hasSelection = computed(() => !!props.selectedText?.trim())

async function handleSend() {
  if (!prompt.value.trim()) return

  let fullPrompt = prompt.value
  if (hasSelection.value) {
    fullPrompt = `Texte sélectionné dans le document :\n"""\n${props.selectedText}\n"""\n\nDemande : ${prompt.value}\n\nRetourne uniquement le texte modifié/demandé, sans explication ni commentaire.`
  } else if (props.documentContent?.trim()) {
    fullPrompt = `Contenu actuel du document :\n"""\n${props.documentContent}\n"""\n\nDemande : ${prompt.value}`
  }

  response.value = await chat(fullPrompt, props.module ?? 'general')
  if (response.value) {
    emit('insert', response.value)
    prompt.value = ''
  }
}
</script>

<template>
  <div v-if="isAvailable()" class="flex flex-col gap-3 p-4 bg-base-200 rounded-xl border border-base-300">
    <div class="flex items-center gap-2 text-sm font-semibold text-primary">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      Assistant IA
    </div>

    <div v-if="hasSelection" class="text-xs text-base-content/50 bg-base-100 rounded p-2 line-clamp-2">
      Sélection : "{{ selectedText }}" — la réponse <strong>remplacera</strong> le texte sélectionné.
    </div>
    <div v-else-if="documentContent?.trim()" class="text-xs text-base-content/50 bg-base-100 rounded p-2">
      L'IA a connaissance du contenu du document.
    </div>

    <textarea
      v-model="prompt"
      class="textarea textarea-bordered w-full resize-none text-sm"
      :placeholder="placeholder ?? 'Que voulez-vous que je fasse ?'"
      rows="3"
      @keydown.ctrl.enter="handleSend"
    />

    <div v-if="error" class="text-error text-xs">{{ error }}</div>

    <div v-if="response" class="bg-base-100 rounded p-3 text-sm whitespace-pre-wrap max-h-48 overflow-y-auto border border-success/30">
      <div class="text-xs text-success font-medium mb-1">Appliqué dans le document ✓</div>
      <div class="text-base-content/70">{{ response }}</div>
    </div>

    <div class="flex justify-end gap-2">
      <button v-if="response" class="btn btn-sm btn-ghost" @click="response = null">
        Nouvelle demande
      </button>
      <button
        class="btn btn-sm btn-primary"
        :disabled="loading || !prompt.trim()"
        @click="handleSend"
      >
        <span v-if="loading" class="loading loading-spinner loading-xs"></span>
        {{ loading ? 'Génération...' : 'Envoyer (Ctrl+↵)' }}
      </button>
    </div>
  </div>

  <div v-else class="text-sm text-base-content/40 text-center p-4">
    L'IA n'est pas activée pour cette entreprise.
  </div>
</template>
