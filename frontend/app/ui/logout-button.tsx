'use client'

import { logoutAction } from '@/app/actions/auth'

export default function LogoutButton() {
  return (
    <button
      formAction={logoutAction}
      className="rounded-lg border border-zinc-300 bg-white px-4 py-2 font-medium text-black transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-700"
    >
      Log Out
    </button>
  )
}
