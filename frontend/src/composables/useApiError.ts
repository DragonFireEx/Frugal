import axios from 'axios'

interface ApiErrorBody {
  error?: string
  message?: string
  violations?: Record<string, string>
}

export function useApiError() {
  function extractErrorMessage(error: unknown, fallback: string): string {
    if (!axios.isAxiosError(error)) {
      return fallback
    }

    const data = error.response?.data as ApiErrorBody | undefined
    return data?.error ?? data?.message ?? Object.values(data?.violations ?? {})[0] ?? fallback
  }

  function extractViolations(error: unknown): Record<string, string> | null {
    if (!axios.isAxiosError(error)) {
      return null
    }

    const data = error.response?.data as ApiErrorBody | undefined
    return data?.violations ?? null
  }

  return { extractErrorMessage, extractViolations }
}
