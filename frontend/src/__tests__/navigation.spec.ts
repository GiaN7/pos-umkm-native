import { config } from '@vue/test-utils'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import App from '@/App.vue'

// Stub semua komponen halaman berat agar fokus ke logika tab
vi.mock('@/components/pos/PosInterface.vue', () => ({
  default: { name: 'PosInterfaceStub', template: '<div data-test="pos">POS PAGE</div>' },
}))
vi.mock('@/components/products/ProductList.vue', () => ({
  default: { name: 'ProductListStub', template: '<div data-test="products">PRODUCTS PAGE</div>' },
}))
vi.mock('@/components/dashboard/Dashboard.vue', () => ({
  default: { name: 'DashboardStub', template: '<div data-test="dashboard">DASHBOARD PAGE</div>' },
}))
vi.mock('@/components/categories/CategoryList.vue', () => ({
  default: { template: '<div>CATEGORIES</div>' },
}))
vi.mock('@/components/customers/CustomerList.vue', () => ({
  default: { template: '<div>CUSTOMERS</div>' },
}))
vi.mock('@/components/sales/SalesHistory.vue', () => ({
  default: { template: '<div>SALES</div>' },
}))
vi.mock('@/components/stock/StockCorrection.vue', () => ({
  default: { template: '<div>STOCK</div>' },
}))
vi.mock('@/components/reports/ReportSales.vue', () => ({
  default: { template: '<div>REPORT SALES</div>' },
}))
vi.mock('@/components/reports/ReportProfitLoss.vue', () => ({
  default: { template: '<div>PROFIT LOSS</div>' },
}))

// Mock API — user owner sudah login
const user = { id: 1, name: 'Owner', email: 'owner@pos.com', role: 'owner', status: true }
vi.mock('@/lib/api', () => ({
  default: {
    get: vi.fn((url: string) => {
      if (url === '/user') return Promise.resolve({ data: user })
      return Promise.resolve({ data: {} })
    }),
    post: vi.fn(() => Promise.resolve({ data: {} })),
  },
}))

async function clickButton(wrapper: any, label: string) {
  const btn = wrapper.findAll('button').find((b: any) => b.text().trim() === label)
  expect(btn, `tombol "${label}" tidak ditemukan`).toBeTruthy()
  await btn.trigger('click')
}

describe('Navigasi tab App.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.setItem('token', 'fake-token')
    localStorage.setItem('user', JSON.stringify(user))
  })

  it('Dashboard → Products → Dashboard: kembali ke Dashboard', async () => {
    const wrapper = shallowMountSafe()
    await flush()

    // Sudah di Dashboard
    expect(wrapper.find('[data-test="dashboard"]').exists()).toBe(true)

    // Pindah ke Products
    await clickButton(wrapper, 'Products')
    expect(wrapper.find('[data-test="products"]').exists()).toBe(true)
    expect(wrapper.find('[data-test="dashboard"]').exists()).toBe(false)

    // Kembali ke Dashboard — INI BUG YANG DILAPORKAN
    await clickButton(wrapper, 'Dashboard')
    expect(
      wrapper.find('[data-test="dashboard"]').exists(),
      'Dashboard tidak tampil setelah kembali dari tab lain'
    ).toBe(true)
    expect(wrapper.find('[data-test="pos"]').exists(), 'POS malah tampil').toBe(false)
  })

  it('Dashboard → POS → Dashboard → Sales → Dashboard', async () => {
    const wrapper = shallowMountSafe()
    await flush()

    await clickButton(wrapper, 'POS')
    expect(wrapper.find('[data-test="pos"]').exists()).toBe(true)

    await clickButton(wrapper, 'Dashboard')
    expect(wrapper.find('[data-test="dashboard"]').exists()).toBe(true)

    await clickButton(wrapper, 'Sales')
    expect(wrapper.text()).toContain('SALES')

    await clickButton(wrapper, 'Dashboard')
    expect(
      wrapper.find('[data-test="dashboard"]').exists(),
      'Dashboard tidak tampil setelah dari Sales'
    ).toBe(true)
  })
})

// helpers — mount dengan global plugins
function shallowMountSafe() {
  // import dinamis agar vi.mock terpakai lebih dulu
  // eslint-disable-next-line @typescript-eslint/no-var-requires
  const { mount } = require('@vue/test-utils')
  return mount(App, {
    global: { plugins: [createPinia()] },
  })
}

async function flush() {
  const { nextTick } = await import('vue')
  await nextTick()
  await nextTick()
}
