import { it, expect } from 'vitest'
import { catalogFilterError, CATALOG_LIMITS } from '../catalog'
it('accepts both price boundaries and blocks invalid or inverted filter submissions', () => {
  expect(
    catalogFilterError(
      { crop: 'Rice', minPrice: 0, maxPrice: CATALOG_LIMITS.PRICE_MAX },
      'minPrice',
      'maxPrice',
    ),
  ).toBe('')
  expect(catalogFilterError({ minPrice: -0.01 }, 'minPrice', 'maxPrice')).toContain('Enter a price')
  expect(
    catalogFilterError({ maxBudget: CATALOG_LIMITS.PRICE_MAX + 1 }, 'minBudget', 'maxBudget'),
  ).toContain('Enter a price')
  expect(catalogFilterError({ minBudget: 50, maxBudget: 40 }, 'minBudget', 'maxBudget')).toContain(
    'maximum',
  )
})
