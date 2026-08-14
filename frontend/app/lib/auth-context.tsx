'use client'

import { createContext, useContext, useState, ReactNode } from 'react'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface AuthContextType {
  user: User | null
  accessToken: string | null
  isLoading: boolean
  login: (user: User, accessToken: string) => void
  logout: () => void
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

function readStoredUser(): User | null {
  if (typeof window === 'undefined') return null
  const storedUser = localStorage.getItem('user')
  if (!storedUser) return null
  try {
    return JSON.parse(storedUser)
  } catch {
    return null
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(readStoredUser)
  const [accessToken, setAccessToken] = useState<string | null>(
    () => (typeof window === 'undefined' ? null : localStorage.getItem('accessToken'))
  )
  const [isLoading, setIsLoading] = useState(false)

  const login = (userData: User, token: string) => {
    setUser(userData)
    setAccessToken(token)
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('accessToken', token)
  }

  const logout = () => {
    setUser(null)
    setAccessToken(null)
    localStorage.removeItem('user')
    localStorage.removeItem('accessToken')
  }

  return (
    <AuthContext.Provider value={{ user, accessToken, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
