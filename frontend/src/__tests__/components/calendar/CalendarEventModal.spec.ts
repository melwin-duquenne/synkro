import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import CalendarEventModal from '@/components/calendar/CalendarEventModal.vue'
import type { CalendarEvent, EventType } from '@/types'

const mockFetch = vi.fn()

beforeEach(() => {
  localStorage.clear()
  mockFetch.mockReset()
  vi.stubGlobal('fetch', mockFetch)
  // Default: empty enterprise users list
  mockFetch.mockResolvedValue({
    ok: true,
    json: async () => ({ 'hydra:member': [] }),
  })
  setActivePinia(createPinia())
  vi.spyOn(window, 'confirm').mockReturnValue(true)
})

afterEach(() => {
  vi.restoreAllMocks()
  vi.unstubAllGlobals()
})

function makeEvent(overrides: Partial<CalendarEvent> = {}): CalendarEvent {
  return {
    id: 1,
    title: 'Team Meeting',
    description: 'Weekly sync',
    eventType: 'meeting' as EventType,
    startDate: '2026-03-26T09:00:00',
    endDate: '2026-03-26T10:00:00',
    isAllDay: false,
    location: 'Room 1',
    isPrivate: false,
    participants: [],
    ...overrides,
  } as CalendarEvent
}

function mountModal(props: Record<string, unknown> = {}) {
  const pinia = createPinia()
  return mount(CalendarEventModal, {
    props: {
      open: true,
      roomId: 1,
      event: null,
      initialDate: null,
      ...props,
    },
    global: {
      plugins: [pinia],
    },
  })
}

describe('CalendarEventModal — create mode', () => {
  it('shows "Nouvel événement" title in create mode', () => {
    const wrapper = mountModal({ event: null })
    expect(wrapper.text()).toContain('Nouvel événement')
  })

  it('renders title input field', () => {
    const wrapper = mountModal()
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
  })

  it('renders event type select', () => {
    const wrapper = mountModal()
    const selects = wrapper.findAll('select')
    expect(selects.length).toBeGreaterThanOrEqual(1)
  })

  it('disables submit when title is empty', () => {
    const wrapper = mountModal()
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()
  })

  it('enables submit when title is filled', async () => {
    const wrapper = mountModal()
    const titleInput = wrapper.find('input[type="text"]')
    await titleInput.setValue('Sprint Review')
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeUndefined()
  })

  it('shows "Créer" on submit button in create mode', () => {
    const wrapper = mountModal({ event: null })
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.text()).toContain('Créer')
  })
})

describe('CalendarEventModal — edit mode', () => {
  it('shows "Modifier l\'événement" title in edit mode', () => {
    const wrapper = mountModal({ event: makeEvent() })
    expect(wrapper.text()).toContain("Modifier l'événement")
  })

  it('pre-fills title from event when modal opens', async () => {
    // Mount closed, then open — the watcher fires on the open transition
    const wrapper = mountModal({ event: makeEvent({ title: 'Existing Event' }), open: false })
    await wrapper.setProps({ open: true })
    await wrapper.vm.$nextTick()
    const titleInput = wrapper.find('input[type="text"]')
    expect((titleInput.element as HTMLInputElement).value).toBe('Existing Event')
  })

  it('shows delete button in edit mode', () => {
    const wrapper = mountModal({ event: makeEvent() })
    const deleteBtn = wrapper.find('.calendar-danger-btn')
    expect(deleteBtn.exists()).toBe(true)
    expect(deleteBtn.text()).toContain('Supprimer')
  })

  it('does not show delete button in create mode', () => {
    const wrapper = mountModal({ event: null })
    expect(wrapper.find('.calendar-danger-btn').exists()).toBe(false)
  })

  it('shows "Enregistrer" on submit button in edit mode', () => {
    const wrapper = mountModal({ event: makeEvent() })
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.text()).toContain('Enregistrer')
  })
})

describe('CalendarEventModal — all-day toggle', () => {
  it('renders all-day checkbox', () => {
    const wrapper = mountModal()
    const checkbox = wrapper.find('input[type="checkbox"]')
    expect(checkbox.exists()).toBe(true)
  })

  it('hides time inputs when all-day is checked', async () => {
    const wrapper = mountModal()
    const allDayCheckbox = wrapper.find('input[type="checkbox"]')
    await allDayCheckbox.setValue(true)

    const timeInputs = wrapper.findAll('input[type="time"]')
    expect(timeInputs.length).toBe(0)
  })
})

describe('CalendarEventModal — close', () => {
  it('emits close when cancel button is clicked', async () => {
    const wrapper = mountModal()
    const cancelBtn = wrapper.findAll('button[type="button"]').find(
      b => b.text().includes('Annuler')
    )
    expect(cancelBtn).toBeTruthy()
    await cancelBtn!.trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})

describe('CalendarEventModal — delete', () => {
  it('calls deleteEvent and emits deleted on confirmed delete', async () => {
    const mockEvent = makeEvent()
    mockFetch.mockResolvedValue({ ok: true, json: async () => ({}) })

    const wrapper = mountModal({ event: mockEvent })
    const deleteBtn = wrapper.find('.calendar-danger-btn')
    await deleteBtn.trigger('click')

    await vi.waitFor(() => wrapper.emitted('deleted'), { timeout: 1000 })
    expect(wrapper.emitted('deleted')).toBeTruthy()
  })
})
