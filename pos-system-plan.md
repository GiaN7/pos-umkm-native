# POS System — Rencana Lengkap (Revisi UMKM)

> **Target:** UMKM (toko listrik, toko kopi, retail, dll)
> **Backend:** Laravel 12 + PHP 8.2+
> **Frontend:** Vue 3 + TypeScript + shadcn-vue
> **Deployment:** Native VPS (Nginx + PHP-FPM)

---

## 1. Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Vue 3)                      │
│              shadcn-admin + Custom POS UI                │
│         Vite + TypeScript + Tailwind CSS                 │
└──────────────────────┬──────────────────────────────────┘
                       │ REST API (JSON)
┌──────────────────────┴──────────────────────────────────┐
│                   Backend (Laravel)                      │
│              API Resource + Sanctum Auth                 │
│              SQLite / MySQL + database cache            │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Vue 3, TypeScript, Vite |
| **UI Framework** | shadcn-vue (port dari shadcn-admin) |
| **Styling** | Tailwind CSS |
| **State Management** | Pinia |
| **HTTP Client** | Axios |
| **Auth** | Laravel Sanctum (SPA token) |
| **Database** | SQLite (lokal) / MySQL 8+ (production) |
| **Cache/Queue** | Database atau file |
| **Print** | Web Bluetooth API (Phase 2) — thermal printer ESC/POS |
| **Deploy** | VPS (Nginx + PHP-FPM) |

---

## 3. Modul/Fitur Utama (Simplified UMKM)

### A. Autentikasi & Manajemen User
- Login/Logout
- 2 Role: **Owner** & **Kasir**
- Permission-based access

### B. Master Data
- **Produk** — nama, SKU, kategori, harga beli, harga jual, stok, barcode, foto
- **Kategori** — hierarki kategori produk
- **Pelanggan** — member/tidak, poin, diskon khusus

### C. Transaksi Penjualan (POS)
- Kasir/POS interface (keyboard-friendly, barcode ready)
- Scan barcode / search produk
- Keranjang belanja (cart)
- Diskon per item & per transaksi
- Multi-payment: Tunai, Debit, QRIS, Transfer
- Cetak struk (thermal printer 80mm, ESC/POS)
- Riwayat transaksi & retur

### D. Manajemen Stok (Sederhana)
- **Koreksi Stok** — input manual stok awal & penyesuaian
- Alert stok menipis
- Riwayat mutasi stok

### E. Laporan & Dashboard
- Dashboard real-time (penjualan hari ini, stok menipis)
- Laporan penjualan harian/bulanan
- Laporan laba rugi
- Top produk terlaris
- Grafik & chart

---

## 4. Database Schema (Design Reference)

> **Catatan:** Schema ini untuk dokumentasi & perencanaan. Implementasi menggunakan Laravel Migration.
> **Single-toko, single-gudang, role: Owner & Kasir saja.**

```sql
-- User & Auth
users (id, name, email, password, role[owner|kasir], status)

-- Master Data
categories (id, name, parent_id, status)
products (id, category_id, name, sku, barcode,
          harga_beli, harga_jual, stok, stok_minimal, status, image)
customers (id, name, phone, email, address, poin, status)

-- Stok (Koreksi Sederhana)
stock_corrections (id, product_id, stok_sebelum, stok_sesudah,
                   selisih, tipe[masuk|keluar|penyesuaian], keterangan, user_id, created_at)

-- Transaksi Penjualan
sales (id, no_transaksi, user_id, customer_id,
       subtotal, diskon, ppn, total, bayar, kembali,
       payment_method, status, created_at)
sale_items (id, sale_id, product_id, qty, harga, diskon, subtotal)
```

---

## 5. Role & Permission (Simplified)

| Role | Akses |
|------|-------|
| **Owner** | Akses penuh: kelola produk, kategori, pelanggan, lihat laporan, koreksi stok, kelola user kasir |
| **Kasir** | POS only, lihat riwayat transaksi sendiri, input pelanggan baru |

---

## 6. Struktur Folder Project

```
pos-system/
├── backend/                    # Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── SalesController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── StockCorrectionController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── config/
│
└── frontend/                   # Vue 3 + shadcn-vue
    ├── src/
    │   ├── components/
    │   │   ├── ui/            # shadcn-vue components
    │   │   ├── pos/           # Custom POS components
    │   │   │   ├── PosCart.vue
    │   │   │   ├── PosProductGrid.vue
    │   │   │   ├── PosPaymentModal.vue
    │   │   │   └── PosReceipt.vue
    │   │   ├── products/
    │   │   ├── sales/
    │   │   ├── customers/
    │   │   ├── stock/
    │   │   └── dashboard/
    │   ├── layouts/
    │   ├── stores/            # Pinia stores
    │   ├── lib/               # API client, utils
    │   ├── router/
    │   └── types/
    └── package.json
```

---

## 7. API Endpoints (REST)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/login` | Login |
| POST | `/api/logout` | Logout |
| GET | `/api/products` | List produk |
| POST | `/api/products` | Tambah produk |
| PUT | `/api/products/{id}` | Update produk |
| DELETE | `/api/products/{id}` | Hapus produk |
| GET | `/api/categories` | List kategori |
| POST | `/api/categories` | Tambah kategori |
| GET | `/api/sales` | Riwayat penjualan |
| POST | `/api/sales` | Buat transaksi baru |
| GET | `/api/sales/{id}` | Detail transaksi |
| GET | `/api/dashboard` | Data dashboard |
| GET | `/api/reports/sales` | Laporan penjualan |
| GET | `/api/reports/stock` | Laporan stok |
| GET | `/api/customers` | List pelanggan |
| POST | `/api/customers` | Tambah pelanggan |
| GET | `/api/stock-corrections` | Riwayat koreksi stok |
| POST | `/api/stock-corrections` | Buat koreksi stok |

---

## 8. Struktur POS Interface

```
┌──────────────────────────────────────────────────────────┐
│  🏪 Toko Kopi XYZ    👤 Kasir: Budi    📅 01-10-2026   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────────────────┐  ┌───────────────────────────┐ │
│  │ 🔍 Search Produk... │  │     KERANJANG (3)        │ │
│  └─────────────────────┘  │                           │ │
│  ┌─────────────────────┐  │  Kopi Arabika 1kg  ×2   │ │
│  │  [Produk Grid]      │  │  @ Rp 80.000 = 160.000  │ │
│  │                     │  │  ─────────────────────── │ │
│  │  ┌───┐ ┌───┐ ┌───┐ │  │  Gula Pasir 1kg    ×1   │ │
│  │  │Kopi│ │Gula│ │Susu│  │  @ Rp 15.000 = 15.000  │ │
│  │  └───┘ └───┘ └───┘ │  │  ─────────────────────── │ │
│  │  ┌───┐ ┌───┐ ┌───┐ │  │  Subtotal: 175.000      │ │
│  │  │Teh │ │Roti│ │Kue │  │  Diskon: 0             │ │
│  │  └───┘ └───┘ └───┘ │  │  PPN (11%): 19.250     │ │
│  │                     │  │  ═══════════════════    │ │
│  │                     │  │  TOTAL: 194.250        │ │
│  │                     │  │                           │ │
│  │                     │  │  [💳 Bayar] [🖨 Struk]  │ │
│  └─────────────────────┘  └───────────────────────────┘ │
│                                                          │
│  [F1] Produk  [F2] Pelanggan  [F3] Diskon  [F4] Bayar  │
└──────────────────────────────────────────────────────────┘
```

---

## 9. Task List (Per Phase) — Simplified UMKM

### Phase 1: Setup & Foundation
- [x] **T1.1** — Install Laravel 12 + konfigurasi database (.env)
- [x] **T1.2** — Install Vue 3 + Vite + TypeScript
- [x] **T1.3** — Install & setup shadcn-vue (components UI)
- [x] **T1.4** — Install Laravel Sanctum + konfigurasi SPA auth
- [x] **T1.5** — Buat migration: users (role: owner, kasir)
- [x] **T1.6** — Buat migration: categories, products
- [x] **T1.7** — Buat migration: customers
- [x] **T1.8** — Buat migration: stock_corrections
- [x] **T1.9** — Buat migration: sales, sale_items
- [x] **T1.10** — Buat Seeder: data awal (user owner, kasir, produk contoh)
- [x] **T1.11** — Konfigurasi CORS + Sanctum stateful domains
- [x] **T1.12** — Setup Axios interceptor di frontend (base URL, auth token)
- [x] **T1.13** — Setup Pinia stores (auth store)

### Phase 2: Master Data
- [x] **T2.1** — CRUD Kategori — backend API
- [x] **T2.2** — CRUD Kategori — frontend page
- [x] **T2.3** — CRUD Produk — backend API
- [x] **T2.4** — CRUD Produk — frontend page
- [x] **T2.5** — CRUD Pelanggan — backend API
- [x] **T2.6** — CRUD Pelanggan — frontend page

### Phase 3: POS & Transaksi
- [x] **T3.1** — POS Layout (split screen: product grid + cart)
- [x] **T3.2** — Product Grid component (search, filter kategori)
- [x] **T3.3** — Cart component (tambah/kurang qty, hapus item)
- [x] **T3.4** — Diskon per item & per transaksi
- [x] **T3.5** — Payment Modal (tunai/debit/qris/transfer + kembali)
- [x] **T3.6** — Proses penjualan (SalesController + update stok otomatis)
- [x] **T3.7** — Struk HTML (thermal 80mm ready)
- [x] **T3.8** — Riwayat transaksi page
- [x] **T3.9** — Detail transaksi + retur

### Phase 4: Stok (Koreksi Sederhana)
- [x] **T4.1** — Koreksi Stok — backend API (input manual, riwayat)
- [x] **T4.2** — Koreksi Stok — frontend page
- [x] **T4.3** — Alert stok menipis (dashboard + notifikasi)

### Phase 5: Laporan & Dashboard
- [x] **T5.1** — Dashboard API (penjualan hari ini, stok menipis, chart)
- [x] **T5.2** — Dashboard page (grafik & statistik)
- [x] **T5.3** — Laporan penjualan (harian/bulanan, filter tanggal)
- [x] **T5.4** — Laporan laba rugi
- [x] **T5.5** — Top produk terlaris

### Phase 6: Polish & Deploy
- [x] **T6.1** — Role & permission (Owner vs Kasir middleware)
- [x] **T6.2** — Testing (feature test & browser test)
- [x] **T6.3** — Deployment VPS (Nginx + PHP-FPM + MySQL)
- [x] **T6.4** — SSL + domain setup
- [x] **T6.5** — Backup strategy

---

## 10. Deployment VPS

```
VPS Stack:
├── Nginx (reverse proxy)
├── PHP-FPM 8.2+
├── MySQL 8 / MariaDB (production)
├── SQLite (development)
├── Node.js (build frontend)
└── Supervisor (queue worker)
```

Struktur deploy:
```
/var/www/pos-umkm-native/
├── backend/          # Laravel
└── frontend/         # Vue (build → serve via Nginx)
```

---

## 11. Catatan Penting

- **Single-toko, single-gudang** — disederhanakan untuk level UMKM.
- **Role:** Owner & Kasir saja.
- **Stok:** Koreksi stok manual, tanpa gudang, tanpa purchase order ke supplier.
- **Harga:** Harga tunggal per produk (tanpa tiered pricing).
- **Thermal printer:** ESC/POS over Bluetooth. Phase 1 = print via browser, Phase 2 = Web Bluetooth API.
- **Payment:** Manual dulu (tunai/debit/qris/transfer), nanti bisa integrate payment gateway.
- **Barcode:** Manual input dulu, nanti bisa pake scanner hardware.
- **Future:** Bisa tambah fitur multi-toko, gudang, purchase order, tiered pricing jika bisnis berkembang.
- **Schema SQL di plan** adalah untuk dokumentasi. Implementasi menggunakan Laravel Migration.
