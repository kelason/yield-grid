import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '@/composables/useApi'

export const useAddressStore = defineStore('address', () => {
  const api = useApi()

  const addresses = ref([])
  const loading = ref(false)
  const error = ref(null)

  const defaultAddress = computed(
    () => addresses.value.find((a) => a.is_default) || addresses.value[0] || null,
  )
  const hasAddress = computed(() => addresses.value.length > 0)

  async function fetchAddresses() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/user/addresses')
      addresses.value = data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load addresses.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createAddress(payload) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.post('/user/addresses', payload)
      const created = data.data || data
      addresses.value.push(created)
      if (created.is_default) {
        addresses.value.forEach((a) => {
          if (a.id !== created.id) a.is_default = false
        })
      }
      return created
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to save address.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateAddress(id, payload) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.put(`/user/addresses/${id}`, payload)
      const updated = data.data || data
      const index = addresses.value.findIndex((a) => a.id === id)
      if (index !== -1) addresses.value[index] = updated
      if (updated.is_default) {
        addresses.value.forEach((a) => {
          if (a.id !== updated.id) a.is_default = false
        })
      }
      return updated
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update address.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteAddress(id) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/user/addresses/${id}`)
      addresses.value = addresses.value.filter((a) => a.id !== id)
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete address.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    addresses,
    loading,
    error,
    defaultAddress,
    hasAddress,
    fetchAddresses,
    createAddress,
    updateAddress,
    deleteAddress,
  }
})
