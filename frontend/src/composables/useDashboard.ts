import { ref } from 'vue'

export interface DashboardData {
  workload: {
    daily: Array<{
      date: string
      dayName: string
      percentage: number
      isOverloaded: boolean
    }>
    overloadDays: number
    hasAlert: boolean
  }
  upcomingEvents: Array<{
    id: number
    title: string
    startDate: string
    endDate: string
    eventType: string
    room: { id: number; name: string } | null
  }>
  todayEvents: Array<{
    id: number
    title: string
    startDate: string
    endDate: string
    eventType: string
  }>
  tasks: {
    today: Array<TaskItem>
    overdue: Array<TaskItem>
    roomProgress: Array<{
      roomId: number
      roomName: string
      total: number
      completed: number
      percentage: number
    }>
  }
  recentRooms: Array<{
    id: number
    name: string
    visibility: string
    moduleCount: number
    layoutType: string
  }>
  teamAvailability: Array<{
    id: number
    name: string
    email: string
    avatar: string | null
    workload: number
    status: 'available' | 'busy' | 'absent'
  }>
  statistics: {
    tasksCompleted: number
    tasksCreated: number
    productivity: number
    meetingsCount: number
    meetingsDuration: number
    meetingsDurationFormatted: string
  }
  notifications: {
    upcomingDeadlines: Array<TaskItem>
  }
}

interface TaskItem {
  id: number
  title: string
  description: string | null
  status: string
  priority: string
  dueDate: string | null
  room: { id: number; name: string } | null
}

export function useDashboard() {
  const dashboardData = ref<DashboardData | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchDashboardData = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/dashboard', {
        headers: {
          'Accept': 'application/ld+json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })

      if (!response.ok) {
        throw new Error('Failed to fetch dashboard data')
      }

      dashboardData.value = await response.json()
    } catch (err: any) {
      error.value = err.message || 'Erreur lors du chargement du dashboard'
      console.error('Error fetching dashboard:', err)
    } finally {
      loading.value = false
    }
  }

  return {
    dashboardData,
    loading,
    error,
    fetchDashboardData
  }
}
