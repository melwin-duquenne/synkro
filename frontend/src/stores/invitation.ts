import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Invitation } from '@/types'
import { useAuthStore } from './auth'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export const useInvitationStore = defineStore('invitation', () => {
  const invitations = ref<Invitation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchInvitations(): Promise<void> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/invitations`, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Failed to fetch invitations')
      }

      const data = await response.json()
      invitations.value = Array.isArray(data) ? data : (data['hydra:member'] ?? data['member'] ?? [])
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch invitations'
    } finally {
      loading.value = false
    }
  }

  async function sendInvitation(email: string): Promise<boolean> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/invitations`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify({ email })
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data['hydra:description'] || data.detail || 'Failed to send invitation')
      }

      await fetchInvitations()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to send invitation'
      return false
    } finally {
      loading.value = false
    }
  }

  async function cancelInvitation(id: number): Promise<boolean> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/invitations/${id}`, {
        method: 'DELETE',
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Failed to cancel invitation')
      }

      invitations.value = invitations.value.filter(i => i.id !== id)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to cancel invitation'
      return false
    } finally {
      loading.value = false
    }
  }

  async function acceptInvitation(token: string): Promise<{ success: boolean; data?: any }> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const headers: Record<string, string> = {
        'Content-Type': 'application/ld+json'
      }
      if (authStore.token) {
        headers['Authorization'] = `Bearer ${authStore.token}`
      }

      const response = await fetch(`${API_URL}/invitations/accept`, {
        method: 'POST',
        headers,
        body: JSON.stringify({ token })
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to accept invitation')
      }

      const data = await response.json()

      if (data.accepted && authStore.token) {
        await authStore.fetchUser()
      }

      return { success: true, data }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to accept invitation'
      return { success: false }
    } finally {
      loading.value = false
    }
  }

  return {
    invitations,
    loading,
    error,
    fetchInvitations,
    sendInvitation,
    cancelInvitation,
    acceptInvitation
  }
})
