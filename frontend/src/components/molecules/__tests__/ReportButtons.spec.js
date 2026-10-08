import { setActivePinia, createPinia } from 'pinia'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import ThreadCard from '../ThreadCard.vue'
import ReplyCard from '../ReplyCard.vue'
import ContractCard from '../ContractCard.vue'
import DemandCard from '../DemandCard.vue'
import ContractGrid from '@/components/organisms/ContractGrid.vue'

describe('content report controls', () => {
  let pinia

  const thread = {
    id: 1,
    title: 'Harvest tips',
    body: 'Rotate your crops.',
    vote_score: 5,
    user_vote: 0,
    has_accepted_reply: false,
    last_activity_at: '2026-01-01T00:00:00Z',
    reply_count: 2,
    tags: [],
    category: null,
    author: { id: 9, name: 'Maria Farmer', avatar_url: null },
  }

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    localStorage.clear()
    sessionStorage.clear()
  })

  function signInAs(user) {
    const authStore = useAuthStore()
    authStore.user = user
  }

  function reportButton(wrapper) {
    const buttons = wrapper.findAll('button')
    return buttons.find((button) => button.text().includes('Report'))
  }

  it('emits a thread report without voting', async () => {
    signInAs({ id: 3, role: 'buyer' })
    const wrapper = mount(ThreadCard, {
      props: { thread },
      global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } },
    })

    await reportButton(wrapper).trigger('click')

    expect(wrapper.emitted('report')).toEqual([
      [{ reportable_type: 'thread', reportable_id: 1, title: 'Harvest tips' }],
    ])
    expect(wrapper.emitted('vote')).toBeUndefined()
  })

  it('hides the report control from the thread owner', () => {
    signInAs({ id: 9, role: 'farmer' })
    const wrapper = mount(ThreadCard, {
      props: { thread },
      global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } },
    })

    expect(reportButton(wrapper)).toBeUndefined()
  })

  it('shows the report control to guests for sign-in guidance', () => {
    const wrapper = mount(ThreadCard, {
      props: { thread },
      global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } },
    })

    expect(reportButton(wrapper).exists()).toBe(true)
  })

  it('reports anonymous replies and propagates nested reply events', async () => {
    signInAs({ id: 3, role: 'buyer' })
    const reply = {
      id: 11,
      body: 'Suspicious advice here',
      vote_score: 0,
      user_vote: 0,
      created_at: '2026-01-02T00:00:00Z',
      author: null,
      children: [
        {
          id: 12,
          body: 'Nested suspicious reply',
          vote_score: 0,
          user_vote: 0,
          created_at: '2026-01-03T00:00:00Z',
          author: { id: 9, name: 'Maria' },
          children: [],
        },
      ],
    }
    const wrapper = mount(ReplyCard, {
      props: { reply },
      global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } },
    })

    const buttons = wrapper.findAll('button').filter((button) => button.text().includes('Report'))
    expect(buttons).toHaveLength(2)

    await buttons[0].trigger('click')
    await buttons[1].trigger('click')

    expect(wrapper.emitted('report')).toEqual([
      [{ reportable_type: 'reply', reportable_id: 11, title: 'Suspicious advice here' }],
      [{ reportable_type: 'reply', reportable_id: 12, title: 'Nested suspicious reply' }],
    ])
    expect(wrapper.emitted('vote')).toBeUndefined()
  })

  it('hides the report control from the reply owner', () => {
    signInAs({ id: 9, role: 'farmer' })
    const wrapper = mount(ReplyCard, {
      props: {
        reply: {
          id: 11,
          body: 'My own reply',
          vote_score: 0,
          user_vote: 0,
          created_at: '2026-01-02T00:00:00Z',
          author: { id: 9, name: 'Maria' },
          children: [],
        },
      },
      global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } },
    })

    expect(reportButton(wrapper)).toBeUndefined()
  })

  it('distinguishes contracts from harvest listings sharing an id', async () => {
    signInAs({ id: 3, role: 'buyer' })
    const base = {
      id: 7,
      title: 'Rice lot',
      crop_name: 'Rice',
      quantity_kg: 100,
      total_price: 5000,
      currency: 'PHP',
      price_per_kg: 50,
      estimated_harvest_date: '2026-05-01',
      status: 'available',
      is_harvest_available: true,
      farmer: { id: 9, name: 'Jose' },
    }
    const contractWrapper = mount(ContractCard, {
      props: { contract: { ...base, type: 'contract' } },
      global: { plugins: [pinia] },
    })
    const listingWrapper = mount(ContractCard, {
      props: { contract: { ...base, type: 'listing' } },
      global: { plugins: [pinia] },
    })

    await reportButton(contractWrapper).trigger('click')
    await reportButton(listingWrapper).trigger('click')

    expect(contractWrapper.emitted('report')).toEqual([
      [{ reportable_type: 'contract', reportable_id: 7, title: 'Rice lot' }],
    ])
    expect(listingWrapper.emitted('report')).toEqual([
      [{ reportable_type: 'listing', reportable_id: 7, title: 'Rice lot' }],
    ])
    expect(contractWrapper.emitted('view-details')).toBeUndefined()
    expect(contractWrapper.emitted('purchase')).toBeUndefined()
  })

  it('hides the report control from the contract owner', () => {
    signInAs({ id: 9, role: 'farmer' })
    const wrapper = mount(ContractCard, {
      props: {
        contract: {
          id: 7,
          title: 'Rice lot',
          crop_name: 'Rice',
          quantity_kg: 100,
          total_price: 5000,
          currency: 'PHP',
          price_per_kg: 50,
          estimated_harvest_date: '2026-05-01',
          status: 'available',
          farmer: { id: 9, name: 'Jose' },
        },
      },
      global: { plugins: [pinia] },
    })

    expect(reportButton(wrapper)).toBeUndefined()
  })

  it('forwards grid report events without opening the detail', async () => {
    signInAs({ id: 3, role: 'buyer' })
    const wrapper = mount(ContractGrid, {
      props: {
        contracts: [
          {
            id: 7,
            type: 'listing',
            title: 'Rice lot',
            crop_name: 'Rice',
            quantity_kg: 100,
            total_price: 5000,
            currency: 'PHP',
            price_per_kg: 50,
            estimated_harvest_date: '2026-05-01',
            status: 'available',
            farmer: { id: 9, name: 'Jose' },
          },
        ],
      },
      global: { plugins: [pinia] },
    })

    await reportButton(wrapper).trigger('click')

    expect(wrapper.emitted('report')).toEqual([
      [{ reportable_type: 'listing', reportable_id: 7, title: 'Rice lot' }],
    ])
    expect(wrapper.emitted('view-contract')).toBeUndefined()
  })

  it('emits a demand report without opening the offer modal', async () => {
    signInAs({ id: 4, role: 'farmer' })
    const wrapper = mount(DemandCard, {
      props: {
        demand: {
          id: 21,
          title: 'Need rice',
          crop_name: 'Rice',
          quantity_kg: 500,
          remaining_quantity_kg: 200,
          target_price_per_kg: 45,
          status: 'open',
          needed_by_date: '2026-06-01',
          buyer: { id: 8, name: 'Ana' },
        },
      },
      global: { plugins: [pinia] },
    })

    await reportButton(wrapper).trigger('click')

    expect(wrapper.emitted('report')).toEqual([
      [{ reportable_type: 'demand', reportable_id: 21, title: 'Need rice' }],
    ])
    expect(wrapper.emitted('view')).toBeUndefined()
  })

  it('hides the report control from the demand owner', () => {
    signInAs({ id: 8, role: 'buyer' })
    const wrapper = mount(DemandCard, {
      props: {
        demand: {
          id: 21,
          title: 'Need rice',
          crop_name: 'Rice',
          quantity_kg: 500,
          remaining_quantity_kg: 200,
          target_price_per_kg: 45,
          status: 'open',
          buyer: { id: 8, name: 'Ana' },
        },
      },
      global: { plugins: [pinia] },
    })

    expect(reportButton(wrapper)).toBeUndefined()
  })
})
