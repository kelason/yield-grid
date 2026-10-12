import { it, expect } from 'vitest'
import { resolveApiDocsUrl, API_DOCS_URL, API_DOCS_PATH } from '../apiDocs'

it('derives the docs URL from versioned and unversioned API bases', () => {
  expect(resolveApiDocsUrl('https://api.yieldgridph.com/api/v1')).toBe(
    'https://api.yieldgridph.com/docs',
  )
  expect(resolveApiDocsUrl('http://localhost:8000/api/v1/')).toBe('http://localhost:8000/docs')
  expect(resolveApiDocsUrl('http://localhost:8000/api')).toBe('http://localhost:8000/docs')
})

it('exposes the docs path and an env-derived docs URL', () => {
  expect(API_DOCS_PATH).toBe('/docs')
  expect(API_DOCS_URL.endsWith('/docs')).toBe(true)
  expect(API_DOCS_URL).not.toContain('/api/')
})
