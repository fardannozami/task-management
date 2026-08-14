'use client'

import { type TaskAttachment } from '@/app/lib/definitions'

export function uploadFileWithProgress(
  taskId: number,
  file: File,
  onProgress: (percent: number) => void
): Promise<{ attachment?: TaskAttachment; error?: string }> {
  return new Promise((resolve) => {
    const formData = new FormData()
    formData.append('file', file)

    const xhr = new XMLHttpRequest()
    xhr.open('POST', `/api/tasks/${taskId}/attachments`)
    xhr.setRequestHeader('Accept', 'application/json')

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        onProgress(Math.round((event.loaded / event.total) * 100))
      }
    }

    xhr.onload = () => {
      let payload: unknown = null
      try {
        payload = JSON.parse(xhr.responseText)
      } catch {
        // not JSON
      }

      if (xhr.status >= 200 && xhr.status < 300 && payload) {
        resolve({ attachment: payload as TaskAttachment })
        return
      }

      const message =
        payload && typeof payload === 'object' && 'message' in payload
          ? String((payload as { message: unknown }).message)
          : null
      resolve({ error: message || `Upload failed (HTTP ${xhr.status}).` })
    }

    xhr.onerror = () => resolve({ error: 'Network error. Please try again.' })
    xhr.onabort = () => resolve({ error: 'Upload aborted.' })

    xhr.send(formData)
  })
}
