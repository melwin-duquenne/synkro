<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import * as Y from 'yjs'
import { WebsocketProvider } from 'y-websocket'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  roomId: number
}>()

const authStore = useAuthStore()

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:3001'

// Canvas refs
const canvasRef = ref<HTMLCanvasElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)
let ctx: CanvasRenderingContext2D | null = null

// Yjs
let ydoc: Y.Doc | null = null
let provider: WebsocketProvider | null = null
let yStrokes: Y.Array<Stroke> | null = null

// Connection status
const isConnected = ref(false)

// Tools
type Tool = 'pen' | 'line' | 'rectangle' | 'circle' | 'eraser'
const currentTool = ref<Tool>('pen')
const currentColor = ref('#000000')
const strokeWidth = ref(3)
const eraserWidth = ref(20)

// Colors palette
const colorPalette = [
  '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff',
  '#ffff00', '#ff00ff', '#00ffff', '#ff8000', '#8000ff',
  '#008080', '#800000', '#008000', '#000080', '#808080'
]

// Stroke widths
const strokeWidths = [1, 2, 3, 5, 8, 12, 20]

// Drawing state
const isDrawing = ref(false)
const currentStroke = ref<Point[]>([])
const startPoint = ref<Point | null>(null)

// Remote cursors
const remoteCursors = ref<Map<number, RemoteCursor>>(new Map())

// User color for cursor
const colors = ['#958DF1', '#F98181', '#FBBC88', '#FAF594', '#70CFF8', '#94FADB', '#B9F18D']
const userColor = colors[Math.floor(Math.random() * colors.length)]

interface Point {
  x: number
  y: number
}

interface Stroke {
  id: string
  tool: Tool
  points: Point[]
  color: string
  width: number
  startPoint?: Point
  endPoint?: Point
}

interface RemoteCursor {
  id: number
  name: string
  color: string
  x: number
  y: number
}

function generateId(): string {
  return Math.random().toString(36).substr(2, 9)
}

function initWhiteboard() {
  // Cleanup previous
  if (provider) {
    provider.disconnect()
    provider.destroy()
  }
  if (ydoc) {
    ydoc.destroy()
  }

  // Create Yjs document
  ydoc = new Y.Doc()

  // Get shared array for strokes
  yStrokes = ydoc.getArray<Stroke>('strokes')

  // Connect to WebSocket
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-whiteboard`, ydoc)

  provider.on('status', (event: { status: string }) => {
    isConnected.value = event.status === 'connected'
  })

  // Set awareness for cursor sharing
  const awareness = provider.awareness
  awareness.setLocalStateField('user', {
    id: authStore.user?.id,
    name: authStore.user?.displayName || 'Anonymous',
    color: userColor,
    cursor: null
  })

  // Listen to awareness changes for remote cursors
  awareness.on('change', () => {
    const states = awareness.getStates()
    const newCursors = new Map<number, RemoteCursor>()

    states.forEach((state, clientId) => {
      if (clientId !== awareness.clientID && state.user?.cursor) {
        newCursors.set(clientId, {
          id: clientId,
          name: state.user.name,
          color: state.user.color,
          x: state.user.cursor.x,
          y: state.user.cursor.y
        })
      }
    })

    remoteCursors.value = newCursors
  })

  // Listen to strokes changes
  yStrokes.observe(() => {
    redrawCanvas()
  })

  // Initial draw
  nextTick(() => {
    resizeCanvas()
    redrawCanvas()
  })
}

function resizeCanvas() {
  if (!canvasRef.value || !containerRef.value) return

  const container = containerRef.value
  const canvas = canvasRef.value

  canvas.width = container.clientWidth
  canvas.height = container.clientHeight

  ctx = canvas.getContext('2d')
  if (ctx) {
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
  }

  redrawCanvas()
}

function redrawCanvas() {
  if (!ctx || !canvasRef.value || !yStrokes) return

  // Clear canvas
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, canvasRef.value.width, canvasRef.value.height)

  // Draw all strokes
  const strokes = yStrokes.toArray()
  strokes.forEach(stroke => {
    drawStroke(stroke)
  })
}

function drawStroke(stroke: Stroke) {
  if (!ctx) return

  ctx.strokeStyle = stroke.color
  ctx.lineWidth = stroke.width
  ctx.fillStyle = stroke.color

  if (stroke.tool === 'eraser') {
    ctx.strokeStyle = '#ffffff'
  }

  switch (stroke.tool) {
    case 'pen':
    case 'eraser':
      if (stroke.points.length < 2) return
      ctx.beginPath()
      ctx.moveTo(stroke.points[0].x, stroke.points[0].y)
      for (let i = 1; i < stroke.points.length; i++) {
        ctx.lineTo(stroke.points[i].x, stroke.points[i].y)
      }
      ctx.stroke()
      break

    case 'line':
      if (!stroke.startPoint || !stroke.endPoint) return
      ctx.beginPath()
      ctx.moveTo(stroke.startPoint.x, stroke.startPoint.y)
      ctx.lineTo(stroke.endPoint.x, stroke.endPoint.y)
      ctx.stroke()
      break

    case 'rectangle':
      if (!stroke.startPoint || !stroke.endPoint) return
      const rectWidth = stroke.endPoint.x - stroke.startPoint.x
      const rectHeight = stroke.endPoint.y - stroke.startPoint.y
      ctx.beginPath()
      ctx.strokeRect(stroke.startPoint.x, stroke.startPoint.y, rectWidth, rectHeight)
      break

    case 'circle':
      if (!stroke.startPoint || !stroke.endPoint) return
      const radius = Math.sqrt(
        Math.pow(stroke.endPoint.x - stroke.startPoint.x, 2) +
        Math.pow(stroke.endPoint.y - stroke.startPoint.y, 2)
      )
      ctx.beginPath()
      ctx.arc(stroke.startPoint.x, stroke.startPoint.y, radius, 0, Math.PI * 2)
      ctx.stroke()
      break
  }
}

function getCanvasPoint(e: MouseEvent | TouchEvent): Point {
  if (!canvasRef.value) return { x: 0, y: 0 }

  const rect = canvasRef.value.getBoundingClientRect()
  let clientX: number, clientY: number

  if (e instanceof TouchEvent) {
    clientX = e.touches[0].clientX
    clientY = e.touches[0].clientY
  } else {
    clientX = e.clientX
    clientY = e.clientY
  }

  return {
    x: clientX - rect.left,
    y: clientY - rect.top
  }
}

function startDrawing(e: MouseEvent | TouchEvent) {
  e.preventDefault()
  isDrawing.value = true

  const point = getCanvasPoint(e)

  if (currentTool.value === 'pen' || currentTool.value === 'eraser') {
    currentStroke.value = [point]
  } else {
    startPoint.value = point
  }

  // Update cursor position
  updateCursorPosition(point)
}

function draw(e: MouseEvent | TouchEvent) {
  e.preventDefault()
  const point = getCanvasPoint(e)

  // Update cursor position for awareness
  updateCursorPosition(point)

  if (!isDrawing.value) return

  if (currentTool.value === 'pen' || currentTool.value === 'eraser') {
    currentStroke.value.push(point)

    // Draw preview
    if (ctx && currentStroke.value.length > 1) {
      ctx.strokeStyle = currentTool.value === 'eraser' ? '#ffffff' : currentColor.value
      ctx.lineWidth = currentTool.value === 'eraser' ? eraserWidth.value : strokeWidth.value
      ctx.beginPath()
      const prev = currentStroke.value[currentStroke.value.length - 2]
      ctx.moveTo(prev.x, prev.y)
      ctx.lineTo(point.x, point.y)
      ctx.stroke()
    }
  } else if (startPoint.value) {
    // Redraw for shape preview
    redrawCanvas()
    drawShapePreview(startPoint.value, point)
  }
}

function drawShapePreview(start: Point, end: Point) {
  if (!ctx) return

  ctx.strokeStyle = currentColor.value
  ctx.lineWidth = strokeWidth.value
  ctx.setLineDash([5, 5])

  switch (currentTool.value) {
    case 'line':
      ctx.beginPath()
      ctx.moveTo(start.x, start.y)
      ctx.lineTo(end.x, end.y)
      ctx.stroke()
      break

    case 'rectangle':
      ctx.beginPath()
      ctx.strokeRect(start.x, start.y, end.x - start.x, end.y - start.y)
      break

    case 'circle':
      const radius = Math.sqrt(Math.pow(end.x - start.x, 2) + Math.pow(end.y - start.y, 2))
      ctx.beginPath()
      ctx.arc(start.x, start.y, radius, 0, Math.PI * 2)
      ctx.stroke()
      break
  }

  ctx.setLineDash([])
}

function stopDrawing(e: MouseEvent | TouchEvent) {
  if (!isDrawing.value) return
  isDrawing.value = false

  const point = getCanvasPoint(e)

  if (!yStrokes) return

  if (currentTool.value === 'pen' || currentTool.value === 'eraser') {
    if (currentStroke.value.length > 1) {
      const stroke: Stroke = {
        id: generateId(),
        tool: currentTool.value,
        points: [...currentStroke.value],
        color: currentTool.value === 'eraser' ? '#ffffff' : currentColor.value,
        width: currentTool.value === 'eraser' ? eraserWidth.value : strokeWidth.value
      }
      yStrokes.push([stroke])
    }
    currentStroke.value = []
  } else if (startPoint.value) {
    const stroke: Stroke = {
      id: generateId(),
      tool: currentTool.value,
      points: [],
      color: currentColor.value,
      width: strokeWidth.value,
      startPoint: startPoint.value,
      endPoint: point
    }
    yStrokes.push([stroke])
    startPoint.value = null
  }
}

function updateCursorPosition(point: Point) {
  if (!provider) return

  provider.awareness.setLocalStateField('user', {
    id: authStore.user?.id,
    name: authStore.user?.displayName || 'Anonymous',
    color: userColor,
    cursor: point
  })
}

function clearCanvas() {
  if (!yStrokes || !ydoc) return

  ydoc.transact(() => {
    yStrokes!.delete(0, yStrokes!.length)
  })
}

function undo() {
  if (!yStrokes || yStrokes.length === 0) return
  yStrokes.delete(yStrokes.length - 1, 1)
}

function selectTool(tool: Tool) {
  currentTool.value = tool
}

// Handle window resize
function handleResize() {
  resizeCanvas()
}

onMounted(() => {
  initWhiteboard()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  if (provider) {
    provider.disconnect()
    provider.destroy()
  }
  if (ydoc) {
    ydoc.destroy()
  }
})

watch(() => props.roomId, (newId, oldId) => {
  if (newId !== oldId) {
    initWhiteboard()
  }
})
</script>

<template>
  <div class="whiteboard-module h-full flex flex-col bg-base-200">
    <!-- Toolbar -->
    <div class="toolbar flex flex-wrap items-center gap-2 p-2 border-b border-base-300 bg-base-100">
      <!-- Drawing tools -->
      <div class="flex gap-0.5 bg-base-200 rounded-lg p-1">
        <button
          class="btn btn-sm btn-ghost"
          :class="{ 'btn-active': currentTool === 'pen' }"
          @click="selectTool('pen')"
          title="Crayon"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost"
          :class="{ 'btn-active': currentTool === 'line' }"
          @click="selectTool('line')"
          title="Ligne"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20l16-16" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost"
          :class="{ 'btn-active': currentTool === 'rectangle' }"
          @click="selectTool('rectangle')"
          title="Rectangle"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <rect x="4" y="4" width="16" height="16" stroke-width="2" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost"
          :class="{ 'btn-active': currentTool === 'circle' }"
          @click="selectTool('circle')"
          title="Cercle"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="8" stroke-width="2" />
          </svg>
        </button>
        <button
          class="btn btn-sm btn-ghost"
          :class="{ 'btn-active': currentTool === 'eraser' }"
          @click="selectTool('eraser')"
          title="Gomme"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>

      <div class="divider divider-horizontal mx-0.5 h-8"></div>

      <!-- Color picker -->
      <div class="flex items-center gap-1">
        <div class="flex flex-wrap gap-0.5 max-w-[200px]">
          <button
            v-for="color in colorPalette"
            :key="color"
            class="w-6 h-6 rounded border-2 transition-transform hover:scale-110"
            :class="{ 'border-primary scale-110': currentColor === color, 'border-base-300': currentColor !== color }"
            :style="{ backgroundColor: color }"
            @click="currentColor = color"
          />
        </div>
        <input
          type="color"
          v-model="currentColor"
          class="w-8 h-8 cursor-pointer rounded"
          title="Couleur personnalisée"
        />
      </div>

      <div class="divider divider-horizontal mx-0.5 h-8"></div>

      <!-- Stroke width -->
      <div class="flex items-center gap-1">
        <span class="text-xs text-base-content/60">Taille:</span>
        <select v-model="strokeWidth" class="select select-sm select-bordered w-20">
          <option v-for="w in strokeWidths" :key="w" :value="w">{{ w }}px</option>
        </select>
      </div>

      <div class="flex-1"></div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <button class="btn btn-sm btn-ghost" @click="undo" title="Annuler (dernier trait)">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
          </svg>
        </button>
        <button class="btn btn-sm btn-ghost text-error" @click="clearCanvas" title="Effacer tout">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Effacer
        </button>

        <div class="divider divider-horizontal mx-0.5 h-8"></div>

        <!-- Connection status -->
        <div class="flex items-center gap-1.5">
          <span
            class="w-2 h-2 rounded-full"
            :class="{
              'bg-success': isConnected,
              'bg-warning': !isConnected
            }"
          ></span>
          <span class="text-xs text-base-content/60">
            {{ isConnected ? 'Connecté' : 'Connexion...' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Canvas container -->
    <div ref="containerRef" class="flex-1 relative overflow-hidden bg-white cursor-crosshair">
      <canvas
        ref="canvasRef"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart="startDrawing"
        @touchmove="draw"
        @touchend="stopDrawing"
        class="absolute inset-0"
      />

      <!-- Remote cursors -->
      <div
        v-for="[id, cursor] in remoteCursors"
        :key="id"
        class="absolute pointer-events-none transition-all duration-75"
        :style="{ left: cursor.x + 'px', top: cursor.y + 'px' }"
      >
        <!-- Cursor icon -->
        <svg
          class="w-5 h-5 -translate-x-0.5 -translate-y-0.5"
          :style="{ color: cursor.color }"
          fill="currentColor"
          viewBox="0 0 24 24"
        >
          <path d="M5.5 3.21V20.8c0 .45.54.67.85.35l4.86-4.86a.5.5 0 0 1 .35-.15h6.87c.48 0 .72-.58.38-.92L5.85 2.86a.5.5 0 0 0-.35.35z"/>
        </svg>
        <!-- Name label -->
        <div
          class="absolute left-4 top-0 px-2 py-0.5 rounded text-xs text-white whitespace-nowrap"
          :style="{ backgroundColor: cursor.color }"
        >
          {{ cursor.name }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.whiteboard-module canvas {
  touch-action: none;
}
</style>
