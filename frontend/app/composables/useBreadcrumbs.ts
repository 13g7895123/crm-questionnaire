import { useState } from '#app'

export interface BreadcrumbItem {
  label: string
  to?: string
  icon?: string
  click?: () => void
}

export const useBreadcrumbs = () => {
  const customBreadcrumbs = useState<BreadcrumbItem[] | null>('customBreadcrumbs', () => null)

  const setBreadcrumbs = (items: BreadcrumbItem[]) => {
    customBreadcrumbs.value = items
  }

  const clearBreadcrumbs = () => {
    customBreadcrumbs.value = null
  }

  return {
    customBreadcrumbs,
    setBreadcrumbs,
    clearBreadcrumbs
  }
}
