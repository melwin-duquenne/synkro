import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboard } from '@/composables/useDashboard'

const mockFetch = vi.fn()

beforeEach(() => {
  localStorage.clear()
  mockFetch.mockReset()
  vi.stubGlobal('fetch', mockFetch)
  setActivePinia(createPinia())
})

afterEach(() => {
  vi.unstubAllGlobals()
})

const emptyDashboard = {
  workload: { daily: [], overloadDays: 0, hasAlert: false },
  upcomingEvents: [],
  todayEvents: [],
  tasks: { today: [], overdue: [], roomProgress: [] },
  recentRooms: [],
  teamAvailability: [],
  statistics: {
    tasksCompleted: 0,
    tasksCreated: 0,
    productivity: 0,
    meetingsCount: 0,
    meetingsDuration: 0,
    meetingsDurationFormatted: '0h',
  },
  notifications: { upcomingDeadlines: [] },
}

describe('useDashboard — initial state', () => {
  it('starts with null data and no loading or error', () => {
    const { dashboardData, loading, error } = useDashboard()
    expect(dashboardData.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })
})

describe('useDashboard — fetchDashboardData', () => {
  it('sets loading during fetch and false after', async () => {
    let resolvePromise!: (value: unknown) => void
    const promise = new Promise(resolve => { resolvePromise = resolve })
    mockFetch.mockReturnValueOnce(promise)

    const { loading, fetchDashboardData } = useDashboard()
    const fetchPromise = fetchDashboardData()

    expect(loading.value).toBe(true)

    resolvePromise({
      ok: true,
      status: 200,
      json: async () => ({ ...emptyDashboard }),
    })
    await fetchPromise

    expect(loading.value).toBe(false)
  })

  it('sets error on 401 response', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, status: 401 })

    const { error, fetchDashboardData } = useDashboard()
    await fetchDashboardData()

    expect(error.value).toBeTruthy()
  })

  it('sets error on non-ok response', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, status: 500 })

    const { error, fetchDashboardData } = useDashboard()
    await fetchDashboardData()

    expect(error.value).toBeTruthy()
  })

  it('handles DashboardData @type format', async () => {
    const apiResponse = {
      '@type': 'DashboardData',
      workload: { daily: [], overloadDays: 0, hasAlert: false },
      upcomingEvents: [],
      todayEvents: [],
      tasks: { today: [], overdue: [], roomProgress: [] },
      recentRooms: [],
      teamAvailability: [],
      statistics: {
        tasksCompleted: 5,
        tasksCreated: 10,
        productivity: 50,
        meetingsCount: 2,
        meetingsDuration: 120,
        meetingsDurationFormatted: '2h',
      },
      notifications: { upcomingDeadlines: [] },
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => apiResponse,
    })

    const { dashboardData, error, fetchDashboardData } = useDashboard()
    await fetchDashboardData()

    expect(error.value).toBeNull()
    expect(dashboardData.value).not.toBeNull()
    expect(dashboardData.value?.statistics.tasksCompleted).toBe(5)
  })

  it('handles Collection @type format (legacy)', async () => {
    const apiResponse = {
      '@type': 'Collection',
      member: [
        { daily: [], overloadDays: 0, hasAlert: false }, // workload
        [], // upcomingEvents
        [], // todayEvents
        { today: [], overdue: [], roomProgress: [] }, // tasks
        [], // recentRooms
        [], // teamAvailability
        { tasksCompleted: 3, tasksCreated: 7, productivity: 42, meetingsCount: 1, meetingsDuration: 60, meetingsDurationFormatted: '1h' }, // statistics
        { upcomingDeadlines: [] }, // notifications
      ],
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => apiResponse,
    })

    const { dashboardData, fetchDashboardData } = useDashboard()
    await fetchDashboardData()

    expect(dashboardData.value?.statistics.tasksCompleted).toBe(3)
  })

  it('appends userId to URL when provided', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => emptyDashboard,
    })

    const { fetchDashboardData } = useDashboard()
    await fetchDashboardData(99)

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('userId=99'),
      expect.any(Object),
    )
  })
})
