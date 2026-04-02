import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCalendarStore } from '@/stores/calendar'
import type { CalendarEvent } from '@/types'

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

const makeEvent = (overrides: Partial<CalendarEvent> = {}): CalendarEvent => ({
  id: 1,
  roomId: null,
  user: { id: 1, displayName: 'Alice' },
  title: 'Test Event',
  eventType: 'meeting',
  startDate: '2026-03-26T10:00:00',
  endDate: '2026-03-26T11:00:00',
  isAllDay: false,
  isPrivate: false,
  createdAt: '2026-03-01T00:00:00',
  participants: [],
  ...overrides,
})

describe('useCalendarStore — fetchEvents', () => {
  it('parses hydra:member and stores events', async () => {
    const events = [makeEvent({ id: 1 }), makeEvent({ id: 2, title: 'Event 2' })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': events }),
    })

    const store = useCalendarStore()
    await store.fetchEvents()

    expect(store.events).toEqual(events)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('builds URL with filters (roomId, eventType, startDate, endDate)', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': [] }),
    })

    const store = useCalendarStore()
    await store.fetchEvents({
      roomId: 42,
      eventType: 'meeting',
      startDate: '2026-03-01',
      endDate: '2026-03-31',
    })

    const calledUrl = mockFetch.mock.calls[0][0] as string
    expect(calledUrl).toContain('/api/rooms/42/calendar-events')
    expect(calledUrl).toContain('eventType=meeting')
    expect(calledUrl).toContain('startDate=2026-03-01')
    expect(calledUrl).toContain('endDate=2026-03-31')
  })

  it('sets error on non-ok response', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, json: async () => ({}) })

    const store = useCalendarStore()
    await store.fetchEvents()

    expect(store.error).toBeTruthy()
    expect(store.events).toEqual([])
  })
})

describe('useCalendarStore — createEvent', () => {
  it('returns the created event and adds it to events', async () => {
    const newEvent = makeEvent({ id: 10, title: 'New Event' })
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => newEvent,
    })

    const store = useCalendarStore()
    const result = await store.createEvent({
      title: 'New Event',
      eventType: 'meeting',
      startDate: '2026-03-26T10:00:00',
      endDate: '2026-03-26T11:00:00',
    })

    expect(result).toEqual(newEvent)
    expect(store.events).toContainEqual(newEvent)
  })

  it('returns null and sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Forbidden' }),
    })

    const store = useCalendarStore()
    const result = await store.createEvent({
      title: 'Bad',
      eventType: 'meeting',
      startDate: '2026-03-26T10:00:00',
      endDate: '2026-03-26T11:00:00',
    })

    expect(result).toBeNull()
    expect(store.error).toBeTruthy()
  })
})

describe('useCalendarStore — updateEvent', () => {
  it('returns updated event and replaces it in events', async () => {
    const original = makeEvent({ id: 5, title: 'Original' })
    const updated = makeEvent({ id: 5, title: 'Updated' })
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => updated,
    })

    const store = useCalendarStore()
    store.events = [original]

    const result = await store.updateEvent(5, { title: 'Updated' })

    expect(result).toEqual(updated)
    expect(store.events[0].title).toBe('Updated')
  })

  it('returns null and sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Not found' }),
    })

    const store = useCalendarStore()
    store.events = [makeEvent({ id: 5 })]

    const result = await store.updateEvent(5, { title: 'Fail' })

    expect(result).toBeNull()
    expect(store.error).toBeTruthy()
  })
})

describe('useCalendarStore — deleteEvent', () => {
  it('returns true and removes event from events on 204', async () => {
    mockFetch.mockResolvedValueOnce({ ok: true, status: 204, json: async () => ({}) })

    const store = useCalendarStore()
    store.events = [makeEvent({ id: 3 }), makeEvent({ id: 4 })]

    const result = await store.deleteEvent(3)

    expect(result).toBe(true)
    expect(store.events).toHaveLength(1)
    expect(store.events.find(e => e.id === 3)).toBeUndefined()
  })

  it('returns false on failure', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, status: 403, json: async () => ({}) })

    const store = useCalendarStore()
    store.events = [makeEvent({ id: 3 })]

    const result = await store.deleteEvent(3)

    expect(result).toBe(false)
    expect(store.events).toHaveLength(1)
  })
})

describe('useCalendarStore — getEventsByDate', () => {
  it('filters events that fall within the given date', () => {
    const events = [
      makeEvent({ id: 1, startDate: '2026-03-26T00:00:00', endDate: '2026-03-26T23:59:59' }),
      makeEvent({ id: 2, startDate: '2026-03-25T00:00:00', endDate: '2026-03-27T23:59:59' }),
      makeEvent({ id: 3, startDate: '2026-03-27T00:00:00', endDate: '2026-03-28T23:59:59' }),
    ]

    const store = useCalendarStore()
    store.events = events

    const result = store.getEventsByDate(new Date('2026-03-26'))

    expect(result).toHaveLength(2)
    expect(result.map(e => e.id)).toContain(1)
    expect(result.map(e => e.id)).toContain(2)
  })

  it('returns empty array when no events match the date', () => {
    const store = useCalendarStore()
    store.events = [
      makeEvent({ id: 1, startDate: '2026-03-01T00:00:00', endDate: '2026-03-10T23:59:59' }),
    ]

    const result = store.getEventsByDate(new Date('2026-03-26'))

    expect(result).toEqual([])
  })
})
