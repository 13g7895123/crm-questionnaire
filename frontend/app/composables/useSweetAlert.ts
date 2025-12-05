import Swal from 'sweetalert2'

export const useSweetAlert = () => {
  const showSystemAlert = (text: string) => {
    if (process.client) {
      return Swal.fire({
        title: '系統提示',
        text: text,
        timer: 1500,
        timerProgressBar: true,
        showConfirmButton: false
      })
    }
    return Promise.resolve()
  }

  return {
    showSystemAlert
  }
}
