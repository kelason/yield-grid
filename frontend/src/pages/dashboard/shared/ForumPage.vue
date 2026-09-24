<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForumStore } from '../../../stores/forumStore'
import { THREAD_SORT_OPTIONS } from '../../../constants/forum'
import ThreadCard from '../../../components/molecules/ThreadCard.vue'
import CategoryCard from '../../../components/molecules/CategoryCard.vue'
import ThreadComposer from '../../../components/molecules/ThreadComposer.vue'
import AppButton from '../../../components/atoms/AppButton.vue'
import AppModal from '../../../components/molecules/AppModal.vue'

const forumStore = useForumStore()
const route = useRoute()
const router = useRouter()

const showComposer = ref(false)
const isSubmitting = ref(false)
const selectedCategory = ref(route.query.category || '')
const currentSort = ref(route.query.sort || 'latest')
const searchQuery = ref(route.query.search || '')

onMounted(async () => {
  await Promise.all([forumStore.fetchCategories(), forumStore.fetchTags(), fetchThreads()])
})

const fetchThreads = async () => {
  await forumStore.fetchThreads({
    category: selectedCategory.value,
    sort: currentSort.value,
    search: searchQuery.value,
    page: forumStore.pagination.currentPage,
  })
}

// Watch filters to trigger fetch
watch([selectedCategory, currentSort], () => {
  // Update URL
  router.replace({
    query: {
      ...route.query,
      category: selectedCategory.value || undefined,
      sort: currentSort.value !== 'latest' ? currentSort.value : undefined,
    },
  })
  forumStore.pagination.currentPage = 1
  fetchThreads()
})

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.replace({ query: { ...route.query, search: searchQuery.value || undefined } })
    forumStore.pagination.currentPage = 1
    fetchThreads()
  }, 500)
}

const handleComposerSubmit = async (data) => {
  if (isSubmitting.value) return
  isSubmitting.value = true
  try {
    await forumStore.createThread(data)
    showComposer.value = false
    // Refresh the categories and threads list instead of navigating away
    await Promise.all([forumStore.fetchCategories(), fetchThreads()])
  } catch (error) {
    console.error(error)
    // Handled in store
  } finally {
    isSubmitting.value = false
  }
}

const handleVote = (threadId, val) => {
  forumStore.voteThread(threadId, val)
}

const clearFilters = () => {
  selectedCategory.value = ''
  currentSort.value = 'latest'
  searchQuery.value = ''
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Top Header: Title & Actions -->
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-8">
      <h1 class="font-serif text-3xl font-bold text-stone-900 w-full lg:w-auto">
        {{
          selectedCategory
            ? forumStore.categories.find((c) => c.slug === selectedCategory)?.name ||
              'Community Forum'
            : 'Community Forum'
        }}
      </h1>

      <div class="flex items-center gap-3 w-full lg:w-auto flex-wrap sm:flex-nowrap">
        <div class="relative w-full sm:w-64">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search discussions..."
            class="w-full pl-10 pr-4 py-2 rounded-full border-stone-300 shadow-soft focus:ring-moss-500 focus:border-moss-500 text-sm"
          />
          <svg
            class="w-5 h-5 text-stone-400 absolute left-3 top-2.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            ></path>
          </svg>
        </div>

        <select
          v-model="currentSort"
          class="appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%20stroke%3D%22%236b7280%22%3E%3Cpath%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1.25rem_1.25rem] bg-[position:right_1rem_center] bg-no-repeat rounded-full border-stone-300 shadow-soft focus:ring-moss-500 focus:border-moss-500 text-sm py-2 pl-4 pr-12"
        >
          <option v-for="opt in THREAD_SORT_OPTIONS" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>

        <AppButton
          rounded="full"
          @click="showComposer = true"
          class="!py-2 !text-sm border border-transparent justify-center bg-gradient-to-br from-moss-500 to-moss-600 hover:from-moss-600 hover:to-moss-700 text-white font-medium shadow-soft hover:shadow-organic px-5 transition-all duration-300 transform hover:-translate-y-0.5 whitespace-nowrap"
        >
          <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              ></path>
            </svg>
            New Discussion
          </span>
        </AppButton>
      </div>
    </div>

    <!-- Content Columns -->
    <div class="flex flex-col md:flex-row gap-8">
      <!-- Left Sidebar: Categories -->
      <aside class="w-full md:w-64 flex-shrink-0">
        <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-4">
          <h2 class="font-serif text-lg font-bold text-stone-900 mb-4">Categories</h2>
          <div class="space-y-1">
            <CategoryCard
              :category="{
                name: 'All Discussions',
                slug: '',
                icon_emoji: '🌍',
                description: 'Everything in one place',
                threads_count: forumStore.categories.reduce(
                  (acc, cat) => acc + cat.threads_count,
                  0,
                ),
              }"
              :isSelected="selectedCategory === ''"
              @select="selectedCategory = ''"
            />
            <CategoryCard
              v-for="cat in forumStore.categories"
              :key="cat.id"
              :category="cat"
              :isSelected="selectedCategory === cat.slug"
              @select="(slug) => (selectedCategory = slug)"
            />
          </div>
        </div>
      </aside>

      <!-- Main Content: Threads List -->
      <main class="flex-grow min-w-0">
        <!-- Thread List -->
        <div class="space-y-4">
          <div v-if="forumStore.isLoading" class="text-center py-12 text-stone-500">
            <svg
              class="animate-spin h-8 w-8 mx-auto text-moss-500 mb-4"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Loading discussions...
          </div>
          <template v-else-if="forumStore.threads.length > 0">
            <ThreadCard
              v-for="thread in forumStore.threads"
              :key="thread.id"
              :thread="thread"
              @vote="handleVote"
            />

            <!-- Pagination -->
            <div class="flex justify-center mt-8 gap-2" v-if="forumStore.pagination.lastPage > 1">
              <AppButton
                variant="outline"
                :disabled="forumStore.pagination.currentPage === 1"
                @click="
                  forumStore.pagination.currentPage--
                  fetchThreads()
                "
              >
                Previous
              </AppButton>
              <AppButton
                variant="outline"
                :disabled="forumStore.pagination.currentPage === forumStore.pagination.lastPage"
                @click="
                  forumStore.pagination.currentPage++
                  fetchThreads()
                "
              >
                Next
              </AppButton>
            </div>
          </template>
          <div
            v-else
            class="text-center py-16 bg-white rounded-2xl shadow-soft border border-stone-200"
          >
            <div class="text-5xl mb-4">🌱</div>
            <h3 class="font-serif text-xl font-bold text-stone-900 mb-2">No discussions found</h3>
            <p class="text-stone-500 max-w-md mx-auto mb-6">
              We couldn't find any discussions matching your current filters.
            </p>
            <AppButton variant="outline" @click="clearFilters">Clear Filters</AppButton>
          </div>
        </div>
      </main>
    </div>

    <!-- Composer Modal -->
    <AppModal :isOpen="showComposer" @close="showComposer = false">
      <ThreadComposer
        :categories="forumStore.categories"
        :tags="forumStore.tags"
        :isSubmitting="isSubmitting"
        @submit="handleComposerSubmit"
        @cancel="showComposer = false"
      />
    </AppModal>
  </div>
</template>
