import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useInvitationStore } from '@/stores/invitation'
import type { Invitation } from '@/types'

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

const makeInvitation = (overrides: Partial<Invitation> = {}): Invitation => ({
  id: 1,
  email: 'user@example.com',
  status: 'pending',
  createdAt: '2026-03-01T00:00:00',
  expiresAt: '2026-04-01T00:00:00',
  invitedBy: { id: 1, displayName: 'Admin' },
  ...overrides,
})

describe('useInvitationStore — fetchInvitations', () => {
  it('parses hydra:member and stores invitations', async () => {
    const invitations = [makeInvitation({ id: 1 }), makeInvitation({ id: 2, email: 'b@b.com' })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': invitations }),
    })

    const store = useInvitationStore()
    await store.fetchInvitations()

    expect(store.invitations).toEqual(invitations)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, json: async () => ({}) })

    const store = useInvitationStore()
    await store.fetchInvitations()

    expect(store.error).toBeTruthy()
    expect(store.invitations).toEqual([])
  })
})

describe('useInvitationStore — sendInvitation', () => {
  it('calls POST and refreshes invitations on success', async () => {
    const invitation = makeInvitation({ id: 10, email: 'new@example.com' })
    // First call: POST /invitations, second call: GET /invitations (fetchInvitations inside sendInvitation)
    mockFetch
      .mockResolvedValueOnce({ ok: true, json: async () => invitation })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ 'hydra:member': [invitation] }),
      })

    const store = useInvitationStore()
    const result = await store.sendInvitation('new@example.com')

    expect(result).toBe(true)
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/invitations'),
      expect.objectContaining({ method: 'POST' }),
    )
  })

  it('sets error and returns false on API failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Already invited' }),
    })

    const store = useInvitationStore()
    const result = await store.sendInvitation('bad@example.com')

    expect(result).toBe(false)
    expect(store.error).toBeTruthy()
  })
})

describe('useInvitationStore — cancelInvitation', () => {
  it('returns true and removes invitation from list', async () => {
    mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) })

    const store = useInvitationStore()
    store.invitations = [makeInvitation({ id: 5 }), makeInvitation({ id: 6 })]

    const result = await store.cancelInvitation(5)

    expect(result).toBe(true)
    expect(store.invitations).toHaveLength(1)
    expect(store.invitations.find(i => i.id === 5)).toBeUndefined()
  })

  it('returns false and keeps list intact on failure', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, json: async () => ({}) })

    const store = useInvitationStore()
    store.invitations = [makeInvitation({ id: 5 })]

    const result = await store.cancelInvitation(5)

    expect(result).toBe(false)
    expect(store.invitations).toHaveLength(1)
  })
})

describe('useInvitationStore — acceptInvitation', () => {
  it('returns success true on ok response', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ accepted: true }),
    })

    const store = useInvitationStore()
    const result = await store.acceptInvitation('valid-token-abc')

    expect(result.success).toBe(true)
    expect(result.data).toEqual({ accepted: true })
  })

  it('returns success false and sets error on failure', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ detail: 'Token expired' }),
    })

    const store = useInvitationStore()
    const result = await store.acceptInvitation('expired-token')

    expect(result.success).toBe(false)
    expect(store.error).toBeTruthy()
  })
})
