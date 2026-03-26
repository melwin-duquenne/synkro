import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TaskCard from '@/components/tasks/TaskCard.vue'

interface Task {
  id: number
  title: string
  description: string | null
  columnId: number
  columnName: string
  columnColor: string
  type: 'active' | 'archived'
  position: number
  assignedTo: { id: number; displayName: string } | null
  estimation: number | null
  createdAt: string
}

function makeTask(overrides: Partial<Task> = {}): Task {
  return {
    id: 1,
    title: 'Default Task',
    description: null,
    columnId: 1,
    columnName: 'To Do',
    columnColor: '#3b82f6',
    type: 'active',
    position: 0,
    assignedTo: null,
    estimation: null,
    createdAt: '2026-03-26T10:00:00',
    ...overrides,
  }
}

describe('TaskCard — rendering', () => {
  it('renders the task title', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ title: 'Fix the bug' }) },
    })
    expect(wrapper.text()).toContain('Fix the bug')
  })

  it('renders description when provided', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ description: 'Detailed description here' }) },
    })
    expect(wrapper.text()).toContain('Detailed description here')
  })

  it('does not render description section when null', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ description: null }) },
    })
    expect(wrapper.find('p').exists()).toBe(false)
  })

  it('renders assignee initials from displayName', () => {
    const wrapper = mount(TaskCard, {
      props: {
        task: makeTask({ assignedTo: { id: 1, displayName: 'John Doe' } }),
      },
    })
    expect(wrapper.text()).toContain('JD')
  })

  it('renders single initial correctly', () => {
    const wrapper = mount(TaskCard, {
      props: {
        task: makeTask({ assignedTo: { id: 1, displayName: 'Alice' } }),
      },
    })
    expect(wrapper.text()).toContain('A')
  })

  it('renders estimation badge when estimation is set', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ estimation: 8 }) },
    })
    expect(wrapper.text()).toContain('8pt')
  })

  it('does not render estimation badge when null', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ estimation: null }) },
    })
    expect(wrapper.text()).not.toContain('pt')
  })

  it('shows archived badge when type is archived', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ type: 'archived' }) },
    })
    expect(wrapper.text()).toContain('Archivée')
  })

  it('does not show archived badge for active tasks', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ type: 'active' }) },
    })
    expect(wrapper.text()).not.toContain('Archivée')
  })
})

describe('TaskCard — CSS classes', () => {
  it('applies opacity-60 class when task is archived', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ type: 'archived' }) },
    })
    expect(wrapper.classes()).toContain('opacity-60')
  })

  it('does not apply opacity-60 class for active tasks', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ type: 'active' }) },
    })
    expect(wrapper.classes()).not.toContain('opacity-60')
  })
})

describe('TaskCard — events', () => {
  it('emits click event when card is clicked', async () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask() },
    })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('emits dragstart event', async () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask(), draggable: true },
    })
    await wrapper.trigger('dragstart')
    expect(wrapper.emitted('dragstart')).toBeTruthy()
  })

  it('emits dragend event', async () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask(), draggable: true },
    })
    await wrapper.trigger('dragend')
    expect(wrapper.emitted('dragend')).toBeTruthy()
  })
})

describe('TaskCard — date formatting', () => {
  it('renders a formatted creation date', () => {
    const wrapper = mount(TaskCard, {
      props: { task: makeTask({ createdAt: '2026-03-26T10:00:00' }) },
    })
    // Should display some date text (locale-dependent, just check it's not empty)
    const dateEl = wrapper.find('.text-xs.text-base-content\\/50')
    expect(dateEl.text()).toBeTruthy()
  })
})
