# MENUJU — Fresh Product Blueprint
**Versi 1.0 · 11 November 2026 · Proposed design baseline**

Paket ini dibuat **dari nol** berdasarkan arah produk baru, bukan menyalin spesifikasi `docs/` lama. Isinya adalah **proposal desain dan produk**, bukan audit kode atau klaim bahwa semua fitur telah ada.

## Urutan baca dan penggunaan
1. `01_PRODUCT_PRD.md` — tujuan produk, pengguna, scope MVP/Next/Later, aturan domain.
2. `02_INFORMATION_ARCHITECTURE.md` — sidebar, sitemap dan pemisahan fitur.
3. `03_USER_FLOWS_AND_SCREENS.md` — inventaris layar dengan ID dan alur end-to-end.
4. `04_DESIGN_SYSTEM.md` — arah visual, komponen, copywriting, responsive dan motion.
5. `05_SCREEN_ACCEPTANCE.md` — kriteria hasil dan bukti verifikasi.
6. `06_GOOGLE_STITCH_HANDOFF.md` — prompt siap pakai untuk Google Stitch.

## Batasan penting
- Pemisahan fitur ini adalah rekomendasi baru, **belum otomatis sesuai dengan database atau route aplikasi yang sedang aktif**.
- Implementasi ke repository harus diawali pemetaan fitur/kontrak API dan strategi migrasi/redirect tanpa kehilangan data.
- Jangan mengadopsi PRD historis sebagai instruksi aktif bila bertentangan dengan baseline baru.
- Jangan mewajibkan Stitch menciptakan seluruh layar dalam satu output bila platform punya batas; lanjutkan per batch dan validasi ID.

## Cara handoff
**Stitch:** unggah/berikan lima dokumen, lalu jalankan prompt di file 06. Cocokkan semua frame dengan ID inventaris.

**Codex:** setelah desain disetujui, implementasikan secara bertahap, mulai dari design foundations dan satu modul prioritas; buat screenshot actual desktop/mobile; pertahankan business logic dan keamanan. Jangan mengklaim selesai dengan build saja.

## Prinsip inti
**Satu halaman, satu pekerjaan utama. Terpisah berdasarkan niat, terhubung melalui data.**
