import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAdminStore } from '@/stores/admin'
import type { AdminUser } from '@/types'

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

const makeUser = (overrides: Partial<AdminUser> = {}): AdminUser => ({
  id: 1,
  email: 'user@example.com',
  displayName: 'Alice',
  role: 'user',
  avatarUrl: null,
  team: null,
  createdAt: '2026-01-01T00:00:00',
  ...overrides,
})

describe('useAdminStore — fetchUsers', () => {
  it('stores users from hydra:member', async () => {
    const users = [makeUser({ id: 1 }), makeUser({ id: 2, email: 'b@b.com' })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': users }),
    })

    const store = useAdminStore()
    await store.fetchUsers()

    expect(store.users).toEqual(users)
    expect(store.error).toBeNull()
  })

  it('sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, json: async () => ({}) })

    const store = useAdminStore()
    await store.fetchUsers()

    expect(store.error).toBeTruthy()
    expect(store.users).toEqual([])
  })

  it('resets loading to false after the call', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': [] }),
    })

    const store = useAdminStore()
    await store.fetchUsers()

    expect(store.loading).toBe(false)
  })
})

describe('useAdminStore — updateUser', () => {
  it('updates the user in list and returns true', async () => {
    const original = makeUser({ id: 7, role: 'user' })
    const updated = makeUser({ id: 7, role: 'admin' })
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => updated,
    })

    const store = useAdminStore()
    store.users = [original]

    const result = await store.updateUser(7, { role: 'admin' })

    expect(result).toBe(true)
    expect(store.users[0].role).toBe('admin')
  })

  it('returns false and sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Forbidden' }),
    })

    const store = useAdminStore()
    store.users = [makeUser({ id: 7 })]

    const result = await store.updateUser(7, { role: 'admin' })

    expect(result).toBe(false)
    expect(store.error).toBeTruthy()
    expect(store.users[0].role).toBe('user')
  })
})

describe('useAdminStore — deleteUser', () => {
  it('removes user from list and returns true', async () => {
    mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })

    const store = useAdminStore()
    store.users = [makeUser({ id: 3 }), makeUser({ id: 4 })]

    const result = await store.deleteUser(3)

    expect(result).toBe(true)
    expect(store.users).toHaveLength(1)
    expect(store.users.find(u => u.id === 3)).toBeUndefined()
  })

  it('returns false and keeps list intact on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Not found' }),
    })

    const store = useAdminStore()
    store.users = [makeUser({ id: 3 })]

    const result = await store.deleteUser(3)

    expect(result).toBe(false)
    expect(store.users).toHaveLength(1)
  })
})
