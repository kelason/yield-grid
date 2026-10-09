<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { useAdminOverview } from '@/composables/useAdminOverview'
import {
  ADMIN_INQUIRY_STATUS_OPTIONS,
  ADMIN_USER_ROLE_MEMBERS,
  REPLY_DELIVERY_STATUS,
} from '@/constants/admin'
import { ISSUE_STATUS_LABEL_KEYS } from '@/constants/issues'
import {
  CONTENT_TYPE_OPTIONS,
  REPORT_STATUS,
  REPORT_STATUS_LABEL_KEYS,
} from '@/constants/reporting'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'

const { overview, loading, error, generatedAt, fetchOverview } = useAdminOverview()
const { t } = useI18n()

const hasData = computed(() => overview.value !== null)
const showLoading = computed(() => loading.value && !hasData.value)
const showInitialError = computed(() => error.value !== '' && !hasData.value)
const showRefreshError = computed(() => error.value !== '' && hasData.value)

function count(value) {
  return typeof value === 'number' ? value : null
}

function bucket(section) {
  const value = overview.value?.[section]
  return value && typeof value === 'object' ? value : null
}

const memberCards = computed(() => {
  const users = bucket('users')
  if (!users) return null
  return [
    {
      testid: 'overview-users-total',
      label: t('admin.overview.members'),
      value: count(users.members_total),
      to: { name: 'admin-users', query: { role: ADMIN_USER_ROLE_MEMBERS } },
      aria: t('admin.overview.view_members'),
      tone: 'default',
      sub: null,
    },
    {
      testid: 'overview-users-suspended',
      label: t('admin.overview.members_suspended'),
      value: count(users.members_suspended),
      to: { name: 'admin-users', query: { suspended: 'suspended' } },
      aria: t('admin.overview.view_suspended'),
      tone: 'harvest',
      sub: null,
    },
  ]
})

const contentCards = computed(() =>
  CONTENT_TYPE_OPTIONS.map((option) => {
    const content = bucket('content') ?? {}
    const counts = content[option.value] ?? null
    const label = t(option.labelKey)
    return {
      testid: `overview-content-${option.value}`,
      label,
      value: counts ? count(counts.total) : null,
      to: { name: 'admin-content', query: { type: option.value } },
      aria: t('admin.overview.view_type', { label: label.toLowerCase() }),
      tone: 'default',
      sub:
        counts && typeof counts.visible === 'number' && typeof counts.hidden === 'number'
          ? t('admin.overview.visible_hidden', { visible: counts.visible, hidden: counts.hidden })
          : null,
    }
  }),
)

const inquiryCards = computed(() => {
  const inquiries = bucket('inquiries')
  if (!inquiries) return null
  const statuses = ADMIN_INQUIRY_STATUS_OPTIONS.filter((option) => option.value !== '')
  const cards = statuses.map((option) => {
    const label = t(option.labelKey)
    return {
      testid: `overview-inquiries-${option.value}`,
      label,
      value: count(inquiries[option.value]),
      to: { name: 'admin-inquiries', query: { status: option.value } },
      aria: t('admin.overview.view_inquiries', { label: label.toLowerCase() }),
      tone: 'default',
      sub: null,
    }
  })
  cards.push({
    testid: 'overview-inquiries-failed',
    label: t('admin.overview.failed_replies'),
    value: count(inquiries.failed_replies),
    to: { name: 'admin-inquiries', query: { delivery: REPLY_DELIVERY_STATUS.FAILED } },
    aria: t('admin.overview.view_failed'),
    tone: 'harvest',
    sub: null,
  })
  return cards
})

const reportCards = computed(() => {
  const reports = bucket('reports')
  if (!reports) return null
  return Object.values(REPORT_STATUS).map((status) => {
    const labelKey = REPORT_STATUS_LABEL_KEYS[status]
    const resolved = labelKey ? t(labelKey) : status
    return {
      testid: `overview-reports-${status}`,
      label: resolved,
      value: count(reports[status]),
      to: { name: 'admin-reports', query: { status } },
      aria: t('admin.overview.view_reports', { label: resolved.toLowerCase() }),
      tone: 'default',
      sub: null,
    }
  })
})

const issueCards = computed(() => {
  const issues = bucket('issues')
  if (!issues) return null
  return Object.entries(ISSUE_STATUS_LABEL_KEYS).map(([status, labelKey]) => {
    const label = t(labelKey)
    return {
      testid: `overview-issues-${status}`,
      label,
      value: count(issues[status]),
      to: { name: 'admin-issues', query: { status } },
      aria: t('admin.overview.view_issues', { label: label.toLowerCase() }),
      tone: 'default',
      sub: null,
    }
  })
})

const updatedLabel = computed(() => {
  if (!generatedAt.value) return t('admin.overview.updated_unknown')
  const stamp = generatedAt.value.slice(0, 16).replace('T', ' ')
  return t('admin.overview.updated', { stamp })
})

function refresh() {
  if (!loading.value) fetchOverview()
}

onMounted(() => {
  fetchOverview()
})
</script>

<template>
  <div class="space-y-8">
    <PageHeader :title="t('admin.overview.title')" :description="t('admin.overview.description')">
      <template #actions>
        <AppButton
          variant="outline"
          data-testid="overview-refresh"
          :loading="loading"
          @click="refresh"
        >
          {{ t('admin.overview.refresh') }}
        </AppButton>
      </template>
    </PageHeader>

    <p class="text-sm text-stone-500" data-testid="overview-updated">{{ updatedLabel }}</p>

    <LoadingState v-if="showLoading" :label="t('admin.overview.loading')" />

    <AppCard v-else-if="showInitialError" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" data-testid="overview-retry" @click="refresh">
        {{ t('shell.retry') }}
      </AppButton>
    </AppCard>

    <template v-else-if="hasData">
      <div v-if="showRefreshError" class="space-y-3">
        <AppAlert type="error">{{ error }}</AppAlert>
        <AppButton variant="outline" data-testid="overview-retry" @click="refresh">
          {{ t('shell.retry') }}
        </AppButton>
      </div>

      <section v-if="memberCards" :aria-label="t('admin.overview.members')" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.overview.members') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <RouterLink
            v-for="card in memberCards"
            :key="card.testid"
            :to="card.to"
            :data-testid="card.testid"
            :aria-label="card.aria"
            class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-600 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50"
          >
            <StatCard :label="card.label" :value="card.value" :tone="card.tone" />
          </RouterLink>
        </div>
      </section>

      <section :aria-label="t('admin.overview.content')" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.overview.content') }}
        </h2>
        <p class="max-w-2xl text-sm leading-relaxed text-stone-500">
          {{ t('admin.overview.content_note') }}
        </p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          <RouterLink
            v-for="card in contentCards"
            :key="card.testid"
            :to="card.to"
            :data-testid="card.testid"
            :aria-label="card.aria"
            class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-600 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50"
          >
            <StatCard :label="card.label" :value="card.value" :tone="card.tone" />
            <p v-if="card.sub" class="mt-2 px-1 text-sm text-stone-500">{{ card.sub }}</p>
          </RouterLink>
        </div>
      </section>

      <section v-if="inquiryCards" :aria-label="t('admin.overview.inquiries')" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.overview.inquiries') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          <RouterLink
            v-for="card in inquiryCards"
            :key="card.testid"
            :to="card.to"
            :data-testid="card.testid"
            :aria-label="card.aria"
            class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-600 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50"
          >
            <StatCard :label="card.label" :value="card.value" :tone="card.tone" />
          </RouterLink>
        </div>
      </section>

      <section v-if="reportCards" :aria-label="t('admin.overview.reports')" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.overview.reports') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <RouterLink
            v-for="card in reportCards"
            :key="card.testid"
            :to="card.to"
            :data-testid="card.testid"
            :aria-label="card.aria"
            class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-600 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50"
          >
            <StatCard :label="card.label" :value="card.value" :tone="card.tone" />
          </RouterLink>
        </div>
      </section>

      <section v-if="issueCards" :aria-label="t('admin.overview.issues')" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">
          {{ t('admin.overview.issues') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <RouterLink
            v-for="card in issueCards"
            :key="card.testid"
            :to="card.to"
            :data-testid="card.testid"
            :aria-label="card.aria"
            class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-600 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50"
          >
            <StatCard :label="card.label" :value="card.value" :tone="card.tone" />
          </RouterLink>
        </div>
      </section>
    </template>
  </div>
</template>
