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

  const showConfirm = async (options: {
    title?: string;
    text: string;
    icon?: SweetAlertIcon;
    confirmButtonText?: string;
    cancelButtonText?: string;
    confirmButtonColor?: string;
    cancelButtonColor?: string;
  }) => {
    if (process.client) {
      const result = await Swal.fire({
        title: options.title || '系統提示',
        text: options.text,
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonColor: options.confirmButtonColor || '#3085d6',
        cancelButtonColor: options.cancelButtonColor || '#aaa',
        confirmButtonText: options.confirmButtonText || '確定',
        cancelButtonText: options.cancelButtonText || '取消'
      })
      return result.isConfirmed
    }
    return false
  }

  const showAlert = (title: string, text: string, icon: SweetAlertIcon = 'info') => {
    if (process.client) {
      return Swal.fire({
        title,
        text,
        icon,
        confirmButtonText: '確定'
      })
    }
    return Promise.resolve()
  }

  const showSuccess = (text: string) => showSystemAlert(text, 'success')
  const showError = (text: string) => showSystemAlert(text, 'error')
  const showErrorWithConfirm = (text: string, title: string = '錯誤') => {
    if (process.client) {
      return Swal.fire({
        title,
        html: text.replace(/\n/g, '<br>'), // 支持換行
        icon: 'error',
        confirmButtonText: '確定',
        customClass: {
          popup: 'swal-wide'
        }
      })
    }
    return Promise.resolve()
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
    showConfirm,
    showAlert,
    showSuccess,
    showError,
    showErrorWithConfirm,
    showLoading,
    closeAlert
  }
}
