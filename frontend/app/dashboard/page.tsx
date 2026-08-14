import { cookies } from 'next/headers'
import { decryptSession } from '@/app/lib/session'
import { redirect } from 'next/navigation'
import { fetchTasks, fetchUsers } from '@/app/actions/tasks'
import { taskPriorities, taskStatuses, type TaskStatus } from '@/app/lib/definitions'
import TaskDashboard from '@/app/ui/task-dashboard'
import LogoutButton from '@/app/ui/logout-button'
import RealtimeTasks from '@/app/ui/realtime-tasks'
import { logoutAction } from '@/app/actions/auth'

async function fetchTaskCount(status: TaskStatus, token: string): Promise<number> {
  try {
    const response = await fetch(
      `${process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:8000/api'}/tasks?status=${status}&per_page=1`,
      {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        cache: 'no-store',
      }
    )

    if (!response.ok) return 0
    const data = await response.json()
    return data.total ?? 0
  } catch {
    return 0
  }
}

export default async function DashboardPage({
  searchParams,
}: {
  searchParams: Promise<{ [key: string]: string | string[] | undefined }>
}) {
  const cookie = (await cookies()).get('session')?.value
  const session = await decryptSession(cookie)

  if (!session?.userId) {
    redirect('/login')
  }

  const params = await searchParams
  const status = taskStatuses.find((s) => s === params.status) ?? ''
  const priority = taskPriorities.find((p) => p === params.priority) ?? ''
  const search = typeof params.search === 'string' ? params.search : ''
  const page = Number(typeof params.page === 'string' && /^\d+$/.test(params.page) ? params.page : 1) || 1

  const [tasks, users, stats] = await Promise.all([
    fetchTasks({ status, priority, search, page, perPage: 10 }),
    fetchUsers(),
    Promise.all(
      (taskStatuses as readonly TaskStatus[]).map((s) => fetchTaskCount(s, session.accessToken))
    ),
  ])

  const counts = Object.fromEntries(
    taskStatuses.map((s, i) => [s, stats[i]])
  ) as Record<TaskStatus, number>

  return (
    <div className="min-h-screen bg-zinc-50 font-sans dark:bg-black">
      <main className="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold text-black sm:text-3xl dark:text-zinc-50">
              Task Dashboard
            </h1>
            <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
              Welcome back, {session.name}
            </p>
          </div>
          <div className="flex items-center gap-3">
            <RealtimeTasks />
            <form action={logoutAction}>
              <LogoutButton />
            </form>
          </div>
        </div>

        <TaskDashboard
          tasks={tasks}
          users={users}
          counts={counts}
          initialStatus={status}
          initialPriority={priority}
          initialSearch={search}
          currentUserId={session.userId}
          currentUserRole={session.role}
        />
      </main>
    </div>
  )
}
