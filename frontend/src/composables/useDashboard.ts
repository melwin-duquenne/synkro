import { ref } from 'vue'

export interface DashboardData {
  workload: {
    daily: Array<{
      date: string
      dayName: string
      percentage: number
      hoursUsed: number
      hoursAvailable: number
      isOverloaded: boolean
      tasks?: Array<{
        id: number
        title: string
        roomName: string
        estimatedHours: number
      }>
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
    creator?: {
      displayName: string
    }
    createdAt?: string
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

  const fetchDashboardData = async (userId?: number | null) => {
    loading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('token')
      console.log('[useDashboard] Fetching dashboard for userId:', userId)
      
      const url = userId ? `/api/dashboard?userId=${userId}` : '/api/dashboard'
      console.log('[useDashboard] Request URL:', url)
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/ld+json',
          'Authorization': `Bearer ${token}`
        }
      })

      if (response.status === 401) {
        throw new Error('Utilisateur non authentifié')
      }
      
      if (!response.ok) {
        throw new Error('Impossible de charger les données du tableau de bord')
      }

      const data = await response.json()
      
      // Helper pour extraire les données d'une Collection API Platform
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const extractData = (value: any): any => {
        if (value && typeof value === 'object' && value['@type'] === 'Collection' && Array.isArray(value.member)) {
          return value.member
        }
        // Si c'est un objet avec des propriétés qui sont des Collections, extraire chacune
        if (value && typeof value === 'object' && !Array.isArray(value)) {
          // eslint-disable-next-line @typescript-eslint/no-explicit-any
          const extracted: Record<string, any> = {}
          for (const [key, val] of Object.entries(value)) {
            if (key.startsWith('@')) continue // Ignorer les métadonnées API Platform
            extracted[key] = extractData(val)
          }
          return Object.keys(extracted).length > 0 ? extracted : value
        }
        return value
      }
      
      // API Platform retourne un objet avec chaque propriété comme Collection
      if (data['@type'] === 'DashboardData') {
        
        const extractedWorkload = extractData(data.workload)
        const extractedTasks = extractData(data.tasks)
        
        const workloadData = Array.isArray(extractedWorkload) 
          ? { daily: extractedWorkload[0] || [], overloadDays: extractedWorkload[1] || 0, hasAlert: extractedWorkload[2] || false }
          : (extractedWorkload || { daily: [], overloadDays: 0, hasAlert: false })
        
        console.log('Workload daily array:', workloadData.daily)
        if (workloadData.daily.length > 0) {
          console.log('First daily item:', workloadData.daily[0])
        }
        
        dashboardData.value = {
          workload: workloadData,
          upcomingEvents: extractData(data.upcomingEvents) || [],
          todayEvents: extractData(data.todayEvents) || [],
          tasks: Array.isArray(extractedTasks)
            ? { today: extractedTasks[0] || [], overdue: extractedTasks[1] || [], roomProgress: extractedTasks[2] || [] }
            : (extractedTasks || { today: [], overdue: [], roomProgress: [] }),
          recentRooms: extractData(data.recentRooms) || [],
          teamAvailability: extractData(data.teamAvailability) || [],
          statistics: extractData(data.statistics) || { tasksCompleted: 0, tasksCreated: 0, productivity: 0, meetingsCount: 0, meetingsDuration: 0, meetingsDurationFormatted: '0h' },
          notifications: extractData(data.notifications) || { upcomingDeadlines: [] }
        }
      } else if (data['@type'] === 'Collection' && Array.isArray(data.member)) {
        // Ancien format (tableau indexé)
        dashboardData.value = {
          workload: data.member[0],
          upcomingEvents: data.member[1],
          todayEvents: data.member[2],
          tasks: data.member[3],
          recentRooms: data.member[4],
          teamAvailability: data.member[5],
          statistics: data.member[6],
          notifications: data.member[7]
        }
      } else {
        // Format direct (si le backend change)
        dashboardData.value = data
      }
    } catch (err: unknown) {
      error.value = err instanceof Error ? err.message : 'Erreur lors du chargement du dashboard'
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
