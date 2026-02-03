import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { FileResource } from '@/types'
import { useAuthStore } from '@/stores/auth'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export interface BreadcrumbItem {
  id: number | null
  name: string
}

export const useFilesStore = defineStore('files', () => {
  const authStore = useAuthStore()

  const files = ref<FileResource[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const currentFolderId = ref<number | null>(null)
  const breadcrumbs = ref<BreadcrumbItem[]>([{ id: null, name: 'Racine' }])
  const sortField = ref<string>('fileName')
  const sortOrder = ref<string>('ASC')
  const filterType = ref<string>('')
  const searchQuery = ref<string>('')

  const folders = computed(() => files.value.filter(f => f.isFolder))
  const regularFiles = computed(() => files.value.filter(f => !f.isFolder))

  async function fetchFiles(roomId: number): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (currentFolderId.value) params.set('parent', String(currentFolderId.value))
      if (searchQuery.value) params.set('search', searchQuery.value)
      if (filterType.value) params.set('type', filterType.value)
      params.set('sort', sortField.value)
      params.set('order', sortOrder.value)

      const response = await fetch(`${API_URL}/rooms/${roomId}/files?${params}`, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data['hydra:description'] || data.detail || 'Failed to fetch files')
      }

      const data = await response.json()
      files.value = Array.isArray(data) ? data : (data['hydra:member'] || data.member || [])
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch files'
    } finally {
      loading.value = false
    }
  }

  async function uploadFile(roomId: number, file: File, onProgress?: (pct: number) => void): Promise<boolean> {
    error.value = null
    return new Promise((resolve) => {
      const xhr = new XMLHttpRequest()
      xhr.open('POST', `${API_URL}/rooms/${roomId}/files`)

      const token = authStore.token
      if (token) {
        xhr.setRequestHeader('Authorization', `Bearer ${token}`)
      }

      if (onProgress) {
        xhr.upload.onprogress = (e) => {
          if (e.lengthComputable) {
            onProgress(Math.round((e.loaded / e.total) * 100))
          }
        }
      }

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve(true)
        } else {
          try {
            const data = JSON.parse(xhr.responseText)
            error.value = data['hydra:description'] || data.detail || 'Upload failed'
          } catch {
            error.value = 'Upload failed'
          }
          resolve(false)
        }
      }

      xhr.onerror = () => {
        error.value = 'Upload failed (network error)'
        resolve(false)
      }

      const formData = new FormData()
      formData.append('file', file)
      if (currentFolderId.value) {
        formData.append('parentId', String(currentFolderId.value))
      }

      xhr.send(formData)
    })
  }

  async function createFolder(roomId: number, name: string): Promise<boolean> {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/rooms/${roomId}/files/folder`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify({
          name,
          parentId: currentFolderId.value
        })
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data['hydra:description'] || data.detail || 'Failed to create folder')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create folder'
      return false
    }
  }

  async function deleteFile(roomId: number, fileId: number): Promise<boolean> {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/rooms/${roomId}/files/${fileId}`, {
        method: 'DELETE',
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok && response.status !== 204) {
        throw new Error('Failed to delete')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete'
      return false
    }
  }

  async function renameFile(roomId: number, fileId: number, newName: string): Promise<boolean> {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/rooms/${roomId}/files/${fileId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify({ fileName: newName })
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data['hydra:description'] || data.detail || 'Failed to rename')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to rename'
      return false
    }
  }

  async function moveFile(roomId: number, fileId: number, targetFolderId: number | null): Promise<boolean> {
    error.value = null
    try {
      const body: Record<string, unknown> = {}
      if (targetFolderId === null) {
        body.moveToRoot = true
      } else {
        body.parentId = targetFolderId
      }

      const response = await fetch(`${API_URL}/rooms/${roomId}/files/${fileId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          ...authStore.getAuthHeaders()
        },
        body: JSON.stringify(body)
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data['hydra:description'] || data.detail || 'Failed to move')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to move'
      return false
    }
  }

  async function downloadFile(roomId: number, file: FileResource): Promise<void> {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/rooms/${roomId}/files/${file.id}/download`, {
        headers: authStore.getAuthHeaders()
      })

      if (!response.ok) {
        throw new Error('Failed to download')
      }

      const blob = await response.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = file.fileName
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to download'
    }
  }

  function navigateToFolder(roomId: number, folder: FileResource) {
    currentFolderId.value = folder.id
    breadcrumbs.value.push({ id: folder.id, name: folder.fileName })
    searchQuery.value = ''
    fetchFiles(roomId)
  }

  function navigateToBreadcrumb(roomId: number, index: number) {
    const item = breadcrumbs.value[index]
    currentFolderId.value = item.id
    breadcrumbs.value = breadcrumbs.value.slice(0, index + 1)
    searchQuery.value = ''
    fetchFiles(roomId)
  }

  function resetNavigation() {
    currentFolderId.value = null
    breadcrumbs.value = [{ id: null, name: 'Racine' }]
    searchQuery.value = ''
    filterType.value = ''
    sortField.value = 'fileName'
    sortOrder.value = 'ASC'
  }

  return {
    files,
    loading,
    error,
    currentFolderId,
    breadcrumbs,
    sortField,
    sortOrder,
    filterType,
    searchQuery,
    folders,
    regularFiles,
    fetchFiles,
    uploadFile,
    createFolder,
    deleteFile,
    renameFile,
    moveFile,
    downloadFile,
    navigateToFolder,
    navigateToBreadcrumb,
    resetNavigation
  }
})
