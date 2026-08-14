'use client'

import { useActionState } from 'react'
import { loginAction, type LoginState } from '@/app/actions/auth'

const initialState: LoginState = {
  errors: {},
  message: null,
}

export default function LoginPage() {
  const [state, formAction, isPending] = useActionState(loginAction, initialState)

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-zinc-50 font-sans dark:bg-black">
      <main className="flex w-full max-w-md flex-col items-center justify-center px-8 py-16 bg-white dark:bg-black">
        <h1 className="mb-8 text-3xl font-semibold text-black dark:text-zinc-50">
          Task Management
        </h1>
        <p className="mb-8 text-center text-zinc-600 dark:text-zinc-400">
          Sign in to your account
        </p>

        <form action={formAction} className="w-full space-y-6">
          {state?.message && (
            <div className="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
              {state.message}
            </div>
          )}

          <div>
            <label htmlFor="email" className="mb-2 block text-sm font-medium text-black dark:text-zinc-50">
              Email
            </label>
            <input
              id="email"
              name="email"
              type="email"
              required
              className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-black dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50"
              placeholder="you@example.com"
            />
            {state?.errors?.email && (
              <p className="mt-1 text-sm text-red-600 dark:text-red-400">{state.errors.email[0]}</p>
            )}
          </div>

          <div>
            <label htmlFor="password" className="mb-2 block text-sm font-medium text-black dark:text-zinc-50">
              Password
            </label>
            <input
              id="password"
              name="password"
              type="password"
              required
              className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-black dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-50"
              placeholder="••••••••"
            />
            {state?.errors?.password && (
              <p className="mt-1 text-sm text-red-600 dark:text-red-400">{state.errors.password[0]}</p>
            )}
          </div>

          <button
            type="submit"
            disabled={isPending}
            className="w-full rounded-lg bg-black px-4 py-2 font-medium text-white transition-colors hover:bg-zinc-800 disabled:opacity-50 dark:bg-zinc-50 dark:text-black dark:hover:bg-zinc-200"
          >
            {isPending ? 'Signing in...' : 'Sign In'}
          </button>
        </form>

        <div className="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
          <p>Default seeded users: password is <code className="font-mono">password</code></p>
        </div>
      </main>
    </div>
  )
}
