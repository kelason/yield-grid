export const FARMER = {
  id: 1,
  name: 'Mang Juan',
  email: 'farmer@example.test',
  role: 'farmer',
  email_verified_at: '2026-01-01T00:00:00Z',
  phone: null,
  avatar_url: null,
  locale: 'en',
}
export const BUYER = {
  ...FARMER,
  id: 2,
  name: 'Cara Santos',
  email: 'buyer@example.test',
  role: 'buyer',
}
export const ADMIN = {
  id: 3,
  name: 'Ramon Cruz',
  email: 'admin@example.test',
  role: 'admin',
  email_verified_at: '2026-01-01T00:00:00Z',
  phone: null,
  avatar_url: null,
  locale: 'en',
}
export const FARMS = [
  {
    id: 3,
    name: 'North Field',
    city: 'Quezon City',
    state: 'Metro Manila',
    country: 'Philippines',
    plots_count: 2,
    total_area: '1.50',
  },
]
export const PURCHASES = [
  {
    id: 4,
    payment_status: 'pending',
    cash_payment_status: null,
    payment_method: 'stripe',
    amount_paid: '0.00',
    total_contract_amount: '2500.00',
    is_downpayment: false,
    created_at: '2026-10-01T04:00:00Z',
    contract: {
      id: 5,
      crop_name: 'Rice',
      quantity_kg: 100,
      estimated_harvest_date: '2026-11-01',
      farmer: { id: 1, name: 'Mang Juan' },
    },
    demand_offer: null,
  },
]
