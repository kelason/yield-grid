import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { PaginationConstants } from '@/constants/pagination'

export const useDemandStore = defineStore('demand', () => {
  const api = useApi()

  const demands = ref([])
  const myDemands = ref([])
  const myOffers = ref([])
  const activeDemand = ref(null)
  const demandOffers = ref([])

  const filters = ref({
    crop: '',
    minBudget: null,
    maxBudget: null,
    neededBefore: '',
    neededAfter: '',
    sort: 'newest',
  })
  const viewerLocation = ref(null)

  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: PaginationConstants.MARKETPLACE_PER_PAGE,
  })

  const loading = ref({
    demands: false,
    details: false,
    offers: false,
    action: false,
  })

  function buildQuery(page) {
    const queryParams = new URLSearchParams()
    queryParams.append('page', page)
    queryParams.append('per_page', pagination.value.perPage)
    if (filters.value.crop) queryParams.append('crop', filters.value.crop)
    if (filters.value.minBudget) queryParams.append('min_budget', filters.value.minBudget)
    if (filters.value.maxBudget) queryParams.append('max_budget', filters.value.maxBudget)
    if (filters.value.neededBefore) queryParams.append('needed_before', filters.value.neededBefore)
    if (filters.value.neededAfter) queryParams.append('needed_after', filters.value.neededAfter)
    if (filters.value.sort) queryParams.append('sort', filters.value.sort)
    if (
      filters.value.sort === 'nearest' &&
      viewerLocation.value?.lat != null &&
      viewerLocation.value?.lng != null
    ) {
      queryParams.append('lat', viewerLocation.value.lat)
      queryParams.append('lng', viewerLocation.value.lng)
    }
    return queryParams.toString()
  }

  async function fetchDemands(page = 1) {
    loading.value.demands = true
    try {
      const { data, meta } = (await api.get(`/market/demands?${buildQuery(page)}`)).data
      demands.value = data
      if (meta) {
        pagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
        }
      }
    } finally {
      loading.value.demands = false
    }
  }

  async function fetchDemandDetail(id) {
    loading.value.details = true
    try {
      const { data } = await api.get(`/market/demands/${id}`)
      activeDemand.value = data.data || data
      return activeDemand.value
    } finally {
      loading.value.details = false
    }
  }

  async function fetchMyDemands(status = null) {
    loading.value.demands = true
    try {
      const query = status ? `?status=${encodeURIComponent(status)}` : ''
      const { data } = await api.get(`/buyer/demands${query}`)
      myDemands.value = data.data || data
    } finally {
      loading.value.demands = false
    }
  }

  async function postDemand(payload) {
    loading.value.action = true
    try {
      const { data } = await api.post('/buyer/demands', payload)
      const created = data.data || data
      myDemands.value.unshift(created)
      return created
    } finally {
      loading.value.action = false
    }
  }

  async function cancelDemand(id) {
    loading.value.action = true
    try {
      const { data } = await api.patch(`/buyer/demands/${id}/cancel`)
      const updated = data.data || data
      replaceDemand(updated)
      return updated
    } finally {
      loading.value.action = false
    }
  }

  async function fetchDemandOffers(demandId) {
    loading.value.offers = true
    try {
      const { data } = await api.get(`/buyer/demands/${demandId}/offers`)
      demandOffers.value = data.data || data
      return demandOffers.value
    } finally {
      loading.value.offers = false
    }
  }

  async function submitOffer(demandId, payload) {
    loading.value.action = true
    try {
      const { data } = await api.post(`/demands/${demandId}/offers`, payload)
      const created = data.data || data
      myOffers.value.unshift(created)
      return created
    } finally {
      loading.value.action = false
    }
  }

  async function fetchMyOffers(status = null) {
    loading.value.offers = true
    try {
      const query = status ? `?status=${encodeURIComponent(status)}` : ''
      const { data } = await api.get(`/farmer/offers${query}`)
      myOffers.value = data.data || []
    } finally {
      loading.value.offers = false
    }
  }

  async function decideOffer(offerId, decision) {
    loading.value.action = true
    try {
      const { data } = await api.post(`/buyer/offers/${offerId}/${decision}`)
      const updated = data.data || data
      replaceOffer(updated)
      return updated
    } finally {
      loading.value.action = false
    }
  }

  async function withdrawOffer(offerId) {
    return mutateOffer(`/farmer/offers/${offerId}/withdraw`)
  }

  async function cancelOffer(offerId, asFarmer) {
    const prefix = asFarmer ? '/farmer' : '/buyer'
    return mutateOffer(`${prefix}/offers/${offerId}/cancel`)
  }

  async function markDelivered(offerId) {
    return mutateOffer(`/farmer/offers/${offerId}/mark-delivered`)
  }

  async function settleBalance(offerId) {
    return mutateOffer(`/farmer/offers/${offerId}/settle-balance`)
  }

  async function confirmCompleted(offerId) {
    return mutateOffer(`/buyer/offers/${offerId}/confirm-completed`)
  }

  async function mutateOffer(url) {
    loading.value.action = true
    try {
      const { data } = await api.post(url)
      const updated = data.data || data
      replaceOffer(updated)
      return updated
    } finally {
      loading.value.action = false
    }
  }

  function replaceOffer(updated) {
    const lists = [myOffers, demandOffers]
    lists.forEach((list) => {
      const index = list.value.findIndex((o) => o.id === updated.id)
      if (index !== -1) list.value[index] = updated
    })
  }

  function replaceDemand(updated) {
    const lists = [myDemands, demands]
    lists.forEach((list) => {
      const index = list.value.findIndex((d) => d.id === updated.id)
      if (index !== -1) list.value[index] = updated
    })
    if (activeDemand.value?.id === updated.id) {
      activeDemand.value = { ...activeDemand.value, ...updated }
    }
  }

  return {
    demands,
    myDemands,
    myOffers,
    activeDemand,
    demandOffers,
    filters,
    viewerLocation,
    pagination,
    loading,
    fetchDemands,
    fetchDemandDetail,
    fetchMyDemands,
    postDemand,
    cancelDemand,
    fetchDemandOffers,
    submitOffer,
    fetchMyOffers,
    decideOffer,
    withdrawOffer,
    cancelOffer,
    markDelivered,
    settleBalance,
    confirmCompleted,
  }
})
