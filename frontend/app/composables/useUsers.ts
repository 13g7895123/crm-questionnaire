import { ref } from 'vue'
import type { User } from '~/types/index'

// Mock data
const mockUsers: User[] = [
  {
    id: 'usr_1',
    username: 'admin',
    email: 'admin@example.com',
    phone: '0912345678',
    role: 'ADMIN',
    organizationId: 'org_host',
    departmentId: 'dept_it',
    department: {
      id: 'dept_it',
      name: 'IT Department',
      organizationId: 'org_host',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  },
  {
    id: 'usr_2',
    username: 'host_user',
    email: 'host@example.com',
    phone: '0923456789',
    role: 'HOST',
    organizationId: 'org_host',
    departmentId: 'dept_sales',
    department: {
      id: 'dept_sales',
      name: 'Sales Department',
      organizationId: 'org_host',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  },
  {
    id: 'usr_3',
    username: 'supplier_user',
    email: 'supplier@example.com',
    phone: '0934567890',
    role: 'SUPPLIER',
    organizationId: 'org_supplier',
    departmentId: 'dept_prod',
    department: {
      id: 'dept_prod',
      name: 'Production',
      organizationId: 'org_supplier',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }
  }
]

export const useUsers = () => {
  const users = ref<User[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchUsers = async () => {
    loading.value = true
    error.value = null
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      users.value = [...mockUsers]
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch users'
    } finally {
      loading.value = false
    }
  }

  const createUser = async (userData: Partial<User>) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const newUser: User = {
        id: `usr_${Date.now()}`,
        username: userData.username || '',
        email: userData.email || '',
        phone: userData.phone || '',
        role: userData.role || 'SUPPLIER',
        organizationId: userData.organizationId || '',
        departmentId: userData.departmentId || '',
        ...userData
      } as User
      mockUsers.push(newUser)
      users.value.push(newUser)
      return { data: newUser }
    } catch (e: any) {
      throw new Error(e.message || 'Failed to create user')
    } finally {
      loading.value = false
    }
  }

  const updateUser = async (id: string, updates: Partial<User>) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockUsers.findIndex(u => u.id === id)
      if (index !== -1) {
        mockUsers[index] = { ...mockUsers[index], ...updates }
        const userIndex = users.value.findIndex(u => u.id === id)
        if (userIndex !== -1) {
          users.value[userIndex] = { ...users.value[userIndex], ...updates }
        }
        return { data: mockUsers[index] }
      }
      throw new Error('User not found')
    } catch (e: any) {
      throw new Error(e.message || 'Failed to update user')
    } finally {
      loading.value = false
    }
  }

  const deleteUser = async (id: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockUsers.findIndex(u => u.id === id)
      if (index !== -1) {
        mockUsers.splice(index, 1)
        users.value = users.value.filter(u => u.id !== id)
      }
    } catch (e: any) {
      throw new Error(e.message || 'Failed to delete user')
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    loading,
    error,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser
  }
}
