import { ref } from 'vue'
import { useApi } from './useApi'
import type { Project } from '~/types/index'

export const useProjects = () => {
  const api = useApi()
  const projects = ref<Project[]>([])

  const fetchProjects = async (type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    try {
      const response = await api.get(`/projects?type=${type}`)
      projects.value = response.data || []
      return projects.value
    } catch (error) {
      throw error
    }
  }

  const getProject = async (id: string) => {
    return await api.get(`/projects/${id}`)
  }

  const createProject = async (data: any) => {
    return await api.post('/projects', data)
  }

  const updateProject = async (id: string, data: any) => {
    return await api.put(`/projects/${id}`, data)
  }

  const deleteProject = async (id: string) => {
    return await api.delete(`/projects/${id}`)
  }

  return {
    projects,
    fetchProjects,
    getProject,
    createProject,
    updateProject,
    deleteProject
  }
}