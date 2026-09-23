import { ref } from 'vue'
import { useApi } from './useApi'

export function usePayment() {
  const { request } = useApi()
  const loading = ref(false)
  const error = ref(null)
  const checkoutUrl = ref(null)

  async function startCheckout(contractId, type = 'contracts', quantityKg = null, paymentOption = 'paymongo') {
    loading.value = true
    error.value = null

    try {
      const response = await request(`/market/${type}/${contractId}/checkout`, {
        method: 'POST',
        data: {
          quantity_kg: quantityKg,
          payment_option: paymentOption,
        }
      })

      if (paymentOption === 'cash') {
        return response.data
      }

      if (response.data && response.data.checkout_url) {
        checkoutUrl.value = response.data.checkout_url
        // Redirect to PayMongo hosted checkout page
        window.location.href = response.data.checkout_url
      } else {
        throw new Error('No checkout URL returned from server')
      }
    } catch (err) {
      console.error('Checkout error:', err)
      error.value =
        err.response?.data?.message || err.message || 'Failed to initialize checkout session'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    checkoutUrl,
    startCheckout,
  }
}
