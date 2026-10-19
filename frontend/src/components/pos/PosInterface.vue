<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Search } from 'lucide-vue-next'
import api from '@/lib/api'
import PosProductGrid from './PosProductGrid.vue'
import PosCart from './PosCart.vue'
import PosPaymentModal from './PosPaymentModal.vue'
import PosReceipt from './PosReceipt.vue'

interface Product {
  id: number
  category_id: number | null
  category_name: string | null
  name: string
  sku: string
  barcode: string | null
  harga_beli: string
  harga_jual: string
  stok: number
  stok_minimal: number
  status: boolean
  image: string | null
}

interface CartItem {
  product: Product
  qty: number
  diskon: number
}

interface Sale {
  id: number
  no_transaksi: string
  created_at: string
  subtotal: string
  diskon: string
  ppn: string
  total: string
  bayar: string
  kembali: string
  payment_method: string
  user: {
    name: string
  }
  items: Array<{
    product: {
      name: string
      harga_jual: string
    }
    qty: number
    diskon: number
    subtotal: string
  }>
}

const products = ref<Product[]>([])
const cart = ref<CartItem[]>([])
const search = ref('')
const loading = ref(false)
const showPaymentModal = ref(false)
const showReceipt = ref(false)
const lastSale = ref<Sale | null>(null)

const cartTotal = computed(() => {
  return cart.value.reduce((sum, item) => {
    const subtotal = Number(item.product.harga_jual) * item.qty - item.diskon
    return sum + subtotal
  }, 0)
})

const cartCount = computed(() => {
  return cart.value.reduce((sum, item) => sum + item.qty, 0)
})

async function fetchProducts() {
  loading.value = true
  try {
    const response = await api.get('/products', {
      params: { search: search.value || undefined }
    })
    products.value = response.data.data
  } catch (err: any) {
    console.error('Failed to fetch products', err)
  } finally {
    loading.value = false
  }
}

function addToCart(product: Product) {
  const existing = cart.value.find(item => item.product.id === product.id)
  if (existing) {
    if (existing.qty < product.stok) {
      existing.qty++
    }
  } else {
    if (product.stok > 0) {
      cart.value.push({ product, qty: 1, diskon: 0 })
    }
  }
}

function removeFromCart(index: number) {
  cart.value.splice(index, 1)
}

function updateQty(index: number, qty: number) {
  if (qty <= 0) {
    removeFromCart(index)
  } else if (qty <= cart.value[index].product.stok) {
    cart.value[index].qty = qty
  }
}

function updateDiskon(index: number, diskon: number) {
  cart.value[index].diskon = diskon
}

function clearCart() {
  cart.value = []
}

function openPayment() {
  showPaymentModal.value = true
}

function closePayment() {
  showPaymentModal.value = false
}

async function handlePay(paymentMethod: string, bayar: number) {
  try {
    const saleItems = cart.value.map(item => ({
      product_id: item.product.id,
      qty: item.qty,
      diskon: item.diskon,
    }))

    const response = await api.post('/sales', {
      items: saleItems,
      bayar: bayar,
      payment_method: paymentMethod,
    })

    lastSale.value = response.data.sale
    showPaymentModal.value = false
    showReceipt.value = true
    clearCart()
    fetchProducts() // Refresh stock
  } catch (err: any) {
    alert(err.response?.data?.message || 'Transaksi gagal')
  }
}

function closeReceipt() {
  showReceipt.value = false
  lastSale.value = null
}

onMounted(fetchProducts)
</script>

<template>
  <div class="pos-layout">
    <section class="pos-products warm-card flex min-w-0 flex-col overflow-hidden rounded-[22px]">
      <div class="border-b border-border p-4">
        <label class="warm-input flex h-12 items-center gap-3 rounded-2xl px-4">
          <Search class="h-4 w-4 shrink-0 text-muted-foreground" />
          <input
            v-model="search"
            @input="fetchProducts"
            type="search"
            class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            placeholder="Cari nama atau kode produk"
          />
        </label>
        <p class="mt-3 px-1 text-xs text-muted-foreground">Pilih produk untuk menambahkannya ke keranjang.</p>
      </div>
      <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4">
        <PosProductGrid
          :products="products"
          :loading="loading"
          @add-to-cart="addToCart"
        />
      </div>
    </section>

    <section class="pos-cart warm-card flex min-h-[380px] min-w-0 flex-col overflow-hidden rounded-[22px]">
      <PosCart
        :items="cart"
        :total="cartTotal"
        :count="cartCount"
        @update-qty="updateQty"
        @update-diskon="updateDiskon"
        @remove="removeFromCart"
        @clear="clearCart"
        @pay="openPayment"
      />
    </section>

    <!-- Payment Modal -->
    <PosPaymentModal
      :show="showPaymentModal"
      :total="cartTotal"
      @close="closePayment"
      @pay="handlePay"
    />

    <!-- Receipt Modal -->
    <PosReceipt
      :show="showReceipt"
      :sale="lastSale"
      @close="closeReceipt"
    />
  </div>
</template>

<style scoped>
.pos-layout {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
}

.pos-products {
  min-height: 390px;
}

@media (min-width: 700px) {
  .pos-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(275px, 0.85fr);
    height: calc(100dvh - 180px);
    min-height: 500px;
  }

  .pos-products {
    min-height: 0;
  }
}
</style>
