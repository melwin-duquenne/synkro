import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useFilesStore } from '@/stores/files'
import type { FileResource } from '@/types'

const mockFetch = vi.fn()

beforeEach(() => {
  localStorage.clear()
  mockFetch.mockReset()
  vi.stubGlobal('fetch', mockFetch)
  // Default: empty file list
  mockFetch.mockResolvedValue({
    ok: true,
    json: async () => ({ 'hydra:member': [] }),
  })
  setActivePinia(createPinia())
})

afterEach(() => {
  vi.unstubAllGlobals()
})

const makeFile = (overrides: Partial<FileResource> = {}): FileResource => ({
  id: 1,
  fileName: 'file.txt',
  isFolder: false,
  mimeType: 'text/plain',
  fileSize: 1024,
  createdAt: '2026-01-01T00:00:00',
  ...overrides,
} as FileResource)

describe('useFilesStore — initial state', () => {
  it('starts at root with default breadcrumb', () => {
    const store = useFilesStore()
    expect(store.currentFolderId).toBeNull()
    expect(store.breadcrumbs).toHaveLength(1)
    expect(store.breadcrumbs[0]).toEqual({ id: null, name: 'Racine' })
    expect(store.files).toEqual([])
  })

  it('starts with default sort settings', () => {
    const store = useFilesStore()
    expect(store.sortField).toBe('fileName')
    expect(store.sortOrder).toBe('ASC')
    expect(store.filterType).toBe('')
    expect(store.searchQuery).toBe('')
  })
})

describe('useFilesStore — computed', () => {
  it('folders computed returns only folders', () => {
    const store = useFilesStore()
    store.files = [
      makeFile({ id: 1, isFolder: true }),
      makeFile({ id: 2, isFolder: false }),
      makeFile({ id: 3, isFolder: true }),
    ]
    expect(store.folders).toHaveLength(2)
    expect(store.folders.every(f => f.isFolder)).toBe(true)
  })

  it('regularFiles computed returns only non-folders', () => {
    const store = useFilesStore()
    store.files = [
      makeFile({ id: 1, isFolder: true }),
      makeFile({ id: 2, isFolder: false }),
      makeFile({ id: 3, isFolder: false }),
    ]
    expect(store.regularFiles).toHaveLength(2)
    expect(store.regularFiles.every(f => !f.isFolder)).toBe(true)
  })
})

describe('useFilesStore — navigateToFolder', () => {
  it('updates currentFolderId and appends to breadcrumbs', () => {
    const store = useFilesStore()
    const folder = makeFile({ id: 5, fileName: 'Documents', isFolder: true })

    store.navigateToFolder(1, folder)

    expect(store.currentFolderId).toBe(5)
    expect(store.breadcrumbs).toHaveLength(2)
    expect(store.breadcrumbs[1]).toEqual({ id: 5, name: 'Documents' })
  })

  it('clears searchQuery on navigation', () => {
    const store = useFilesStore()
    store.searchQuery = 'old query'

    store.navigateToFolder(1, makeFile({ id: 2, isFolder: true }))

    expect(store.searchQuery).toBe('')
  })

  it('can navigate multiple levels deep', () => {
    const store = useFilesStore()
    store.navigateToFolder(1, makeFile({ id: 2, fileName: 'Level1', isFolder: true }))
    store.navigateToFolder(1, makeFile({ id: 3, fileName: 'Level2', isFolder: true }))

    expect(store.breadcrumbs).toHaveLength(3)
    expect(store.breadcrumbs[2]).toEqual({ id: 3, name: 'Level2' })
  })
})

describe('useFilesStore — navigateToBreadcrumb', () => {
  it('goes back to root when clicking index 0', () => {
    const store = useFilesStore()
    store.navigateToFolder(1, makeFile({ id: 2, fileName: 'Docs', isFolder: true }))
    store.navigateToFolder(1, makeFile({ id: 3, fileName: 'Sub', isFolder: true }))
    expect(store.breadcrumbs).toHaveLength(3)

    store.navigateToBreadcrumb(1, 0)

    expect(store.currentFolderId).toBeNull()
    expect(store.breadcrumbs).toHaveLength(1)
    expect(store.breadcrumbs[0]).toEqual({ id: null, name: 'Racine' })
  })

  it('slices breadcrumbs to selected index + 1', () => {
    const store = useFilesStore()
    store.navigateToFolder(1, makeFile({ id: 2, fileName: 'A', isFolder: true }))
    store.navigateToFolder(1, makeFile({ id: 3, fileName: 'B', isFolder: true }))
    store.navigateToFolder(1, makeFile({ id: 4, fileName: 'C', isFolder: true }))

    store.navigateToBreadcrumb(1, 1)

    expect(store.breadcrumbs).toHaveLength(2)
    expect(store.currentFolderId).toBe(2)
  })
})

describe('useFilesStore — resetNavigation', () => {
  it('resets all navigation state to defaults', () => {
    const store = useFilesStore()
    store.navigateToFolder(1, makeFile({ id: 5, fileName: 'Folder', isFolder: true }))
    store.searchQuery = 'test'
    store.filterType = 'image'
    store.sortField = 'createdAt'
    store.sortOrder = 'DESC'

    store.resetNavigation()

    expect(store.currentFolderId).toBeNull()
    expect(store.breadcrumbs).toEqual([{ id: null, name: 'Racine' }])
    expect(store.searchQuery).toBe('')
    expect(store.filterType).toBe('')
    expect(store.sortField).toBe('fileName')
    expect(store.sortOrder).toBe('ASC')
  })
})

describe('useFilesStore — fetchFiles', () => {
  it('stores files from hydra:member response', async () => {
    const files = [makeFile({ id: 1 }), makeFile({ id: 2 })]
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ 'hydra:member': files }),
    })

    const store = useFilesStore()
    await store.fetchFiles(1)

    expect(store.files).toEqual(files)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('sets error when response is not ok', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ 'hydra:description': 'Unauthorized' }),
    })

    const store = useFilesStore()
    await store.fetchFiles(1)

    expect(store.error).toBe('Unauthorized')
    expect(store.loading).toBe(false)
  })
})
