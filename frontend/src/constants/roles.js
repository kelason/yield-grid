export const USER_ROLES = {
  FARMER: 'farmer',
  BUYER: 'buyer',
  ADMIN: 'admin',
}

export const ROLE_HOME = {
  farmer: { name: 'farmer-dashboard' },
  buyer: { name: 'buyer-dashboard' },
  admin: { name: 'admin-overview' },
}

export function isKnownRole(role) {
  return role === USER_ROLES.FARMER || role === USER_ROLES.BUYER || role === USER_ROLES.ADMIN
}

export function roleHomeTarget(role) {
  return ROLE_HOME[role] ?? { name: 'forbidden' }
}
