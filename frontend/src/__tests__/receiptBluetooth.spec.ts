import { describe, expect, it } from 'vitest'
import { encodeReceipt } from '@/lib/receiptBluetooth'

describe('encodeReceipt', () => {
  it('encodes an ESC/POS receipt with sale totals and line items', () => {
    const data = encodeReceipt({
      no_transaksi: 'TRX-20261003-001',
      created_at: '2026-10-03T10:00:00.000Z',
      subtotal: '30000',
      diskon: '2000',
      ppn: '0',
      total: '28000',
      bayar: '50000',
      kembali: '22000',
      payment_method: 'tunai',
      user: { name: 'Kasir' },
      items: [{
        product: { name: 'Kopi Arabika', harga_jual: '15000' },
        qty: 2,
        diskon: 0,
        subtotal: '30000',
      }],
    }, 'esc-pos', 'default')

    expect(data).toBeInstanceOf(Uint8Array)
    expect(data.length).toBeGreaterThan(0)
    expect(new TextDecoder().decode(data)).toContain('TRX-20261003-001')
    expect(new TextDecoder().decode(data)).toContain('Kopi Arabika')
  })
})
