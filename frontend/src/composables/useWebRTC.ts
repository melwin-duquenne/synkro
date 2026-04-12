import { ref, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import type {
  Participant,
  LocalMediaState,
  CallState
} from '@/types/webrtc'

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:3001'

// STUN servers for ICE (free Google servers)
const ICE_SERVERS: RTCIceServer[] = [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
]

const RTC_CONFIG: RTCConfiguration = {
  iceServers: ICE_SERVERS,
  iceCandidatePoolSize: 10,
}

// Message types
const MSG_TYPES = {
  OFFER: 2,
  ANSWER: 3,
  ICE_CANDIDATE: 4,
  CONTROL: 5,
}

export function useWebRTC(roomId: number) {
  const authStore = useAuthStore()

  // Unique peer ID for this session
  const peerId = `${authStore.user?.id}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`

  // State
  const callState = ref<CallState>('idle')
  const error = ref<string | null>(null)
  const participants = ref<Map<string, Participant>>(new Map())

  const localMedia = ref<LocalMediaState>({
    audioEnabled: true,
    videoEnabled: true,
    screenSharing: false,
    audioStream: null,
    videoStream: null,
    screenStream: null,
  })

  // WebSocket connection
  let ws: WebSocket | null = null

  // Store peer connections
  const peerConnections = new Map<string, RTCPeerConnection>()

  // ICE candidate queue (for candidates received before connection is ready)
  const iceCandidateQueues = new Map<string, RTCIceCandidateInit[]>()

  // ============================================
  // MEDIA STREAM MANAGEMENT
  // ============================================

  async function getLocalMedia(): Promise<MediaStream | null> {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
        },
        video: {
          width: { ideal: 1280, max: 1920 },
          height: { ideal: 720, max: 1080 },
          frameRate: { ideal: 30, max: 30 },
        },
      })

      localMedia.value.audioStream = stream
      localMedia.value.videoStream = stream

      return stream
    } catch (e) {
      console.error('getUserMedia error:', e)
      error.value = 'Impossible d\'accéder à la caméra/microphone. Vérifiez les permissions.'
      return null
    }
  }

  async function startScreenShare(): Promise<void> {
    try {
      const screenStream = await navigator.mediaDevices.getDisplayMedia({
        video: true,
        audio: false,
      })

      localMedia.value.screenStream = screenStream
      localMedia.value.screenSharing = true

      // Replace video track in all peer connections
      const screenTrack = screenStream.getVideoTracks()[0]
      if (!screenTrack) return

      screenTrack.onended = () => {
        stopScreenShare()
      }

      // Replace track in all peer connections
      for (const [, pc] of peerConnections) {
        const sender = pc.getSenders().find(s => s.track?.kind === 'video')
        if (sender) {
          await sender.replaceTrack(screenTrack)
        }
      }

      // Broadcast screen share start
      broadcastControl('screen-share-start')

    } catch (e) {
      if ((e as Error).name !== 'NotAllowedError') {
        error.value = 'Erreur lors du partage d\'écran'
      }
    }
  }

  function stopScreenShare(): void {
    if (localMedia.value.screenStream) {
      localMedia.value.screenStream.getTracks().forEach(track => track.stop())
      localMedia.value.screenStream = null
    }
    localMedia.value.screenSharing = false

    // Restore camera video track
    const videoTrack = localMedia.value.videoStream?.getVideoTracks()[0]
    if (videoTrack) {
      for (const [, pc] of peerConnections) {
        const sender = pc.getSenders().find(s => s.track?.kind === 'video')
        if (sender) {
          sender.replaceTrack(videoTrack)
        }
      }
    }

    broadcastControl('screen-share-stop')
  }

  function toggleAudio(): void {
    const audioTrack = localMedia.value.audioStream?.getAudioTracks()[0]
    if (audioTrack) {
      audioTrack.enabled = !audioTrack.enabled
      localMedia.value.audioEnabled = audioTrack.enabled
      broadcastControl(audioTrack.enabled ? 'unmute' : 'mute')
    }
  }

  function toggleVideo(): void {
    const videoTrack = localMedia.value.videoStream?.getVideoTracks()[0]
    if (videoTrack) {
      videoTrack.enabled = !videoTrack.enabled
      localMedia.value.videoEnabled = videoTrack.enabled
      broadcastControl(videoTrack.enabled ? 'video-on' : 'video-off')
    }
  }

  // ============================================
  // SIGNALING
  // ============================================

  function setupSignaling(): void {
    ws = new WebSocket(`${WS_URL}/room-${roomId}-video`)

    ws.binaryType = 'arraybuffer'

    ws.onopen = () => {
      console.log('[WebRTC] Signaling connected')
      // Register as a peer
      sendControl('join')
    }

    ws.onmessage = (event) => {
      handleSignalingMessage(event)
    }

    ws.onclose = () => {
      console.log('[WebRTC] Signaling disconnected')
    }

    ws.onerror = (e) => {
      console.error('[WebRTC] Signaling error:', e)
      error.value = 'Erreur de connexion au serveur'
    }
  }

  function handleSignalingMessage(event: MessageEvent): void {
    try {
      const data = event.data

      // Handle binary messages (WebRTC signaling)
      if (data instanceof ArrayBuffer) {
        const view = new Uint8Array(data)
        if (view.length < 2) return

        const messageType = view[0]
        if (messageType === undefined) return

        // Only handle WebRTC message types (2-5)
        if (messageType < 2 || messageType > 5) return

        const payload = JSON.parse(new TextDecoder().decode(view.slice(1)))

        switch (messageType) {
          case MSG_TYPES.OFFER:
            handleOffer(payload)
            break
          case MSG_TYPES.ANSWER:
            handleAnswer(payload)
            break
          case MSG_TYPES.ICE_CANDIDATE:
            handleIceCandidate(payload)
            break
          case MSG_TYPES.CONTROL:
            handleControl(payload)
            break
        }
      }
    } catch {
      // Not a WebRTC message or parse error
    }
  }

  function sendSignalingMessage(type: number, payload: object): void {
    if (!ws || ws.readyState !== WebSocket.OPEN) return

    const message = {
      fromPeerId: peerId,
      ...payload,
    }

    const encoded = new TextEncoder().encode(JSON.stringify(message))
    const buffer = new Uint8Array(1 + encoded.length)
    buffer[0] = type
    buffer.set(encoded, 1)

    ws.send(buffer)
  }

  function sendControl(action: string): void {
    sendSignalingMessage(MSG_TYPES.CONTROL, {
      action,
      peerId,
      userId: authStore.user?.id,
      userName: authStore.user?.displayName || 'Anonymous',
      audioEnabled: localMedia.value.audioEnabled,
      videoEnabled: localMedia.value.videoEnabled,
      screenSharing: localMedia.value.screenSharing,
    })
  }

  function broadcastControl(action: string): void {
    sendControl(action)
  }

  // ============================================
  // PEER CONNECTION MANAGEMENT
  // ============================================

  async function createPeerConnection(
    remotePeerId: string,
    userId: number,
    displayName: string,
    isInitiator: boolean
  ): Promise<void> {
    if (peerConnections.has(remotePeerId)) {
      console.log(`[WebRTC] Connection already exists for ${remotePeerId}`)
      return
    }

    console.log(`[WebRTC] Creating peer connection to ${remotePeerId}, initiator: ${isInitiator}`)

    const pc = new RTCPeerConnection(RTC_CONFIG)
    peerConnections.set(remotePeerId, pc)

    // Initialize participant
    participants.value.set(remotePeerId, {
      peerId: remotePeerId,
      userId,
      displayName,
      stream: null,
      screenStream: null,
      isAudioEnabled: true,
      isVideoEnabled: true,
      isScreenSharing: false,
      connection: pc,
    })
    // Force reactivity
    participants.value = new Map(participants.value)

    // Add local tracks
    const localStream = localMedia.value.screenSharing
      ? localMedia.value.screenStream
      : localMedia.value.videoStream

    if (localStream) {
      localStream.getTracks().forEach(track => {
        pc.addTrack(track, localStream)
      })
    }

    // Handle incoming tracks
    pc.ontrack = (event) => {
      console.log(`[WebRTC] Received track from ${remotePeerId}`)
      const participant = participants.value.get(remotePeerId)
      if (participant) {
        participant.stream = event.streams[0] ?? null
        // Force reactivity
        participants.value = new Map(participants.value)
      }
    }

    // Handle ICE candidates
    pc.onicecandidate = (event) => {
      if (event.candidate) {
        sendSignalingMessage(MSG_TYPES.ICE_CANDIDATE, {
          targetPeerId: remotePeerId,
          candidate: event.candidate.toJSON(),
        })
      }
    }

    // Handle connection state changes
    pc.onconnectionstatechange = () => {
      console.log(`[WebRTC] Connection state with ${remotePeerId}: ${pc.connectionState}`)
      if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
        // Try to reconnect or remove peer
        setTimeout(() => {
          if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
            removePeer(remotePeerId)
          }
        }, 5000)
      }
    }

    // Process any queued ICE candidates
    const queuedCandidates = iceCandidateQueues.get(remotePeerId)
    if (queuedCandidates) {
      for (const candidate of queuedCandidates) {
        try {
          await pc.addIceCandidate(candidate)
        } catch (e) {
          console.warn('[WebRTC] Failed to add queued ICE candidate:', e)
        }
      }
      iceCandidateQueues.delete(remotePeerId)
    }

    // If we're the initiator, create and send offer
    if (isInitiator) {
      try {
        const offer = await pc.createOffer()
        await pc.setLocalDescription(offer)

        sendSignalingMessage(MSG_TYPES.OFFER, {
          targetPeerId: remotePeerId,
          sdp: pc.localDescription,
        })
      } catch (e) {
        console.error('[WebRTC] Failed to create offer:', e)
      }
    }
  }

  async function handleOffer(message: { fromPeerId: string; sdp: RTCSessionDescriptionInit; userId?: number; userName?: string }): Promise<void> {
    const { fromPeerId, sdp, userId = 0, userName = 'Unknown' } = message
    console.log(`[WebRTC] Received offer from ${fromPeerId}`)

    let pc = peerConnections.get(fromPeerId)

    if (!pc) {
      // Create new connection for incoming offer
      await createPeerConnection(fromPeerId, userId, userName, false)
      pc = peerConnections.get(fromPeerId)
    }

    if (!pc) return

    try {
      await pc.setRemoteDescription(sdp)
      const answer = await pc.createAnswer()
      await pc.setLocalDescription(answer)

      sendSignalingMessage(MSG_TYPES.ANSWER, {
        targetPeerId: fromPeerId,
        sdp: pc.localDescription,
      })
    } catch (e) {
      console.error('[WebRTC] Failed to handle offer:', e)
    }
  }

  async function handleAnswer(message: { fromPeerId: string; sdp: RTCSessionDescriptionInit }): Promise<void> {
    const { fromPeerId, sdp } = message
    console.log(`[WebRTC] Received answer from ${fromPeerId}`)

    const pc = peerConnections.get(fromPeerId)

    if (pc && pc.signalingState === 'have-local-offer') {
      try {
        await pc.setRemoteDescription(sdp)
      } catch (e) {
        console.error('[WebRTC] Failed to set remote description:', e)
      }
    }
  }

  async function handleIceCandidate(message: { fromPeerId: string; candidate: RTCIceCandidateInit }): Promise<void> {
    const { fromPeerId, candidate } = message
    const pc = peerConnections.get(fromPeerId)

    if (pc && pc.remoteDescription) {
      try {
        await pc.addIceCandidate(candidate)
      } catch (e) {
        console.warn('[WebRTC] Failed to add ICE candidate:', e)
      }
    } else {
      // Queue candidate for later
      if (!iceCandidateQueues.has(fromPeerId)) {
        iceCandidateQueues.set(fromPeerId, [])
      }
      iceCandidateQueues.get(fromPeerId)!.push(candidate)
    }
  }

  function handleControl(message: {
    fromPeerId: string;
    action: string;
    userId?: number;
    userName?: string;
    audioEnabled?: boolean;
    videoEnabled?: boolean;
    screenSharing?: boolean;
  }): void {
    const { fromPeerId, action, userId = 0, userName = 'Unknown' } = message

    console.log(`[WebRTC] Control message from ${fromPeerId}: ${action}`)

    if (action === 'join') {
      // New peer joined - create connection to them
      // Only initiate if our peerId is "greater" (to avoid double connections)
      if (peerId > fromPeerId) {
        createPeerConnection(fromPeerId, userId, userName, true)
      } else {
        createPeerConnection(fromPeerId, userId, userName, false)
      }
      return
    }

    if (action === 'leave') {
      removePeer(fromPeerId)
      return
    }

    // Update participant state
    const participant = participants.value.get(fromPeerId)
    if (!participant) return

    switch (action) {
      case 'mute':
        participant.isAudioEnabled = false
        break
      case 'unmute':
        participant.isAudioEnabled = true
        break
      case 'video-off':
        participant.isVideoEnabled = false
        break
      case 'video-on':
        participant.isVideoEnabled = true
        break
      case 'screen-share-start':
        participant.isScreenSharing = true
        break
      case 'screen-share-stop':
        participant.isScreenSharing = false
        break
    }

    // Force reactivity
    participants.value = new Map(participants.value)
  }

  function removePeer(remotePeerId: string): void {
    console.log(`[WebRTC] Removing peer ${remotePeerId}`)

    const pc = peerConnections.get(remotePeerId)
    if (pc) {
      pc.close()
      peerConnections.delete(remotePeerId)
    }

    participants.value.delete(remotePeerId)
    participants.value = new Map(participants.value)
    iceCandidateQueues.delete(remotePeerId)
  }

  // ============================================
  // CALL MANAGEMENT
  // ============================================

  async function joinCall(): Promise<void> {
    if (callState.value !== 'idle') return

    callState.value = 'joining'
    error.value = null

    try {
      // Get local media first
      const stream = await getLocalMedia()
      if (!stream) {
        callState.value = 'error'
        return
      }

      // Setup signaling
      setupSignaling()

      callState.value = 'connected'
    } catch (e) {
      error.value = 'Erreur lors de la connexion à l\'appel'
      callState.value = 'error'
      console.error('Join call error:', e)
    }
  }

  function leaveCall(): void {
    // Notify others that we're leaving
    if (ws && ws.readyState === WebSocket.OPEN) {
      sendControl('leave')
    }

    // Close all peer connections
    for (const [, pc] of peerConnections) {
      pc.close()
    }
    peerConnections.clear()
    participants.value.clear()
    participants.value = new Map(participants.value)
    iceCandidateQueues.clear()

    // Stop local media
    if (localMedia.value.audioStream) {
      localMedia.value.audioStream.getTracks().forEach(track => track.stop())
      localMedia.value.audioStream = null
    }
    if (localMedia.value.videoStream) {
      localMedia.value.videoStream.getTracks().forEach(track => track.stop())
      localMedia.value.videoStream = null
    }
    if (localMedia.value.screenStream) {
      localMedia.value.screenStream.getTracks().forEach(track => track.stop())
      localMedia.value.screenStream = null
    }

    // Close WebSocket
    if (ws) {
      ws.close()
      ws = null
    }

    callState.value = 'idle'
    localMedia.value = {
      audioEnabled: true,
      videoEnabled: true,
      screenSharing: false,
      audioStream: null,
      videoStream: null,
      screenStream: null,
    }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    leaveCall()
  })

  return {
    // State
    callState,
    localMedia,
    participants,
    error,
    peerId,

    // Actions
    joinCall,
    leaveCall,
    toggleAudio,
    toggleVideo,
    startScreenShare,
    stopScreenShare,
  }
}
