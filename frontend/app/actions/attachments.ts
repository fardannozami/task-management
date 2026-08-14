'use server'

import { revalidatePath } from 'next/cache'
import { getSessionToken } from '@/app/lib/session'
import { type Task, type VirusScanResult } from '@/app/lib/definitions'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

async function getToken(): Promise<string> {
  const token = await getSessionToken()
  if (!token) {
    throw new Error('Not authenticated')
  }
  return token
}

export async function fetchTaskDetailAction(
  taskId: number
): Promise<{ task?: Task; error?: string }> {
  try {
    const token = await getToken()
    const response = await fetch(`${BACKEND_URL}/tasks/${taskId}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      cache: 'no-store',
    })

    if (!response.ok) {
      return { error: 'Failed to load task details.' }
    }

    return { task: await response.json() }
  } catch {
    return { error: 'Network error. Please try again.' }
  }
}

export async function deleteAttachmentAction(
  attachmentId: number
): Promise<{ error?: string }> {
  try {
    const token = await getToken()
    const response = await fetch(`${BACKEND_URL}/attachments/${attachmentId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      cache: 'no-store',
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return { error: error?.message || 'Failed to delete attachment.' }
    }

    revalidatePath('/dashboard')
    return {}
  } catch {
    return { error: 'Network error. Please try again.' }
  }
}

export async function scanAttachmentAction(
  attachmentId: number
): Promise<{ result?: VirusScanResult; error?: string }> {
  try {
    const token = await getToken()
    const response = await fetch(`${BACKEND_URL}/attachments/${attachmentId}/scan`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      cache: 'no-store',
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return { error: error?.message || 'Scan failed.' }
    }

    return { result: await response.json() }
  } catch {
    return { error: 'Network error. Please try again.' }
  }
}
