import { ref } from 'vue'
import { useApi } from './useApi'
import type { Project } from '~/types/index'

export const useProjects = () => {
  const api = useApi()
  const projects = ref<Project[]>([])

  const fetchProjects = async (type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    try {
      const url = type === 'CONFLICT' ? '/rm/projects' : `/projects?type=${type}`
      const response = await api.get(url)
      projects.value = response.data.data || response.data || []
      return projects.value
    } catch (error) {
      throw error
    }
  }

  const getProject = async (id: string | number, type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    const url = type === 'CONFLICT' ? `/rm/projects/${id}` : `/projects/${id}`
    return await api.get(url)
  }

  const createProject = async (data: any) => {
    const url = data.type === 'CONFLICT' ? '/rm/projects' : '/projects'
    return await api.post(url, data)
  }

  const updateProject = async (id: string | number, data: any) => {
    const url = data.type === 'CONFLICT' ? `/rm/projects/${id}` : `/projects/${id}`
    return await api.put(url, data)
  }

  const deleteProject = async (id: string | number, type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    const url = type === 'CONFLICT' ? `/rm/projects/${id}` : `/projects/${id}`
    return await api.delete(url)
  }

  const duplicateProject = async (id: string | number, type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    const url = type === 'CONFLICT' ? `/rm/projects/${id}/duplicate` : `/projects/${id}/duplicate`
    return await api.post(url)
  }

  return {
    projects,
    fetchProjects,
    getProject,
    createProject,
    updateProject,
    deleteProject,
    duplicateProject
  }
}