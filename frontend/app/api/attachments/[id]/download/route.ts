import { NextResponse } from 'next/server'
import { getSessionToken } from '@/app/lib/session'

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params
  const token = await getSessionToken()

  if (!token) {
    return new Response('Unauthorized', { status: 401 })
  }

  const upstream = await fetch(`${BACKEND_URL}/attachments/${id}/download`, {
    headers: {
      'Accept': '*/*',
      'Authorization': `Bearer ${token}`,
    },
    cache: 'no-store',
  })

  if (!upstream.ok) {
    const text = await upstream.text().catch(() => upstream.statusText)
    return new Response(text, { status: upstream.status })
  }

  const headers = new Headers()
  const contentType = upstream.headers.get('content-type')
  const contentDisposition = upstream.headers.get('content-disposition')
  const contentLength = upstream.headers.get('content-length')
  if (contentType) headers.set('Content-Type', contentType)
  if (contentDisposition) headers.set('Content-Disposition', contentDisposition)
  if (contentLength) headers.set('Content-Length', contentLength)
  headers.set('Cache-Control', 'private, no-store')

  return new NextResponse(upstream.body, { headers })
}
