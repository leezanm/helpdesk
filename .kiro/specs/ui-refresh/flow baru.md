# Flow Aduan Baharu — Sistem Meja Bantuan KKDW

## Status yang disahkan

| ID | Status | Keterangan | Siapa bertindak | Perubahan |
|----|--------|-----------|----------------|-----------|
| 1 | **Baru** | Aduan baru dihantar oleh pengguna | Auto (oleh sistem) | Rename dari "Dihantar" |
| 2 | **Aduan Disemak** | Peg. helpdesk telah buka/lihat aduan | Auto (middleware, bila admin buka) | Rename dari "Ambil Maklum" |
| 3 | **Dalam Tindakan** | Aduan ditugaskan kepada peg. bertanggungjawab | Peg. helpdesk | Sama |
| 4 | **Selesai** | Aduan disahkan selesai, rating diberi | Peg. helpdesk ATAU pengguna (dari status Selesai Tindakan) | Sama (laluan berubah) |
| 5 | **Tindakan Lanjut Diperlukan** | BAHARU: Peg. bertanggungjawab tak dapat selesaikan, catatan wajib | Peg. bertanggungjawab | STATUS BAHARU (ganti fungsi lama status 5) |
| 6 | **KIV** | Aduan ditangguhkan dengan catatan | Peg. helpdesk | Sama |
| 7 | **Selesai Tindakan** | BAHARU: Peg. bertanggungjawab dah buat tindakan + catatan | Peg. bertanggungjawab | STATUS BAHARU |

> Nota: ID status 5 lama ("Selesai") masih digunakan untuk aduan yang ditutup sebenar.
> Status 7 ("Selesai Tindakan") adalah peralihan menuju status 4 ("Selesai").

## Flow lengkap

[Pengguna cipta & hantar aduan]
        │
        ▼
  ① BARU  (status 1)
  sistem auto set apabila pengguna hantar
        │
        │ Peg. helpdesk buka aduan → auto
        ▼
  ② ADUAN DISEMAK  (status 2)
  middleware auto-advance bila helpdesk buka halaman aduan
        │
        │ Peg. helpdesk: Tugaskan kepada pegawai bertanggungjawab
        ▼
  ③ DALAM TINDAKAN  (status 3)
  aduan kini di tangan peg. bertanggungjawab
        │
        ├──────────────────────────────────────────┐
        │ Peg. bertanggungjawab:                   │ Peg. bertanggungjawab:
        │ Masalah BOLEH diselesaikan               │ Masalah TIDAK DAPAT diselesaikan
        ▼                                          ▼
  ⑦ SELESAI TINDAKAN  (status 7)          ⑤ TINDAKAN LANJUT DIPERLUKAN  (status 5)
  + Catatan Teknikal (lesson learn)        + Catatan Teknikal (punca + kekangan)
  + Punca Masalah                                  │
  + Langkah Penyelesaian                           │ Peg. helpdesk decide:
        │                                          ├──→ KIV (status 6) + catatan
        │ Peg. helpdesk ATAU Pengguna:             └──→ Tugaskan semula → status 3
        │ Sahkan selesai
        ▼
  ④ SELESAI  (status 4)
  Rating default 5⭐ (boleh diubah pengguna/helpdesk)
  Pengguna boleh beri komen/maklum balas
```

## Catatan Teknikal (Lesson Learn)

Catatan teknikal ini adalah **lebih daripada sekadar nota** — ia adalah pangkalan pengetahuan organisasi.

### Tujuan
- Merekod **punca masalah** (root cause) bagi setiap aduan```

- Merekod **langkah penyelesaian** yang diambil
- **Membantu pegawai masa hadapan** bila jumpa masalah serupa
- Membolehkan pihak pengurusan **analisa trend** punca berulang

### Struktur catatan teknikal (dalam `report_trails`)
Apabila pegawai bertanggungjawab update status kepada **Selesai Tindakan** atau
**Tindakan Lanjut Diperlukan**, mereka perlu isi:

| Field | Keterangan | Wajib? |
|-------|-----------|--------|
| `punca_masalah` | Punca sebenar masalah yang dilaporkan | Ya |
| `langkah_tindakan` | Tindakan yang diambil untuk selesaikan | Ya (jika Selesai Tindakan) |
| `catatan_teknikal` | Catatan tambahan, rujukan, atau konteks | Tidak |
| `kenapa_lanjut` | Kenapa masalah tidak dapat diselesaikan | Ya (jika Tindakan Lanjut) |

### Implikasi reka bentuk
1. `report_trails` perlu **4 field baharu**: `punca_masalah`, `langkah_tindakan`, `catatan_teknikal`, `kenapa_lanjut`
2. Paparan khusus untuk **cari lesson learn** berdasarkan kategori / punca
3. Dalam laporan: boleh tapis/cari rekod selesai berdasarkan punca untuk analisa trend

## Perubahan teknikal diperlukan

### 1. Database migration
- `_report_statuses`: update `desc` untuk status 1,2,3; tambah status 7 (Selesai Tindakan), status 5 dikekalkan untuk Tindakan Lanjut Diperlukan
- `report_trails`: tambah kolum `punca_masalah`, `langkah_tindakan`, `catatan_teknikal`, `kenapa_lanjut`

### 2. Seeders
- `ReportStatusSeeder`: nama status baharu
- `ReportTrailStatusSeeder`: trail status baharu

### 3. Code
- `UpdateReportStatus` middleware: status 1 → 2 (bukan 2 → 3)
- `TransitionReportStatus` action: tambah tindakan 5 (Selesai Tindakan) dan 6 (Tindakan Lanjut)
- `ReportShow` Livewire: panel peg. bertanggungjawab, panel helpdesk tutup aduan, rating default 5
- Notifikasi: kemas kini status ID

### 4. Paparan lesson learn
- Skrin carian/senarai catatan teknikal (boleh dicari ikut kategori, punca, tarikh)
