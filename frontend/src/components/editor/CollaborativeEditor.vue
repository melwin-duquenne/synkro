<script setup lang="ts">
import { onMounted, onUnmounted, watch, computed } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Collaboration from '@tiptap/extension-collaboration'
import CollaborationCursor from '@tiptap/extension-collaboration-cursor'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  roomId: number
}>()

const authStore = useAuthStore()

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:3001'

// Generate random color for cursor
const colors = ['#958DF1', '#F98181', '#FBBC88', '#FAF594', '#70CFF8', '#94FADB', '#B9F18D']
const userColor = colors[Math.floor(Math.random() * colors.length)]

let ydoc: Y.Doc | null = null
let provider: WebsocketProvider | null = null

const editor = useEditor({
  extensions: [
    StarterKit.configure({
      history: false // Disable default history, use Yjs
    }),
    Collaboration.configure({
      document: null as unknown as Y.Doc // Will be set on mount
    }),
    CollaborationCursor.configure({
      provider: null as unknown as WebsocketProvider, // Will be set on mount
      user: {
        name: authStore.user?.displayName || 'Anonymous',
        color: userColor
      }
    })
  ],
  content: '',
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl focus:outline-none min-h-[300px] p-4'
    }
  }
})

const connectionStatus = computed(() => {
  if (!provider) return 'disconnected'
  return provider.wsconnected ? 'connected' : 'connecting'
})

onMounted(() => {
  // Create Yjs document
  ydoc = new Y.Doc()

  // Connect to WebSocket
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc)

  // Update editor with Yjs
  if (editor.value) {
    editor.value.extensionManager.extensions.forEach(ext => {
      if (ext.name === 'collaboration') {
        ext.options.document = ydoc
      }
      if (ext.name === 'collaborationCursor') {
        ext.options.provider = provider
      }
    })

    // Recreate editor with correct config
    editor.value.destroy()
  }

  // Create new editor with proper Yjs integration
  editor.value = useEditor({
    extensions: [
      StarterKit.configure({
        history: false
      }),
      Collaboration.configure({
        document: ydoc
      }),
      CollaborationCursor.configure({
        provider: provider,
        user: {
          name: authStore.user?.displayName || 'Anonymous',
          color: userColor
        }
      })
    ],
    editorProps: {
      attributes: {
        class: 'prose prose-sm sm:prose focus:outline-none min-h-[300px] p-4'
      }
    }
  }).value
})

onUnmounted(() => {
  if (provider) {
    provider.disconnect()
    provider.destroy()
  }
  if (ydoc) {
    ydoc.destroy()
  }
  if (editor.value) {
    editor.value.destroy()
  }
})

// Watch for room changes
watch(() => props.roomId, () => {
  // Reconnect to new room
  if (provider) {
    provider.disconnect()
    provider.destroy()
  }
  if (ydoc) {
    ydoc.destroy()
  }

  ydoc = new Y.Doc()
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc)

  if (editor.value) {
    editor.value.destroy()
    editor.value = useEditor({
      extensions: [
        StarterKit.configure({ history: false }),
        Collaboration.configure({ document: ydoc }),
        CollaborationCursor.configure({
          provider: provider,
          user: {
            name: authStore.user?.displayName || 'Anonymous',
            color: userColor
          }
        })
      ],
      editorProps: {
        attributes: {
          class: 'prose prose-sm sm:prose focus:outline-none min-h-[300px] p-4'
        }
      }
    }).value
  }
})
</script>

<template>
  <div class="collaborative-editor h-full flex flex-col">
    <!-- Toolbar -->
    <div v-if="editor" class="toolbar flex flex-wrap gap-1 p-2 border-b border-base-300 bg-base-200">
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('bold') }"
        @click="editor.chain().focus().toggleBold().run()"
        title="Gras"
      >
        <strong>B</strong>
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('italic') }"
        @click="editor.chain().focus().toggleItalic().run()"
        title="Italique"
      >
        <em>I</em>
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('strike') }"
        @click="editor.chain().focus().toggleStrike().run()"
        title="Barré"
      >
        <s>S</s>
      </button>
      <div class="divider divider-horizontal mx-1"></div>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('heading', { level: 1 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        title="Titre 1"
      >
        H1
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('heading', { level: 2 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        title="Titre 2"
      >
        H2
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('heading', { level: 3 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        title="Titre 3"
      >
        H3
      </button>
      <div class="divider divider-horizontal mx-1"></div>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('bulletList') }"
        @click="editor.chain().focus().toggleBulletList().run()"
        title="Liste à puces"
      >
        • Liste
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('orderedList') }"
        @click="editor.chain().focus().toggleOrderedList().run()"
        title="Liste numérotée"
      >
        1. Liste
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('blockquote') }"
        @click="editor.chain().focus().toggleBlockquote().run()"
        title="Citation"
      >
        " Citation
      </button>
      <button
        class="btn btn-sm btn-ghost"
        :class="{ 'btn-active': editor.isActive('codeBlock') }"
        @click="editor.chain().focus().toggleCodeBlock().run()"
        title="Bloc de code"
      >
        &lt;/&gt;
      </button>

      <div class="flex-1"></div>

      <!-- Connection status -->
      <div class="flex items-center gap-2 text-sm">
        <span
          class="w-2 h-2 rounded-full"
          :class="{
            'bg-success': connectionStatus === 'connected',
            'bg-warning': connectionStatus === 'connecting',
            'bg-error': connectionStatus === 'disconnected'
          }"
        ></span>
        <span class="text-base-content/60">
          {{ connectionStatus === 'connected' ? 'Connecté' : connectionStatus === 'connecting' ? 'Connexion...' : 'Déconnecté' }}
        </span>
      </div>
    </div>

    <!-- Editor content -->
    <div class="flex-1 overflow-auto bg-base-100">
      <EditorContent :editor="editor" class="h-full" />
    </div>
  </div>
</template>

<style>
.collaborative-editor .ProseMirror {
  min-height: 300px;
  padding: 1rem;
}

.collaborative-editor .ProseMirror:focus {
  outline: none;
}

/* Collaboration cursor styles */
.collaboration-cursor__caret {
  border-left: 1px solid;
  border-right: 1px solid;
  margin-left: -1px;
  margin-right: -1px;
  pointer-events: none;
  position: relative;
  word-break: normal;
}

.collaboration-cursor__label {
  border-radius: 3px 3px 3px 0;
  color: #fff;
  font-size: 12px;
  font-style: normal;
  font-weight: 600;
  left: -1px;
  line-height: normal;
  padding: 0.1rem 0.3rem;
  position: absolute;
  top: -1.4em;
  user-select: none;
  white-space: nowrap;
}
</style>
