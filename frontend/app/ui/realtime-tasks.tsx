'use client'

import { useEffect, useRef, useState } from 'react'
import { useRouter } from 'next/navigation'
import type Pusher from 'pusher-js'

type ConnectionState = 'connecting' | 'connected' | 'disconnected'

export default function RealtimeTasks() {
  const router = useRouter()
  const pusherRef = useRef<Pusher | null>(null)
  const refreshTimeout = useRef<ReturnType<typeof setTimeout>>(null)
  const [state, setState] = useState<ConnectionState>('connecting')

  useEffect(() => {
    let disposed = false

    async function connect() {
      const { default: PusherClient } = await import('pusher-js')

      if (disposed) return

      const pusher = new PusherClient(process.env.NEXT_PUBLIC_REVERB_APP_KEY || '', {
        cluster: '',
        wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || '127.0.0.1',
        wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080),
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        channelAuthorization: {
          transport: 'ajax',
          endpoint: '/api/broadcasting/auth',
        },
      })

      pusherRef.current = pusher

      const refresh = () => {
        if (refreshTimeout.current) clearTimeout(refreshTimeout.current)
        refreshTimeout.current = setTimeout(() => {
          router.refresh()
        }, 300)
      }

      const channel = pusher.subscribe('private-tasks')
      channel.bind('task.created', refresh)
      channel.bind('task.updated', refresh)
      channel.bind('task.deleted', refresh)

      pusher.connection.bind('state_change', (states: { current: string }) => {
        if (disposed) return
        setState(states.current === 'connected' ? 'connected' : states.current === 'connecting' ? 'connecting' : 'disconnected')
      })
    }

    connect()

    return () => {
      disposed = true
      if (refreshTimeout.current) clearTimeout(refreshTimeout.current)
      pusherRef.current?.disconnect()
      pusherRef.current = null
    }
  }, [router])

  const label = state === 'connected' ? 'Live' : state === 'connecting' ? 'Connecting' : 'Offline'
  const dotClass =
    state === 'connected'
      ? 'bg-green-500'
      : state === 'connecting'
        ? 'bg-amber-500'
        : 'bg-zinc-400'

  return (
    <span
      className="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
      title="Real-time updates"
    >
      <span className={`h-2 w-2 rounded-full ${dotClass}`} />
      {label}
    </span>
  )
}
