import { NextResponse } from 'next/server'
import { getSessionToken } from '@/app/lib/session'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

const FORWARD = ['Content-Type', 'Content-Length', 'Content-Range', 'Accept-Ranges']

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const token = await getSessionToken()

  if (!token) {
    return new Response('Unauthorized', { status: 401 })
  }

  const headers = new Headers({
    'Accept': '*/*',
    'Authorization': `Bearer ${token}`,
  })

  const range = request.headers.get('range')
  if (range) headers.set('Range', range)

  const ifRange = request.headers.get('if-range')
  if (ifRange) headers.set('If-Range', ifRange)

  const upstream = await fetch(`${BACKEND_URL}/attachments/${id}/stream`, {
    headers,
    cache: 'no-store',
  })

  if (!upstream.ok && upstream.status !== 206) {
    const text = await upstream.text().catch(() => upstream.statusText)
    return new Response(text, { status: upstream.status })
  }

  const responseHeaders = new Headers()
  for (const name of FORWARD) {
    const value = upstream.headers.get(name)
    if (value) responseHeaders.set(name, value)
  }
  if (!responseHeaders.has('Accept-Ranges')) {
    responseHeaders.set('Accept-Ranges', 'bytes')
  }
  responseHeaders.set('Cache-Control', 'no-store')

  return new NextResponse(upstream.body, {
    status: upstream.status,
    headers: responseHeaders,
  })
}
