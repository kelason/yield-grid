import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import RecommendationCard from './RecommendationCard.vue';

// Mock the CropConfidenceMeter since it uses chart.js which might complain in jsdom
vi.mock('../atoms/CropConfidenceMeter.vue', () => ({
  default: {
    template: '<div class="mock-confidence-meter"></div>'
  }
}));

describe('RecommendationCard.vue', () => {
  const mockRecommendation = {
    id: 1,
    crop_name: 'Wheat',
    confidence_score: 85,
    reasoning: 'Good soil and weather for wheat.',
    projected_yield: '4 tons/ha',
    status: 'pending'
  };

  it('renders correctly', () => {
    const wrapper = mount(RecommendationCard, {
      props: {
        recommendation: mockRecommendation
      }
    });

    expect(wrapper.text()).toContain('Wheat');
    expect(wrapper.text()).toContain('Good soil and weather for wheat.');
    expect(wrapper.text()).toContain('4 tons/ha');
  });

  it('emits accept event when accept button is clicked', async () => {
    const wrapper = mount(RecommendationCard, {
      props: {
        recommendation: mockRecommendation
      }
    });

    await wrapper.findAll('button').filter(b => b.text().includes('Accept'))[0].trigger('click');
    
    expect(wrapper.emitted()).toHaveProperty('accept');
    expect(wrapper.emitted('accept')[0]).toEqual([1]);
  });

  it('emits reject event when reject button is clicked', async () => {
    const wrapper = mount(RecommendationCard, {
      props: {
        recommendation: mockRecommendation
      }
    });

    await wrapper.findAll('button').filter(b => b.text().includes('Reject'))[0].trigger('click');
    
    expect(wrapper.emitted()).toHaveProperty('reject');
    expect(wrapper.emitted('reject')[0]).toEqual([1]);
  });
});
