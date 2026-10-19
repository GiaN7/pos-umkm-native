import api from '@/lib/api'

/**
 * Download file dari API (CSV/PDF) dengan header Authorization,
 * lalu simpan sebagai file di komputer user.
 */
export async function downloadFile(url: string, params: Record<string, string>) {
  const response = await api.get(url, {
    params,
    responseType: 'blob',
  })

  // Ambil nama file dari header Content-Disposition, fallback ke nama default
  const disposition = response.headers['content-disposition'] as string | undefined
  const match = disposition?.match(/filename="?([^";]+)"?/)
  const filename = match?.[1] ?? 'export.csv'

  const blobUrl = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = blobUrl
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(blobUrl)
}

/** Cetak halaman saat ini (untuk export PDF via print dialog browser). */
export function printPage() {
  window.print()
}
