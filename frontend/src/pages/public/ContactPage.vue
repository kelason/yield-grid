<script setup>
import { useI18n } from 'vue-i18n'
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/atoms/AppCard.vue'
import ContactForm from '@/components/organisms/ContactForm.vue'

const { t } = useI18n()
</script>
<template>
  <div class="break-words bg-stone-100">
    <div
      class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-20 lg:px-8 lg:py-24"
    >
      <div class="max-w-xl lg:pt-8">
        <p class="mb-5 text-sm font-medium uppercase tracking-widest text-moss-700">
          {{ t('public.contact.eyebrow') }}
        </p>
        <h1
          class="text-balance font-serif text-3xl font-bold leading-tight tracking-tight text-stone-900 sm:text-4xl lg:text-5xl"
        >
          {{ t('public.contact.title') }}
        </h1>
        <p class="mt-6 text-base leading-relaxed text-stone-600 sm:text-lg">
          {{ t('public.contact.description') }}
        </p>

        <div class="mt-8 border-t border-stone-200 pt-8 sm:mt-12 sm:pt-10">
          <div
            class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-moss-100 text-moss-700"
          >
            <ChatBubbleLeftRightIcon class="h-6 w-6" aria-hidden="true" />
          </div>
          <h2 class="text-balance font-serif text-2xl font-bold text-stone-900">
            {{ t('public.contact.help_title') }}
          </h2>
          <p class="mt-4 text-base leading-relaxed text-stone-600">
            {{ t('public.contact.help_desc') }}
          </p>
        </div>
      </div>
      <AppCard padding="p-6 sm:p-8 lg:p-10" class="self-start">
        <ContactForm />
      </AppCard>
    </div>
  </div>
</template>
