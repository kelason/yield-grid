import { mockSession } from './session'

export async function mockInsuranceApi(page) {
  await mockSession(page)
  await page.route('**/api/v1/farmer/insurance/profile', (route) => {
    if (route.request().method() === 'PUT') {
      return route.fulfill({
        json: { data: { rsbsa_number: 'RSBSA-1', rsbsa_status: 'registered' } },
      })
    }
    return route.fulfill({ json: { data: { rsbsa_number: null, rsbsa_status: 'not_registered' } } })
  })
  await page.route('**/api/v1/farmer/insurance/enrollments', (route) =>
    route.fulfill({ json: { data: [] } }),
  )
  await page.route('**/api/v1/farmer/insurance/reminders', (route) =>
    route.fulfill({ json: { data: [] } }),
  )
  await page.route('**/api/v1/farmer/insurance/offices', (route) =>
    route.fulfill({
      json: {
        data: [
          {
            name: 'PCIC Head Office',
            region_code: null,
            city: 'Quezon City',
            address: 'NIA Complex',
            phone: '(02) 8441-1323',
            source_note: '',
            is_head_office: true,
            is_serving_region: false,
          },
        ],
      },
    }),
  )
  await page.route('**/api/v1/plots', (route) =>
    route.fulfill({ json: { data: [{ id: 3, name: 'North Plot', calculated_area: 1.5 }] } }),
  )
}
