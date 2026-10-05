import { useI18n } from 'vue-i18n'
import { INSURANCE_LIMITS } from '@/constants/insurance'

const DESTRUCTIVE_STATUSES = ['cancelled', 'rejected']

export function useInsurancePageActions({ store, confirm, selectedEnrollmentId, closeClaimForm }) {
  const { t } = useI18n()

  const destructive = (status) => (DESTRUCTIVE_STATUSES.includes(status) ? 'danger' : 'primary')

  const handleSaveProfile = (payload) => {
    confirm(
      {
        title: t('insurance.rsbsa.title'),
        message: t('insurance.rsbsa.description'),
        type: 'primary',
        confirmText: t('insurance.rsbsa.save'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.saveProfile(payload)
      },
    )
  }

  const handleCreateEnrollment = (payload) => {
    confirm(
      {
        title: t('insurance.guide.start_enrollment'),
        message: t('insurance.guide.step_review_text'),
        type: 'primary',
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        const created = await store.createEnrollment(payload)
        if (created) {
          selectedEnrollmentId.value = created.id
          store.fetchClaims(created.id)
        }
      },
    )
  }

  const handleRequestPack = (id) => {
    confirm(
      {
        title: t('insurance.guide.download_pack'),
        message: t('insurance.guide.step_pack_text'),
        type: 'primary',
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.generatePackAndDownload(id)
      },
    )
  }

  const handleAdvanceEnrollment = (id, status) => {
    confirm(
      {
        title: t('insurance.enrollments.title'),
        message: t(`insurance.enrollments.status_${status}`),
        type: destructive(status),
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.advanceEnrollment(id, status)
      },
    )
  }

  const handleRecordPolicyDetails = (id, payload) => {
    confirm(
      {
        title: t('insurance.guide.record_cic'),
        message: payload.cic_number,
        type: 'primary',
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.updatePolicyDetails(id, payload)
      },
    )
  }

  const handleFileClaim = (payload) => {
    confirm(
      {
        title: t('insurance.claims.file_claim'),
        message: `${t('insurance.claims.loss_date_label')}: ${payload.loss_date}`,
        type: 'primary',
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        const created = await store.createClaim(selectedEnrollmentId.value, payload)
        if (created) {
          closeClaimForm()
        }
      },
    )
  }

  const handleAdvanceClaim = (id, status) => {
    confirm(
      {
        title: t('insurance.claims.advance'),
        message: t(`insurance.claims.status_${status}`),
        type: destructive(status),
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.advanceClaim(selectedEnrollmentId.value, id, { status })
      },
    )
  }

  const handleRecordPayout = (id, amount) => {
    if (!(amount > 0) || amount > INSURANCE_LIMITS.PAYOUT_AMOUNT_MAX) {
      store.errorMessage = t('insurance.claims.payout_invalid')
      return
    }
    confirm(
      {
        title: t('insurance.claims.mark_paid'),
        message: `PHP ${amount}`,
        type: 'primary',
        confirmText: t('insurance.common.confirm'),
        cancelText: t('insurance.common.cancel'),
      },
      async () => {
        await store.advanceClaim(selectedEnrollmentId.value, id, {
          status: 'paid',
          paid_amount_php: amount,
        })
      },
    )
  }

  return {
    handleSaveProfile,
    handleCreateEnrollment,
    handleRequestPack,
    handleAdvanceEnrollment,
    handleRecordPolicyDetails,
    handleFileClaim,
    handleAdvanceClaim,
    handleRecordPayout,
  }
}
