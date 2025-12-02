import { useApi } from './useApi'
import { useAuthStore } from '~/stores/auth'

export const useUser = () => {
  const api = useApi()
  const authStore = useAuthStore()

  const updateProfile = async (data: any) => {
    const response = await api.put(`/users/${authStore.user?.id}`, data)
    authStore.updateUser(response)
    return response
  }

  const changePassword = async (currentPassword: string, newPassword: string) => {
    await api.post('/users/change-password', { currentPassword, newPassword })
  }

  return {
    updateProfile,
    changePassword
  }
}