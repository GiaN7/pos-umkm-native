<script setup lang="ts">
import { computed, ref } from 'vue'
import WebBluetoothReceiptPrinter, {
  type ConnectedReceiptPrinter,
} from '@point-of-sale/webbluetooth-receipt-printer'
import { encodeReceipt, loadReceiptLogo, type ReceiptSale } from '@/lib/receiptBluetooth'

const props = defineProps<{
  sale: ReceiptSale | null
  show: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const bluetoothAvailable = computed(() =>
  typeof window !== 'undefined' && window.isSecureContext && 'bluetooth' in navigator
)
const bluetoothBusy = ref(false)
const bluetoothStatus = ref('')
let bluetoothPrinter: WebBluetoothReceiptPrinter | null = null
const connectedPrinter = ref<ConnectedReceiptPrinter | null>(null)
let disconnectedDuringPrint = false

function formatRupiah(value: string | number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(value))
}

function formatDate(date: string): string {
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function handlePrint() {
  window.print()
}

async function handleBluetoothPrint() {
  if (!props.sale || !bluetoothAvailable.value) {
    bluetoothStatus.value = 'Bluetooth langsung memerlukan Chrome/Edge pada koneksi aman (HTTPS).'
    console.warn('[Bluetooth printer] Attempt blocked', {
      hasSale: Boolean(props.sale),
      secureContext: typeof window !== 'undefined' && window.isSecureContext,
      bluetoothApiAvailable: typeof navigator !== 'undefined' && 'bluetooth' in navigator,
    })
    return
  }

  bluetoothBusy.value = true
  bluetoothStatus.value = 'Pilih printer pada daftar Bluetooth…'
  let bluetoothStage = 'checking connection'

  console.info('[Bluetooth printer] Attempt started', {
    secureContext: window.isSecureContext,
    bluetoothApiAvailable: 'bluetooth' in navigator,
    userAgent: navigator.userAgent,
  })

  try {
    if (!bluetoothPrinter || !connectedPrinter.value) {
      const printer = new WebBluetoothReceiptPrinter()
      let resolveConnected: ((device: ConnectedReceiptPrinter) => void) | undefined
      const connectedEvent = new Promise<ConnectedReceiptPrinter>((resolve) => {
        resolveConnected = resolve
      })

      bluetoothStage = 'waiting for printer selection and BLE connection'
      console.info('[Bluetooth printer] Opening device chooser')
      printer.addEventListener('connected', (device) => {
        console.info('[Bluetooth printer] Connected event received', {
          name: device.name,
          language: device.language,
          codepageMapping: device.codepageMapping,
        })
        resolveConnected?.(device)
      })
      printer.addEventListener('disconnected', () => {
        console.warn('[Bluetooth printer] Disconnected event received')
        if (bluetoothPrinter === printer) {
          bluetoothPrinter = null
          connectedPrinter.value = null
        }
        disconnectedDuringPrint = true
      })

      await printer.connect()
      bluetoothStage = 'waiting for connected event'
      let connectionTimeout: ReturnType<typeof setTimeout> | undefined
      const selectedPrinter = await Promise.race([
        connectedEvent,
        new Promise<never>((_, reject) => {
          connectionTimeout = setTimeout(() => {
            reject(new Error('Printer belum mengirim event koneksi dalam 5 detik. Pastikan printer menyala dan mendukung BLE ESC/POS.'))
          }, 5000)
        }),
      ]).finally(() => {
        if (connectionTimeout) clearTimeout(connectionTimeout)
      })

      bluetoothPrinter = printer
      connectedPrinter.value = selectedPrinter
    }

    if (!bluetoothPrinter || !connectedPrinter.value) {
      throw new Error('Koneksi Bluetooth terputus. Coba hubungkan printer lagi.')
    }

    disconnectedDuringPrint = false
    const printer = bluetoothPrinter
    const device = connectedPrinter.value
    bluetoothStage = 'encoding receipt'
    let logo: ImageData | undefined

    try {
      logo = await loadReceiptLogo()
      console.info('[Bluetooth printer] Receipt logo loaded', {
        width: logo.width,
        height: logo.height,
      })
    } catch (error) {
      console.warn('[Bluetooth printer] Could not load receipt logo; printing without it', error)
    }

    const commands = encodeReceipt(props.sale, device.language, device.codepageMapping, logo)
    bluetoothStage = 'sending receipt data'
    console.info('[Bluetooth printer] Sending print data', {
      name: device.name,
      language: device.language,
      byteLength: commands.length,
    })
    await printer.print(commands)

    if (disconnectedDuringPrint) {
      throw new Error('Koneksi terputus saat mencetak. Periksa struk sebelum mencoba cetak ulang.')
    }

    console.info('[Bluetooth printer] Print data sent successfully', { name: device.name })
    bluetoothStatus.value = `Struk berhasil dikirim ke ${device.name}.`
  } catch (error) {
    console.error('[Bluetooth printer] Attempt failed', {
      stage: bluetoothStage,
      message: error instanceof Error ? error.message : String(error),
      error,
    })
    bluetoothStatus.value = error instanceof Error
      ? error.message
      : 'Gagal mencetak melalui Bluetooth. Gunakan cetak sistem sebagai alternatif.'
  } finally {
    bluetoothBusy.value = false
  }
}

function handleClose() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show && sale" class="receipt-print-root fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
      <div class="receipt-dialog my-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="receipt-modal-header flex items-center justify-between border-b border-border px-4 py-3">
          <div>
            <h2 class="text-base font-bold text-foreground">Struk pembayaran</h2>
            <p class="text-xs text-muted-foreground">Hubungkan lewat Bluetooth, atau gunakan dialog cetak sistem.</p>
          </div>
          <button
            type="button"
            aria-label="Tutup pratinjau struk"
            class="icon-button flex h-10 w-10 items-center justify-center rounded-full text-muted-foreground"
            @click="handleClose"
          >
            ✕
          </button>
        </div>

        <div class="receipt-content mx-auto w-full max-w-[58mm] px-4 py-5 font-mono text-[13px] leading-snug text-black">
          <header class="mb-4 text-center">
            <img src="/logo.png" alt="Mitra Tech" class="mx-auto mb-2 h-20 w-20 object-contain" />
            <h3 class="text-lg font-bold tracking-wide">Mitra Tech</h3>
            <p class="mt-1 text-xs">Terima kasih sudah berbelanja</p>
          </header>

          <div class="receipt-divider"></div>

          <div class="space-y-1 py-3 text-xs">
            <div class="flex justify-between gap-3">
              <span class="shrink-0">No. transaksi</span>
              <span class="break-all text-right font-semibold">{{ sale.no_transaksi }}</span>
            </div>
            <div class="flex justify-between gap-3">
              <span class="shrink-0">Tanggal</span>
              <span class="text-right">{{ formatDate(sale.created_at) }}</span>
            </div>
            <div class="flex justify-between gap-3">
              <span class="shrink-0">Kasir</span>
              <span class="text-right">{{ sale.user.name }}</span>
            </div>
          </div>

          <div class="receipt-divider"></div>

          <div class="space-y-3 py-3">
            <div v-for="(item, index) in sale.items" :key="`${item.product.name}-${index}`" class="space-y-1 text-xs">
              <p class="break-words font-semibold">{{ item.product.name }}</p>
              <div class="flex justify-between gap-3">
                <span>{{ item.qty }} x {{ formatRupiah(item.product.harga_jual) }}</span>
                <span class="shrink-0 text-right">{{ formatRupiah(item.subtotal) }}</span>
              </div>
              <div v-if="item.diskon > 0" class="flex justify-between gap-3 text-muted-foreground">
                <span>Diskon item</span>
                <span class="shrink-0">-{{ formatRupiah(item.diskon) }}</span>
              </div>
            </div>
          </div>

          <div class="receipt-divider"></div>

          <div class="space-y-1.5 py-3 text-xs">
            <div class="flex justify-between gap-3">
              <span>Subtotal</span>
              <span class="shrink-0 text-right">{{ formatRupiah(sale.subtotal) }}</span>
            </div>
            <div v-if="Number(sale.diskon) > 0" class="flex justify-between gap-3">
              <span>Diskon</span>
              <span class="shrink-0 text-right">-{{ formatRupiah(sale.diskon) }}</span>
            </div>
            <div v-if="Number(sale.ppn) > 0" class="flex justify-between gap-3">
              <span>PPN</span>
              <span class="shrink-0 text-right">{{ formatRupiah(sale.ppn) }}</span>
            </div>
            <div class="receipt-divider my-2"></div>
            <div class="flex justify-between gap-3 text-sm font-bold">
              <span>Total</span>
              <span class="shrink-0 text-right">{{ formatRupiah(sale.total) }}</span>
            </div>
            <div class="flex justify-between gap-3 pt-1">
              <span>Bayar ({{ sale.payment_method.toUpperCase() }})</span>
              <span class="shrink-0 text-right">{{ formatRupiah(sale.bayar) }}</span>
            </div>
            <div class="flex justify-between gap-3 font-semibold">
              <span>Kembali</span>
              <span class="shrink-0 text-right">{{ formatRupiah(sale.kembali) }}</span>
            </div>
          </div>

          <div class="receipt-divider"></div>

          <footer class="pt-3 text-center text-xs">
            <p>Semoga harimu menyenangkan!</p>
            <p class="mt-1">Simpan struk ini sebagai bukti pembayaran.</p>
          </footer>
        </div>

        <div class="receipt-actions flex gap-2 border-t border-border p-4">
          <div class="w-full space-y-3">
            <p
              v-if="bluetoothStatus || !bluetoothAvailable"
              class="text-center text-xs text-muted-foreground"
              role="status"
              aria-live="polite"
            >
              {{ bluetoothStatus || 'Bluetooth langsung tidak tersedia di browser ini. Gunakan cetak sistem.' }}
            </p>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                :disabled="bluetoothBusy || !bluetoothAvailable"
                class="min-h-11 rounded-xl bg-primary px-3 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                @click="handleBluetoothPrint"
              >
                {{ bluetoothBusy ? 'Mengirim…' : connectedPrinter ? 'Cetak Bluetooth' : 'Hubungkan & cetak' }}
              </button>
              <button
                type="button"
                class="warm-button-secondary min-h-11 rounded-xl px-3 text-xs font-semibold"
                @click="handlePrint"
              >
                Cetak sistem
              </button>
            </div>
            <button
              type="button"
              class="w-full py-1 text-xs font-semibold text-muted-foreground"
              @click="handleClose"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.receipt-divider {
  border-top: 1px dashed #737373;
}

.receipt-modal-header,
.receipt-actions {
  font-family: var(--font-sans);
}

.warm-button-secondary {
  border: 1px solid var(--border);
  background: white;
  color: var(--foreground);
}
</style>
