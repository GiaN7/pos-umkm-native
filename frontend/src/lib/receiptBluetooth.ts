import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder'

export interface ReceiptSale {
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

function normalizeLanguage(language: string): 'esc-pos' | 'star-prnt' | 'star-line' {
  if (language === 'star-prnt' || language === 'star-line') return language
  return 'esc-pos'
}

function normalizeCodepageMapping(mapping: string): 'epson' | 'star' | 'mpt' | 'xprinter' {
  if (mapping === 'star' || mapping === 'mpt' || mapping === 'xprinter') return mapping
  return 'epson'
}

export async function loadReceiptLogo(): Promise<ImageData> {
  const logo = new Image()
  logo.src = '/logo.png'
  await logo.decode()

  const canvas = document.createElement('canvas')
  canvas.width = logo.naturalWidth
  canvas.height = logo.naturalHeight
  const context = canvas.getContext('2d')

  if (!context) {
    throw new Error('Tidak dapat memproses logo struk.')
  }

  context.drawImage(logo, 0, 0)
  return context.getImageData(0, 0, canvas.width, canvas.height)
}

function formatRow(label: string, value: string, width = 32): string {
  const right = Array.from(value).slice(-(width - 2)).join('')
  const leftWidth = Math.max(1, width - right.length - 1)
  const left = Array.from(label).slice(0, leftWidth).join('')
  return `${left}${' '.repeat(Math.max(1, width - left.length - right.length))}${right}`
}

export function encodeReceipt(
  sale: ReceiptSale,
  language: string,
  codepageMapping: string,
  logo?: ImageData,
): Uint8Array {
  const encoder = new ReceiptPrinterEncoder({
    columns: 32,
    language: normalizeLanguage(language),
    codepageMapping: normalizeCodepageMapping(codepageMapping),
  })

  encoder.initialize()

  if (logo) {
    encoder.align('center').image(logo, {
      width: 240,
      mode: 'raster',
      algorithm: 'threshold',
      threshold: 160,
    }).newline()
  }

  encoder
    .align('center')
    .bold()
    .line('MITRA TECH')
    .bold(false)
    .line('STRUK PEMBAYARAN')
    .align('left')
    .line('-'.repeat(32))
    .line(`No: ${sale.no_transaksi}`)
    .line(`Tanggal: ${formatDate(sale.created_at)}`)
    .line(`Kasir: ${sale.user.name}`)
    .line('-'.repeat(32))

  for (const item of sale.items) {
    encoder
      .bold()
      .line(item.product.name)
      .bold(false)
      .line(formatRow(
        `${item.qty} x ${formatRupiah(item.product.harga_jual)}`,
        formatRupiah(item.subtotal),
      ))

    if (item.diskon > 0) {
      encoder.line(formatRow('Diskon item', `-${formatRupiah(item.diskon)}`))
    }
  }

  encoder
    .line('-'.repeat(32))
    .line(formatRow('Subtotal', formatRupiah(sale.subtotal)))

  if (Number(sale.diskon) > 0) {
    encoder.line(formatRow('Diskon', `-${formatRupiah(sale.diskon)}`))
  }

  if (Number(sale.ppn) > 0) {
    encoder.line(formatRow('PPN', formatRupiah(sale.ppn)))
  }

  encoder
    .bold()
    .line(formatRow('TOTAL', formatRupiah(sale.total)))
    .bold(false)
    .line(formatRow(`Bayar (${sale.payment_method.toUpperCase()})`, formatRupiah(sale.bayar)))
    .line(formatRow('Kembali', formatRupiah(sale.kembali)))
    .line('-'.repeat(32))
    .align('center')
    .line('Terima kasih sudah berbelanja!')
    .newline(3)

  return encoder.encode()
}
