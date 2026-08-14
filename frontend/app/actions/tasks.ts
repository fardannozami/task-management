'use server'

import { revalidatePath } from 'next/cache'
import { redirect } from 'next/navigation'
import { getSessionToken } from '@/app/lib/session'
import {
  taskSchema,
  type Paginated,
  type Task,
  type TaskInput,
  type TaskStatus,
  type TaskPriority,
} from '@/app/lib/definitions'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

export type TaskListParams = {
  status?: TaskStatus | ''
  priority?: TaskPriority | ''
  search?: string
  page?: number
  perPage?: number
}

async function getAccessToken(): Promise<string> {
  const token = await getSessionToken()

  if (!token) {
    redirect('/login')
  }

  return token
}

async function authorizedFetch(path: string, init?: RequestInit) {
  const token = await getAccessToken()

  const response = await fetch(`${BACKEND_URL}${path}`, {
    ...init,
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`,
      ...(init?.body ? { 'Content-Type': 'application/json' } : {}),
      ...init?.headers,
    },
    cache: 'no-store',
  })

  return response
}

export async function fetchTasks(params: TaskListParams = {}): Promise<Paginated<Task>> {
  const query = new URLSearchParams()
  if (params.status) query.set('status', params.status)
  if (params.priority) query.set('priority', params.priority)
  if (params.search) query.set('search', params.search)
  query.set('page', String(params.page ?? 1))
  query.set('per_page', String(params.perPage ?? 10))

  const response = await authorizedFetch(`/tasks?${query.toString()}`)

  if (!response.ok) {
    throw new Error('Failed to fetch tasks')
  }

  return response.json()
}

export async function fetchUsers(): Promise<{ id: number; name: string; email: string }[]> {
  const response = await authorizedFetch('/users')

  if (!response.ok) {
    return []
  }

  const data = await response.json()
  return data
}

export type TaskFormState = {
  errors?: Partial<Record<keyof TaskInput, string[]>>
  message?: string | null
  success?: boolean
}

const initialState: TaskFormState = {
  errors: {},
  message: null,
  success: false,
}

function normalizeDueDate(value: string | undefined): string | null {
  if (!value) return null
  const normalized = value.replace('T', ' ')
  return normalized.length === 16 ? `${normalized}:00` : normalized
}

export async function createTaskAction(prevState: TaskFormState, formData: FormData): Promise<TaskFormState> {
  const rawData = {
    title: formData.get('title') as string,
    description: (formData.get('description') as string) ?? '',
    status: (formData.get('status') as TaskStatus) || 'pending',
    priority: (formData.get('priority') as TaskPriority) || 'medium',
    assigned_user_id: formData.get('assigned_user_id'),
    due_date: normalizeDueDate((formData.get('due_date') as string) || ''),
  }

  const validated = taskSchema.safeParse(rawData)

  if (!validated.success) {
    return {
      errors: validated.error.flatten().fieldErrors,
      message: 'Please fix the errors below.',
    }
  }

  const payload: TaskInput = validated.data

  try {
    const response = await authorizedFetch('/tasks', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return {
        errors: error?.errors ?? {},
        message: error?.message || 'Failed to create task.',
      }
    }

    revalidatePath('/dashboard')
    return { ...initialState, success: true }
  } catch {
    return {
      errors: {},
      message: 'Network error. Please try again.',
    }
  }
}

export async function updateTaskAction(
  taskId: number,
  prevState: TaskFormState,
  formData: FormData
): Promise<TaskFormState> {
  const rawData = {
    title: formData.get('title') as string,
    description: (formData.get('description') as string) ?? '',
    status: (formData.get('status') as TaskStatus) || 'pending',
    priority: (formData.get('priority') as TaskPriority) || 'medium',
    assigned_user_id: formData.get('assigned_user_id'),
    due_date: normalizeDueDate((formData.get('due_date') as string) || ''),
  }

  const validated = taskSchema.safeParse(rawData)

  if (!validated.success) {
    return {
      errors: validated.error.flatten().fieldErrors,
      message: 'Please fix the errors below.',
    }
  }

  const payload: TaskInput = validated.data

  try {
    const response = await authorizedFetch(`/tasks/${taskId}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return {
        errors: error?.errors ?? {},
        message: error?.message || 'Failed to update task.',
      }
    }

    revalidatePath('/dashboard')
    return { ...initialState, success: true }
  } catch {
    return {
      errors: {},
      message: 'Network error. Please try again.',
    }
  }
}

export async function deleteTaskAction(taskId: number): Promise<{ message?: string }> {
  try {
    const response = await authorizedFetch(`/tasks/${taskId}`, {
      method: 'DELETE',
    })

    if (!response.ok) {
      const error = await response.json().catch(() => null)
      return { message: error?.message || 'Failed to delete task.' }
    }

    revalidatePath('/dashboard')
    return {}
  } catch {
    return { message: 'Network error. Please try again.' }
  }
}
