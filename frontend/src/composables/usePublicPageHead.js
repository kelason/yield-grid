import { computed, unref } from 'vue'
import { useHead } from '@unhead/vue'

export function usePublicPageHead({ title, description }) {
  useHead(
    computed(() => ({
      title: unref(title),
      meta: [
        { name: 'description', content: unref(description) },
        { property: 'og:title', content: unref(title) },
        { property: 'og:description', content: unref(description) },
      ],
    })),
  )
}
