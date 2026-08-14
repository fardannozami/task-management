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
