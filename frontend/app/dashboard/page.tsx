import { cookies } from 'next/headers'
import { decryptSession } from '@/app/lib/session'
import { redirect } from 'next/navigation'
import LogoutButton from '@/app/ui/logout-button'
import { logoutAction } from '@/app/actions/auth'

export default async function DashboardPage() {
  const cookie = (await cookies()).get('session')?.value
  const session = await decryptSession(cookie)

  if (!session?.userId) {
    redirect('/login')
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-zinc-50 font-sans dark:bg-black">
      <main className="flex w-full max-w-3xl flex-col items-center justify-between py-32 px-16 bg-white dark:bg-black">
        <div className="w-full">
          <div className="mb-8 flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-semibold text-black dark:text-zinc-50">
                Dashboard
              </h1>
              <p className="mt-2 text-zinc-600 dark:text-zinc-400">
                Welcome back, {session.name}
              </p>
            </div>
            <form action={logoutAction}>
              <LogoutButton />
            </form>
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div className="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
              <h3 className="text-lg font-medium text-black dark:text-zinc-50">My Tasks</h3>
              <p className="mt-2 text-3xl font-bold text-black dark:text-zinc-50">0</p>
              <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Assigned to you</p>
            </div>

            <div className="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
              <h3 className="text-lg font-medium text-black dark:text-zinc-50">Pending</h3>
              <p className="mt-2 text-3xl font-bold text-black dark:text-zinc-50">0</p>
              <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Awaiting action</p>
            </div>

            <div className="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
              <h3 className="text-lg font-medium text-black dark:text-zinc-50">Completed</h3>
              <p className="mt-2 text-3xl font-bold text-black dark:text-zinc-50">0</p>
              <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">This month</p>
            </div>
          </div>

          <div className="mt-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h3 className="text-lg font-medium text-black dark:text-zinc-50">Account Info</h3>
            <dl className="mt-4 space-y-3">
              <div className="flex justify-between">
                <dt className="text-zinc-600 dark:text-zinc-400">Email</dt>
                <dd className="text-black dark:text-zinc-50">{session.email}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-zinc-600 dark:text-zinc-400">Role</dt>
                <dd className="text-black dark:text-zinc-50 capitalize">{session.role}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-zinc-600 dark:text-zinc-400">User ID</dt>
                <dd className="text-black dark:text-zinc-50">{session.userId}</dd>
              </div>
            </dl>
          </div>
        </div>
      </main>
    </div>
  )
}
