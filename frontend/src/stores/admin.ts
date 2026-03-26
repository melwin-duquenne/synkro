import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import type { AdminUser } from '@/types'
import { extractApiError } from '@/utils/apiError'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export const useAdminStore = defineStore('admin', () => {
  const users = ref<AdminUser[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  function getHeaders(): Record<string, string> {
    const authStore = useAuthStore()
    return {
      'Authorization': `Bearer ${authStore.token}`,
      'Content-Type': 'application/merge-patch+json'
    }
  }

  async function fetchUsers(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const response = await fetch(`${API_URL}/account/admin/users`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      })

      if (!response.ok) {
        throw new Error('Impossible de charger les utilisateurs')
      }

      const data = await response.json()
      users.value = Array.isArray(data) ? data : (data['hydra:member'] ?? data['member'] ?? [])
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de charger les utilisateurs'
    } finally {
      loading.value = false
    }
  }

  async function updateUser(userId: number, data: { role?: string; teamId?: number }): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/admin/users/${userId}`, {
        method: 'PATCH',
        headers: getHeaders(),
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de mettre à jour l\'utilisateur'))
      }

      const updatedUser: AdminUser = await response.json()
      const index = users.value.findIndex(u => u.id === userId)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de mettre à jour l\'utilisateur'
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteUser(userId: number): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const authStore = useAuthStore()
      const response = await fetch(`${API_URL}/account/admin/users/${userId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de supprimer l\'utilisateur'))
      }

      users.value = users.value.filter(u => u.id !== userId)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de supprimer l\'utilisateur'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    loading,
    error,
    fetchUsers,
    updateUser,
    deleteUser
  }
})
