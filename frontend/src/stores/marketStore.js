import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '@/composables/useApi'
import { PaginationConstants } from '@/constants/pagination'

export const useMarketStore = defineStore('market', () => {
  const api = useApi()

  const contracts = ref([])
  const farmerContracts = ref([])
  const farmerStats = ref({
    total_listed: 0,
    total_sold: 0,
    total_reserved: 0,
    total_revenue: 0,
  })
  const buyerPurchases = ref([])
  const farmerPurchases = ref([])
  const activeContract = ref(null)

  const filters = ref({
    crop: '',
    minPrice: null,
    maxPrice: null,
    harvestBefore: '',
    harvestAfter: '',
    sort: 'newest',
  })

  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: PaginationConstants.MARKETPLACE_PER_PAGE,
  })

  const farmerPagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: PaginationConstants.DEFAULT_PER_PAGE,
  })

  const buyerPurchasesFilters = ref({
    search: '',
    sort: 'newest',
  })

  const buyerPurchasesPagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: PaginationConstants.PURCHASES_PER_PAGE,
  })

  const loading = ref({
    contracts: false,
    farmerContracts: false,
    purchases: false,
    details: false,
    publish: false,
    cancel: false,
  })

  const availableContracts = computed(() => contracts.value.filter((c) => c.status === 'available'))
  const totalSpent = computed(() =>
    buyerPurchases.value.reduce((total, p) => total + parseFloat(p.amount_paid), 0),
  )

  async function fetchMarketContracts(page = 1) {
    loading.value.contracts = true
    try {
      const queryParams = new URLSearchParams()
      queryParams.append('page', page)
      queryParams.append('per_page', pagination.value.perPage)

      if (filters.value.crop) queryParams.append('crop', filters.value.crop)
      if (filters.value.minPrice) queryParams.append('min_price', filters.value.minPrice)
      if (filters.value.maxPrice) queryParams.append('max_price', filters.value.maxPrice)
      if (filters.value.harvestBefore) queryParams.append('harvest_before', filters.value.harvestBefore)
      if (filters.value.harvestAfter) queryParams.append('harvest_after', filters.value.harvestAfter)
      if (filters.value.availability && filters.value.availability !== 'all') {
        queryParams.append('availability', filters.value.availability)
      }
      if (filters.value.sort) queryParams.append('sort', filters.value.sort)

      const { data, meta } = (await api.get(`/market/contracts?${queryParams.toString()}`)).data

      contracts.value = data

      if (meta) {
        pagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
        }
      }
    } catch (error) {
      console.error('Error fetching market contracts:', error)
    } finally {
      loading.value.contracts = false
    }
  }

  async function fetchContractDetail(id, type = 'contract') {
    loading.value.details = true
    try {
      const { data } = await api.get(`/market/items/${type}/${id}`)
      activeContract.value = data.data || data
      return data
    } catch (error) {
      console.error('Error fetching contract detail:', error)
      throw error
    } finally {
      loading.value.details = false
    }
  }

  async function fetchFarmerContracts(page = 1, status = null) {
    loading.value.farmerContracts = true
    try {
      let url = `/farmer/contracts?page=${page}&per_page=${farmerPagination.value.perPage}`
      if (status) url += `&status=${status}`

      const response = await api.get(url)
      farmerContracts.value = response.data.data || response.data

      const meta = response.data.meta
      if (meta) {
        farmerPagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
        }
      }
    } catch (error) {
      console.error('Error fetching farmer contracts:', error)
    } finally {
      loading.value.farmerContracts = false
    }
  }

  async function fetchFarmerContractsStats() {
    try {
      const response = await api.get('/farmer/contracts/stats')
      farmerStats.value = response.data.data || response.data
    } catch (error) {
      console.error('Error fetching farmer stats:', error)
    }
  }

  async function publishContract(recommendationId, formData) {
    loading.value.publish = true
    try {
      const response = await api.post(`/recommendations/${recommendationId}/publish`, formData)
      farmerContracts.value.unshift(response.data.data || response.data)
      return response.data
    } catch (error) {
      console.error('Error publishing contract:', error)
      throw error
    } finally {
      loading.value.publish = false
    }
  }

  async function createManualListing(formData) {
    loading.value.publish = true
    try {
      const response = await api.post('/farmer/listings', formData)
      return response.data
    } catch (error) {
      console.error('Error creating manual listing:', error)
      throw error
    } finally {
      loading.value.publish = false
    }
  }

  async function cancelContract(id, type = 'contract') {
    loading.value.cancel = true
    try {
      const endpoint = type === 'listing' 
        ? `/farmer/listings/${id}/cancel` 
        : `/farmer/contracts/${id}/cancel`
        
      const response = await api.patch(endpoint)
      const updatedData = response.data.data || response.data

      const index = farmerContracts.value.findIndex((c) => c.id === id && (c.type || 'contract') === type)
      if (index !== -1) {
        farmerContracts.value[index] = updatedData
      }
      return updatedData
    } catch (error) {
      console.error('Error cancelling item:', error)
      throw error
    } finally {
      loading.value.cancel = false
    }
  }

  async function fetchBuyerPurchases(page = 1) {
    loading.value.purchases = true
    try {
      const queryParams = new URLSearchParams()
      queryParams.append('page', page)
      queryParams.append('per_page', buyerPurchasesPagination.value.perPage)
      if (buyerPurchasesFilters.value.search)
        queryParams.append('search', buyerPurchasesFilters.value.search)
      if (buyerPurchasesFilters.value.sort)
        queryParams.append('sort', buyerPurchasesFilters.value.sort)

      const response = await api.get(`/buyer/purchases?${queryParams.toString()}`)
      buyerPurchases.value = response.data.data || response.data

      const meta = response.data.meta
      if (meta) {
        buyerPurchasesPagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
        }
      }
    } catch (error) {
      console.error('Error fetching purchases:', error)
    } finally {
      loading.value.purchases = false
    }
  }

  async function fetchFarmerPurchases(page = 1) {
    loading.value.purchases = true
    try {
      const response = await api.get(`/farmer/purchases?page=${page}`)
      farmerPurchases.value = response.data.data || response.data
      
      const meta = response.data.meta
      if (meta) {
        farmerPagination.value = {
          currentPage: meta.current_page,
          lastPage: meta.last_page,
          total: meta.total,
          perPage: meta.per_page,
        }
      }
    } catch (error) {
      console.error('Error fetching farmer purchases:', error)
    } finally {
      loading.value.purchases = false
    }
  }

  async function approveCashPayment(purchaseId, type, amount = null) {
    try {
      const response = await api.post(`/farmer/purchases/${purchaseId}/approve`, {
        type,
        amount
      })
      
      // Update local state
      const index = farmerPurchases.value.findIndex(p => p.id === purchaseId)
      if (index !== -1) {
        farmerPurchases.value[index] = response.data.data || response.data
      }
      return response.data
    } catch (error) {
      console.error('Error approving cash payment:', error)
      throw error
    }
  }

  function handleContractPurchased(eventData) {
    // Update local state if needed
    const contract = farmerContracts.value.find((c) => c.id === eventData.contract_id)
    if (contract) {
      contract.status = 'sold'
    }
  }

  return {
    contracts,
    farmerContracts,
    farmerStats,
    farmerPagination,
    buyerPurchases,
    farmerPurchases,
    buyerPurchasesFilters,
    buyerPurchasesPagination,
    activeContract,
    filters,
    pagination,
    loading,
    availableContracts,
    totalSpent,
    fetchMarketContracts,
    fetchContractDetail,
    fetchFarmerContracts,
    fetchFarmerContractsStats,
    publishContract,
    createManualListing,
    cancelContract,
    fetchBuyerPurchases,
    fetchFarmerPurchases,
    approveCashPayment,
    handleContractPurchased,
  }
})
