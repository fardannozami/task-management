import { cookies } from 'next/headers'
import { decryptSession } from '@/app/lib/session'
import { redirect } from 'next/navigation'

export default async function Home() {
  const cookie = (await cookies()).get('session')?.value
  const session = await decryptSession(cookie)

  if (session?.userId) {
    redirect('/dashboard')
  }

  redirect('/login')
}
