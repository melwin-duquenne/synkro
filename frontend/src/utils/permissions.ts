import type { UserRole } from '@/types'

const roleHierarchy: UserRole[] = ['user', 'editor', 'owner', 'admin']

export function isAtLeast(userRole: UserRole | string, requiredRole: UserRole): boolean {
  const userLevel = roleHierarchy.indexOf(userRole as UserRole)
  const requiredLevel = roleHierarchy.indexOf(requiredRole)
  if (userLevel === -1 || requiredLevel === -1) {
    return false
  }
  return userLevel >= requiredLevel
}

export function canAssignRole(userRole: UserRole | string, targetRole: UserRole): boolean {
  // Admin can assign any role
  if (userRole === 'admin') {
    return true
  }
  // Owner can assign user/editor/owner (not admin)
  if (userRole === 'owner' && targetRole !== 'admin') {
    return true
  }
  return false
}

export function canCreateRoom(userRole: UserRole | string): boolean {
  return isAtLeast(userRole, 'editor')
}

export function canManageRoomMembers(userRole: UserRole | string): boolean {
  return isAtLeast(userRole, 'editor')
}
