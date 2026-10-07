import { describe, it, expect } from 'vitest'
import { DESIGN_COLORS } from '../designTokens'

function luminance(hex) {
  const channels = hex.match(/[a-f\d]{2}/gi).map((part) => parseInt(part, 16) / 255)
  const linear = channels.map((value) =>
    value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4,
  )
  return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722
}

function contrast(first, second) {
  const [dark, light] = [luminance(first), luminance(second)].sort((a, b) => a - b)
  return (light + 0.05) / (dark + 0.05)
}

describe('Field & Linen readable text pairs', () => {
  it.each([
    ['primary', '#FFFFFF', DESIGN_COLORS.moss[500]],
    ['primary endpoint', '#FFFFFF', DESIGN_COLORS.moss[600]],
    ['body', DESIGN_COLORS.stone[900], DESIGN_COLORS.stone[50]],
    ['secondary', DESIGN_COLORS.stone[600], DESIGN_COLORS.stone[50]],
    ['muted', DESIGN_COLORS.stone[500], DESIGN_COLORS.stone[50]],
    ['financial', DESIGN_COLORS.harvest[700], DESIGN_COLORS.harvest[50]],
    ['advisory', DESIGN_COLORS.dew[700], DESIGN_COLORS.dew[50]],
  ])('%s maintains normal-text contrast', (_, foreground, background) => {
    expect(contrast(foreground, background)).toBeGreaterThanOrEqual(4.5)
  })
})
