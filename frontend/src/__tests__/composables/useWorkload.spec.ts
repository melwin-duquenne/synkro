import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useWorkload } from '@/composables/useWorkload'
import type { WorkloadData } from '@/composables/useWorkload'

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

const makeWorkload = (overrides: Partial<WorkloadData> = {}): WorkloadData => ({
  status: 'normal',
  statusLabel: 'Normal',
  color: 'green',
  percentage: 50,
  totalHours: 4,
  eventCount: 2,
  meetingCount: 1,
  hasShortBreaks: false,
  standardHours: 8,
  period: 'day',
  events: [],
  modifiers: { tooManyMeetings: false, shortBreaks: false },
  ...overrides,
})

describe('useWorkload — getColorClass', () => {
  it('returns correct background CSS class for each color', () => {
    const { getColorClass } = useWorkload()
    expect(getColorClass('green')).toBe('bg-green-500')
    expect(getColorClass('orange')).toBe('bg-orange-500')
    expect(getColorClass('red')).toBe('bg-red-500')
  })

  it('returns fallback class for unknown color', () => {
    const { getColorClass } = useWorkload()
    expect(getColorClass('purple')).toBe('bg-gray-500')
    expect(getColorClass('')).toBe('bg-gray-500')
  })
})

describe('useWorkload — getTextColorClass', () => {
  it('returns correct text CSS class for each color', () => {
    const { getTextColorClass } = useWorkload()
    expect(getTextColorClass('green')).toBe('text-green-600')
    expect(getTextColorClass('orange')).toBe('text-orange-600')
    expect(getTextColorClass('red')).toBe('text-red-600')
  })

  it('returns fallback class for unknown color', () => {
    const { getTextColorClass } = useWorkload()
    expect(getTextColorClass('blue')).toBe('text-gray-600')
  })
})

describe('useWorkload — getBorderColorClass', () => {
  it('returns correct border CSS class for each color', () => {
    const { getBorderColorClass } = useWorkload()
    expect(getBorderColorClass('green')).toBe('border-green-500')
    expect(getBorderColorClass('orange')).toBe('border-orange-500')
    expect(getBorderColorClass('red')).toBe('border-red-500')
  })

  it('returns fallback class for unknown color', () => {
    const { getBorderColorClass } = useWorkload()
    expect(getBorderColorClass('yellow')).toBe('border-gray-500')
  })
})

describe('useWorkload — fetchDailyWorkload', () => {
  it('sets workload data on success', async () => {
    const data = makeWorkload({ status: 'busy', color: 'orange', percentage: 80 })
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => data,
    })

    const { workload, loading, error, fetchDailyWorkload } = useWorkload()

    await fetchDailyWorkload()

    expect(workload.value).toEqual(data)
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('sets error and clears workload on failure', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false })

    const { workload, error, fetchDailyWorkload } = useWorkload()

    await fetchDailyWorkload()

    expect(workload.value).toBeNull()
    expect(error.value).toBeTruthy()
  })

  it('sets loading to true during fetch and false after', async () => {
    let resolvePromise!: (value: unknown) => void
    const promise = new Promise(resolve => { resolvePromise = resolve })
    mockFetch.mockReturnValueOnce(promise)

    const { loading, fetchDailyWorkload } = useWorkload()

    const fetchPromise = fetchDailyWorkload()
    expect(loading.value).toBe(true)

    resolvePromise({ ok: true, json: async () => makeWorkload() })
    await fetchPromise

    expect(loading.value).toBe(false)
  })

  it('includes date in URL when provided', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => makeWorkload(),
    })

    const { fetchDailyWorkload } = useWorkload()
    await fetchDailyWorkload('2026-03-26')

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('2026-03-26'),
      expect.any(Object),
    )
  })
})

describe('useWorkload — fetchWeeklyWorkload', () => {
  it('sets workload data with period=week', async () => {
    const data = makeWorkload({ period: 'week', percentage: 60 })
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => data,
    })

    const { workload, fetchWeeklyWorkload } = useWorkload()
    await fetchWeeklyWorkload()

    expect(workload.value?.period).toBe('week')
  })

  it('appends userId as query param when provided', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => makeWorkload(),
    })

    const { fetchWeeklyWorkload } = useWorkload()
    await fetchWeeklyWorkload(undefined, 42)

    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('userId=42'),
      expect.any(Object),
    )
  })
})
