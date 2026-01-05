// User types
export interface User {
  id: number
  email: string
  displayName: string
  role: 'admin' | 'user'
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
}

export interface RoomTemplate {
  id: number
  name: string
  description?: string
  isDefault: boolean
  templateModules: { module: Module }[]
}

// Permission types
export interface TeamRoomPermission {
  id: number
  team: Team
  role: 'viewer' | 'editor' | 'owner'
}

export interface UserRoomPermission {
  id: number
  user: User
  role: 'viewer' | 'editor' | 'owner'
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

export interface CalendarEvent {
  id: number
  room?: Room
  user: User
  entreprise: Entreprise
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
  room: Room
  user: User
  fileName: string
  filePath: string
  mimeType: string
  size: number
  createdAt: string
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
