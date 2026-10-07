<script setup>
import AppButton from '../atoms/AppButton.vue'
import FormField from './FormField.vue'
import AppSelect from '../atoms/AppSelect.vue'
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { ref, computed } from 'vue'
import { FORUM_CONSTANTS } from '../../constants/forum'

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
  tags: {
    type: Array,
    required: true,
  },
  error: { type: String, default: '' },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

const validationError = ref('')
const emit = defineEmits(['submit', 'cancel'])

const form = ref({
  title: '',
  category_id: '',
  body: '',
  is_anonymous: false,
  tag_ids: [],
})

const toggleTag = (id) => {
  const index = form.value.tag_ids.indexOf(id)
  if (index > -1) {
    form.value.tag_ids.splice(index, 1)
  } else {
    if (form.value.tag_ids.length < FORUM_CONSTANTS.MAX_TAGS_PER_THREAD) {
      form.value.tag_ids.push(id)
    }
  }
}

const bodyLength = computed(() => (form.value.body || '').length)
const isBodyOverLimit = computed(() => bodyLength.value > FORUM_CONSTANTS.BODY_MAX_LENGTH)

const submit = () => {
  if (props.isSubmitting) return
  const title = form.value.title.trim()
  const body = form.value.body.trim()
  validationError.value = ''
  if (
    title.length < FORUM_CONSTANTS.TITLE_MIN_LENGTH ||
    title.length > FORUM_CONSTANTS.TITLE_MAX_LENGTH
  )
    validationError.value = `Title must be ${FORUM_CONSTANTS.TITLE_MIN_LENGTH}–${FORUM_CONSTANTS.TITLE_MAX_LENGTH} characters.`
  else if (body.length < FORUM_CONSTANTS.BODY_MIN_LENGTH || isBodyOverLimit.value)
    validationError.value = `Details must be ${FORUM_CONSTANTS.BODY_MIN_LENGTH}–${FORUM_CONSTANTS.BODY_MAX_LENGTH} characters.`
  else if (
    !props.categories?.some((category) => String(category.id) === String(form.value.category_id))
  )
    validationError.value = 'Select a category.'
  else if (form.value.tag_ids.length > FORUM_CONSTANTS.MAX_TAGS_PER_THREAD)
    validationError.value = 'Select fewer tags.'
  if (validationError.value) return
  emit('submit', { ...form.value, tag_ids: [...form.value.tag_ids] })
}
</script>

<template>
  <div class="w-full">
    <form @submit.prevent="submit" class="space-y-5">
      <p v-if="validationError || error" role="alert" class="text-sm text-red-600">
        {{ validationError || error }}
      </p>
      <FormField
        id="thread-title"
        v-model="form.title"
        label="Title"
        required
        :minlength="FORUM_CONSTANTS.TITLE_MIN_LENGTH"
        :maxlength="FORUM_CONSTANTS.TITLE_MAX_LENGTH"
        :disabled="isSubmitting"
        placeholder="What do you want to ask or share?"
      />
      <AppSelect
        id="thread-category"
        label="Category"
        v-model="form.category_id"
        required
        :disabled="isSubmitting"
        ><option value="" disabled>Select a category</option>
        <option v-for="category in categories || []" :key="category.id" :value="category.id">
          {{ category.name }}
        </option></AppSelect
      >
      <div>
        <label class="block text-sm font-medium text-soil-700 mb-2"
          >Tags (Max {{ FORUM_CONSTANTS.MAX_TAGS_PER_THREAD }})</label
        >
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            v-for="tag in tags"
            :key="tag.id"
            @click="toggleTag(tag.id)"
            :aria-pressed="form.tag_ids.includes(tag.id)"
            class="min-h-11 px-3 py-1 rounded-full text-xs font-medium transition-colors border"
            :class="
              form.tag_ids.includes(tag.id)
                ? 'bg-moss-500 text-white border-moss-600'
                : 'bg-stone-50 text-stone-600 border-stone-200 hover:bg-stone-100'
            "
            :disabled="
              isSubmitting ||
              (!form.tag_ids.includes(tag.id) &&
                form.tag_ids.length >= FORUM_CONSTANTS.MAX_TAGS_PER_THREAD)
            "
          >
            {{ tag.name }}
          </button>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="thread-details" class="block text-sm font-medium text-soil-700"
            >Details</label
          >
          <span
            class="text-[11px]"
            :class="isBodyOverLimit ? 'text-red-600 font-semibold' : 'text-stone-500'"
            id="thread-details-counter"
          >
            {{ bodyLength }}/{{ FORUM_CONSTANTS.BODY_MAX_LENGTH }}
          </span>
        </div>
        <AppTextarea
          id="thread-details"
          aria-describedby="thread-details-counter"
          v-model="form.body"
          required
          rows="5"
          :minlength="FORUM_CONSTANTS.BODY_MIN_LENGTH"
          :maxlength="FORUM_CONSTANTS.BODY_MAX_LENGTH"
          class="resize-y"
          :disabled="isSubmitting"
          placeholder="Provide more details..."
        ></AppTextarea>
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model="form.is_anonymous"
          type="checkbox"
          id="anon"
          class="rounded-xl text-moss-600 focus:ring-moss-500 w-4 h-4 border-stone-300"
        />
        <label for="anon" class="text-sm text-stone-600 cursor-pointer"
          >Post anonymously (Your name will be hidden)</label
        >
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-stone-100">
        <AppButton variant="ghost" :disabled="isSubmitting" @click="emit('cancel')"
          >Cancel</AppButton
        >
        <AppButton type="submit" :loading="isSubmitting">Post Discussion</AppButton>
      </div>
    </form>
  </div>
</template>
