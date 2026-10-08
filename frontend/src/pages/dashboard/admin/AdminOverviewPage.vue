<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAdminOverview } from '@/composables/useAdminOverview'
import {
  ADMIN_INQUIRY_STATUS_OPTIONS,
  ADMIN_USER_ROLE_MEMBERS,
  REPLY_DELIVERY_STATUS,
} from '@/constants/admin'
import { ISSUE_STATUS_LABELS } from '@/constants/issues'
import { CONTENT_TYPE_OPTIONS, REPORT_STATUS, REPORT_STATUS_LABELS } from '@/constants/reporting'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'

const { overview, loading, error, generatedAt, fetchOverview } = useAdminOverview()

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
      label: 'Members',
      value: count(users.members_total),
      to: { name: 'admin-users', query: { role: ADMIN_USER_ROLE_MEMBERS } },
      aria: 'View all members',
      tone: 'default',
      sub: null,
    },
    {
      testid: 'overview-users-suspended',
      label: 'Suspended members',
      value: count(users.members_suspended),
      to: { name: 'admin-users', query: { suspended: 'suspended' } },
      aria: 'View suspended members',
      tone: 'harvest',
      sub: null,
    },
  ]
})

const contentCards = computed(() =>
  CONTENT_TYPE_OPTIONS.map((option) => {
    const content = bucket('content') ?? {}
    const counts = content[option.value] ?? null
    return {
      testid: `overview-content-${option.value}`,
      label: option.label,
      value: counts ? count(counts.total) : null,
      to: { name: 'admin-content', query: { type: option.value } },
      aria: `View ${option.label.toLowerCase()}`,
      tone: 'default',
      sub:
        counts && typeof counts.visible === 'number' && typeof counts.hidden === 'number'
          ? `${counts.visible} visible · ${counts.hidden} hidden`
          : null,
    }
  }),
)

const inquiryCards = computed(() => {
  const inquiries = bucket('inquiries')
  if (!inquiries) return null
  const statuses = ADMIN_INQUIRY_STATUS_OPTIONS.filter((option) => option.value !== '')
  const cards = statuses.map((option) => ({
    testid: `overview-inquiries-${option.value}`,
    label: option.label,
    value: count(inquiries[option.value]),
    to: { name: 'admin-inquiries', query: { status: option.value } },
    aria: `View ${option.label.toLowerCase()} inquiries`,
    tone: 'default',
    sub: null,
  }))
  cards.push({
    testid: 'overview-inquiries-failed',
    label: 'Failed replies',
    value: count(inquiries.failed_replies),
    to: { name: 'admin-inquiries', query: { delivery: REPLY_DELIVERY_STATUS.FAILED } },
    aria: 'View inquiries with failed replies',
    tone: 'harvest',
    sub: null,
  })
  return cards
})

const reportCards = computed(() => {
  const reports = bucket('reports')
  if (!reports) return null
  return Object.values(REPORT_STATUS).map((status) => ({
    testid: `overview-reports-${status}`,
    label: REPORT_STATUS_LABELS[status] ?? status,
    value: count(reports[status]),
    to: { name: 'admin-reports', query: { status } },
    aria: `View ${(REPORT_STATUS_LABELS[status] ?? status).toLowerCase()} reports`,
    tone: 'default',
    sub: null,
  }))
})

const issueCards = computed(() => {
  const issues = bucket('issues')
  if (!issues) return null
  return Object.entries(ISSUE_STATUS_LABELS).map(([status, label]) => ({
    testid: `overview-issues-${status}`,
    label,
    value: count(issues[status]),
    to: { name: 'admin-issues', query: { status } },
    aria: `View ${label.toLowerCase()} issues`,
    tone: 'default',
    sub: null,
  }))
})

const updatedLabel = computed(() => {
  if (!generatedAt.value) return 'Updated at unknown'
  const stamp = generatedAt.value.slice(0, 16).replace('T', ' ')
  return `Updated at ${stamp}`
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
    <PageHeader
      title="Overview"
      description="Database-wide operational counts for members, content, inquiries, reports, and issues."
    >
      <template #actions>
        <AppButton
          variant="outline"
          data-testid="overview-refresh"
          :loading="loading"
          @click="refresh"
        >
          Refresh
        </AppButton>
      </template>
    </PageHeader>

    <p class="text-sm text-stone-500" data-testid="overview-updated">{{ updatedLabel }}</p>

    <LoadingState v-if="showLoading" label="Loading overview" />

    <AppCard v-else-if="showInitialError" padding="p-6" role="alert">
      <p class="text-base text-stone-900">{{ error }}</p>
      <AppButton variant="outline" class="mt-4" data-testid="overview-retry" @click="refresh">
        Retry
      </AppButton>
    </AppCard>

    <template v-else-if="hasData">
      <div v-if="showRefreshError" class="space-y-3">
        <AppAlert type="error">{{ error }}</AppAlert>
        <AppButton variant="outline" data-testid="overview-retry" @click="refresh">
          Retry
        </AppButton>
      </div>

      <section v-if="memberCards" aria-label="Members" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Members</h2>
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

      <section aria-label="Content" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Content</h2>
        <p class="max-w-2xl text-sm leading-relaxed text-stone-500">
          Non-deleted records. Contract and listing totals are record counts and include split
          items; suppressed items count as hidden.
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

      <section v-if="inquiryCards" aria-label="Inquiries" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Inquiries</h2>
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

      <section v-if="reportCards" aria-label="Reports" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Reports</h2>
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

      <section v-if="issueCards" aria-label="Issues" class="space-y-4">
        <h2 class="font-serif text-2xl font-bold text-stone-900">Issues</h2>
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
