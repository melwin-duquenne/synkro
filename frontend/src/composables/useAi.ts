import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export function useAi() {
  const authStore = useAuthStore()
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAvailable = () => authStore.currentEntreprise?.aiEnabled === true

  async function chat(message: string, module: string = 'general'): Promise<string | null> {
    if (!isAvailable()) {
      error.value = "L'IA n'est pas activée pour cette entreprise."
      return null
    }

    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/ai/chat`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify({ message, module }),
      })

      if (!response.ok) {
        let detail = "Erreur de l'assistant IA"
        try {
          const data = await response.json()
          detail = data.detail || data['hydra:description'] || detail
        } catch { /* corps non-JSON */ }
        throw new Error(detail)
      }

      const data = await response.json()
      return data.response as string
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erreur inconnue'
      return null
    } finally {
      loading.value = false
    }
  }

  return { chat, loading, error, isAvailable }
}
