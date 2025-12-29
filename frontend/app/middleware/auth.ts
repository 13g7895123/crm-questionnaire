/**
 * Authentication Middleware
 * Protects routes and ensures user is authenticated
 */

import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // List of public routes that don't require authentication
  const publicRoutes = ['/login', '/register']

  // Check if route requires authentication
  const isProtectedRoute = !publicRoutes.includes(to.path)

  console.log('Auth Middleware:', to.path, 'Protected:', isProtectedRoute, 'Auth:', authStore.isAuthenticated)

  // Server-side check: if we have the access_token cookie, consider authenticated
  if (process.server && isProtectedRoute && !authStore.isAuthenticated) {
    const token = useCookie('auth_token')
    if (token.value) {
      console.log('Server-side auth: cookie found')
      return
    }
  }

  // If trying to access protected route without auth, redirect to login
  if (isProtectedRoute && !authStore.isAuthenticated) {
    // Try to restore auth first (handle page refresh)
    authStore.restoreAuth()
    console.log('Restored auth. Auth:', authStore.isAuthenticated)

    // Check again after restore
    if (!authStore.isAuthenticated) {
      console.log('Redirecting to login')
      return navigateTo('/login')
    }
  }

  // If already authenticated and trying to access login page, redirect to home
  if (to.path === '/login' && authStore.isAuthenticated) {
    console.log('Already auth, redirecting to home')
    return navigateTo('/')
  }
})
