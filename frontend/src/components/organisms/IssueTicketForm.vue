<script setup>
import AppButton from '../atoms/AppButton.vue'
import AppSelect from '../atoms/AppSelect.vue'
import FormField from '../molecules/FormField.vue'
import {
  ISSUE_CATEGORY_OPTIONS,
  ISSUE_CREDENTIAL_WARNING,
  ISSUE_DESCRIPTION_MAX_LENGTH,
  ISSUE_PAGE_PATH_MAX_LENGTH,
  ISSUE_SUBJECT_MAX_LENGTH,
} from '@/constants/issues'

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
      {{ ISSUE_CREDENTIAL_WARNING }}
    </p>

    <AppSelect
      id="issue-category"
      label="Category"
      :model-value="category"
      :options="ISSUE_CATEGORY_OPTIONS"
      :disabled="busy"
      :required="true"
      :error="errors.category || ''"
      @update:model-value="$emit('update:category', $event)"
    />

    <FormField
      id="issue-subject"
      label="Subject"
      :model-value="subject"
      :maxlength="ISSUE_SUBJECT_MAX_LENGTH"
      :disabled="busy"
      :required="true"
      :error="errors.subject || ''"
      placeholder="What went wrong?"
      @update:model-value="$emit('update:subject', $event)"
    />

    <FormField
      id="issue-description"
      label="Description"
      :model-value="description"
      :multiline="true"
      :maxlength="ISSUE_DESCRIPTION_MAX_LENGTH"
      :disabled="busy"
      :required="true"
      :error="errors.description || ''"
      placeholder="What happened, and what did you expect instead?"
      @update:model-value="$emit('update:description', $event)"
    />

    <div class="space-y-3">
      <FormField
        id="issue-page-path"
        label="Related page (optional)"
        :model-value="pagePath"
        :maxlength="ISSUE_PAGE_PATH_MAX_LENGTH"
        :disabled="busy"
        :error="errors.pagePath || ''"
        placeholder="/dashboard/issues"
        hint="Only the page path is stored, never the full address."
        @update:model-value="$emit('update:pagePath', $event)"
      />
      <AppButton
        type="button"
        variant="outline"
        :disabled="busy"
        data-testid="issue-use-current-page"
        @click="$emit('use-current-page')"
      >
        Use current page
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
        Start new draft
      </AppButton>
      <AppButton type="submit" variant="primary" :loading="busy" :disabled="busy">
        Submit issue
      </AppButton>
    </div>
  </form>
</template>
