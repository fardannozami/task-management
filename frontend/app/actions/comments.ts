'use server'

import { getSessionToken } from '@/app/lib/session'
import { type TaskComment } from '@/app/lib/definitions'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

async function getToken(): Promise<string> {
  const token = await getSessionToken()
  if (!token) {
    throw new Error('Not authenticated')
  }
  return token
}

export async function createCommentAction(
  taskId: number,
  comment: string
): Promise<{ comment?: TaskComment; error?: string }> {
  try {
    const token = await getToken()
    const response = await fetch(`${BACKEND_URL}/tasks/${taskId}/comments`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ comment }),
      cache: 'no-store',
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return { error: error?.message || 'Failed to post comment.' }
    }

    return { comment: await response.json() }
  } catch {
    return { error: 'Network error. Please try again.' }
  }
}

export async function deleteCommentAction(
  commentId: number
): Promise<{ error?: string }> {
  try {
    const token = await getToken()
    const response = await fetch(`${BACKEND_URL}/comments/${commentId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      cache: 'no-store',
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return { error: error?.message || 'Failed to delete comment.' }
    }

    return {}
  } catch {
    return { error: 'Network error. Please try again.' }
  }
}
