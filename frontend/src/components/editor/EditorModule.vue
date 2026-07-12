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
        class: "editor-surface__content",
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
  <div class="editor-module h-full flex flex-col">
    <!-- Toolbar -->
    <div v-if="editor" class="editor-toolbar">
      <!-- Text formatting -->
      <div class="editor-toolbar__group">
        <button
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('bold') }"
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
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()"
          title="Italique (Ctrl+I)"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z" />
          </svg>
        </button>
        <button
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('underline') }"
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
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('strike') }"
          @click="editor.chain().focus().toggleStrike().run()"
          title="Barré"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z" />
          </svg>
        </button>
      </div>

      <div class="editor-toolbar__divider"></div>

      <!-- Headings -->
      <div class="editor-toolbar__group">
        <button
          class="editor-tool-btn editor-tool-btn--text"
          :class="{
            'editor-tool-btn--active': editor.isActive('heading', { level: 1 }),
          }"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          title="Titre 1"
        >
          H1
        </button>
        <button
          class="editor-tool-btn editor-tool-btn--text"
          :class="{
            'editor-tool-btn--active': editor.isActive('heading', { level: 2 }),
          }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          title="Titre 2"
        >
          H2
        </button>
        <button
          class="editor-tool-btn editor-tool-btn--text"
          :class="{
            'editor-tool-btn--active': editor.isActive('heading', { level: 3 }),
          }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          title="Titre 3"
        >
          H3
        </button>
      </div>

      <div class="editor-toolbar__divider"></div>

      <!-- Lists -->
      <div class="editor-toolbar__group">
        <button
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('bulletList') }"
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
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('orderedList') }"
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
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()"
          title="Citation"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z" />
          </svg>
        </button>
        <button
          class="editor-tool-btn"
          :class="{ 'editor-tool-btn--active': editor.isActive('codeBlock') }"
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

      <div class="editor-toolbar__divider"></div>

      <!-- Alignment -->
      <div class="editor-toolbar__group">
        <button
          class="editor-tool-btn"
          :class="{
            'editor-tool-btn--active': editor.isActive({ textAlign: 'left' }),
          }"
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
          class="editor-tool-btn"
          :class="{
            'editor-tool-btn--active': editor.isActive({ textAlign: 'center' }),
          }"
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
          class="editor-tool-btn"
          :class="{
            'editor-tool-btn--active': editor.isActive({ textAlign: 'right' }),
          }"
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

      <div class="editor-toolbar__spacer"></div>

      <!-- Actions -->
      <div class="editor-toolbar__actions">
        <!-- Save status -->
        <span v-if="saving" class="editor-status-text">
          <span class="loading loading-spinner loading-xs"></span>
          Sauvegarde...
        </span>
        <span v-else-if="lastSaved" class="editor-status-text">
          {{ formatLastSaved(lastSaved) }}
        </span>

        <!-- Manual save -->
        <button
          class="editor-action-btn"
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
          class="editor-action-btn"
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
          class="editor-ai-btn"
          :class="{ 'editor-ai-btn--active': showAiPanel }"
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

        <div class="editor-toolbar__divider"></div>

        <!-- Connection status -->
        <div class="editor-connection-pill">
          <span
            class="editor-connection-pill__dot"
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
    <div class="editor-surface">
      <EditorContent
        v-if="editor"
        :editor="editor"
        class="editor-surface__inner"
      />
      <div v-else class="editor-loading-state">
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
      class="editor-ai-panel"
    />

    <!-- Toast notification -->
    <div v-if="toastMessage" class="toast toast-bottom toast-end z-50">
      <div class="editor-toast">
        <span>{{ toastMessage }}</span>
      </div>
    </div>
  </div>
</template>

<style>
.editor-module {
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.18),
      transparent 28%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
  padding: 0.8rem 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  backdrop-filter: blur(10px);
}

.editor-toolbar__group {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.025);
}

.editor-toolbar__divider {
  width: 1px;
  height: 1.9rem;
  background: rgba(255, 255, 255, 0.08);
}

.editor-toolbar__spacer {
  flex: 1 1 auto;
}

.editor-toolbar__actions {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.editor-tool-btn,
.editor-action-btn,
.editor-ai-btn {
  min-height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.38rem;
  padding: 0.42rem 0.62rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.03);
  color: #a9c0d8;
  transition:
    border-color 0.22s ease,
    background-color 0.22s ease,
    transform 0.22s ease,
    box-shadow 0.22s ease,
    color 0.22s ease;
}

.editor-tool-btn svg,
.editor-action-btn svg,
.editor-ai-btn svg {
  width: 1rem;
  height: 1rem;
}

.editor-tool-btn--text {
  min-width: 2.15rem;
  font-size: 0.74rem;
  font-weight: 700;
}

.editor-tool-btn:hover,
.editor-action-btn:hover,
.editor-ai-btn:hover {
  border-color: rgba(91, 163, 232, 0.3);
  background: rgba(91, 163, 232, 0.08);
  color: #edf6ff;
  transform: translateY(-1px);
}

.editor-tool-btn--active,
.editor-ai-btn--active {
  border-color: rgba(91, 163, 232, 0.38);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.18),
    rgba(91, 163, 232, 0.06)
  );
  color: #eef7ff;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.editor-action-btn:disabled,
.editor-ai-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.editor-ai-btn {
  padding-inline: 0.72rem;
}

.editor-status-text {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  color: #90a6bf;
  font-size: 0.74rem;
}

.editor-connection-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.58rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.03);
}

.editor-connection-pill__dot {
  width: 0.48rem;
  height: 0.48rem;
  border-radius: 999px;
  box-shadow: 0 0 10px currentColor;
}

.editor-surface {
  position: relative;
  flex: 1;
  overflow: auto;
  background: linear-gradient(
    180deg,
    rgba(8, 18, 31, 0.55),
    rgba(8, 18, 31, 0)
  );
}

.editor-surface__inner {
  height: 100%;
}

.editor-surface__content {
  max-width: none;
  min-height: 400px;
  padding: 1.75rem 1.6rem 2.2rem;
  color: #eef4ff;
  font-size: 0.95rem;
  line-height: 1.72;
}

.editor-loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.editor-ai-panel {
  margin: 0.9rem;
}

.editor-toast {
  border: 1px solid rgba(111, 221, 159, 0.22);
  border-radius: 14px;
  background: rgba(18, 53, 40, 0.95);
  color: #dff9ea;
  padding: 0.72rem 0.9rem;
  font-size: 0.84rem;
  box-shadow: 0 16px 32px rgba(0, 0, 0, 0.24);
}

.editor-module .ProseMirror {
  min-height: 400px;
  padding: 1.75rem 1.6rem 2.2rem;
  background-color: transparent;
  color: #eef4ff;
}

.editor-module .ProseMirror:focus {
  outline: none;
}

.editor-module .ProseMirror::selection {
  background: rgba(91, 163, 232, 0.22);
}

.editor-module .ProseMirror h1 {
  font-size: 2em;
  font-weight: bold;
  margin-bottom: 0.5em;
  color: #f4f8ff;
}

.editor-module .ProseMirror h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 0.5em;
  color: #f4f8ff;
}

.editor-module .ProseMirror h3 {
  font-size: 1.25em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 0.5em;
  color: #f4f8ff;
}

.editor-module .ProseMirror p {
  margin: 1em 0;
  color: #dbe6f4;
}

.editor-module .ProseMirror ul,
.editor-module .ProseMirror ol {
  padding-left: 1.5em;
  margin: 1em 0;
  color: #dbe6f4;
}

.editor-module .ProseMirror blockquote {
  border-left: 3px solid rgba(91, 163, 232, 0.5);
  padding: 0.2rem 0 0.2rem 1em;
  margin: 1em 0;
  color: #9eb4ca;
  background: rgba(255, 255, 255, 0.025);
  border-radius: 0 12px 12px 0;
}

.editor-module .ProseMirror pre {
  background: rgba(14, 31, 52, 0.95);
  color: #8ab4f8;
  padding: 1em;
  border-radius: 0.9em;
  overflow-x: auto;
  border: 1px solid rgba(91, 163, 232, 0.18);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
}

.editor-module .ProseMirror code {
  background: rgba(14, 31, 52, 0.95);
  color: #8ab4f8;
  padding: 0.2em 0.4em;
  border-radius: 0.45em;
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

@media (max-width: 768px) {
  .editor-toolbar {
    padding: 0.7rem;
    gap: 0.4rem;
  }

  .editor-toolbar__actions {
    width: 100%;
    justify-content: flex-start;
  }

  .editor-toolbar__spacer {
    display: none;
  }

  .editor-tool-btn,
  .editor-action-btn,
  .editor-ai-btn {
    min-height: 1.9rem;
    padding: 0.38rem 0.55rem;
  }

  .editor-surface__content,
  .editor-module .ProseMirror {
    padding: 1.2rem 1rem 1.5rem;
  }
}
</style>
