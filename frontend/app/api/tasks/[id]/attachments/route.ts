import { NextResponse } from 'next/server'
import { getSessionToken } from '@/app/lib/session'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

export const runtime = 'nodejs'

export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const token = await getSessionToken()

  if (!token) {
    return NextResponse.json({ message: 'Unauthorized' }, { status: 401 })
  }

  if (!request.body) {
    return NextResponse.json({ message: 'Empty request body.' }, { status: 400 })
  }

  const contentType = request.headers.get('content-type') ?? ''
  if (!contentType.includes('multipart/form-data')) {
    return NextResponse.json({ message: 'Expected multipart/form-data.' }, { status: 400 })
  }

  const upstream = await fetch(`${BACKEND_URL}/tasks/${id}/attachments`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`,
      'Content-Type': contentType,
    },
    body: request.body,
    duplex: 'half',
    cache: 'no-store',
  } as RequestInit)

  const text = await upstream.text()
  let payload: unknown = null
  try {
    payload = JSON.parse(text)
  } catch {
    // non-JSON response
  }

  if (!upstream.ok) {
    const message =
      payload && typeof payload === 'object' && 'message' in payload
        ? String((payload as { message: unknown }).message)
        : 'Upload failed.'
    return NextResponse.json({ message }, { status: upstream.status })
  }

  return NextResponse.json(payload, { status: upstream.status })
}
