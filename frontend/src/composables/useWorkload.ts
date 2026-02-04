import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export interface WorkloadData {
  status: 'normal' | 'busy' | 'overloaded'
  statusLabel: string
  color: 'green' | 'orange' | 'red'
  percentage: number
  totalHours: number
  eventCount: number
  meetingCount: number
  hasShortBreaks: boolean
  standardHours: number
  period: 'day' | 'week'
  events: Array<{
    id: number
    title: string
    type: string
    duration: number
    startDate: string
  }>
  modifiers: {
    tooManyMeetings: boolean
    shortBreaks: boolean
  }
}

export function useWorkload() {
  const authStore = useAuthStore()
  const workload = ref<WorkloadData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchDailyWorkload(date?: string, userId?: number): Promise<void> {
    loading.value = true
    error.value = null

    try {
      let url = date
        ? `/api/workload/daily/${date}`
        : '/api/workload/daily'

      if (userId) {
        url += `?userId=${userId}`
      }

      const response = await fetch(url, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Failed to fetch workload')
      }

      workload.value = await response.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch workload'
      workload.value = null
    } finally {
      loading.value = false
    }
  }

  async function fetchWeeklyWorkload(date?: string, userId?: number): Promise<void> {
    loading.value = true
    error.value = null

    try {
      let url = date
        ? `/api/workload/weekly/${date}`
        : '/api/workload/weekly'

      if (userId) {
        url += `?userId=${userId}`
      }

      const response = await fetch(url, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Failed to fetch workload')
      }

      workload.value = await response.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch workload'
      workload.value = null
    } finally {
      loading.value = false
    }
  }

  function getColorClass(color: string): string {
    const colors = {
      green: 'bg-green-500',
      orange: 'bg-orange-500',
      red: 'bg-red-500'
    }
    return colors[color as keyof typeof colors] || 'bg-gray-500'
  }

  function getTextColorClass(color: string): string {
    const colors = {
      green: 'text-green-600',
      orange: 'text-orange-600',
      red: 'text-red-600'
    }
    return colors[color as keyof typeof colors] || 'text-gray-600'
  }

  function getBorderColorClass(color: string): string {
    const colors = {
      green: 'border-green-500',
      orange: 'border-orange-500',
      red: 'border-red-500'
    }
    return colors[color as keyof typeof colors] || 'border-gray-500'
  }

  return {
    workload,
    loading,
    error,
    fetchDailyWorkload,
    fetchWeeklyWorkload,
    getColorClass,
    getTextColorClass,
    getBorderColorClass
  }
}
