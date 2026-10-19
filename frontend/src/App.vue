<script setup lang="ts">
import { computed, onMounted, ref, type Component } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PosInterface from '@/components/pos/PosInterface.vue'
import CategoryList from '@/components/categories/CategoryList.vue'
import ProductList from '@/components/products/ProductList.vue'
import CustomerList from '@/components/customers/CustomerList.vue'
import SalesHistory from '@/components/sales/SalesHistory.vue'
import StockCorrection from '@/components/stock/StockCorrection.vue'
import Dashboard from '@/components/dashboard/Dashboard.vue'
import ReportSales from '@/components/reports/ReportSales.vue'
import ReportProfitLoss from '@/components/reports/ReportProfitLoss.vue'
import AccountSettings from '@/components/account/AccountSettings.vue'
import { LayoutDashboard, LogOut, MoreHorizontal, Package, ReceiptText, Settings, ShoppingBag } from 'lucide-vue-next'

const auth = useAuthStore()

const email = ref('')
const password = ref('')
const message = ref('')
const isError = ref(false)
const isMoreOpen = ref(false)

type Tab =
  | 'dashboard' | 'pos' | 'products' | 'categories'
  | 'customers' | 'sales' | 'stock' | 'reports' | 'profit-loss' | 'account'

const activeTab = ref<Tab>('dashboard')

const isOwner = computed(() => auth.user?.role === 'owner')

const tabs: { id: Tab; label: string; ownerOnly: boolean; icon: Component }[] = [
  { id: 'dashboard', label: 'Dashboard', ownerOnly: true, icon: LayoutDashboard },
  { id: 'pos', label: 'POS', ownerOnly: false, icon: ShoppingBag },
  { id: 'products', label: 'Products', ownerOnly: true, icon: Package },
  { id: 'categories', label: 'Categories', ownerOnly: true, icon: Package },
  { id: 'customers', label: 'Customers', ownerOnly: true, icon: ShoppingBag },
  { id: 'sales', label: 'Sales', ownerOnly: false, icon: ReceiptText },
  { id: 'stock', label: 'Stock', ownerOnly: true, icon: Package },
  { id: 'reports', label: 'Sales Report', ownerOnly: true, icon: ReceiptText },
  { id: 'profit-loss', label: 'Profit/Loss', ownerOnly: true, icon: LayoutDashboard },
  { id: 'account', label: 'Akun', ownerOnly: true, icon: Settings },
]

const primaryTabs = computed(() =>
  tabs.filter((tab) =>
    (!tab.ownerOnly || isOwner.value) &&
    ['dashboard', 'pos', 'products', 'sales'].includes(tab.id)
  )
)
const moreTabs = computed(() =>
  tabs.filter((tab) =>
    (!tab.ownerOnly || isOwner.value) &&
    !['dashboard', 'pos', 'products', 'sales'].includes(tab.id)
  )
)
const isMoreActive = computed(() => moreTabs.value.some((tab) => tab.id === activeTab.value))

function canAccess(tab: string): boolean {
  const found = tabs.find((item) => item.id === tab)
  return !found || isOwner.value || !found.ownerOnly
}

function selectTab(tab: Tab) {
  activeTab.value = tab
  isMoreOpen.value = false
}

onMounted(async () => {
  if (auth.isAuthenticated() && !auth.user) {
    await auth.fetchUser()
  }
  activeTab.value = isOwner.value ? 'dashboard' : 'pos'
})

async function handleLogin() {
  isError.value = false
  try {
    await auth.login(email.value, password.value)
    password.value = ''
    message.value = `Selamat datang, ${auth.user?.name}`
    activeTab.value = isOwner.value ? 'dashboard' : 'pos'
  } catch (err: any) {
    isError.value = true
    message.value = err.response?.data?.message || 'Login gagal. Periksa email dan password Anda.'
  }
}

async function handleLogout() {
  email.value = ''
  password.value = ''
  message.value = ''
  isError.value = false
  await auth.logout()
  activeTab.value = 'dashboard'
}
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <section
      v-if="!auth.isAuthenticated()"
      class="login-scene flex min-h-screen items-center justify-center px-4 py-10"
    >
      <div class="w-full max-w-[430px]">
        <div class="mb-8 text-center">
          <img
            src="/logo.png"
            alt="Mitra Tech"
            class="mx-auto mb-4 h-36 w-36 object-contain"
          />
          <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-black">Toko perlengkapan listrik</p>
          <h1 class="text-3xl font-bold tracking-tight text-foreground">Mitra Tech</h1>
          <p class="mt-2 text-sm text-muted-foreground">Kasir sederhana untuk usaha yang tumbuh.</p>
        </div>

        <div class="warm-card rounded-[24px] p-6 sm:p-8">
          <div class="mb-6 text-center">
            <h2 class="text-xl font-bold">Selamat datang kembali</h2>
            <p class="mt-1 text-sm text-muted-foreground">Masuk untuk mulai mengelola tokomu.</p>
          </div>

          <form class="space-y-4" @submit.prevent="handleLogin">
            <label class="block space-y-2 text-sm font-semibold" for="email">
              <span>Email</span>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="username"
                required
                class="warm-input h-12 w-full rounded-xl px-4 text-sm font-normal"
                placeholder="nama@toko.com"
              />
            </label>

            <label class="block space-y-2 text-sm font-semibold" for="password">
              <span>Password</span>
              <input
                id="password"
                v-model="password"
                type="password"
                autocomplete="current-password"
                required
                class="warm-input h-12 w-full rounded-xl px-4 text-sm font-normal"
                placeholder="Masukkan password"
              />
            </label>

            <button
              type="submit"
              class="warm-primary mt-2 flex h-[52px] w-full items-center justify-center rounded-2xl px-5 text-sm font-semibold transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60"
              :disabled="auth.loading"
            >
              {{ auth.loading ? 'Memeriksa akun…' : 'Masuk ke tokomu' }}
            </button>
          </form>

          <div
            v-if="message"
            class="mt-4 rounded-xl px-4 py-3 text-sm"
            :class="isError ? 'bg-error-container text-on-error-container' : 'bg-green-50 text-green-800'"
            role="status"
          >
            {{ message }}
          </div>

        </div>

        <p class="mt-6 text-center text-xs text-muted-foreground">Dibuat untuk usaha lokal.</p>
      </div>
    </section>

    <div v-else class="app-shell mx-auto flex min-h-screen w-full max-w-[768px] flex-col">
      <header class="app-header sticky top-0 z-30 px-4 sm:px-6">
        <div class="flex min-h-[72px] items-center justify-between gap-3">
          <div class="flex min-w-0 items-center gap-3">
            <img
              src="/logo.png"
              alt=""
              class="h-11 w-11 shrink-0 object-contain"
            />
            <div class="min-w-0">
              <p class="truncate text-sm font-bold tracking-tight">Mitra Tech</p>
              <p class="truncate text-xs text-muted-foreground">{{ activeTab === 'pos' ? 'Meja kasir' : 'Catatan tokomu' }}</p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <div class="hidden text-right sm:block">
              <p class="max-w-32 truncate text-sm font-semibold">{{ auth.user?.name }}</p>
              <p class="text-xs capitalize text-muted-foreground">{{ auth.user?.role }}</p>
            </div>
            <button
              type="button"
              class="icon-button flex h-11 w-11 items-center justify-center rounded-full"
              aria-label="Logout"
              title="Logout"
              @click="handleLogout"
            >
              <LogOut class="h-4 w-4" />
            </button>
          </div>
        </div>
        <div class="h-px bg-border"></div>
      </header>

      <main class="min-h-0 flex-1 px-3 pb-28 pt-4 sm:px-6 sm:pt-6">
        <div v-if="canAccess(activeTab)">
          <Dashboard v-if="activeTab === 'dashboard'" />
          <PosInterface v-else-if="activeTab === 'pos'" />
          <div v-else class="mx-auto max-w-[720px]">
            <ProductList v-if="activeTab === 'products'" />
            <CategoryList v-else-if="activeTab === 'categories'" />
            <CustomerList v-else-if="activeTab === 'customers'" />
            <SalesHistory v-else-if="activeTab === 'sales'" />
            <StockCorrection v-else-if="activeTab === 'stock'" />
            <ReportSales v-else-if="activeTab === 'reports'" />
            <ReportProfitLoss v-else-if="activeTab === 'profit-loss'" />
            <AccountSettings v-else />
          </div>
        </div>
        <PosInterface v-else />
      </main>

      <Transition name="menu-rise">
        <div
          v-if="isMoreOpen"
          class="more-menu fixed bottom-[calc(76px+env(safe-area-inset-bottom))] left-1/2 z-40 w-[calc(100%-2rem)] max-w-[720px] -translate-x-1/2 rounded-2xl p-2"
        >
          <button
            v-for="tab in moreTabs"
            :key="tab.id"
            type="button"
            class="nav-menu-item flex min-h-12 w-full items-center gap-3 rounded-xl px-4 text-left text-sm font-medium"
            :class="activeTab === tab.id ? 'nav-menu-item-active' : ''"
            @click="selectTab(tab.id)"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </div>
      </Transition>

      <nav class="bottom-nav fixed inset-x-0 bottom-0 z-30 px-2 pb-[env(safe-area-inset-bottom)]" aria-label="Navigasi utama">
        <div class="mx-auto flex min-h-[72px] max-w-[768px] items-center justify-around gap-1 px-1">
          <button
            v-for="tab in primaryTabs"
            :key="tab.id"
            type="button"
            class="bottom-nav-item flex min-h-14 min-w-0 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-semibold sm:text-xs"
            :class="activeTab === tab.id ? 'bottom-nav-item-active' : ''"
            :aria-current="activeTab === tab.id ? 'page' : undefined"
            @click="selectTab(tab.id)"
          >
            <component :is="tab.icon" class="h-5 w-5" />
            <span class="truncate">{{ tab.label }}</span>
          </button>
          <button
            v-if="moreTabs.length"
            type="button"
            class="bottom-nav-item flex min-h-14 min-w-0 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-1 text-[10px] font-semibold sm:text-xs"
            :class="isMoreActive || isMoreOpen ? 'bottom-nav-item-active' : ''"
            :aria-expanded="isMoreOpen"
            @click="isMoreOpen = !isMoreOpen"
          >
            <MoreHorizontal class="h-5 w-5" />
            <span>Lainnya</span>
          </button>
        </div>
      </nav>
    </div>
  </div>
</template>
