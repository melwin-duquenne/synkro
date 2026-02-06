// User types
export type UserRole = 'user' | 'editor' | 'owner' | 'admin'

export interface User {
  id: number
  email: string
  displayName: string
  role: UserRole
  avatarUrl?: string | null
  entreprise: Entreprise | null
  team: Team | null
  createdAt?: string
}

export interface Entreprise {
  id: number
  name: string
  domain?: string
  createdAt?: string
}

export interface Team {
  id: number
  name: string
  entreprise?: Entreprise
}

// Room types
export interface Room {
  id: number
  name: string
  isTemporary: boolean
  visibility: 'enterprise' | 'private'
  creator: User
  entreprise: Entreprise
  template?: RoomTemplate
  moduleRooms: ModuleRoom[]
  createdAt: string
  layoutType?: string
}

export interface Module {
  id: number
  name: string
  code: 'editor' | 'whiteboard' | 'chat' | 'video' | 'files' | 'tasks' | 'calendar'
  description?: string
}

export interface ModuleRoom {
  id: number
  module: Module
  configJson?: Record<string, unknown>
  displayOrder?: number
}
type TemplateModule = {
  id: number
  module: {
    code: string
    name: string
  }
}
export interface RoomTemplate {
  id: number
  name: string
  description?: string
  isDefault: boolean
  templateModules: TemplateModule[]
}

// Permission types
export interface TeamRoomPermission {
  id: number
  team: Team
}

export interface UserRoomPermission {
  id: number
  user: User
}

export interface RoomMember {
  id: number
  displayName: string
  email?: string
  isCreator: boolean
}

// Message types
export interface Message {
  id: number
  room: Room
  user: User
  content: string
  createdAt: string
}

// Task types
export interface Task {
  id: number
  room: Room
  title: string
  description?: string
  status: 'todo' | 'in_progress' | 'done'
  position: number
  assignedTo?: User
  createdAt: string
}

// Calendar types
export type EventType = 'meeting' | 'absence' | 'blocked' | 'reminder' | 'other'

export interface CalendarEventParticipant {
  id: number
  userId: number
  displayName: string
  status: 'pending' | 'accepted' | 'declined'
}

export interface CalendarEvent {
  id: number
  roomId: number | null
  user: { id: number; displayName: string }
  title: string
  description?: string
  eventType: EventType
  startDate: string
  endDate: string
  isAllDay: boolean
  recurrence?: string
  color?: string
  location?: string
  isPrivate: boolean
  createdAt: string
  participants: CalendarEventParticipant[]
}

export interface CreateCalendarEventData {
  title: string
  description?: string
  eventType: EventType
  startDate: string
  endDate: string
  isAllDay?: boolean
  recurrence?: string
  color?: string
  location?: string
  isPrivate?: boolean
  roomId?: number | null
  participantIds?: number[]
}

// Document types
export interface Document {
  id: number
  room: Room
  contentMarkdown?: string
  updatedAt: string
}

// File types
export interface FileResource {
  id: number
  fileName: string
  filePath: string | null
  mimeType: string | null
  size: number
  isFolder: boolean
  parentId: number | null
  roomId: number
  user: { id: number; displayName: string } | null
  createdAt: string
  childCount: number
}

// Account types
export interface UpdateProfileData {
  displayName?: string
  email?: string
}

export interface AdminUser {
  id: number
  email: string
  displayName: string
  role: UserRole
  avatarUrl?: string | null
  team: Team | null
  createdAt: string
}

// Invitation types
export interface Invitation {
  id: number
  email: string
  status: 'pending' | 'accepted' | 'expired'
  createdAt: string
  expiresAt: string
  invitedBy: {
    id: number
    displayName: string
  }
}

export interface SendInvitationData {
  email: string
}

// Auth types
export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  email: string
  password: string
  displayName: string
}

export interface AuthResponse {
  token: string
}

// WebRTC types
export * from './webrtc'
