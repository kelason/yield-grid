import { ref, computed, toValue, watch } from 'vue'
import i18n from '@/i18n'
import { usePendingConfirmation } from './useConfirmModal'

const t = (...args) => i18n.global.t(...args)
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
    return t('community.address.err_label', { max: ADDRESS_LABEL_MAX_LENGTH })
  if ((draft.street || '').length > ADDRESS_STREET_MAX_LENGTH)
    return t('community.address.err_street', { max: ADDRESS_STREET_MAX_LENGTH })
  if (!draft.region_code || !draft.city_municipality_code || !draft.barangay_code)
    return t('community.address.err_select')
  return ''
}
function deleteConfig() {
  return {
    title: t('community.address.del_title'),
    message: t('community.address.del_msg'),
    confirmText: t('community.address.del_ok'),
    type: 'danger',
  }
}
function addressState() {
  return {
    showAddressModal: ref(false),
    editingAddress: ref(null),
    addressDraft: ref(blankAddress()),
    addressErrors: ref({}),
    pinValid: ref(true),
    pendingConfirm: ref(null),
  }
}
export function useProfileAddresses(options) {
  const state = addressState()
  const guard = usePendingConfirmation(state.pendingConfirm)
  const s = { ...options, ...state, savingAddress: guard.isExecuting }
  const confirmConfig = computed(() => addressConfirmation(state.pendingConfirm.value))
  watch(
    () => toValue(options.isOwnProfile),
    (own) => {
      if (!own) {
        state.showAddressModal.value = false
        guard.cancel()
      }
    },
  )
  return { ...state, savingAddress: guard.isExecuting, confirmConfig, ...addressActions(s, guard) }
}
function addressActions(s, guard) {
  return {
    openAddAddress: () => openAddAddress(s),
    openEditAddress: (address) => openEditAddress(s, address),
    closeEditor: () => {
      if (!s.savingAddress.value) s.showAddressModal.value = false
    },
    requestDeleteAddress: (id) => requestDeleteAddress(s, id),
    requestDefaultAddress: (address) => requestDefaultAddress(s, address),
    requestSaveAddress: () => requestSaveAddress(s),
    confirmPending: () => guard.execute((pending) => runAddressAction(s, pending)),
    cancelConfirm: guard.cancel,
  }
}
function ownsAddress(s, id) {
  return toValue(s.isOwnProfile) && s.addressStore.addresses.some((address) => address.id === id)
}
function openAddAddress(s) {
  if (!toValue(s.isOwnProfile) || s.savingAddress.value) return
  openEditor(s, null, blankAddress())
}
function openEditAddress(s, address) {
  if (!address || !ownsAddress(s, address.id) || s.savingAddress.value) return
  const blank = blankAddress()
  const draft = Object.fromEntries(
    Object.keys(blank).map((key) => [key, address[key] ?? blank[key]]),
  )
  openEditor(s, address, draft)
}
function openEditor(s, address, draft) {
  s.editingAddress.value = address
  s.addressDraft.value = draft
  s.addressErrors.value = {}
  s.pinValid.value = true
  s.showAddressModal.value = true
}
function requestDeleteAddress(s, id) {
  if (ownsAddress(s, id) && !s.savingAddress.value)
    s.pendingConfirm.value = { action: 'delete', id }
}
function requestDefaultAddress(s, address) {
  if (address && ownsAddress(s, address.id) && !s.savingAddress.value)
    s.pendingConfirm.value = {
      action: 'default',
      id: address.id,
      label: address.label,
      payload: {
        ...Object.fromEntries(Object.keys(blankAddress()).map((key) => [key, address[key]])),
        is_default: true,
      },
    }
}
function requestSaveAddress(s) {
  if (!toValue(s.isOwnProfile) || s.savingAddress.value) return
  const error = !s.pinValid.value
    ? t('community.address.err_pin')
    : validationMessage(s.addressDraft.value)
  s.addressErrors.value = {}
  if (error) {
    s.addressErrors.value.form = error
    s.notificationStore.error(error)
    return
  }
  s.pendingConfirm.value = {
    action: 'save',
    id: s.editingAddress.value?.id,
    payload: addressPayload(s.addressDraft.value),
  }
}
async function runAddressAction(s, pending) {
  if (!toValue(s.isOwnProfile) || (pending.id && !ownsAddress(s, pending.id))) return
  s.addressErrors.value = {}
  try {
    if (pending.action === 'delete') await s.addressStore.deleteAddress(pending.id)
    else if (pending.id) await s.addressStore.updateAddress(pending.id, pending.payload)
    else await s.addressStore.createAddress(pending.payload)
    s.notificationStore.success(
      t(pending.action === 'delete' ? 'community.address.deleted' : 'community.address.saved'),
    )
    if (pending.action === 'save') s.showAddressModal.value = false
    await s.onSaved?.()
  } catch (error) {
    showAddressError(s, error)
  }
}
function showAddressError(s, error) {
  const errors = error.response?.data?.errors || {}
  s.addressErrors.value = Object.fromEntries(
    Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
  )
  if (!Object.keys(errors).length)
    s.addressErrors.value.form = error.response?.data?.message || t('community.address.save_failed')
  s.notificationStore.error(s.addressErrors.value.form || t('community.address.review'))
}
function addressConfirmation(pending) {
  if (!pending) return null
  if (pending.action === 'delete') return deleteConfig()
  if (pending.action === 'default')
    return {
      title: t('community.address.default_title'),
      message: t('community.address.default_msg', {
        label: pending.label || t('community.address.default_fallback'),
      }),
      confirmText: t('community.address.default_ok'),
      type: 'primary',
    }
  return {
    title: t(pending.id ? 'community.address.save_title' : 'community.address.add_title'),
    message: t(pending.id ? 'community.address.save_msg' : 'community.address.add_msg'),
    confirmText: t(pending.id ? 'community.address.save_ok' : 'community.address.add_ok'),
    type: 'primary',
  }
}
