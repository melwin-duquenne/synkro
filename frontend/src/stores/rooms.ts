import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import type { Room, Module, RoomMember } from '@/types'
import { extractApiError } from '@/utils/apiError'

export interface TemplateItem {
  id: number
  name: string
  description: string
  modules: { code: string; name: string }[]
}

export interface EnterpriseUser {
  id: number
  displayName: string
  email: string
}

export const useRoomsStore = defineStore('rooms', () => {
  const rooms = ref<Room[]>([])
  const modules = ref<Module[]>([])
  const templates = ref<TemplateItem[]>([])
  const enterpriseUsers = ref<EnterpriseUser[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchModules(): Promise<void> {
    try {
      const response = await fetch('/api/modules')
      if (response.ok) {
        const data = await response.json()
        // Handle both API Platform format and custom format
        modules.value = data.member || data['hydra:member'] || data
      }
    } catch (e) {
      console.error('Failed to fetch modules:', e)
    }
  }

  async function fetchTemplates(): Promise<void> {
    try {
      const response = await fetch('/api/templates')
      if (response.ok) {
        const data = await response.json()
        // Handle both API Platform format and custom format
        templates.value = data.member || data['hydra:member'] || data
      }
    } catch (e) {
      console.error('Failed to fetch templates:', e)
    }
  }

  async function fetchRooms(): Promise<void> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/rooms', {
        headers: authStore.getAuthHeaders()
      })
      if (response.ok) {
        const data = await response.json()
        // Handle both API Platform format and custom format
        rooms.value = data.member || data['hydra:member'] || data
      }
    } catch {
      error.value = 'Impossible de charger les salons'
    } finally {
      loading.value = false
    }
  }

  async function fetchEnterpriseUsers(): Promise<EnterpriseUser[]> {
    const authStore = useAuthStore()

    try {
      const response = await fetch('/api/entreprise/members', {
        headers: authStore.getAuthHeaders()
      })
      if (response.ok) {
        const data = await response.json()
        enterpriseUsers.value = data.member || data['hydra:member'] || data
        return enterpriseUsers.value
      }
    } catch (e) {
      console.error('Failed to fetch enterprise users:', e)
    }
    return []
  }

  async function fetchRoomMembers(roomId: number): Promise<RoomMember[]> {
    const authStore = useAuthStore()

    try {
      const response = await fetch(`/api/rooms/${roomId}/members`, {
        headers: authStore.getAuthHeaders()
      })
      if (response.ok) {
        const data = await response.json()
        return data.member || data['hydra:member'] || data
      }
    } catch (e) {
      console.error('Failed to fetch room members:', e)
    }
    return []
  }

  async function manageRoomMembers(
    roomId: number,
    data: { addUserIds?: number[]; removeUserIds?: number[] }
  ): Promise<RoomMember[] | null> {
    const authStore = useAuthStore()

    try {
      const response = await fetch(`/api/rooms/${roomId}/members`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Accept': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify(data)
      })
      if (response.ok) {
        const result = await response.json()
        return result.member || result['hydra:member'] || result
      }
    } catch (e) {
      console.error('Failed to manage room members:', e)
    }
    return null
  }

  async function createRoom(data: {
    name: string
    modules: string[]
    visibility?: string
    isTemporary?: boolean
    memberIds?: number[]
  }): Promise<Room | null> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/rooms', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Accept': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(extractApiError(errorData, 'Impossible de créer le salon'))
      }

      const room = await response.json()
      rooms.value.unshift(room)
      return room
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de créer le salon'
      return null
    } finally {
      loading.value = false
    }
  }

  async function updateRoom(
    roomId: number,
    data: { name?: string; visibility?: string; layoutType?: string }
  ): Promise<Room | null> {
    const authStore = useAuthStore()

    try {
      const response = await fetch(`/api/rooms/${roomId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          'Accept': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify(data)
      })

      if (response.ok) {
        const updatedRoom = await response.json()
        // Update in local state
        const index = rooms.value.findIndex(r => r.id === roomId)
        if (index !== -1) {
          rooms.value[index] = updatedRoom
        }
        return updatedRoom
      }
    } catch (e) {
      console.error('Failed to update room:', e)
    }
    return null
  }

  async function deleteRoom(id: number): Promise<boolean> {
    const authStore = useAuthStore()

    try {
      const response = await fetch(`/api/rooms/${id}`, {
        method: 'DELETE',
        headers: authStore.getAuthHeaders()
      })

      if (response.ok) {
        rooms.value = rooms.value.filter(r => r.id !== id)
        return true
      }
      return false
    } catch {
      return false
    }
  }

  return {
    rooms,
    modules,
    templates,
    enterpriseUsers,
    loading,
    error,
    fetchModules,
    fetchTemplates,
    fetchRooms,
    fetchEnterpriseUsers,
    fetchRoomMembers,
    manageRoomMembers,
    createRoom,
    updateRoom,
    deleteRoom
  }
})
