'use server'

import { cookies } from 'next/headers'
import { redirect } from 'next/navigation'
import { loginSchema, type LoginInput } from '@/app/lib/definitions'
import { createSession, deleteSession, decryptSession } from '@/app/lib/session'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

export type LoginState = {
  errors?: {
    email?: string[]
    password?: string[]
  }
  message?: string | null
}

export async function loginAction(prevState: LoginState | null, formData: FormData): Promise<LoginState> {
  const rawData = {
    email: formData.get('email') as string,
    password: formData.get('password') as string,
  }

  const validatedFields = loginSchema.safeParse(rawData)

  if (!validatedFields.success) {
    return {
      errors: validatedFields.error.flatten().fieldErrors,
      message: 'Invalid input',
    }
  }

  const { email, password }: LoginInput = validatedFields.data

  try {
    const response = await fetch(`${BACKEND_URL}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    })

    if (!response.ok) {
      const error = await response.json()
      return {
        errors: {},
        message: error.message || 'Invalid credentials',
      }
    }

    const data = await response.json()

    if (!data.access_token) {
      return {
        errors: {},
        message: 'Login failed - no token received',
      }
    }

    const expiresAt = new Date()
    expiresAt.setSeconds(expiresAt.getSeconds() + (data.expires_in || 3600))

    await createSession({
      userId: data.user.id,
      name: data.user.name,
      email: data.user.email,
      role: data.user.role,
      accessToken: data.access_token,
      expiresAt,
    })

    redirect('/dashboard')
  } catch (error) {
    return {
      errors: {},
      message: 'Network error. Please try again.',
    }
  }
}

export async function logoutAction() {
  try {
    const cookieStore = await cookies()
    const sessionCookie = cookieStore.get('session')?.value
    const session = await decryptSession(sessionCookie)

    if (session?.accessToken) {
      await fetch(`${BACKEND_URL}/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${session.accessToken}`,
          'Accept': 'application/json',
        },
      }).catch(() => {})
    }
  } catch (error) {
    // Ignore logout backend errors
  } finally {
    await deleteSession()
    redirect('/login')
  }
}
