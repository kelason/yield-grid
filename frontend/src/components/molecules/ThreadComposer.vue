<script setup>
import { ref, computed } from 'vue'
import { FORUM_CONSTANTS } from '../../constants/forum'

defineProps({
  categories: {
    type: Array,
    required: true,
  },
  tags: {
    type: Array,
    required: true,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
})

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
  if (isBodyOverLimit.value) return
  emit('submit', { ...form.value })
}
</script>

<template>
  <div class="w-full">
    <h3 class="font-serif text-2xl font-bold text-stone-900 mb-6 pr-8">Create New Discussion</h3>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-soil-700 mb-1">Title</label>
        <input
          v-model="form.title"
          type="text"
          required
          :minlength="FORUM_CONSTANTS.TITLE_MIN_LENGTH"
          :maxlength="FORUM_CONSTANTS.TITLE_MAX_LENGTH"
          class="block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-sm placeholder-stone-400 transition-all duration-200 sm:text-sm bg-stone-50 text-soil-700 hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white"
          placeholder="What do you want to ask or share?"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-soil-700 mb-1">Category</label>
        <select
          v-model="form.category_id"
          required
          class="block w-full pl-4 pr-10 py-2.5 appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%20stroke%3D%22%236b7280%22%3E%3Cpath%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1.25rem_1.25rem] bg-[position:right_1rem_center] bg-no-repeat border border-stone-300 rounded-xl shadow-sm bg-stone-50 text-soil-700 transition-all duration-200 sm:text-sm hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white"
        >
          <option value="" disabled>Select a category</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.icon_emoji }} {{ cat.name }}
          </option>
        </select>
      </div>

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
            class="px-3 py-1 rounded-full text-xs font-medium transition-colors border"
            :class="
              form.tag_ids.includes(tag.id)
                ? 'bg-moss-500 text-white border-moss-600'
                : 'bg-stone-50 text-stone-600 border-stone-200 hover:bg-stone-100'
            "
            :disabled="
              !form.tag_ids.includes(tag.id) &&
              form.tag_ids.length >= FORUM_CONSTANTS.MAX_TAGS_PER_THREAD
            "
          >
            {{ tag.name }}
          </button>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-sm font-medium text-soil-700">Details</label>
          <span
            class="text-[11px]"
            :class="isBodyOverLimit ? 'text-red-600 font-semibold' : 'text-stone-400'"
          >
            {{ bodyLength }}/{{ FORUM_CONSTANTS.BODY_MAX_LENGTH }}
          </span>
        </div>
        <textarea
          v-model="form.body"
          required
          rows="5"
          :minlength="FORUM_CONSTANTS.BODY_MIN_LENGTH"
          :maxlength="FORUM_CONSTANTS.BODY_MAX_LENGTH"
          class="block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-sm placeholder-stone-400 transition-all duration-200 sm:text-sm bg-stone-50 text-soil-700 hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white resize-y"
          placeholder="Provide more details..."
        ></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model="form.is_anonymous"
          type="checkbox"
          id="anon"
          class="rounded text-moss-600 focus:ring-moss-500 w-4 h-4 border-stone-300"
        />
        <label for="anon" class="text-sm text-stone-600 cursor-pointer"
          >Post anonymously (Your name will be hidden)</label
        >
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-stone-100">
        <button
          type="button"
          @click="emit('cancel')"
          class="px-4 py-2 text-stone-600 font-medium hover:bg-stone-100 rounded-xl transition-colors"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2 bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 text-white font-medium rounded-xl shadow-soft hover:shadow-organic hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isSubmitting">Posting...</span>
          <span v-else>Post Discussion</span>
        </button>
      </div>
    </form>
  </div>
</template>
