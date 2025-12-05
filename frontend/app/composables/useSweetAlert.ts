import Swal from 'sweetalert2'

type SweetAlertIcon = 'success' | 'error' | 'warning' | 'info' | 'question'

export const useSweetAlert = () => {
  const showSystemAlert = (text: string, icon?: SweetAlertIcon) => {
    if (process.client) {
      return Swal.fire({
        title: '系統提示',
        text: text,
        icon: icon,
        timer: 1500,
        timerProgressBar: true,
        showConfirmButton: false
      })
    }
    return Promise.resolve()
  }

  const showConfirmDialog = async (text: string) => {
    if (process.client) {
      const result = await Swal.fire({
        title: '系統提示',
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '確定刪除',
        cancelButtonText: '取消'
      })
      return result.isConfirmed
    }
    return false
  }

  const showLoading = (title: string = '處理中...') => {
    if (process.client) {
      Swal.fire({
        title,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading()
        }
      })
    }
  }

  const closeAlert = () => {
    if (process.client) {
      Swal.close()
    }
  }

  return {
    showSystemAlert,
    showConfirmDialog,
    showLoading,
    closeAlert
  }
}
