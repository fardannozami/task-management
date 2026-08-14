'use client'

import { useEffect } from 'react'
import { useActionState } from 'react'
import { createTaskAction, updateTaskAction, type TaskFormState } from '@/app/actions/tasks'
import {
  taskPriorities,
  taskStatuses,
  type Task,
} from '@/app/lib/definitions'

const initialState: TaskFormState = {
  errors: {},
  message: null,
  success: false,
}

function toDatetimeLocal(value: string | null | undefined): string {
  if (!value) return ''
  return value.replace(' ', 'T').slice(0, 16)
}

const inputClass =
  'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-black dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50'
const labelClass = 'mb-1.5 block text-sm font-medium text-black dark:text-zinc-50'
const errorClass = 'mt-1 text-sm text-red-600 dark:text-red-400'

export default function TaskForm({
  mode,
  task,
  users,
  onClose,
}: {
  mode: 'create' | 'edit'
  task?: Task
  users: { id: number; name: string; email: string }[]
  onClose: () => void
}) {
  const action =
    mode === 'edit' && task
      ? updateTaskAction.bind(null, task.id)
      : createTaskAction

  const [state, formAction, isPending] = useActionState<TaskFormState, FormData>(action, initialState)

  useEffect(() => {
    if (state?.success) {
      onClose()
    }
  }, [state, onClose])

  return (
    <div
      className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4"
      onClick={onClose}
    >
      <div
        className="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-6 shadow-xl sm:max-w-lg sm:rounded-2xl dark:bg-zinc-900"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-5 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-black dark:text-zinc-50">
            {mode === 'create' ? 'New Task' : 'Edit Task'}
          </h2>
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

        {state?.message && !state.success && (
          <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
            {state.message}
          </div>
        )}

        <form action={formAction} className="space-y-4">
          <div>
            <label htmlFor="title" className={labelClass}>
              Title
            </label>
            <input
              id="title"
              name="title"
              type="text"
              required
              defaultValue={task?.title ?? ''}
              placeholder="What needs to be done?"
              className={inputClass}
            />
            {state?.errors?.title && (
              <p className={errorClass}>{state.errors.title[0]}</p>
            )}
          </div>

          <div>
            <label htmlFor="description" className={labelClass}>
              Description
            </label>
            <textarea
              id="description"
              name="description"
              rows={3}
              defaultValue={task?.description ?? ''}
              placeholder="Optional details..."
              className={`${inputClass} resize-y`}
            />
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="status" className={labelClass}>
                Status
              </label>
              <select
                id="status"
                name="status"
                defaultValue={task?.status ?? 'pending'}
                className={inputClass}
              >
                {taskStatuses.map((s) => (
                  <option key={s} value={s}>
                    {s.replace('_', ' ')}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="priority" className={labelClass}>
                Priority
              </label>
              <select
                id="priority"
                name="priority"
                defaultValue={task?.priority ?? 'medium'}
                className={inputClass}
              >
                {taskPriorities.map((p) => (
                  <option key={p} value={p}>
                    {p}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="assigned_user_id" className={labelClass}>
                Assignee
              </label>
              <select
                id="assigned_user_id"
                name="assigned_user_id"
                defaultValue={task?.assigned_user_id ? String(task.assigned_user_id) : ''}
                className={inputClass}
              >
                <option value="">Unassigned</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
              {state?.errors?.assigned_user_id && (
                <p className={errorClass}>{state.errors.assigned_user_id[0]}</p>
              )}
            </div>

            <div>
              <label htmlFor="due_date" className={labelClass}>
                Due Date
              </label>
              <input
                id="due_date"
                name="due_date"
                type="datetime-local"
                defaultValue={toDatetimeLocal(task?.due_date)}
                className={inputClass}
              />
              {state?.errors?.due_date && (
                <p className={errorClass}>{state.errors.due_date[0]}</p>
              )}
            </div>
          </div>

          <div className="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-black transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-700"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isPending}
              className="rounded-lg bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-black dark:hover:bg-zinc-200"
            >
              {isPending
                ? mode === 'create'
                  ? 'Creating...'
                  : 'Saving...'
                : mode === 'create'
                  ? 'Create Task'
                  : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
