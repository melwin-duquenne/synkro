import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import type { CalendarEvent, CreateCalendarEventData, EventType } from '@/types'
import { extractApiError } from '@/utils/apiError'

export interface CalendarFilters {
  roomId?: number | null
  userId?: number
  eventType?: EventType
  startDate?: string
  endDate?: string
  personal?: boolean
}

export interface EnterpriseUser {
  id: number
  displayName: string
  email: string
}

export const useCalendarStore = defineStore('calendar', () => {
  const events = ref<CalendarEvent[]>([])
  const enterpriseUsers = ref<EnterpriseUser[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchEvents(filters: CalendarFilters = {}): Promise<void> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const params = new URLSearchParams()

      if (filters.personal) {
        params.append('personal', 'true')
      }
      if (filters.userId) {
        params.append('userId', String(filters.userId))
      }
      if (filters.eventType) {
        params.append('eventType', filters.eventType)
      }
      if (filters.startDate) {
        params.append('startDate', filters.startDate)
      }
      if (filters.endDate) {
        params.append('endDate', filters.endDate)
      }

      // Use room-specific endpoint if roomId is provided
      let url = '/api/calendar-events'
      if (filters.roomId) {
        url = `/api/rooms/${filters.roomId}/calendar-events`
      }

      const queryString = params.toString()
      if (queryString) {
        url += `?${queryString}`
      }

      const response = await fetch(url, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Impossible de charger les événements')
      }

      const data = await response.json()
      // Handle API Platform format
      events.value = Array.isArray(data) ? data : (data['hydra:member'] || data.member || [])
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de charger les événements'
    } finally {
      loading.value = false
    }
  }

  async function createEvent(data: CreateCalendarEventData): Promise<CalendarEvent | null> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/calendar-events', {
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
        throw new Error(extractApiError(errorData, 'Impossible de créer l\'événement'))
      }

      const event = await response.json()
      events.value.push(event)
      return event
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de créer l\'événement'
      return null
    } finally {
      loading.value = false
    }
  }

  async function updateEvent(id: number, data: Partial<CreateCalendarEventData>): Promise<CalendarEvent | null> {
    const authStore = useAuthStore()
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/calendar-events/${id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          'Accept': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(extractApiError(errorData, 'Impossible de mettre à jour l\'événement'))
      }

      const updatedEvent = await response.json()
      const index = events.value.findIndex(e => e.id === id)
      if (index !== -1) {
        events.value[index] = updatedEvent
      }
      return updatedEvent
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de mettre à jour l\'événement'
      return null
    } finally {
      loading.value = false
    }
  }

  async function deleteEvent(id: number): Promise<boolean> {
    const authStore = useAuthStore()

    try {
      const response = await fetch(`/api/calendar-events/${id}`, {
        method: 'DELETE',
        headers: authStore.getAuthHeaders()
      })

      if (response.ok || response.status === 204) {
        events.value = events.value.filter(e => e.id !== id)
        return true
      }
      return false
    } catch {
      return false
    }
  }

  function getEventsByDate(date: Date): CalendarEvent[] {
    const dateStr = date.toISOString().split('T')[0]
    return events.value.filter(event => {
      const start = event.startDate.split('T')[0]
      const end = event.endDate.split('T')[0]
      return dateStr >= start && dateStr <= end
    })
  }

  async function fetchEnterpriseUsers(): Promise<void> {
    const authStore = useAuthStore()

    try {
      const response = await fetch('/api/entreprise/members', {
        headers: authStore.getAuthHeaders()
      })

      if (response.ok) {
        const data = await response.json()
        // Handle API Platform format
        const users = data['hydra:member'] || data.member || (Array.isArray(data) ? data : [])
        enterpriseUsers.value = users.map((u: { id: number; displayName: string; email?: string }) => ({
          id: u.id,
          displayName: u.displayName,
          email: u.email || ''
        }))
      }
    } catch (e) {
      console.error('Failed to fetch enterprise users:', e)
    }
  }

  return {
    events,
    enterpriseUsers,
    loading,
    error,
    fetchEvents,
    createEvent,
    updateEvent,
    deleteEvent,
    getEventsByDate,
    fetchEnterpriseUsers
  }
})
