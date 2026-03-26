import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

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

describe('useAuthStore — login', () => {
  it('stores token and fetches user on successful login', async () => {
    mockFetch
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ token: 'test-token-123' }),
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ id: 1, displayName: 'Alice', email: 'alice@example.com' }),
      })

    const store = useAuthStore()
    const result = await store.login({ email: 'alice@example.com', password: 'secret' })

    expect(result).toBe(true)
    expect(store.token).toBe('test-token-123')
    expect(localStorage.getItem('token')).toBe('test-token-123')
    expect(store.user).toMatchObject({ id: 1, displayName: 'Alice' })
    expect(store.error).toBeNull()
  })

  it('sets error and returns false on failed login', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ message: 'Invalid credentials' }),
    })

    const store = useAuthStore()
    const result = await store.login({ email: 'bad@example.com', password: 'wrong' })

    expect(result).toBe(false)
    expect(store.token).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
    expect(store.error).toBe('Invalid credentials')
  })

  it('sets generic error message when API response has no message', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({}),
    })

    const store = useAuthStore()
    await store.login({ email: 'a@b.com', password: 'x' })

    expect(store.error).toBeTruthy()
    expect(store.token).toBeNull()
  })

  it('resets loading to false after login attempt', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ message: 'fail' }),
    })

    const store = useAuthStore()
    await store.login({ email: 'a@b.com', password: 'x' })

    expect(store.loading).toBe(false)
  })
})

describe('useAuthStore — logout', () => {
  it('clears token, user, and localStorage on logout', async () => {
    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({ token: 'tok' }) })
      .mockResolvedValueOnce({ ok: true, json: async () => ({ id: 1, displayName: 'Bob' }) })

    const store = useAuthStore()
    await store.login({ email: 'b@b.com', password: '123' })
    expect(store.isAuthenticated).toBe(true)

    store.logout()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem('token')).toBeNull()
  })
})

describe('useAuthStore — getAuthHeaders', () => {
  it('returns empty object when not authenticated', () => {
    const store = useAuthStore()
    expect(store.getAuthHeaders()).toEqual({})
  })

  it('returns Authorization header when token is set', async () => {
    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => ({ token: 'my-jwt' }) })
      .mockResolvedValueOnce({ ok: true, json: async () => ({ id: 2, displayName: 'Carol' }) })

    const store = useAuthStore()
    await store.login({ email: 'c@c.com', password: 'pass' })

    expect(store.getAuthHeaders()).toEqual({ Authorization: 'Bearer my-jwt' })
  })
})

describe('useAuthStore — isAuthenticated', () => {
  it('is false when no token in localStorage', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
  })

  it('is true when token exists in localStorage at store creation', () => {
    localStorage.setItem('token', 'persisted-token')
    // Mock the fetchUser call triggered on creation
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ id: 3, displayName: 'Dave' }),
    })
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(true)
    expect(store.token).toBe('persisted-token')
  })
})

describe('useAuthStore — initial state', () => {
  it('starts with loading false and no error', () => {
    const store = useAuthStore()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })
})
