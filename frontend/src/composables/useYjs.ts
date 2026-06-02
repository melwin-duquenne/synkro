import { ref, onUnmounted } from 'vue'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { useAuthStore } from '@/stores/auth'

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:3001'

export function useYjs(roomId: string) {
  const authStore = useAuthStore()
  const ydoc = ref<Y.Doc | null>(null)
  const provider = ref<WebsocketProvider | null>(null)
  const connected = ref(false)
  const synced = ref(false)

  function connect() {
    if (ydoc.value) return

    ydoc.value = new Y.Doc()
    provider.value = new WebsocketProvider(WS_URL, roomId, ydoc.value, {
      params: { token: authStore.token ?? '' },
    })

    provider.value.on('status', (event: { status: string }) => {
      connected.value = event.status === 'connected'
    })

    provider.value.on('sync', (isSynced: boolean) => {
      synced.value = isSynced
    })
  }

  function disconnect() {
    if (provider.value) {
      provider.value.disconnect()
      provider.value.destroy()
      provider.value = null
    }
    if (ydoc.value) {
      ydoc.value.destroy()
      ydoc.value = null
    }
    connected.value = false
    synced.value = false
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    ydoc,
    provider,
    connected,
    synced,
    connect,
    disconnect
  }
}
