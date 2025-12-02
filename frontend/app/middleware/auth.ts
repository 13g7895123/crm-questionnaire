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

  // If trying to access protected route without auth, redirect to login
  if (isProtectedRoute && !authStore.isAuthenticated) {
    // Try to restore auth first (handle page refresh)
    authStore.restoreAuth()
    
    // Check again after restore
    if (!authStore.isAuthenticated) {
      return navigateTo('/login')
    }
  }

  // If already authenticated and trying to access login page, redirect to home
  if (to.path === '/login' && authStore.isAuthenticated) {
    return navigateTo('/')
  }
})
