import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import type { Room, Module } from '@/types'

export interface TemplateItem {
  id: number
  name: string
  description: string
  modules: { code: string; name: string }[]
}

export const useRoomsStore = defineStore('rooms', () => {
  const rooms = ref<Room[]>([])
  const modules = ref<Module[]>([])
  const templates = ref<TemplateItem[]>([])
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
    } catch (e) {
      error.value = 'Failed to fetch rooms'
    } finally {
      loading.value = false
    }
  }

  async function createRoom(data: {
    name: string
    modules: string[]
    visibility?: string
    isTemporary?: boolean
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
        throw new Error(errorData.error || 'Failed to create room')
      }

      const room = await response.json()
      rooms.value.unshift(room)
      return room
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create room'
      return null
    } finally {
      loading.value = false
    }
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
    loading,
    error,
    fetchModules,
    fetchTemplates,
    fetchRooms,
    createRoom,
    deleteRoom
  }
})
