<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from "vue";
import * as Y from "yjs";
import { WebsocketProvider } from "y-websocket";
import { useAuthStore } from "@/stores/auth";

const props = defineProps<{
  roomId: number;
}>();

const authStore = useAuthStore();

const WS_URL = import.meta.env.VITE_WS_URL || "ws://localhost:3001";

// Canvas refs
const canvasRef = ref<HTMLCanvasElement | null>(null);
const containerRef = ref<HTMLDivElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
let resizeObserver: ResizeObserver | null = null;
let resizeRetryFrame: number | null = null;

// Yjs
let ydoc: Y.Doc | null = null;
let provider: WebsocketProvider | null = null;
let yStrokes: Y.Array<Stroke> | null = null;

// Connection status
const isConnected = ref(false);

// Tools
type Tool = "pen" | "line" | "rectangle" | "circle" | "eraser";
const currentTool = ref<Tool>("pen");
const currentColor = ref("#000000");
const strokeWidth = ref(3);
const eraserWidth = ref(20);

// Colors palette
const colorPalette = [
  "#000000",
  "#ffffff",
  "#ff0000",
  "#00ff00",
  "#0000ff",
  "#ffff00",
  "#ff00ff",
  "#00ffff",
  "#ff8000",
  "#8000ff",
  "#008080",
  "#800000",
  "#008000",
  "#000080",
  "#808080",
];

// Stroke widths
const strokeWidths = [1, 2, 3, 5, 8, 12, 20];

// Drawing state
const isDrawing = ref(false);
const currentStroke = ref<Point[]>([]);
const startPoint = ref<Point | null>(null);

// Remote cursors
const remoteCursors = ref<Map<number, RemoteCursor>>(new Map());

// User color for cursor
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

interface Point {
  x: number;
  y: number;
}

interface Stroke {
  id: string;
  tool: Tool;
  points: Point[];
  color: string;
  width: number;
  startPoint?: Point;
  endPoint?: Point;
}

interface RemoteCursor {
  id: number;
  name: string;
  color: string;
  x: number;
  y: number;
}

function generateId(): string {
  return Math.random().toString(36).substr(2, 9);
}

function initWhiteboard() {
  // Cleanup previous
  if (provider) {
    provider.disconnect();
    provider.destroy();
  }
  if (ydoc) {
    ydoc.destroy();
  }

  // Create Yjs document
  ydoc = new Y.Doc();

  // Get shared array for strokes
  yStrokes = ydoc.getArray<Stroke>("strokes");

  // Connect to WebSocket
  provider = new WebsocketProvider(
    WS_URL,
    `room-${props.roomId}-whiteboard`,
    ydoc,
    { params: { token: authStore.token ?? '' } },
  );

  provider.on("status", (event: { status: string }) => {
    isConnected.value = event.status === "connected";
  });

  // Set awareness for cursor sharing
  const awareness = provider.awareness;
  awareness.setLocalStateField("user", {
    id: authStore.user?.id,
    name: authStore.user?.displayName || "Anonymous",
    color: userColor,
    cursor: null,
  });

  // Listen to awareness changes for remote cursors
  awareness.on("change", () => {
    const states = awareness.getStates();
    const newCursors = new Map<number, RemoteCursor>();

    states.forEach((state, clientId) => {
      if (clientId !== awareness.clientID && state.user?.cursor) {
        newCursors.set(clientId, {
          id: clientId,
          name: state.user.name,
          color: state.user.color,
          x: state.user.cursor.x,
          y: state.user.cursor.y,
        });
      }
    });

    remoteCursors.value = newCursors;
  });

  // Listen to strokes changes
  yStrokes.observe(() => {
    redrawCanvas();
  });

  // Initial draw
  nextTick(() => {
    resizeCanvas();
    redrawCanvas();
  });
}

function resizeCanvas() {
  if (!canvasRef.value || !containerRef.value) return;

  const container = containerRef.value;
  const canvas = canvasRef.value;
  const bounds = container.getBoundingClientRect();
  const width = Math.floor(container.clientWidth || bounds.width);
  const height = Math.floor(container.clientHeight || bounds.height);

  if (width <= 0 || height <= 0) {
    return;
  }

  if (canvas.width !== width) canvas.width = width;
  if (canvas.height !== height) canvas.height = height;

  ctx = canvas.getContext("2d");
  if (ctx) {
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
  }

  redrawCanvas();
}

function ensureCanvasReady(): boolean {
  if (!canvasRef.value || !containerRef.value) return false;

  if (!ctx || canvasRef.value.width === 0 || canvasRef.value.height === 0) {
    resizeCanvas();
  }

  // Final fallback in case layout timing prevents resize observer from firing.
  if (!ctx || canvasRef.value.width === 0 || canvasRef.value.height === 0) {
    const bounds = containerRef.value.getBoundingClientRect();
    const width = Math.max(1, Math.floor(bounds.width || 1));
    const height = Math.max(1, Math.floor(bounds.height || 1));
    canvasRef.value.width = width;
    canvasRef.value.height = height;
    ctx = canvasRef.value.getContext("2d");
    if (ctx) {
      ctx.lineCap = "round";
      ctx.lineJoin = "round";
      redrawCanvas();
    }
  }

  return !!ctx;
}

function scheduleResizeRetry(maxAttempts = 20) {
  let attempts = 0;

  const tick = () => {
    attempts += 1;
    resizeCanvas();

    const canvas = canvasRef.value;
    if (!canvas) return;

    const isReady = canvas.width > 0 && canvas.height > 0;
    if (!isReady && attempts < maxAttempts) {
      resizeRetryFrame = requestAnimationFrame(tick);
    }
  };

  if (resizeRetryFrame) {
    cancelAnimationFrame(resizeRetryFrame);
  }
  resizeRetryFrame = requestAnimationFrame(tick);
}

function redrawCanvas() {
  if (!ctx || !canvasRef.value || !yStrokes) return;

  // Clear canvas
  ctx.fillStyle = "#ffffff";
  ctx.fillRect(0, 0, canvasRef.value.width, canvasRef.value.height);

  // Draw all strokes
  const strokes = yStrokes.toArray();
  strokes.forEach((stroke) => {
    drawStroke(stroke);
  });
}

function drawStroke(stroke: Stroke) {
  if (!ctx) return;

  ctx.strokeStyle = stroke.color;
  ctx.lineWidth = stroke.width;
  ctx.fillStyle = stroke.color;

  if (stroke.tool === "eraser") {
    ctx.strokeStyle = "#ffffff";
  }

  switch (stroke.tool) {
    case "pen":
    case "eraser":
      if (stroke.points.length < 2) return;
      ctx.beginPath();
      ctx.moveTo(stroke.points[0]!.x, stroke.points[0]!.y);
      for (let i = 1; i < stroke.points.length; i++) {
        ctx.lineTo(stroke.points[i]!.x, stroke.points[i]!.y);
      }
      ctx.stroke();
      break;

    case "line":
      if (!stroke.startPoint || !stroke.endPoint) return;
      ctx.beginPath();
      ctx.moveTo(stroke.startPoint.x, stroke.startPoint.y);
      ctx.lineTo(stroke.endPoint.x, stroke.endPoint.y);
      ctx.stroke();
      break;

    case "rectangle": {
      if (!stroke.startPoint || !stroke.endPoint) return;
      const rectWidth = stroke.endPoint.x - stroke.startPoint.x;
      const rectHeight = stroke.endPoint.y - stroke.startPoint.y;
      ctx.beginPath();
      ctx.strokeRect(
        stroke.startPoint.x,
        stroke.startPoint.y,
        rectWidth,
        rectHeight,
      );
      break;
    }

    case "circle": {
      if (!stroke.startPoint || !stroke.endPoint) return;
      const radius = Math.sqrt(
        Math.pow(stroke.endPoint.x - stroke.startPoint.x, 2) +
          Math.pow(stroke.endPoint.y - stroke.startPoint.y, 2),
      );
      ctx.beginPath();
      ctx.arc(stroke.startPoint.x, stroke.startPoint.y, radius, 0, Math.PI * 2);
      ctx.stroke();
      break;
    }
  }
}

function getCanvasPoint(e: MouseEvent | TouchEvent | PointerEvent): Point {
  if (!canvasRef.value) return { x: 0, y: 0 };

  const rect = canvasRef.value.getBoundingClientRect();
  let clientX: number, clientY: number;

  const isTouchEvent =
    typeof TouchEvent !== "undefined" && e instanceof TouchEvent;

  if (isTouchEvent) {
    const touch = e.touches[0] ?? e.changedTouches[0];
    if (!touch) return { x: 0, y: 0 };
    clientX = touch.clientX;
    clientY = touch.clientY;
  } else {
    clientX = (e as MouseEvent | PointerEvent).clientX;
    clientY = (e as MouseEvent | PointerEvent).clientY;
  }

  return {
    x: clientX - rect.left,
    y: clientY - rect.top,
  };
}

function startDrawing(e: MouseEvent | TouchEvent) {
  e.preventDefault();
  if (!ensureCanvasReady()) return;
  isDrawing.value = true;

  const point = getCanvasPoint(e);

  if (currentTool.value === "pen" || currentTool.value === "eraser") {
    currentStroke.value = [point];
  } else {
    startPoint.value = point;
  }

  // Update cursor position
  updateCursorPosition(point);
}

function draw(e: MouseEvent | TouchEvent) {
  e.preventDefault();
  if (!ensureCanvasReady()) return;
  const point = getCanvasPoint(e);

  // Update cursor position for awareness
  updateCursorPosition(point);

  if (!isDrawing.value) return;

  if (currentTool.value === "pen" || currentTool.value === "eraser") {
    currentStroke.value.push(point);

    // Draw preview
    if (ctx && currentStroke.value.length > 1) {
      ctx.strokeStyle =
        currentTool.value === "eraser" ? "#ffffff" : currentColor.value;
      ctx.lineWidth =
        currentTool.value === "eraser" ? eraserWidth.value : strokeWidth.value;
      ctx.beginPath();
      const prev = currentStroke.value[currentStroke.value.length - 2]!;
      ctx.moveTo(prev.x, prev.y);
      ctx.lineTo(point.x, point.y);
      ctx.stroke();
    }
  } else if (startPoint.value) {
    // Redraw for shape preview
    redrawCanvas();
    drawShapePreview(startPoint.value, point);
  }
}

function drawShapePreview(start: Point, end: Point) {
  if (!ctx) return;

  ctx.strokeStyle = currentColor.value;
  ctx.lineWidth = strokeWidth.value;
  ctx.setLineDash([5, 5]);

  switch (currentTool.value) {
    case "line":
      ctx.beginPath();
      ctx.moveTo(start.x, start.y);
      ctx.lineTo(end.x, end.y);
      ctx.stroke();
      break;

    case "rectangle":
      ctx.beginPath();
      ctx.strokeRect(start.x, start.y, end.x - start.x, end.y - start.y);
      break;

    case "circle": {
      const radius = Math.sqrt(
        Math.pow(end.x - start.x, 2) + Math.pow(end.y - start.y, 2),
      );
      ctx.beginPath();
      ctx.arc(start.x, start.y, radius, 0, Math.PI * 2);
      ctx.stroke();
      break;
    }
  }

  ctx.setLineDash([]);
}

function stopDrawing(e: MouseEvent | TouchEvent) {
  if (!ensureCanvasReady()) return;
  if (!isDrawing.value) return;
  isDrawing.value = false;

  const point = getCanvasPoint(e);

  if (!yStrokes) return;

  if (currentTool.value === "pen" || currentTool.value === "eraser") {
    if (currentStroke.value.length > 1) {
      const stroke: Stroke = {
        id: generateId(),
        tool: currentTool.value,
        points: [...currentStroke.value],
        color: currentTool.value === "eraser" ? "#ffffff" : currentColor.value,
        width:
          currentTool.value === "eraser"
            ? eraserWidth.value
            : strokeWidth.value,
      };
      yStrokes.push([stroke]);
    }
    currentStroke.value = [];
  } else if (startPoint.value) {
    const stroke: Stroke = {
      id: generateId(),
      tool: currentTool.value,
      points: [],
      color: currentColor.value,
      width: strokeWidth.value,
      startPoint: startPoint.value,
      endPoint: point,
    };
    yStrokes.push([stroke]);
    startPoint.value = null;
  }
}

function startDrawingPointer(e: PointerEvent) {
  if (e.pointerType === "mouse" && e.button !== 0) return;
  canvasRef.value?.setPointerCapture?.(e.pointerId);
  startDrawing(e);
}

function drawPointer(e: PointerEvent) {
  draw(e);
}

function stopDrawingPointer(e: PointerEvent) {
  canvasRef.value?.releasePointerCapture?.(e.pointerId);
  stopDrawing(e);
}

function updateCursorPosition(point: Point) {
  if (!provider) return;

  provider.awareness.setLocalStateField("user", {
    id: authStore.user?.id,
    name: authStore.user?.displayName || "Anonymous",
    color: userColor,
    cursor: point,
  });
}

function clearCanvas() {
  if (!yStrokes || !ydoc) return;

  ydoc.transact(() => {
    yStrokes!.delete(0, yStrokes!.length);
  });
}

function undo() {
  if (!yStrokes || yStrokes.length === 0) return;
  yStrokes.delete(yStrokes.length - 1, 1);
}

function selectTool(tool: Tool) {
  currentTool.value = tool;
}

// Handle window resize
function handleResize() {
  resizeCanvas();
}

onMounted(() => {
  initWhiteboard();
  window.addEventListener("resize", handleResize);

  // Keep canvas dimensions in sync with layout changes.
  if (containerRef.value) {
    resizeObserver = new ResizeObserver(() => {
      resizeCanvas();
    });
    resizeObserver.observe(containerRef.value);
  }

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      resizeCanvas();
      scheduleResizeRetry();
    });
  });
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }
  if (resizeRetryFrame) {
    cancelAnimationFrame(resizeRetryFrame);
    resizeRetryFrame = null;
  }
  if (provider) {
    provider.disconnect();
    provider.destroy();
  }
  if (ydoc) {
    ydoc.destroy();
  }
});

watch(
  () => props.roomId,
  (newId, oldId) => {
    if (newId !== oldId) {
      initWhiteboard();
      nextTick(() => {
        resizeCanvas();
        scheduleResizeRetry();
      });
    }
  },
);
</script>

<template>
  <div class="whiteboard-module h-full flex flex-col">
    <div class="whiteboard-toolbar">
      <div class="whiteboard-toolbar__group">
        <button
          class="whiteboard-tool-btn"
          :class="{ 'whiteboard-tool-btn--active': currentTool === 'pen' }"
          @click="selectTool('pen')"
          title="Crayon"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
            />
          </svg>
        </button>
        <button
          class="whiteboard-tool-btn"
          :class="{ 'whiteboard-tool-btn--active': currentTool === 'line' }"
          @click="selectTool('line')"
          title="Ligne"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 20l16-16"
            />
          </svg>
        </button>
        <button
          class="whiteboard-tool-btn"
          :class="{
            'whiteboard-tool-btn--active': currentTool === 'rectangle',
          }"
          @click="selectTool('rectangle')"
          title="Rectangle"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <rect x="4" y="4" width="16" height="16" stroke-width="2" />
          </svg>
        </button>
        <button
          class="whiteboard-tool-btn"
          :class="{ 'whiteboard-tool-btn--active': currentTool === 'circle' }"
          @click="selectTool('circle')"
          title="Cercle"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <circle cx="12" cy="12" r="8" stroke-width="2" />
          </svg>
        </button>
        <button
          class="whiteboard-tool-btn"
          :class="{ 'whiteboard-tool-btn--active': currentTool === 'eraser' }"
          @click="selectTool('eraser')"
          title="Gomme"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16.862 4.487l1.688 1.688a2.25 2.25 0 010 3.182l-7.16 7.159a2.25 2.25 0 01-1.59.659H6.75a2.25 2.25 0 01-1.591-.659l-1.688-1.688a2.25 2.25 0 010-3.182l7.16-7.16a2.25 2.25 0 013.181 0z"
            />
          </svg>
        </button>
      </div>

      <div class="whiteboard-toolbar__divider"></div>

      <div class="whiteboard-color-panel">
        <span class="whiteboard-toolbar__label">Couleurs</span>
        <div class="whiteboard-color-panel__controls">
          <div class="whiteboard-palette">
            <button
              v-for="color in colorPalette"
              :key="color"
              class="whiteboard-color-swatch"
              :class="{
                'whiteboard-color-swatch--active': currentColor === color,
              }"
              :style="{ backgroundColor: color }"
              @click="currentColor = color"
            />
          </div>
          <label class="whiteboard-color-picker" title="Couleur personnalisée">
            <input
              type="color"
              v-model="currentColor"
              class="whiteboard-color-picker__input"
            />
          </label>
        </div>
      </div>

      <div class="whiteboard-toolbar__divider"></div>

      <div class="whiteboard-size-panel">
        <span class="whiteboard-toolbar__label">Taille</span>
        <select v-model="strokeWidth" class="whiteboard-select">
          <option v-for="w in strokeWidths" :key="w" :value="w">
            {{ w }}px
          </option>
        </select>
      </div>

      <div class="whiteboard-toolbar__spacer"></div>

      <div class="whiteboard-toolbar__actions">
        <button
          class="whiteboard-action-btn"
          @click="undo"
          title="Annuler (dernier trait)"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
            />
          </svg>
        </button>
        <button
          class="whiteboard-danger-btn"
          @click="clearCanvas"
          title="Effacer tout"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          <span>Effacer</span>
        </button>

        <div class="whiteboard-toolbar__divider"></div>

        <div class="whiteboard-connection-pill">
          <span
            class="whiteboard-connection-pill__dot"
            :class="{
              'bg-[#6fdd9f]': isConnected,
              'bg-[#fbbf24]': !isConnected,
            }"
          ></span>
          <span class="whiteboard-connection-pill__text">{{
            isConnected ? "Connecté" : "Connexion..."
          }}</span>
        </div>
      </div>
    </div>

    <div ref="containerRef" class="whiteboard-canvas-shell">
      <canvas
        ref="canvasRef"
        @pointerdown.prevent="startDrawingPointer"
        @pointermove.prevent="drawPointer"
        @pointerup.prevent="stopDrawingPointer"
        @pointerleave.prevent="stopDrawingPointer"
        @pointercancel.prevent="stopDrawingPointer"
        @mousedown.prevent="startDrawing"
        @mousemove.prevent="draw"
        @mouseup.prevent="stopDrawing"
        @mouseleave.prevent="stopDrawing"
        @touchstart.prevent="startDrawing"
        @touchmove.prevent="draw"
        @touchend.prevent="stopDrawing"
        class="absolute inset-0 whiteboard-canvas"
      />

      <div
        v-for="[id, cursor] in remoteCursors"
        :key="id"
        class="absolute pointer-events-none transition-all duration-75"
        :style="{ left: cursor.x + 'px', top: cursor.y + 'px' }"
      >
        <svg
          class="w-5 h-5 -translate-x-0.5 -translate-y-0.5"
          :style="{ color: cursor.color }"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            d="M5.5 3.21V20.8c0 .45.54.67.85.35l4.86-4.86a.5.5 0 0 1 .35-.15h6.87c.48 0 .72-.58.38-.92L5.85 2.86a.5.5 0 0 0-.35.35z"
          />
        </svg>
        <div
          class="whiteboard-remote-label"
          :style="{ backgroundColor: cursor.color }"
        >
          {{ cursor.name }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.whiteboard-module {
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.18),
      transparent 30%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.whiteboard-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.55rem;
  padding: 0.8rem 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  backdrop-filter: blur(10px);
}

.whiteboard-toolbar__group {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.025);
}

.whiteboard-toolbar__divider {
  width: 1px;
  height: 1.9rem;
  background: rgba(255, 255, 255, 0.08);
}

.whiteboard-toolbar__label {
  color: #7f95ac;
  font-size: 0.62rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.whiteboard-toolbar__spacer {
  flex: 1 1 auto;
}

.whiteboard-toolbar__actions {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.whiteboard-tool-btn,
.whiteboard-action-btn,
.whiteboard-danger-btn {
  min-height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
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

.whiteboard-tool-btn:hover,
.whiteboard-action-btn:hover,
.whiteboard-danger-btn:hover {
  border-color: rgba(91, 163, 232, 0.3);
  background: rgba(91, 163, 232, 0.08);
  color: #edf6ff;
  transform: translateY(-1px);
}

.whiteboard-tool-btn--active {
  border-color: rgba(91, 163, 232, 0.38);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.18),
    rgba(91, 163, 232, 0.06)
  );
  color: #eef7ff;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.whiteboard-danger-btn {
  color: #f7b7b7;
}

.whiteboard-danger-btn:hover {
  border-color: rgba(248, 113, 113, 0.28);
  background: rgba(248, 113, 113, 0.1);
  color: #ffd0d0;
}

.whiteboard-color-panel,
.whiteboard-size-panel {
  display: grid;
  gap: 0.3rem;
}

.whiteboard-color-panel__controls {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.whiteboard-palette {
  display: flex;
  flex-wrap: wrap;
  gap: 0.28rem;
  max-width: 12rem;
}

.whiteboard-color-swatch {
  width: 1.2rem;
  height: 1.2rem;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.16);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.whiteboard-color-swatch:hover {
  transform: scale(1.08);
}

.whiteboard-color-swatch--active {
  border-color: #d8ecff;
  box-shadow: 0 0 0 2px rgba(91, 163, 232, 0.28);
}

.whiteboard-color-picker {
  width: 2rem;
  height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.03);
  overflow: hidden;
}

.whiteboard-color-picker__input {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.whiteboard-select {
  min-width: 5.2rem;
  min-height: 2rem;
  padding: 0.38rem 0.6rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.03);
  color: #edf6ff;
}

.whiteboard-connection-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.42rem;
  padding: 0.35rem 0.58rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.03);
}

.whiteboard-connection-pill__dot {
  width: 0.48rem;
  height: 0.48rem;
  border-radius: 999px;
}

.whiteboard-connection-pill__text {
  color: #90a6bf;
  font-size: 0.74rem;
}

.whiteboard-canvas-shell {
  position: relative;
  flex: 1;
  min-height: 320px;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0)),
    #ffffff;
  cursor: crosshair;
}

.whiteboard-canvas {
  display: block;
  width: 100%;
  height: 100%;
  touch-action: none;
}

.whiteboard-watermark {
  position: absolute;
  top: 0.65rem;
  left: 0.8rem;
  z-index: 1;
  padding: 0.22rem 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(8, 18, 31, 0.55);
  color: #6e8298;
  font-size: 0.68rem;
  letter-spacing: 0.04em;
  pointer-events: none;
}

.whiteboard-remote-label {
  position: absolute;
  left: 1rem;
  top: 0;
  padding: 0.18rem 0.5rem;
  border-radius: 999px;
  color: #fff;
  font-size: 0.7rem;
  white-space: nowrap;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
}

@media (max-width: 768px) {
  .whiteboard-toolbar {
    padding: 0.7rem;
    gap: 0.45rem;
  }

  .whiteboard-toolbar__spacer {
    display: none;
  }

  .whiteboard-toolbar__actions {
    width: 100%;
    justify-content: flex-start;
  }

  .whiteboard-palette {
    max-width: none;
  }

  .whiteboard-tool-btn,
  .whiteboard-action-btn,
  .whiteboard-danger-btn,
  .whiteboard-select {
    min-height: 1.9rem;
  }
}
</style>
