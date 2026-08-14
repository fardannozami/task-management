'use client'

import { useEffect, useRef, useState, type FormEvent } from 'react'
import type Pusher from 'pusher-js'
import { createCommentAction, deleteCommentAction } from '@/app/actions/comments'
import { type TaskComment, type TaskUser } from '@/app/lib/definitions'
import { useToast } from '@/app/ui/toast'

type CommentEvent = {
  id: number
  task_id: number
  user_id: number
  comment: string
  created_at: string
  user?: TaskUser | null
}

function timeAgo(value: string): string {
  const diff = Date.now() - new Date(value).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days}d ago`
  return new Date(value).toLocaleDateString()
}

export default function CommentSection({
  taskId,
  initialComments,
  currentUserId,
  currentUserRole,
}: {
  taskId: number
  initialComments: TaskComment[]
  currentUserId: number
  currentUserRole: string
}) {
  const [comments, setComments] = useState<TaskComment[]>(initialComments)
  const [text, setText] = useState('')
  const [posting, setPosting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const pusherRef = useRef<Pusher | null>(null)
  const { success, error: toastError } = useToast()

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

      const channel = pusher.subscribe(`private-task-comments.${taskId}`)

      channel.bind('comment.created', (data: CommentEvent) => {
        setComments((prev) =>
          prev.some((c) => c.id === data.id)
            ? prev
            : [...prev, { ...data, user: data.user ?? undefined }]
        )
      })

      channel.bind('comment.deleted', (data: { id: number }) => {
        setComments((prev) => prev.filter((c) => c.id !== data.id))
      })
    }

    connect()

    return () => {
      disposed = true
      pusherRef.current?.disconnect()
      pusherRef.current = null
    }
  }, [taskId])

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    const body = text.trim()
    if (!body || posting) return

    setPosting(true)
    setError(null)

    const result = await createCommentAction(taskId, body)

    setPosting(false)
    if (result.error || !result.comment) {
      setError(result.error ?? 'Failed to post comment.')
      return
    }

    setText('')
    setComments((prev) =>
      prev.some((c) => c.id === result.comment!.id)
        ? prev
        : [...prev, result.comment!]
    )
    success('Comment posted.')
  }

  async function handleDelete(comment: TaskComment) {
    setDeletingId(comment.id)
    setError(null)

    const result = await deleteCommentAction(comment.id)

    setDeletingId(null)
    if (result.error) {
      toastError(result.error)
      return
    }

    setComments((prev) => prev.filter((c) => c.id !== comment.id))
    success('Comment deleted.')
  }

  const canDelete = (comment: TaskComment) =>
    comment.user_id === currentUserId ||
    currentUserRole === 'admin' ||
    currentUserRole === 'manager'

  return (
    <div>
      <div className="space-y-3">
        {comments.length === 0 ? (
          <p className="text-sm text-zinc-400">No comments yet.</p>
        ) : (
          comments.map((comment) => (
            <div
              key={comment.id}
              className="flex items-start gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50"
            >
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold uppercase text-zinc-200 dark:bg-zinc-700 dark:text-zinc-300">
                {(comment.user?.name ?? '?').charAt(0)}
              </span>
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                  <span className="text-sm font-medium text-black dark:text-zinc-50">
                    {comment.user?.name ?? 'Unknown'}
                  </span>
                  <span className="text-xs text-zinc-400">{timeAgo(comment.created_at)}</span>
                </div>
                <p className="mt-0.5 whitespace-pre-wrap break-words text-sm text-zinc-600 dark:text-zinc-400">
                  {comment.comment}
                </p>
              </div>
              {canDelete(comment) && (
                <button
                  type="button"
                  disabled={deletingId === comment.id}
                  onClick={() => handleDelete(comment)}
                  aria-label="Delete comment"
                  className="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-40 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                >
                  {deletingId === comment.id ? (
                    <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                  ) : (
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                  )}
                </button>
              )}
            </div>
          ))
        )}
      </div>

      <form onSubmit={handleSubmit} className="mt-4">
        <textarea
          value={text}
          onChange={(e) => setText(e.target.value)}
          rows={3}
          maxLength={2000}
          placeholder="Add a comment…"
          className="w-full resize-y rounded-lg border border-zinc-300 bg-white p-3 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50"
        />
        {error && (
          <p className="mt-2 text-sm text-red-600 dark:text-red-400">{error}</p>
        )}
        <div className="mt-2 flex justify-end">
          <button
            type="submit"
            disabled={posting || text.trim().length === 0}
            className="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-100 dark:text-black dark:hover:bg-zinc-200"
          >
            {posting ? 'Posting…' : 'Post comment'}
          </button>
        </div>
      </form>
    </div>
  )
}
