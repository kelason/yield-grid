import { computed, useAttrs } from 'vue'

const WRAPPER_ATTRIBUTES = ['class', 'style', 'data-testid']

export function useControlAttrs() {
  const attrs = useAttrs()
  const select = (wrapper) =>
    Object.fromEntries(
      Object.entries(attrs).filter(
        ([key]) => (WRAPPER_ATTRIBUTES.includes(key) || key.startsWith('data-')) === wrapper,
      ),
    )
  return {
    wrapperAttrs: computed(() => select(true)),
    controlAttrs: computed(() => select(false)),
  }
}
