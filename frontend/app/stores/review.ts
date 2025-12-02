import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useReviewStore = defineStore('review', () => {
  const currentPermissions = ref([])

  return {
    currentPermissions
  }
})