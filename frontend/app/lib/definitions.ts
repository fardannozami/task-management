import { z } from 'zod'

export const loginSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(1, 'Password is required'),
})

export const sessionSchema = z.object({
  userId: z.number(),
  name: z.string(),
  email: z.string().email(),
  role: z.string(),
  accessToken: z.string(),
  expiresAt: z.coerce.date(),
})

export type LoginInput = z.infer<typeof loginSchema>
export type SessionData = z.infer<typeof sessionSchema>

export const taskStatuses = ['pending', 'in_progress', 'completed', 'cancelled'] as const
export const taskPriorities = ['low', 'medium', 'high', 'urgent'] as const

export type TaskStatus = (typeof taskStatuses)[number]
export type TaskPriority = (typeof taskPriorities)[number]

const optionalId = z.preprocess(
  (val) => {
    if (val === '' || val === null || val === undefined) return null
    return Number(val)
  },
  z.number().int().positive().nullable().optional()
)

const optionalDueDate = z.preprocess(
  (val) => {
    if (val === '' || val === null || val === undefined) return null
    return val
  },
  z.string().nullable().optional()
)

const optionalText = z.preprocess(
  (val) => {
    if (typeof val === 'string' && val.trim() === '') return null
    if (val === null || val === undefined) return null
    return val
  },
  z.string().trim().nullable().optional()
)

export const taskSchema = z.object({
  title: z.string().trim().min(1, 'Title is required').max(255, 'Title must be 255 characters or less'),
  description: optionalText,
  status: z.enum(taskStatuses),
  priority: z.enum(taskPriorities),
  assigned_user_id: optionalId,
  due_date: optionalDueDate,
})

export type TaskInput = z.infer<typeof taskSchema>

export interface TaskUser {
  id: number
  name: string
  email: string
}

export interface VirusScanResult {
  id: number
  task_attachment_id: number
  status: 'clean' | 'infected' | 'suspicious' | 'pending'
  threats_found: string | null
  action_taken: string
  scanned_at: string | null
}

export interface TaskAttachment {
  id: number
  task_id: number
  file_name: string
  file_size: number
  mime_type: string
  uploaded_at: string
  thumbnail_path: string | null
  thumbnail_size: number | null
  created_at: string
  updated_at: string
  virus_scan_result?: VirusScanResult | null
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  assigned_user_id: number | null
  created_by: number
  due_date: string | null
  created_at: string
  updated_at: string
  assigned_user?: TaskUser | null
  creator?: TaskUser | null
  attachments?: TaskAttachment[]
  attachments_count?: number
}

export interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}
