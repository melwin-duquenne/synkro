import { describe, it, expect } from 'vitest'
import {
  isAtLeast,
  canAssignRole,
  canCreateRoom,
  canManageRoomMembers,
} from '@/utils/permissions'
import type { UserRole } from '@/types'

describe('isAtLeast', () => {
  it('returns true when user role equals required role', () => {
    expect(isAtLeast('user', 'user')).toBe(true)
    expect(isAtLeast('editor', 'editor')).toBe(true)
    expect(isAtLeast('owner', 'owner')).toBe(true)
    expect(isAtLeast('admin', 'admin')).toBe(true)
  })

  it('returns true when user role is higher than required', () => {
    expect(isAtLeast('admin', 'user')).toBe(true)
    expect(isAtLeast('admin', 'editor')).toBe(true)
    expect(isAtLeast('admin', 'owner')).toBe(true)
    expect(isAtLeast('owner', 'user')).toBe(true)
    expect(isAtLeast('owner', 'editor')).toBe(true)
    expect(isAtLeast('editor', 'user')).toBe(true)
  })

  it('returns false when user role is lower than required', () => {
    expect(isAtLeast('user', 'editor')).toBe(false)
    expect(isAtLeast('user', 'owner')).toBe(false)
    expect(isAtLeast('user', 'admin')).toBe(false)
    expect(isAtLeast('editor', 'owner')).toBe(false)
    expect(isAtLeast('editor', 'admin')).toBe(false)
    expect(isAtLeast('owner', 'admin')).toBe(false)
  })

  it('returns false for unknown roles', () => {
    expect(isAtLeast('unknown', 'user')).toBe(false)
    expect(isAtLeast('user', 'unknown' as UserRole)).toBe(false)
    expect(isAtLeast('', 'user')).toBe(false)
  })
})

describe('canAssignRole', () => {
  it('allows admin to assign any role including admin', () => {
    expect(canAssignRole('admin', 'user')).toBe(true)
    expect(canAssignRole('admin', 'editor')).toBe(true)
    expect(canAssignRole('admin', 'owner')).toBe(true)
    expect(canAssignRole('admin', 'admin')).toBe(true)
  })

  it('allows owner to assign user, editor, and owner roles', () => {
    expect(canAssignRole('owner', 'user')).toBe(true)
    expect(canAssignRole('owner', 'editor')).toBe(true)
    expect(canAssignRole('owner', 'owner')).toBe(true)
  })

  it('prevents owner from assigning admin role', () => {
    expect(canAssignRole('owner', 'admin')).toBe(false)
  })

  it('prevents editor from assigning any role', () => {
    expect(canAssignRole('editor', 'user')).toBe(false)
    expect(canAssignRole('editor', 'editor')).toBe(false)
    expect(canAssignRole('editor', 'owner')).toBe(false)
  })

  it('prevents user from assigning any role', () => {
    expect(canAssignRole('user', 'user')).toBe(false)
    expect(canAssignRole('user', 'editor')).toBe(false)
  })
})

describe('canCreateRoom', () => {
  it('allows editor, owner and admin to create rooms', () => {
    expect(canCreateRoom('editor')).toBe(true)
    expect(canCreateRoom('owner')).toBe(true)
    expect(canCreateRoom('admin')).toBe(true)
  })

  it('prevents user from creating rooms', () => {
    expect(canCreateRoom('user')).toBe(false)
  })

  it('returns false for unknown roles', () => {
    expect(canCreateRoom('unknown')).toBe(false)
  })
})

describe('canManageRoomMembers', () => {
  it('allows editor, owner and admin to manage room members', () => {
    expect(canManageRoomMembers('editor')).toBe(true)
    expect(canManageRoomMembers('owner')).toBe(true)
    expect(canManageRoomMembers('admin')).toBe(true)
  })

  it('prevents user from managing room members', () => {
    expect(canManageRoomMembers('user')).toBe(false)
  })
})
