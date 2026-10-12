export const API_DOCS_PATH = '/docs'

const API_VERSION_SUFFIX = /\/api(?:\/v\d+)?\/?$/
const DEFAULT_API_BASE_URL = 'http://localhost:8000/api/v1'

export function resolveApiDocsUrl(apiBaseUrl) {
  return `${String(apiBaseUrl || '').replace(API_VERSION_SUFFIX, '')}${API_DOCS_PATH}`
}

export const API_DOCS_URL = resolveApiDocsUrl(import.meta.env.VITE_API_URL || DEFAULT_API_BASE_URL)
