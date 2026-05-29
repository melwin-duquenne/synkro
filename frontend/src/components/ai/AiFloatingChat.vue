<script setup lang="ts">
import { ref, nextTick } from 'vue'
import { useAi } from '@/composables/useAi'

const { chat, loading, isAvailable } = useAi()

const open = ref(false)
const prompt = ref('')
const messagesEndRef = ref<HTMLDivElement | null>(null)

interface Message {
  role: 'user' | 'assistant'
  content: string
}

const messages = ref<Message[]>([])

async function handleSend() {
  const text = prompt.value.trim()
  if (!text || loading.value) return

  messages.value.push({ role: 'user', content: text })
  prompt.value = ''
  await scrollToBottom()

  const response = await chat(text, 'assistant')
  if (response) {
    messages.value.push({ role: 'assistant', content: response })
    await scrollToBottom()
  }
}

async function scrollToBottom() {
  await nextTick()
  messagesEndRef.value?.scrollIntoView({ behavior: 'smooth' })
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    handleSend()
  }
}
</script>

<template>
  <div v-if="isAvailable()" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

    <!-- Chat panel -->
    <Transition name="chat-panel">
      <div
        v-if="open"
        class="w-80 bg-base-100 rounded-2xl shadow-2xl border border-base-300 flex flex-col overflow-hidden"
        style="height: 420px"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-primary text-primary-content">
          <div class="flex items-center gap-2 font-semibold text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Assistant IA
          </div>
          <button class="btn btn-ghost btn-xs text-primary-content" @click="open = false">✕</button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto p-3 space-y-3">
          <div v-if="messages.length === 0" class="text-center text-base-content/40 text-sm mt-8">
            Bonjour ! Comment puis-je vous aider ?
          </div>

          <div
            v-for="(msg, i) in messages"
            :key="i"
            class="flex"
            :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[85%] rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap"
              :class="msg.role === 'user'
                ? 'bg-primary text-primary-content rounded-br-sm'
                : 'bg-base-200 text-base-content rounded-bl-sm'"
            >
              {{ msg.content }}
            </div>
          </div>

          <div v-if="loading" class="flex justify-start">
            <div class="bg-base-200 rounded-2xl rounded-bl-sm px-3 py-2">
              <span class="loading loading-dots loading-xs"></span>
            </div>
          </div>

          <div ref="messagesEndRef" />
        </div>

        <!-- Input -->
        <div class="p-3 border-t border-base-300">
          <div class="flex gap-2">
            <textarea
              v-model="prompt"
              class="textarea textarea-bordered textarea-sm flex-1 resize-none text-sm"
              placeholder="Posez votre question..."
              rows="2"
              @keydown="handleKeydown"
            />
            <button
              class="btn btn-primary btn-sm self-end"
              :disabled="loading || !prompt.trim()"
              @click="handleSend"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Bubble button -->
    <button
      class="btn btn-primary btn-circle shadow-lg w-14 h-14"
      @click="open = !open"
    >
      <svg v-if="!open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
      </svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<style scoped>
.chat-panel-enter-active,
.chat-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.chat-panel-enter-from,
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.97);
}
</style>
