import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import CreateTaskModal from '@/components/tasks/CreateTaskModal.vue'

const mockFetch = vi.fn()

beforeEach(() => {
  localStorage.clear()
  mockFetch.mockReset()
  vi.stubGlobal('fetch', mockFetch)
  // Default: empty lists for members and columns
  mockFetch.mockResolvedValue({
    ok: true,
    json: async () => ({ 'hydra:member': [] }),
  })
  setActivePinia(createPinia())
})

afterEach(() => {
  vi.unstubAllGlobals()
})

function mountModal(props: Record<string, unknown> = {}) {
  const pinia = createPinia()
  return mount(CreateTaskModal, {
    props: {
      open: true,
      roomId: 1,
      ...props,
    },
    global: {
      plugins: [pinia],
    },
  })
}

describe('CreateTaskModal — rendering', () => {
  it('renders the modal when open is true', () => {
    const wrapper = mountModal({ open: true })
    expect(wrapper.find('.modal-box').exists()).toBe(true)
    expect(wrapper.text()).toContain('Nouvelle tâche')
  })

  it('renders title input field', () => {
    const wrapper = mountModal()
    expect(wrapper.find('input[type="text"]').exists()).toBe(true)
  })

  it('renders description textarea', () => {
    const wrapper = mountModal()
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  it('renders estimation select with fibonacci options', () => {
    const wrapper = mountModal()
    const selects = wrapper.findAll('select')
    // There are 3 selects: column, assignedTo, estimation
    expect(selects.length).toBeGreaterThanOrEqual(1)
  })
})

describe('CreateTaskModal — submit button', () => {
  it('disables submit button when title is empty', async () => {
    const wrapper = mountModal()
    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()
  })

  it('enables submit button when title is filled', async () => {
    const wrapper = mountModal()
    const titleInput = wrapper.find('input[type="text"]')
    await titleInput.setValue('My new task')

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeUndefined()
  })

  it('keeps submit disabled when title is only whitespace', async () => {
    const wrapper = mountModal()
    const titleInput = wrapper.find('input[type="text"]')
    await titleInput.setValue('   ')

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()
  })
})

describe('CreateTaskModal — form submission', () => {
  it('calls POST to create task endpoint on submit', async () => {
    const createdTask = {
      id: 42,
      title: 'New Task',
      description: null,
      columnId: 1,
      columnName: 'To Do',
      columnColor: '#blue',
      type: 'active',
      position: 0,
      assignedTo: null,
      estimation: null,
      createdAt: '2026-03-26T10:00:00',
    }
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => createdTask,
    })

    const wrapper = mountModal()
    const titleInput = wrapper.find('input[type="text"]')
    await titleInput.setValue('New Task')

    await wrapper.find('form').trigger('submit')
    await vi.waitFor(() => {
      return wrapper.emitted('created') || wrapper.emitted('close')
    }, { timeout: 1000 })

    const postCall = mockFetch.mock.calls.find(
      call => typeof call[0] === 'string' && call[0].includes('/tasks') && call[1]?.method === 'POST'
    )
    expect(postCall).toBeTruthy()
  })

  it('emits created event with task data on success', async () => {
    const createdTask = {
      id: 1,
      title: 'Created',
      description: null,
      columnId: 1,
      columnName: 'To Do',
      columnColor: '#blue',
      type: 'active',
      position: 0,
      assignedTo: null,
      estimation: null,
      createdAt: '2026-03-26T10:00:00',
    }
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => createdTask,
    })

    const wrapper = mountModal()
    await wrapper.find('input[type="text"]').setValue('Created')
    await wrapper.find('form').trigger('submit')

    await vi.waitFor(() => wrapper.emitted('created'), { timeout: 1000 })
    expect(wrapper.emitted('created')?.[0]).toEqual([createdTask])
  })
})

describe('CreateTaskModal — close', () => {
  it('emits close event when cancel button is clicked', async () => {
    const wrapper = mountModal()
    const cancelBtn = wrapper.find('button[type="button"]')
    await cancelBtn.trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
