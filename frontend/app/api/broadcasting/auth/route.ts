import { NextResponse } from 'next/server'
import { cookies } from 'next/headers'
import { decryptSession } from '@/app/lib/session'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'
const BACKEND_BASE = BACKEND_URL.replace(/\/api$/, '')

export async function POST(request: Request) {
  const cookie = (await cookies()).get('session')?.value
  const session = await decryptSession(cookie)

  if (!session?.accessToken) {
    return NextResponse.json({ message: 'Unauthorized' }, { status: 401 })
  }

  const contentType = request.headers.get('content-type') ?? 'application/x-www-form-urlencoded'
  const body = await request.text()

  try {
    const response = await fetch(`${BACKEND_BASE}/broadcasting/auth`, {
      method: 'POST',
      headers: {
        'Content-Type': contentType,
        'Accept': 'application/json',
        'Authorization': `Bearer ${session.accessToken}`,
      },
      body,
      cache: 'no-store',
    })

    const data = await response.json().catch(() => null)

    if (!response.ok) {
      return NextResponse.json(
        { message: data?.message ?? 'Channel authorization failed' },
        { status: response.status }
      )
    }

    return NextResponse.json(data)
  } catch {
    return NextResponse.json({ message: 'Network error' }, { status: 502 })
  }
}
