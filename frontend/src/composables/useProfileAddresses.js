import { ref, computed, toValue, watch } from 'vue'
import { usePendingConfirmation } from './useConfirmModal'
const ADDRESS_LABEL_MAX_LENGTH = 50
const ADDRESS_STREET_MAX_LENGTH = 255
function blankAddress() {
  return {
    label: '',
    street: '',
    region_code: '',
    province_code: null,
    city_municipality_code: '',
    barangay_code: '',
    latitude: null,
    longitude: null,
    is_default: false,
  }
}
function addressPayload(draft) {
  const payload = { ...draft, province_code: draft.province_code || null }
  if (!payload.label) delete payload.label
  if (!payload.street) delete payload.street
  return payload
}
function validationMessage(draft) {
  if ((draft.label || '').length > ADDRESS_LABEL_MAX_LENGTH)
    return `Label must be at most ${ADDRESS_LABEL_MAX_LENGTH} characters.`
  if ((draft.street || '').length > ADDRESS_STREET_MAX_LENGTH)
    return `Street must be at most ${ADDRESS_STREET_MAX_LENGTH} characters.`
  if (!draft.region_code || !draft.city_municipality_code || !draft.barangay_code)
    return 'Please select your region, city/municipality, and barangay.'
  return ''
}
const DELETE_CONFIG = {
  title: 'Delete address?',
  message: 'This address will be removed from your profile. This cannot be undone.',
  confirmText: 'Delete',
  type: 'danger',
}
export function useProfileAddresses({ addressStore, isOwnProfile, notificationStore, onSaved }) {
  const showAddressModal = ref(false)
  const editingAddress = ref(null)
  const addressDraft = ref(blankAddress())
  const addressErrors = ref({})
  const pinValid = ref(true)
  const pendingConfirm = ref(null)
  const {
    isExecuting: savingAddress,
    execute,
    cancel: cancelConfirm,
  } = usePendingConfirmation(pendingConfirm)
  const confirmConfig = computed(() => addressConfirmation(pendingConfirm.value))
  function ownsAddress(id) {
    return toValue(isOwnProfile) && addressStore.addresses.some((address) => address.id === id)
  }
  function openAddAddress() {
    if (!toValue(isOwnProfile) || savingAddress.value) return
    openEditor(null, blankAddress())
  }
  function openEditAddress(address) {
    if (!address || !ownsAddress(address.id) || savingAddress.value) return
    const draft = Object.fromEntries(
      Object.keys(blankAddress()).map((key) => [key, address[key] ?? blankAddress()[key]]),
    )
    openEditor(address, draft)
  }
  function openEditor(address, draft) {
    editingAddress.value = address
    addressDraft.value = draft
    addressErrors.value = {}
    pinValid.value = true
    showAddressModal.value = true
  }
  function closeEditor() {
    if (!savingAddress.value) showAddressModal.value = false
  }
  function requestDeleteAddress(id) {
    if (ownsAddress(id) && !savingAddress.value) pendingConfirm.value = { action: 'delete', id }
  }
  function requestDefaultAddress(address) {
    if (address && ownsAddress(address.id) && !savingAddress.value)
      pendingConfirm.value = {
        action: 'default',
        id: address.id,
        label: address.label,
        payload: {
          ...Object.fromEntries(Object.keys(blankAddress()).map((key) => [key, address[key]])),
          is_default: true,
        },
      }
  }
  function requestSaveAddress() {
    if (!toValue(isOwnProfile) || savingAddress.value) return
    const error = !pinValid.value
      ? 'Please place your pin within the selected area.'
      : validationMessage(addressDraft.value)
    addressErrors.value = {}
    if (error) {
      addressErrors.value.form = error
      notificationStore.error(error)
      return
    }
    pendingConfirm.value = {
      action: 'save',
      id: editingAddress.value?.id,
      payload: addressPayload(addressDraft.value),
    }
  }
  async function runAddressAction(pending) {
    if (!toValue(isOwnProfile) || (pending.id && !ownsAddress(pending.id))) return
    addressErrors.value = {}
    try {
      if (pending.action === 'delete') await addressStore.deleteAddress(pending.id)
      else if (pending.id) await addressStore.updateAddress(pending.id, pending.payload)
      else await addressStore.createAddress(pending.payload)
      notificationStore.success(pending.action === 'delete' ? 'Address deleted.' : 'Address saved.')
      if (pending.action === 'save') showAddressModal.value = false
      await onSaved?.()
    } catch (error) {
      const errors = error.response?.data?.errors || {}
      addressErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [
          key,
          Array.isArray(value) ? value[0] : value,
        ]),
      )
      if (!Object.keys(errors).length)
        addressErrors.value.form = error.response?.data?.message || 'Failed to save address.'
      notificationStore.error(addressErrors.value.form || 'Please review the address details.')
    }
  }
  const confirmPending = () => execute(runAddressAction)
  watch(
    () => toValue(isOwnProfile),
    (own) => {
      if (!own) {
        showAddressModal.value = false
        cancelConfirm()
      }
    },
  )
  return {
    showAddressModal,
    editingAddress,
    addressDraft,
    addressErrors,
    pinValid,
    savingAddress,
    pendingConfirm,
    confirmConfig,
    openAddAddress,
    openEditAddress,
    closeEditor,
    requestDeleteAddress,
    requestDefaultAddress,
    requestSaveAddress,
    confirmPending,
    cancelConfirm,
  }
}
function addressConfirmation(pending) {
  if (!pending) return null
  if (pending.action === 'delete') return DELETE_CONFIG
  if (pending.action === 'default')
    return {
      title: 'Set default address?',
      message: `Use "${pending.label || 'this address'}" as your default address?`,
      confirmText: 'Set default',
      type: 'primary',
    }
  return {
    title: pending.id ? 'Save address changes?' : 'Add this address?',
    message: pending.id
      ? 'Your address will be updated with these details.'
      : 'This address will be added to your profile.',
    confirmText: pending.id ? 'Save changes' : 'Add address',
    type: 'primary',
  }
}
