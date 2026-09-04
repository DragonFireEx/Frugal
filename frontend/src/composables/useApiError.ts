import axios from 'axios'

interface ApiErrorBody {
  error?: string
  message?: string
  errors?: Record<string, string>
}

export function useApiError() {
  function extractErrorMessage(error: unknown, fallback: string): string {
    if (!axios.isAxiosError(error)) {
      return fallback
    }

    const data = error.response?.data as ApiErrorBody | undefined
    return data?.error ?? data?.message ?? Object.values(data?.errors ?? {})[0] ?? fallback
  }

  return { extractErrorMessage }
}
