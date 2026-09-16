import { ref } from 'vue'
import { useApi } from './useApi'

export function usePayment() {
  const { request } = useApi()
  const loading = ref(false)
  const error = ref(null)
  const checkoutUrl = ref(null)

  async function startCheckout(contractId) {
    loading.value = true
    error.value = null
    
    try {
      const response = await request(`/market/contracts/${contractId}/checkout`, {
        method: 'POST'
      })
      
      if (response.checkout_url) {
        checkoutUrl.value = response.checkout_url
        // Redirect to PayMongo hosted checkout page
        window.location.href = response.checkout_url
      } else {
        throw new Error('No checkout URL returned from server')
      }
    } catch (err) {
      console.error('Checkout error:', err)
      error.value = err.message || 'Failed to initialize checkout session'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    checkoutUrl,
    startCheckout
  }
}
