import { describe, expect, it } from 'vitest'
import { TUTORIAL_JOURNEYS, TUTORIAL_SCREENSHOTS } from '../tutorialJourneys'
import { getGuide } from '../userGuides'

describe('tutorial destinations', () => {
  it('keeps the four entry paths independently available with valid detailed-guide links', () => {
    expect(TUTORIAL_JOURNEYS.map((journey) => journey.id)).toEqual([
      'plan-a-crop',
      'sell-a-harvest',
      'buy-a-harvest',
      'request-a-crop',
    ])
    for (const journey of TUTORIAL_JOURNEYS) {
      expect(journey.steps.length).toBeGreaterThan(0)
      for (const step of journey.steps) {
        expect(getGuide(step.guide.topic).sections.map((section) => section.id)).toContain(
          step.guide.sectionId,
        )
        expect(step.instructionKey).toMatch(/^guides\./)
        expect(step.resultKey).toMatch(/^guides\./)
      }
    }
    for (const screenshot of Object.values(TUTORIAL_SCREENSHOTS)) {
      expect(screenshot.src).toMatch(/-en\.png$/)
      expect(screenshot.width).toBeGreaterThan(1)
      expect(screenshot.height).toBeGreaterThan(1)
    }
  })
})
