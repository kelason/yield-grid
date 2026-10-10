<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
import FormField from '../molecules/FormField.vue'
import {
  ISSUE_CATEGORY_OPTIONS,
  ISSUE_DESCRIPTION_MAX_LENGTH,
  ISSUE_PAGE_PATH_MAX_LENGTH,
  ISSUE_SUBJECT_MAX_LENGTH,
} from '@/constants/issues'

const { t } = useI18n()

const categoryOptions = computed(() =>
  ISSUE_CATEGORY_OPTIONS.map((option) => ({ value: option.value, label: t(option.labelKey) })),
)

defineProps({
  category: { type: String, default: '' },
  subject: { type: String, default: '' },
  description: { type: String, default: '' },
  pagePath: { type: String, default: '' },
  busy: Boolean,
  errors: { type: Object, default: () => ({}) },
  submitError: { type: String, default: '' },
})

defineEmits([
  'update:category',
  'update:subject',
  'update:description',
  'update:pagePath',
  'use-current-page',
  'submit',
  'new-draft',
])
</script>

<template>
  <form class="space-y-5" @submit.prevent="$emit('submit')">
    <p class="rounded-2xl bg-harvest-100 p-4 text-sm leading-relaxed text-stone-700">
      {{ t('issues.form.warning') }}
    </p>

    <AppSelect
      id="issue-category"
      :label="t('issues.form.category')"
      :model-value="category"
      :options="categoryOptions"
      :disabled="busy"
      :required="true"
      :error="errors.category || ''"
      @update:model-value="$emit('update:category', $event)"
    />

    <FormField
      id="issue-subject"
      :label="t('issues.form.subject')"
      :model-value="subject"
      :maxlength="ISSUE_SUBJECT_MAX_LENGTH"
      :disabled="busy"
      :required="true"
      :error="errors.subject || ''"
      :placeholder="t('issues.form.subject_ph')"
      @update:model-value="$emit('update:subject', $event)"
    />

    <FormField
      id="issue-description"
      :label="t('issues.form.description')"
      :model-value="description"
      :multiline="true"
      :maxlength="ISSUE_DESCRIPTION_MAX_LENGTH"
      :disabled="busy"
      :required="true"
      :error="errors.description || ''"
      :placeholder="t('issues.form.desc_ph')"
      @update:model-value="$emit('update:description', $event)"
    />

    <div class="space-y-3">
      <FormField
        id="issue-page-path"
        :label="t('issues.form.path')"
        :model-value="pagePath"
        :maxlength="ISSUE_PAGE_PATH_MAX_LENGTH"
        :disabled="busy"
        :error="errors.pagePath || ''"
        :placeholder="t('issues.form.path_ph')"
        :hint="t('issues.form.path_hint')"
        @update:model-value="$emit('update:pagePath', $event)"
      />
      <AppButton
        type="button"
        variant="outline"
        :disabled="busy"
        data-testid="issue-use-current-page"
        @click="$emit('use-current-page')"
      >
        {{ t('issues.form.use_current') }}
      </AppButton>
    </div>

    <p
      v-if="submitError"
      role="alert"
      data-testid="issue-submit-error"
      class="text-sm text-red-600"
    >
      {{ submitError }}
    </p>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <AppButton
        type="button"
        variant="secondary"
        :disabled="busy"
        data-testid="issue-new-draft"
        @click="$emit('new-draft')"
      >
        {{ t('issues.form.new_draft') }}
      </AppButton>
      <AppButton type="submit" variant="primary" :loading="busy" :disabled="busy">
        {{ t('issues.form.submit') }}
      </AppButton>
    </div>
  </form>
</template>
