import { ref } from 'vue'

export const useExcel = () => {
    const loading = ref(false)

    /**
     * Parse Excel file and return rows as arrays of data
     * Uses dynamic import for sheetjs to reduce bundle size
     */
    const parseExcel = async (file: File): Promise<any[][]> => {
        loading.value = true
        try {
            // Dynamic import to optimize performance
            const XLSX = await import('xlsx')

            return new Promise((resolve, reject) => {
                const reader = new FileReader()

                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target?.result as ArrayBuffer)
                        const workbook = XLSX.read(data, { type: 'array' })
                        const firstSheetName = workbook.SheetNames[0]
                        const worksheet = workbook.Sheets[firstSheetName]

                        // Convert to JSON (array of arrays)
                        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 }) as any[][]
                        resolve(jsonData)
                    } catch (err) {
                        reject(err)
                    }
                }

                reader.onerror = (err) => reject(err)
                reader.readAsArrayBuffer(file)
            })
        } finally {
            loading.value = false
        }
    }

    /**
     * Helper to download a simple template
     */
    const downloadTemplate = async (filename: string, headers: string[], data?: any[][]) => {
        const XLSX = await import('xlsx')
        const rows = data ? [headers, ...data] : [headers]
        const worksheet = XLSX.utils.aoa_to_sheet(rows)
        const workbook = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Template')
        XLSX.writeFile(workbook, `${filename}.xlsx`)
    }

    return {
        loading,
        parseExcel,
        downloadTemplate
    }
}
