import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useRoomsStore } from '@/stores/rooms'
import type { Room } from '@/types'

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

const makeRoom = (overrides: Partial<Room> = {}): Room => ({
  id: 1,
  name: 'Test Room',
  visibility: 'private',
  ...overrides,
} as Room)

describe('useRoomsStore — fetchRooms', () => {
  it('parses hydra:member format and stores rooms', async () => {
    const rooms = [makeRoom({ id: 1, name: 'Room A' }), makeRoom({ id: 2, name: 'Room B' })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': rooms }),
    })

    const store = useRoomsStore()
    await store.fetchRooms()

    expect(store.rooms).toEqual(rooms)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('parses member format (non-hydra)', async () => {
    const rooms = [makeRoom({ id: 3, name: 'Room C' })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ member: rooms }),
    })

    const store = useRoomsStore()
    await store.fetchRooms()

    expect(store.rooms).toEqual(rooms)
  })

  it('sets error on fetch failure', async () => {
    mockFetch.mockRejectedValueOnce(new Error('network error'))

    const store = useRoomsStore()
    await store.fetchRooms()

    expect(store.error).toBeTruthy()
    expect(store.rooms).toEqual([])
    expect(store.loading).toBe(false)
  })
})

describe('useRoomsStore — createRoom', () => {
  it('adds created room to the beginning of the rooms list', async () => {
    const existingRoom = makeRoom({ id: 1, name: 'Existing' })
    const newRoom = makeRoom({ id: 2, name: 'New Room' })

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => newRoom,
    })

    const store = useRoomsStore()
    store.rooms = [existingRoom]

    const result = await store.createRoom({ name: 'New Room', modules: [] })

    expect(result).toEqual(newRoom)
    expect(store.rooms[0]).toEqual(newRoom)
    expect(store.rooms).toHaveLength(2)
  })

  it('sets error and returns null on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ error: 'Forbidden' }),
    })

    const store = useRoomsStore()
    const result = await store.createRoom({ name: 'Bad', modules: [] })

    expect(result).toBeNull()
    expect(store.error).toBeTruthy()
    expect(store.loading).toBe(false)
  })
})

describe('useRoomsStore — deleteRoom', () => {
  it('removes the room from the list on success', async () => {
    const rooms = [makeRoom({ id: 1 }), makeRoom({ id: 2 }), makeRoom({ id: 3 })]
    mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })

    const store = useRoomsStore()
    store.rooms = rooms

    const result = await store.deleteRoom(2)

    expect(result).toBe(true)
    expect(store.rooms).toHaveLength(2)
    expect(store.rooms.find(r => r.id === 2)).toBeUndefined()
  })

  it('returns false and keeps rooms intact on failure', async () => {
    const rooms = [makeRoom({ id: 1 }), makeRoom({ id: 2 })]
    mockFetch.mockResolvedValueOnce({ ok: false, json: async () => ({}) })

    const store = useRoomsStore()
    store.rooms = rooms

    const result = await store.deleteRoom(1)

    expect(result).toBe(false)
    expect(store.rooms).toHaveLength(2)
  })
})

describe('useRoomsStore — initial state', () => {
  it('starts with empty arrays and no error', () => {
    const store = useRoomsStore()
    expect(store.rooms).toEqual([])
    expect(store.modules).toEqual([])
    expect(store.templates).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })
})
