// Types pour le module WebRTC Visioconference

export interface Participant {
  peerId: string
  userId: number
  displayName: string
  stream: MediaStream | null
  screenStream: MediaStream | null
  isAudioEnabled: boolean
  isVideoEnabled: boolean
  isScreenSharing: boolean
  connection: RTCPeerConnection | null
}

export interface LocalMediaState {
  audioEnabled: boolean
  videoEnabled: boolean
  screenSharing: boolean
  audioStream: MediaStream | null
  videoStream: MediaStream | null
  screenStream: MediaStream | null
}

export type CallState = 'idle' | 'joining' | 'connected' | 'error'

export interface SignalingMessage {
  type: 'offer' | 'answer' | 'ice-candidate' | 'control'
  targetPeerId?: string
  fromPeerId: string
  payload: RTCSessionDescriptionInit | RTCIceCandidateInit | ControlPayload
}

export interface ControlPayload {
  action: 'join' | 'leave' | 'mute' | 'unmute' | 'video-off' | 'video-on' | 'screen-share-start' | 'screen-share-stop'
  userId: number
  userName: string
}

export interface WebRTCConfig {
  iceServers: RTCIceServer[]
  maxParticipants: number
}

export interface UseWebRTCReturn {
  // State
  callState: import('vue').Ref<CallState>
  localMedia: import('vue').Ref<LocalMediaState>
  participants: import('vue').Ref<Map<string, Participant>>
  error: import('vue').Ref<string | null>
  peerId: string

  // Actions
  joinCall: () => Promise<void>
  leaveCall: () => void
  toggleAudio: () => void
  toggleVideo: () => void
  startScreenShare: () => Promise<void>
  stopScreenShare: () => void
}

// Message types pour le signaling WebSocket
export const SIGNALING_MESSAGE_TYPES = {
  OFFER: 2,
  ANSWER: 3,
  ICE_CANDIDATE: 4,
  CONTROL: 5,
} as const

export type SignalingMessageType = typeof SIGNALING_MESSAGE_TYPES[keyof typeof SIGNALING_MESSAGE_TYPES]
