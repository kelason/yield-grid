import { config } from '@vue/test-utils'
import i18n from '@/i18n'

// Shell components render translated strings via useI18n; provide the real
// catalogs (default English locale) so every mount resolves t() calls.
config.global.plugins = [i18n]
