<script setup lang="ts">
import { ref } from 'vue'
import { useAi } from '@/composables/useAi'

const props = defineProps<{
  module?: string
  placeholder?: string
  selectedText?: string
}>()

const emit = defineEmits<{
  (e: 'insert', text: string): void
}>()

const { chat, loading, error, isAvailable } = useAi()

const prompt = ref('')
const response = ref<string | null>(null)

async function handleSend() {
  if (!prompt.value.trim()) return

  const fullPrompt = props.selectedText
    ? `Contexte sélectionné :\n"${props.selectedText}"\n\nDemande : ${prompt.value}`
    : prompt.value

  response.value = await chat(fullPrompt, props.module ?? 'general')
}

function handleInsert() {
  if (response.value) {
    emit('insert', response.value)
    response.value = null
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

    <div v-if="selectedText" class="text-xs text-base-content/50 bg-base-100 rounded p-2 line-clamp-2">
      Contexte : "{{ selectedText }}"
    </div>

    <textarea
      v-model="prompt"
      class="textarea textarea-bordered w-full resize-none text-sm"
      :placeholder="placeholder ?? 'Que voulez-vous que je fasse ?'"
      rows="3"
      @keydown.ctrl.enter="handleSend"
    />

    <div v-if="error" class="text-error text-xs">{{ error }}</div>

    <div v-if="response" class="bg-base-100 rounded p-3 text-sm whitespace-pre-wrap max-h-48 overflow-y-auto">
      {{ response }}
    </div>

    <div class="flex gap-2 justify-end">
      <button
        v-if="response"
        class="btn btn-sm btn-outline"
        @click="response = null; prompt = ''"
      >
        Effacer
      </button>
      <button
        v-if="response"
        class="btn btn-sm btn-success"
        @click="handleInsert"
      >
        Insérer dans le document
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
