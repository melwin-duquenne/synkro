<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  roomId: number
  floating?: boolean
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
const isOpen = ref(false)
const unreadCount = ref(0)

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
      const oldLength = messages.value.length
      messages.value = yMessages.toArray()
      
      // Incrémenter les non-lus si le chat est fermé et que ce n'est pas notre message
      if (props.floating && !isOpen.value && messages.value.length > oldLength) {
        const lastMessage = messages.value[messages.value.length - 1]
        if (lastMessage && lastMessage.userId !== authStore.user?.id) {
          unreadCount.value++
        }
      }
      
      scrollToBottom()
    }
  })
}

function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    unreadCount.value = 0
    scrollToBottom()
  }
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
  <!-- Mode flottant -->
  <div v-if="floating" class="fixed bottom-6 right-6 z-50">
    <!-- Bouton flottant -->
    <button
      v-if="!isOpen"
      @click="toggleChat"
      class="btn btn-circle btn-primary btn-lg shadow-lg relative"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
      <!-- Badge non-lus -->
      <span v-if="unreadCount > 0" class="absolute -top-2 -right-2 badge badge-error badge-sm">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Fenêtre de chat -->
    <div
      v-else
      class="chat-window bg-base-100 shadow-2xl rounded-lg border border-base-300 w-80 sm:w-96 h-[500px] flex flex-col"
    >
      <!-- Header -->
      <div class="p-3 border-b border-base-300 flex items-center justify-between bg-primary text-primary-content rounded-t-lg">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <h3 class="font-semibold">Chat</h3>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1">
            <span
              class="w-2 h-2 rounded-full"
              :class="connected ? 'bg-success' : 'bg-error'"
            ></span>
            <span class="text-xs opacity-80">
              {{ connected ? 'Connecté' : 'Déconnecté' }}
            </span>
          </div>
          <button @click="toggleChat" class="btn btn-ghost btn-xs btn-circle">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3 bg-base-200">
        <div v-if="messages.length === 0" class="text-center text-base-content/50 py-8">
          <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
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
            class="chat-bubble text-sm"
            :class="msg.userId === authStore.user?.id ? 'chat-bubble-primary' : ''"
          >
            {{ msg.content }}
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="p-3 border-t border-base-300 bg-base-100">
        <form @submit.prevent="sendMessage" class="flex gap-2">
          <input
            v-model="newMessage"
            type="text"
            placeholder="Votre message..."
            class="input input-bordered input-sm flex-1"
            :disabled="!connected"
          />
          <button
            type="submit"
            class="btn btn-primary btn-sm"
            :disabled="!newMessage.trim() || !connected"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Mode normal (dans la page room) -->
  <div v-else class="chat-module h-full flex flex-col bg-base-100">
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
