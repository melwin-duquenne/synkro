<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed, shallowRef } from "vue";
import { Editor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Collaboration from "@tiptap/extension-collaboration";
import CollaborationCursor from "@tiptap/extension-collaboration-cursor";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import * as Y from "yjs";
import { WebsocketProvider } from "y-websocket";
import { useAuthStore } from "@/stores/auth";
import AiChatPanel from "@/components/ai/AiChatPanel.vue";

const props = defineProps<{
  roomId: number;
}>();

const authStore = useAuthStore();

const WS_URL = import.meta.env.VITE_WS_URL || "ws://localhost:3001";

// Colors for cursors
const colors = [
  "#958DF1",
  "#F98181",
  "#FBBC88",
  "#FAF594",
  "#70CFF8",
  "#94FADB",
  "#B9F18D",
];
const userColor = colors[Math.floor(Math.random() * colors.length)];

let ydoc: Y.Doc | null = null;
let provider: WebsocketProvider | null = null;

const saving = ref(false);
const lastSaved = ref<Date | null>(null);
const exportingPdf = ref(false);
const isConnected = ref(false);
const showAiPanel = ref(false);
const selectedText = ref("");
const selectionRange = ref<{ from: number; to: number } | null>(null);
const aiRequestHadDocContent = ref(false);
const toastMessage = ref<string | null>(null);
let toastTimeout: ReturnType<typeof setTimeout> | null = null;

function showToast(msg: string) {
  toastMessage.value = msg;
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toastMessage.value = null;
  }, 3000);
}

const editor = shallowRef<Editor | null>(null);

const connectionStatus = computed(() => {
  if (!provider) return "disconnected";
  return isConnected.value ? "connected" : "connecting";
});

function initEditor() {
  // Cleanup previous instances
  if (provider) {
    provider.disconnect();
    provider.destroy();
  }
  if (ydoc) {
    ydoc.destroy();
  }
  if (editor.value) {
    editor.value.destroy();
  }

  // Create Yjs document
  ydoc = new Y.Doc();

  // Connect to WebSocket
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc, {
    params: { token: authStore.token ?? '' },
  });

  provider.on("status", (event: { status: string }) => {
    isConnected.value = event.status === "connected";
  });

  // Create editor with Yjs integration
  editor.value = new Editor({
    extensions: [
      StarterKit.configure({
        history: false, // Disable default history, use Yjs
      }),
      Underline,
      TextAlign.configure({
        types: ["heading", "paragraph"],
      }),
      Collaboration.configure({
        document: ydoc,
      }),
      CollaborationCursor.configure({
        provider: provider,
        user: {
          name: authStore.user?.displayName || "Anonymous",
          color: userColor,
        },
      }),
    ],
    editorProps: {
      attributes: {
        class:
          "prose prose-sm sm:prose max-w-none focus:outline-none min-h-[400px] p-6",
      },
    },
    onUpdate: () => {
      // Auto-save after 2 seconds of inactivity
      debouncedSave();
    },
  });
}

// Debounced save
let saveTimeout: ReturnType<typeof setTimeout> | null = null;
function debouncedSave() {
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
  saveTimeout = setTimeout(() => {
    saveDocument();
  }, 2000);
}

async function saveDocument() {
  if (!editor.value || saving.value) return;

  saving.value = true;
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/document`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/ld+json",
        ...authStore.getAuthHeaders(),
      },
      body: JSON.stringify({
        contentHtml: editor.value.getHTML(),
      }),
    });

    if (response.ok) {
      lastSaved.value = new Date();
    }
  } catch (e) {
    console.error("Failed to save document:", e);
  } finally {
    saving.value = false;
  }
}

async function loadDocument() {
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/document`, {
      headers: authStore.getAuthHeaders(),
    });

    if (response.ok) {
      const data = await response.json();
      // Only set content if editor is empty and we have saved content
      if (editor.value && data.contentHtml && editor.value.isEmpty) {
        editor.value.commands.setContent(data.contentHtml);
      }
    }
  } catch (e) {
    console.error("Failed to load document:", e);
  }
}

async function exportToPdf() {
  if (!editor.value) return;

  exportingPdf.value = true;
  try {
    const html = editor.value.getHTML();

    // Create a styled HTML document for PDF
    const styledHtml = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <style>
          body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            color: #333;
          }
          h1 { font-size: 2em; margin-bottom: 0.5em; border-bottom: 2px solid #333; padding-bottom: 0.3em; }
          h2 { font-size: 1.5em; margin-top: 1.5em; margin-bottom: 0.5em; }
          h3 { font-size: 1.25em; margin-top: 1.2em; margin-bottom: 0.4em; }
          p { margin: 1em 0; }
          ul, ol { margin: 1em 0; padding-left: 2em; }
          li { margin: 0.5em 0; }
          blockquote {
            border-left: 4px solid #ddd;
            margin: 1em 0;
            padding-left: 1em;
            color: #666;
            font-style: italic;
          }
          code {
            background: #f4f4f4;
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-family: 'Consolas', monospace;
          }
          pre {
            background: #f4f4f4;
            padding: 1em;
            border-radius: 5px;
            overflow-x: auto;
          }
          pre code {
            background: none;
            padding: 0;
          }
        </style>
      </head>
      <body>
        ${html}
      </body>
      </html>
    `;

    // Create blob and download
    const blob = new Blob([styledHtml], { type: "text/html" });
    const url = URL.createObjectURL(blob);

    // Open print dialog (user can save as PDF)
    const printWindow = window.open(url, "_blank");
    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print();
      };
    }

    URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Failed to export PDF:", e);
  } finally {
    exportingPdf.value = false;
  }
}

function openAiPanel() {
  if (editor.value) {
    const { from, to } = editor.value.state.selection;
    if (from !== to) {
      selectedText.value = editor.value.state.doc.textBetween(from, to);
      selectionRange.value = { from, to };
    } else {
      selectedText.value = "";
      selectionRange.value = null;
    }
    aiRequestHadDocContent.value = !editor.value?.isEmpty;
  }
  showAiPanel.value = !showAiPanel.value;
}

function insertAiResponse(text: string) {
  if (!editor.value) return;
  if (selectionRange.value) {
    const { from, to } = selectionRange.value;
    editor.value
      .chain()
      .focus()
      .deleteRange({ from, to })
      .insertContentAt(from, text)
      .run();
  } else if (aiRequestHadDocContent.value) {
    editor.value.chain().focus().clearContent().insertContent(text).run();
  } else {
    editor.value.chain().focus().insertContent(text).run();
  }
  selectionRange.value = null;
  aiRequestHadDocContent.value = false;
  showAiPanel.value = false;
  showToast("Modification appliquée — Ctrl+Z pour annuler");
}

function formatLastSaved(date: Date | null): string {
  if (!date) return "";
  const now = new Date();
  const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (diff < 60) return "Sauvegardé il y a quelques secondes";
  if (diff < 3600) return `Sauvegardé il y a ${Math.floor(diff / 60)} min`;
  return `Sauvegardé à ${date.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })}`;
}

onMounted(() => {
  initEditor();
  // Load saved content after a short delay to let Yjs sync first
  setTimeout(() => {
    loadDocument();
  }, 1000);
});

onUnmounted(() => {
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
  if (provider) {
    provider.disconnect();
    provider.destroy();
  }
  if (ydoc) {
    ydoc.destroy();
  }
  if (editor.value) {
    editor.value.destroy();
  }
});

// Watch for room changes
watch(
  () => props.roomId,
  (newId, oldId) => {
    if (newId !== oldId) {
      initEditor();
      setTimeout(() => {
        loadDocument();
      }, 1000);
    }
  },
);
</script>

<template>
  <div class="editor-module h-full flex flex-col bg-[#0a1628] rounded-lg">
    <!-- Toolbar -->
    <div
      v-if="editor"
      class="toolbar flex flex-wrap items-center gap-1 p-3 border-b border-[#4115df]/10 bg-[#1a3a52]"
    >
      <!-- Text formatting -->
      <div class="flex gap-0.5">
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('bold') }"
          @click="editor.chain().focus().toggleBold().run()"
          title="Gras (Ctrl+B)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()"
          title="Italique (Ctrl+I)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('underline') }"
          @click="editor.chain().focus().toggleUnderline().run()"
          title="Souligné (Ctrl+U)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('strike') }"
          @click="editor.chain().focus().toggleStrike().run()"
          title="Barré"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z" />
          </svg>
        </button>
      </div>

      <div class="divider divider-horizontal mx-0.5 h-6 bg-gray-600/30"></div>

      <!-- Headings -->
      <div class="flex gap-0.5">
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('heading', { level: 1 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          title="Titre 1"
        >
          H1
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('heading', { level: 2 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          title="Titre 2"
        >
          H2
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('heading', { level: 3 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          title="Titre 3"
        >
          H3
        </button>
      </div>

      <div class="divider divider-horizontal mx-0.5 h-6 bg-gray-600/30"></div>

      <!-- Lists -->
      <div class="flex gap-0.5">
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('bulletList') }"
          @click="editor.chain().focus().toggleBulletList().run()"
          title="Liste à puces"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('orderedList') }"
          @click="editor.chain().focus().toggleOrderedList().run()"
          title="Liste numérotée"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()"
          title="Citation"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive('codeBlock') }"
          @click="editor.chain().focus().toggleCodeBlock().run()"
          title="Bloc de code"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"
            />
          </svg>
        </button>
      </div>

      <div class="divider divider-horizontal mx-0.5 h-6 bg-gray-600/30"></div>

      <!-- Alignment -->
      <div class="flex gap-0.5">
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive({ textAlign: 'left' }) }"
          @click="editor.chain().focus().setTextAlign('left').run()"
          title="Aligner à gauche"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive({ textAlign: 'center' }) }"
          @click="editor.chain().focus().setTextAlign('center').run()"
          title="Centrer"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"
            />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0]"
          :class="{ 'bg-[#2a4a62]': editor.isActive({ textAlign: 'right' }) }"
          @click="editor.chain().focus().setTextAlign('right').run()"
          title="Aligner à droite"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"
            />
          </svg>
        </button>
      </div>

      <div class="flex-1"></div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <!-- Save status -->
        <span
          v-if="saving"
          class="text-xs text-[#b0b0b0] flex items-center gap-1"
        >
          <span class="loading loading-spinner loading-xs"></span>
          Sauvegarde...
        </span>
        <span v-else-if="lastSaved" class="text-xs text-[#b0b0b0]">
          {{ formatLastSaved(lastSaved) }}
        </span>

        <!-- Manual save -->
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0] hover:bg-[#2a4a62]"
          @click="saveDocument"
          :disabled="saving"
          title="Sauvegarder (Ctrl+S)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
            />
          </svg>
        </button>

        <!-- Export PDF -->
        <button
          class="btn btn-sm btn-ghost rounded-lg text-[#b0b0b0] hover:text-[#e0e0e0] hover:bg-[#2a4a62]"
          @click="exportToPdf"
          :disabled="exportingPdf"
          title="Exporter en PDF"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"
            />
          </svg>
          PDF
        </button>

        <!-- AI assistant -->
        <button
          type="button"
          class="btn btn-sm gap-1"
          :class="showAiPanel ? 'btn-primary' : 'btn-ghost'"
          @click="openAiPanel"
          title="Assistant IA"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-3.5 w-3.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"
            />
          </svg>
          IA
        </button>

        <div class="divider divider-horizontal mx-0.5 h-6"></div>

        <!-- Connection status -->
        <div class="flex items-center gap-1.5">
          <span
            class="w-2 h-2 rounded-full"
            :class="{
              'bg-[#6fdd9f]': connectionStatus === 'connected',
              'bg-[#fbbf24]': connectionStatus === 'connecting',
              'bg-[#ef5350]': connectionStatus === 'disconnected',
            }"
          ></span>
          <span class="text-xs text-[#b0b0b0]">
            {{
              connectionStatus === "connected"
                ? "Connecté"
                : connectionStatus === "connecting"
                  ? "Connexion..."
                  : "Déconnecté"
            }}
          </span>
        </div>
      </div>
    </div>

    <!-- Editor content -->
    <div class="flex-1 overflow-auto">
      <EditorContent v-if="editor" :editor="editor" class="h-full" />
      <div v-else class="flex items-center justify-center h-full">
        <span class="loading loading-spinner loading-lg"></span>
      </div>
    </div>

    <!-- AI panel -->
    <AiChatPanel
      v-if="showAiPanel"
      module="text_editor"
      placeholder="Écris un paragraphe sur... / Compacte ce texte / Corrige les fautes..."
      :selected-text="selectedText"
      :document-content="editor?.getText() ?? ''"
      @insert="insertAiResponse"
      class="m-3"
    />

    <!-- Toast notification -->
    <div v-if="toastMessage" class="toast toast-bottom toast-end z-50">
      <div class="alert alert-success text-sm py-2">
        <span>{{ toastMessage }}</span>
      </div>
    </div>
  </div>
</template>

<style>
.editor-module .ProseMirror {
  min-height: 400px;
  padding: 1.5rem;
  background-color: #0a1628;
  color: #e0e0e0;
}

.editor-module .ProseMirror:focus {
  outline: none;
}

.editor-module .ProseMirror h1 {
  font-size: 2em;
  font-weight: bold;
  margin-bottom: 0.5em;
  color: #e0e0e0;
}

.editor-module .ProseMirror h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 0.5em;
  color: #e0e0e0;
}

.editor-module .ProseMirror h3 {
  font-size: 1.25em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 0.5em;
  color: #e0e0e0;
}

.editor-module .ProseMirror p {
  margin: 1em 0;
  color: #e0e0e0;
}

.editor-module .ProseMirror ul,
.editor-module .ProseMirror ol {
  padding-left: 1.5em;
  margin: 1em 0;
  color: #e0e0e0;
}

.editor-module .ProseMirror blockquote {
  border-left: 3px solid #6b7280;
  padding-left: 1em;
  margin: 1em 0;
  color: #b0b0b0;
  background-color: transparent;
}

.editor-module .ProseMirror pre {
  background: #1a3a52;
  color: #8ab4f8;
  padding: 1em;
  border-radius: 0.5em;
  overflow-x: auto;
  border: 1px solid #2a4a62;
}

.editor-module .ProseMirror code {
  background: #1a3a52;
  color: #8ab4f8;
  padding: 0.2em 0.4em;
  border-radius: 0.25em;
  font-family: monospace;
}

.editor-module .ProseMirror pre code {
  background: none;
  color: #8ab4f8;
  padding: 0;
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
