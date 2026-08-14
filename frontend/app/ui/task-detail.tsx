'use client'

import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import {
  deleteAttachmentAction,
  fetchTaskDetailAction,
  scanAttachmentAction,
  uploadAttachmentAction,
} from '@/app/actions/attachments'
import {
  type Task,
  type TaskAttachment,
  type TaskComment,
  type VirusScanResult,
} from '@/app/lib/definitions'
import AttachmentDropzone from '@/app/ui/attachment-dropzone'
import CommentSection from '@/app/ui/comment-section'

const ALLOWED_EXTENSIONS = [
  'jpg', 'jpeg', 'png', 'gif', 'webp',
  'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
  'mp4', 'mov', 'avi',
]

const MAX_FILE_SIZE = 50 * 1024 * 1024

type UploadItem = {
  key: string
  name: string
  size: number
  status: 'uploading' | 'error'
  error?: string
}

const statusStyles: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  cancelled: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
}

const priorityStyles: Record<string, string> = {
  low: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
  medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(value: string | null | undefined): string {
  if (!value) return '—'
  return new Date(value).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function extensionOf(name: string): string {
  const ext = name.split('.').pop()?.toLowerCase() ?? ''
  return ext || 'file'
}

function isVideoMime(mime: string | null | undefined): boolean {
  return typeof mime === 'string' && mime.startsWith('video/')
}

function VideoIcon() {
  return (
    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0118 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5C6 5.004 5.496 4.5 5.25 4.5m1.5 8.25H18m-12.75 0a1.125 1.125 0 01-1.125 1.125H3.375M18 12.75V7.125c0-.621.504-1.125 1.125-1.125h1.5" />
    </svg>
  )
}

function Badge({ className, children }: { className: string; children: ReactNode }) {
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}>
      {children}
    </span>
  )
}

function ScanBadge({ result }: { result: VirusScanResult | null | undefined }) {
  if (!result) {
    return <span className="text-xs text-zinc-400">Not scanned</span>
  }

  const styles: Record<string, string> = {
    clean: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    suspicious: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    infected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    pending: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
  }

  const label: Record<string, string> = {
    clean: 'Clean',
    suspicious: 'Suspicious',
    infected: result.action_taken === 'quarantined' ? 'Quarantined' : 'Infected',
    pending: 'Scanning',
  }

  return <Badge className={styles[result.status] ?? styles.pending}>{label[result.status] ?? result.status}</Badge>
}

export default function TaskDetail({
  task,
  currentUserId,
  currentUserRole,
  onClose,
  onChanged,
}: {
  task: Task
  currentUserId: number
  currentUserRole: string
  onClose: () => void
  onChanged: () => void
}) {
  const [attachments, setAttachments] = useState<TaskAttachment[] | null>(null)
  const [comments, setComments] = useState<TaskComment[]>([])
  const [loadError, setLoadError] = useState<string | null>(null)
  const [uploads, setUploads] = useState<UploadItem[]>([])
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [scanningId, setScanningId] = useState<number | null>(null)
  const [playingId, setPlayingId] = useState<number | null>(null)
  const mountedRef = useRef(true)

  useEffect(() => {
    mountedRef.current = true
    return () => {
      mountedRef.current = false
    }
  }, [])

  useEffect(() => {
    let cancelled = false

    async function load() {
      const result = await fetchTaskDetailAction(task.id)
      if (cancelled || !mountedRef.current) return
      if (result.error || !result.task) {
        setLoadError(result.error ?? 'Failed to load task details.')
      } else {
        setAttachments(result.task.attachments ?? [])
        setComments(result.task.comments ?? [])
      }
    }

    load()
    return () => {
      cancelled = true
    }
  }, [task.id])

  const updateAttachment = useCallback((updated: TaskAttachment) => {
    setAttachments((prev) => {
      if (!prev) return prev
      return prev.map((a) => (a.id === updated.id ? updated : a))
    })
  }, [])

  async function handleFiles(files: File[]) {
    const items: UploadItem[] = files.map((file, i) => ({
      key: `${file.name}-${file.size}-${i}-${Date.now()}`,
      name: file.name,
      size: file.size,
      status: 'uploading',
    }))
    setUploads((prev) => [...prev, ...items])

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const item = items[i]

      const extension = extensionOf(file.name)
      if (!ALLOWED_EXTENSIONS.includes(extension)) {
        setUploads((prev) =>
          prev.map((u) =>
            u.key === item.key
              ? { ...u, status: 'error', error: `Unsupported file type .${extension}` }
              : u
          )
        )
        continue
      }

      if (file.size > MAX_FILE_SIZE) {
        setUploads((prev) =>
          prev.map((u) =>
            u.key === item.key
              ? { ...u, status: 'error', error: 'File exceeds the 50MB limit.' }
              : u
          )
        )
        continue
      }

      const formData = new FormData()
      formData.append('file', file)

      const result = await uploadAttachmentAction(task.id, formData)

      if (!mountedRef.current) return

      if (result.error || !result.attachment) {
        setUploads((prev) =>
          prev.map((u) =>
            u.key === item.key ? { ...u, status: 'error', error: result.error ?? 'Upload failed.' } : u
          )
        )
      } else {
        setUploads((prev) => prev.filter((u) => u.key !== item.key))
        setAttachments((prev) => [...(prev ?? []), result.attachment!])
      }
    }

    onChanged()
  }

  async function handleDelete(attachment: TaskAttachment) {
    setDeletingId(attachment.id)
    setActionError(null)
    const result = await deleteAttachmentAction(attachment.id)
    if (!mountedRef.current) return
    setDeletingId(null)

    if (result.error) {
      setActionError(result.error)
      return
    }

    setAttachments((prev) => (prev ?? []).filter((a) => a.id !== attachment.id))
    onChanged()
  }

  async function handleScan(attachment: TaskAttachment) {
    setScanningId(attachment.id)
    setActionError(null)
    const result = await scanAttachmentAction(attachment.id)
    if (!mountedRef.current) return
    setScanningId(null)

    if (result.error || !result.result) {
      setActionError(result.error ?? 'Scan failed.')
      return
    }

    updateAttachment({ ...attachment, virus_scan_result: result.result })
  }

  const attachmentCount = useMemo(() => attachments?.length ?? 0, [attachments])
  const busy = deletingId !== null

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" onClick={onClose}>
      <div
        className="flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:max-w-2xl sm:rounded-2xl dark:bg-zinc-900"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-4 border-b border-zinc-200 p-6 pb-4 dark:border-zinc-800">
          <div className="min-w-0">
            <h2 className="text-lg font-semibold text-black dark:text-zinc-50">{task.title}</h2>
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <Badge className={statusStyles[task.status] ?? statusStyles.pending}>
                {task.status.replace('_', ' ')}
              </Badge>
              <Badge className={priorityStyles[task.priority] ?? priorityStyles.medium}>
                {task.priority}
              </Badge>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
          >
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-6">
          {task.description && (
            <p className="whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-400">{task.description}</p>
          )}

          <dl className="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div className="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
              <dt className="text-xs text-zinc-500 dark:text-zinc-400">Assignee</dt>
              <dd className="mt-0.5 font-medium text-black dark:text-zinc-50">
                {task.assigned_user?.name ?? 'Unassigned'}
              </dd>
            </div>
            <div className="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
              <dt className="text-xs text-zinc-500 dark:text-zinc-400">Due date</dt>
              <dd className="mt-0.5 font-medium text-black dark:text-zinc-50">{formatDate(task.due_date)}</dd>
            </div>
          </dl>

          <section className="mt-6">
            <h3 className="mb-3 text-sm font-semibold text-black dark:text-zinc-50">
              Attachments
              {attachmentCount > 0 && (
                <span className="ml-2 text-xs font-normal text-zinc-500 dark:text-zinc-400">
                  {attachmentCount} {attachmentCount === 1 ? 'file' : 'files'}
                </span>
              )}
            </h3>

            {loadError && (
              <div className="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
                {loadError}
              </div>
            )}

            {actionError && (
              <div className="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
                {actionError}
              </div>
            )}

            <AttachmentDropzone onFiles={handleFiles} disabled={busy} />

            {uploads.length > 0 && (
              <ul className="mt-3 space-y-2">
                {uploads.map((item) => (
                  <li
                    key={item.key}
                    className="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800"
                  >
                    {item.status === 'uploading' ? (
                      <svg
                        className="h-4 w-4 shrink-0 animate-spin text-zinc-400"
                        viewBox="0 0 24 24"
                        fill="none"
                      >
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path
                          className="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                      </svg>
                    ) : (
                      <svg className="h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                      </svg>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-medium text-black dark:text-zinc-50">{item.name}</p>
                      {item.status === 'error' && item.error && (
                        <p className="truncate text-xs text-red-600 dark:text-red-400">{item.error}</p>
                      )}
                    </div>
                    <span className="shrink-0 text-xs text-zinc-400">
                      {item.status === 'uploading' ? 'Uploading…' : formatBytes(item.size)}
                    </span>
                  </li>
                ))}
              </ul>
            )}

            {attachments === null && !loadError ? (
              <p className="mt-4 text-sm text-zinc-400">Loading attachments…</p>
            ) : (attachments ?? []).length === 0 ? (
              <p className="mt-4 text-sm text-zinc-400">No attachments yet.</p>
            ) : (
              <ul className="mt-3 divide-y divide-zinc-200 dark:divide-zinc-800">
                {attachments!.map((attachment) => {
                  const isVideo = isVideoMime(attachment.mime_type)
                  const isPlaying = playingId === attachment.id
                  return (
                    <li key={attachment.id}>
                      <div className="flex items-center gap-3 py-3">
                        {attachment.thumbnail_path ? (
                          <img
                            src={`/api/attachments/${attachment.id}/thumbnail`}
                            alt={attachment.file_name}
                            className="h-10 w-10 shrink-0 rounded object-cover"
                          />
                        ) : (
                          <span
                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded ${
                              isVideo
                                ? 'bg-zinc-900 text-zinc-200 dark:bg-zinc-700 dark:text-zinc-300'
                                : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'
                            }`}
                          >
                            {isVideo ? (
                              <VideoIcon />
                            ) : (
                              extensionOf(attachment.file_name).slice(0, 4)
                            )}
                          </span>
                        )}

                        <div className="min-w-0 flex-1">
                          <p className="truncate font-medium text-black dark:text-zinc-50">{attachment.file_name}</p>
                          <p className="text-xs text-zinc-500 dark:text-zinc-400">
                            {formatBytes(attachment.file_size)} · {formatDate(attachment.uploaded_at)}
                          </p>
                        </div>

                        <ScanBadge result={attachment.virus_scan_result} />

                        <div className="flex shrink-0 items-center gap-1">
                          {isVideo && (
                            <button
                              type="button"
                              onClick={() => setPlayingId(isPlaying ? null : attachment.id)}
                              aria-label={isPlaying ? `Stop playing ${attachment.file_name}` : `Play ${attachment.file_name}`}
                              className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                            >
                              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                {isPlaying ? (
                                  <path d="M6 4.75A1.75 1.75 0 007.75 3h2.5A1.75 1.75 0 0012 4.75v14.5A1.75 1.75 0 0010.25 21h-2.5A1.75 1.75 0 006 19.25V4.75zm8 0A1.75 1.75 0 0115.75 3h2.5A1.75 1.75 0 0120 4.75v14.5a1.75 1.75 0 01-1.75 1.75h-2.5A1.75 1.75 0 0114 19.25V4.75z" />
                                ) : (
                                  <path d="M8 5.14v14.72a1 1 0 001.5.86l11.6-7.36a1 1 0 000-1.72L9.5 4.28A1 1 0 008 5.14z" />
                                )}
                              </svg>
                            </button>
                          )}
                          <a
                            href={`/api/attachments/${attachment.id}/download`}
                            aria-label={`Download ${attachment.file_name}`}
                            className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                          >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                              <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                          </a>
                          <button
                            type="button"
                            disabled={scanningId === attachment.id}
                            onClick={() => handleScan(attachment)}
                            aria-label={`Scan ${attachment.file_name}`}
                            className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black disabled:opacity-40 dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
                          >
                            {scanningId === attachment.id ? (
                              <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                              </svg>
                            ) : (
                              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                              </svg>
                            )}
                          </button>
                          <button
                            type="button"
                            disabled={busy}
                            onClick={() => handleDelete(attachment)}
                            aria-label={`Delete ${attachment.file_name}`}
                            className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-40 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                          >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                              <path strokeLinecap="round" strokeLinejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                          </button>
                        </div>
                      </div>
                      {isPlaying && (
                        <div className="pb-3">
                          <video
                            controls
                            preload="metadata"
                            src={`/api/attachments/${attachment.id}/stream`}
                            className="aspect-video w-full rounded-lg bg-black"
                          />
                        </div>
                      )}
                    </li>
                  )
                })}
              </ul>
            )}
          </section>

          {attachments !== null && (
            <section className="mt-6">
              <h3 className="mb-3 text-sm font-semibold text-black dark:text-zinc-50">
                Comments
                {comments.length > 0 && (
                  <span className="ml-2 text-xs font-normal text-zinc-500 dark:text-zinc-400">
                    {comments.length} {comments.length === 1 ? 'comment' : 'comments'}
                  </span>
                )}
              </h3>
              <CommentSection
                key={task.id}
                taskId={task.id}
                initialComments={comments}
                currentUserId={currentUserId}
                currentUserRole={currentUserRole}
              />
            </section>
          )}
        </div>
      </div>
    </div>
  )
}
