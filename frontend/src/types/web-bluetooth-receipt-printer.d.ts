declare module '@point-of-sale/webbluetooth-receipt-printer' {
  export interface ConnectedReceiptPrinter {
    type: 'bluetooth'
    name: string
    id: string
    language: string
    codepageMapping: string
    columns?: number
  }

  export default class WebBluetoothReceiptPrinter {
    connect(): Promise<void>
    print(commands: Uint8Array): Promise<void>
    addEventListener(
      event: 'connected',
      callback: (printer: ConnectedReceiptPrinter) => void,
    ): void
    addEventListener(event: 'disconnected', callback: () => void): void
  }
}
