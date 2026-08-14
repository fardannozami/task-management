'use client'

import { useEffect, useRef, useState, useTransition, type ReactNode } from 'react'
import { usePathname, useRouter, useSearchParams } from 'next/navigation'
import { deleteTaskAction } from '@/app/actions/tasks'
import {
  taskPriorities,
  taskStatuses,
  type Paginated,
  type Task,
  type TaskPriority,
  type TaskStatus,
} from '@/app/lib/definitions'
import TaskForm from '@/app/ui/task-form'
import TaskDetail from '@/app/ui/task-detail'

const statusStyles: Record<TaskStatus, string> = {
  pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  cancelled: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
}

const priorityStyles: Record<TaskPriority, string> = {
  low: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
  medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
}

const selectClass =
  'rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50'

function formatDate(value: string | null | undefined): string {
  if (!value) return '—'
  return new Date(value).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function Badge({ className, children }: { className: string; children: ReactNode }) {
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}>
      {children}
    </span>
  )
}

function ActionButtons({
  task,
  onView,
  onEdit,
  onDelete,
}: {
  task: Task
  onView: (task: Task) => void
  onEdit: (task: Task) => void
  onDelete: (task: Task) => void
}) {
  return (
    <div className="flex items-center justify-end gap-1">
      <button
        type="button"
        onClick={() => onView(task)}
        aria-label={`View ${task.title}`}
        className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
      >
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
          <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
      <button
        type="button"
        onClick={() => onEdit(task)}
        aria-label={`Edit ${task.title}`}
        className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-black dark:hover:bg-zinc-800 dark:hover:text-zinc-50"
      >
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </button>
      <button
        type="button"
        onClick={() => onDelete(task)}
        aria-label={`Delete ${task.title}`}
        className="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
      >
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
      </button>
    </div>
  )
}

export default function TaskDashboard({
  tasks,
  users,
  counts,
  initialStatus,
  initialPriority,
  initialSearch,
  currentUserId,
  currentUserRole,
}: {
  tasks: Paginated<Task>
  users: { id: number; name: string; email: string }[]
  counts: Record<TaskStatus, number>
  initialStatus: TaskStatus | ''
  initialPriority: TaskPriority | ''
  initialSearch: string
  currentUserId: number
  currentUserRole: string
}) {
  const router = useRouter()
  const pathname = usePathname()
  const searchParams = useSearchParams()

  const [search, setSearch] = useState(initialSearch)
  const [prevSearch, setPrevSearch] = useState(initialSearch)
  const [modal, setModal] = useState<{ mode: 'create' | 'edit'; task?: Task } | null>(null)
  const [detailTask, setDetailTask] = useState<Task | null>(null)
  const [taskToDelete, setTaskToDelete] = useState<Task | null>(null)
  const [deleteError, setDeleteError] = useState<string | null>(null)
  const [isDeleting, startTransition] = useTransition()
  const searchTimeout = useRef<ReturnType<typeof setTimeout>>(null)

  if (initialSearch !== prevSearch) {
    setPrevSearch(initialSearch)
    setSearch(initialSearch)
  }

  useEffect(() => {
    return () => {
      if (searchTimeout.current) clearTimeout(searchTimeout.current)
    }
  }, [])

  function updateUrl(next: Record<string, string | null>) {
    const params = new URLSearchParams(searchParams.toString())
    for (const [key, value] of Object.entries(next)) {
      if (value) params.set(key, value)
      else params.delete(key)
    }
    router.replace(`${pathname}?${params.toString()}`)
  }

  function handleSearchChange(value: string) {
    setSearch(value)
    if (searchTimeout.current) clearTimeout(searchTimeout.current)
    searchTimeout.current = setTimeout(() => {
      updateUrl({ search: value || null, page: null })
    }, 400)
  }

  function handleFilterChange(key: 'status' | 'priority', value: string) {
    updateUrl({ [key]: value || null, page: null })
  }

  function goToPage(page: number) {
    updateUrl({ page: page > 1 ? String(page) : null })
  }

  function handleDelete() {
    if (!taskToDelete) return
    setDeleteError(null)
    startTransition(async () => {
      const result = await deleteTaskAction(taskToDelete.id)
      if (result?.message) {
        setDeleteError(result.message)
      } else {
        setTaskToDelete(null)
      }
    })
  }

  const totalCount = taskStatuses.reduce((sum, s) => sum + counts[s], 0)

  const stats = [
    { label: 'Total Tasks', value: totalCount, accent: 'text-black dark:text-zinc-50' },
    { label: 'Pending', value: counts.pending, accent: 'text-amber-600 dark:text-amber-400' },
    { label: 'In Progress', value: counts.in_progress, accent: 'text-blue-600 dark:text-blue-400' },
    { label: 'Completed', value: counts.completed, accent: 'text-green-600 dark:text-green-400' },
  ]

  return (
    <>
      <section className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        {stats.map((s) => (
          <div
            key={s.label}
            className="rounded-xl border border-zinc-200 bg-white p-4 sm:p-5 dark:border-zinc-800 dark:bg-zinc-900"
          >
            <h3 className="text-xs font-medium text-zinc-500 sm:text-sm dark:text-zinc-400">{s.label}</h3>
            <p className={`mt-1.5 text-2xl font-bold sm:text-3xl ${s.accent}`}>{s.value}</p>
          </div>
        ))}
      </section>

      <section className="mt-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div className="flex flex-col gap-3 border-b border-zinc-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-zinc-800">
          <div className="relative flex-1">
            <svg
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="search"
              value={search}
              onChange={(e) => handleSearchChange(e.target.value)}
              placeholder="Search tasks..."
              aria-label="Search tasks"
              className="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-9 pr-3 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50"
            />
          </div>

          <div className="flex flex-wrap items-center gap-2 sm:gap-3">
            <select
              value={initialStatus}
              onChange={(e) => handleFilterChange('status', e.target.value)}
              aria-label="Filter by status"
              className={selectClass}
            >
              <option value="">All statuses</option>
              {taskStatuses.map((s) => (
                <option key={s} value={s}>
                  {s.replace('_', ' ')}
                </option>
              ))}
            </select>

            <select
              value={initialPriority}
              onChange={(e) => handleFilterChange('priority', e.target.value)}
              aria-label="Filter by priority"
              className={selectClass}
            >
              <option value="">All priorities</option>
              {taskPriorities.map((p) => (
                <option key={p} value={p}>
                  {p}
                </option>
              ))}
            </select>

            <button
              type="button"
              onClick={() => setModal({ mode: 'create' })}
              className="w-full rounded-lg bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-zinc-800 sm:w-auto dark:bg-zinc-50 dark:text-black dark:hover:bg-zinc-200"
            >
              New Task
            </button>
          </div>
        </div>

        {tasks.data.length === 0 ? (
          <div className="flex flex-col items-center justify-center px-4 py-16 text-center">
            <svg
              className="mb-3 h-10 w-10 text-zinc-300 dark:text-zinc-700"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={1.5}
            >
              <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p className="text-sm font-medium text-black dark:text-zinc-50">No tasks found</p>
            <p className="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              {search || initialStatus || initialPriority
                ? 'Try adjusting your search or filters.'
                : 'Create your first task to get started.'}
            </p>
          </div>
        ) : (
          <>
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr className="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <th className="px-4 py-3 font-medium">Title</th>
                    <th className="px-4 py-3 font-medium">Status</th>
                    <th className="px-4 py-3 font-medium">Priority</th>
                    <th className="px-4 py-3 font-medium">Assignee</th>
                    <th className="px-4 py-3 font-medium">Due Date</th>
                    <th className="px-4 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                  {tasks.data.map((task) => (
                    <tr key={task.id} className="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <p className="font-medium text-black dark:text-zinc-50">{task.title}</p>
                          {task.attachments_count ? (
                            <span
                              className="inline-flex items-center gap-1 text-xs text-zinc-400"
                              title={`${task.attachments_count} attachment${task.attachments_count === 1 ? '' : 's'}`}
                            >
                              <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                              </svg>
                              {task.attachments_count}
                            </span>
                          ) : null}
                        </div>
                        {task.description && (
                          <p className="mt-0.5 line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {task.description}
                          </p>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <Badge className={statusStyles[task.status]}>{task.status.replace('_', ' ')}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge className={priorityStyles[task.priority]}>{task.priority}</Badge>
                      </td>
                      <td className="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                        {task.assigned_user?.name ?? 'Unassigned'}
                      </td>
                      <td className="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                        {formatDate(task.due_date)}
                      </td>
                      <td className="px-4 py-3">
                        <ActionButtons
                          task={task}
                          onView={setDetailTask}
                          onEdit={() => setModal({ mode: 'edit', task })}
                          onDelete={setTaskToDelete}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="divide-y divide-zinc-200 md:hidden dark:divide-zinc-800">
              {tasks.data.map((task) => (
                <div key={task.id} className="p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="flex items-center gap-2">
                        <p className="font-medium text-black dark:text-zinc-50">{task.title}</p>
                        {task.attachments_count ? (
                          <span
                            className="inline-flex items-center gap-1 text-xs text-zinc-400"
                            title={`${task.attachments_count} attachment${task.attachments_count === 1 ? '' : 's'}`}
                          >
                            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                              <path strokeLinecap="round" strokeLinejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                            {task.attachments_count}
                          </span>
                        ) : null}
                      </div>
                      <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Badge className={statusStyles[task.status]}>{task.status.replace('_', ' ')}</Badge>
                        <Badge className={priorityStyles[task.priority]}>{task.priority}</Badge>
                      </div>
                    </div>
                    <ActionButtons
                      task={task}
                      onView={setDetailTask}
                      onEdit={() => setModal({ mode: 'edit', task })}
                      onDelete={setTaskToDelete}
                    />
                  </div>
                  <dl className="mt-3 space-y-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <div className="flex justify-between">
                      <dt>Assignee</dt>
                      <dd>{task.assigned_user?.name ?? 'Unassigned'}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt>Due date</dt>
                      <dd>{formatDate(task.due_date)}</dd>
                    </div>
                    {task.description && (
                      <div className="flex justify-between gap-3">
                        <dt className="shrink-0">Details</dt>
                        <dd className="text-right line-clamp-2">{task.description}</dd>
                      </div>
                    )}
                  </dl>
                </div>
              ))}
            </div>
          </>
        )}

        {tasks.last_page > 1 && (
          <div className="flex flex-col items-center justify-between gap-3 border-t border-zinc-200 px-4 py-3 sm:flex-row dark:border-zinc-800">
            <p className="text-sm text-zinc-500 dark:text-zinc-400">
              Showing {tasks.from ?? 0}–{tasks.to ?? 0} of {tasks.total} tasks
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                disabled={tasks.current_page <= 1}
                onClick={() => goToPage(tasks.current_page - 1)}
                className="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-black transition-colors hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-50 dark:hover:bg-zinc-800"
              >
                Previous
              </button>
              <span className="text-sm text-zinc-500 dark:text-zinc-400">
                Page {tasks.current_page} of {tasks.last_page}
              </span>
              <button
                type="button"
                disabled={tasks.current_page >= tasks.last_page}
                onClick={() => goToPage(tasks.current_page + 1)}
                className="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-black transition-colors hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-50 dark:hover:bg-zinc-800"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </section>

      {modal && (
        <TaskForm
          mode={modal.mode}
          task={modal.task}
          users={users}
          onClose={() => setModal(null)}
        />
      )}

      {detailTask && (
        <TaskDetail
          task={detailTask}
          currentUserId={currentUserId}
          currentUserRole={currentUserRole}
          onClose={() => setDetailTask(null)}
          onChanged={() => router.refresh()}
        />
      )}

      {taskToDelete && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          onClick={() => !isDeleting && setTaskToDelete(null)}
        >
          <div
            className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900"
            onClick={(e) => e.stopPropagation()}
          >
            <h2 className="text-lg font-semibold text-black dark:text-zinc-50">Delete task</h2>
            <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
              Are you sure you want to delete{' '}
              <span className="font-medium text-black dark:text-zinc-50">&quot;{taskToDelete.title}&quot;</span>?
              This action cannot be undone.
            </p>

            {deleteError && (
              <div className="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
                {deleteError}
              </div>
            )}

            <div className="mt-5 flex justify-end gap-3">
              <button
                type="button"
                disabled={isDeleting}
                onClick={() => setTaskToDelete(null)}
                className="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-black transition-colors hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-50 dark:hover:bg-zinc-800"
              >
                Cancel
              </button>
              <button
                type="button"
                disabled={isDeleting}
                onClick={handleDelete}
                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
              >
                {isDeleting ? 'Deleting...' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
