<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  roomId: number
}>()

interface ChatMessage {
  id: string
  userId: number
  userName: string
  content: string
  timestamp: number
}

const authStore = useAuthStore()
const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:3001'

const messages = ref<ChatMessage[]>([])
const newMessage = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const connected = ref(false)

let ydoc: Y.Doc | null = null
let provider: WebsocketProvider | null = null
let yMessages: Y.Array<ChatMessage> | null = null

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

function sendMessage() {
  if (!newMessage.value.trim() || !yMessages || !authStore.user) return

  const message: ChatMessage = {
    id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    userId: authStore.user.id,
    userName: authStore.user.displayName,
    content: newMessage.value.trim(),
    timestamp: Date.now()
  }

  yMessages.push([message])
  newMessage.value = ''
}

function formatTime(timestamp: number): string {
  return new Date(timestamp).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function connectToRoom() {
  if (ydoc) {
    provider?.disconnect()
    provider?.destroy()
    ydoc.destroy()
  }

  ydoc = new Y.Doc()
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-chat`, ydoc)

  provider.on('status', (event: { status: string }) => {
    connected.value = event.status === 'connected'
  })

  yMessages = ydoc.getArray<ChatMessage>('messages')

  // Sync messages
  messages.value = yMessages.toArray()

  yMessages.observe(() => {
    if (yMessages) {
      messages.value = yMessages.toArray()
      scrollToBottom()
    }
  })
}

onMounted(() => {
  connectToRoom()
})

onUnmounted(() => {
  if (provider) {
    provider.disconnect()
    provider.destroy()
  }
  if (ydoc) {
    ydoc.destroy()
  }
})

watch(() => props.roomId, () => {
  connectToRoom()
})
</script>

<template>
  <div class="chat-module h-full flex flex-col bg-base-100">
    <!-- Header -->
    <div class="p-3 border-b border-base-300 flex items-center justify-between">
      <h3 class="font-semibold">Chat</h3>
      <div class="flex items-center gap-2">
        <span
          class="w-2 h-2 rounded-full"
          :class="connected ? 'bg-success' : 'bg-error'"
        ></span>
        <span class="text-xs text-base-content/60">
          {{ connected ? 'Connecté' : 'Déconnecté' }}
        </span>
      </div>
    </div>

    <!-- Messages -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div v-if="messages.length === 0" class="text-center text-base-content/50 py-8">
        <p>Aucun message</p>
        <p class="text-sm">Commencez la conversation !</p>
      </div>

      <div
        v-for="msg in messages"
        :key="msg.id"
        class="chat"
        :class="msg.userId === authStore.user?.id ? 'chat-end' : 'chat-start'"
      >
        <div class="chat-header mb-1">
          <span class="font-medium text-sm">{{ msg.userName }}</span>
          <time class="text-xs opacity-50 ml-2">{{ formatTime(msg.timestamp) }}</time>
        </div>
        <div
          class="chat-bubble"
          :class="msg.userId === authStore.user?.id ? 'chat-bubble-primary' : ''"
        >
          {{ msg.content }}
        </div>
      </div>
    </div>

    <!-- Input -->
    <div class="p-3 border-t border-base-300">
      <form @submit.prevent="sendMessage" class="flex gap-2">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Votre message..."
          class="input input-bordered flex-1"
          :disabled="!connected"
        />
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="!newMessage.trim() || !connected"
        >
          Envoyer
        </button>
      </form>
    </div>
  </div>
</template>
