# MENUJU — Information Architecture & Navigation
**Versi:** 1.0 · **Status:** Proposed · **Ruang lingkup:** MVP (modul Next/Later diberi label)

## 1. Prinsip navigasi
1. **Terpisah berdasarkan niat, terhubung berdasarkan data.** Tabungan dan Belanja boleh terhubung, tetapi bukan satu mega-page.
2. **Satu tujuan utama per halaman.** Index menampilkan data, create/edit kontekstual, detail memuat histori/aksi lanjutan.
3. **Jangan membuat menu untuk sekadar aksi.** `Tambah Tamu` adalah tombol, bukan menu sidebar.
4. **Minimal nesting.** Sidebar maksimal dua level visual jika sangat diperlukan, layar detail tidak harus masuk sidebar.
5. **Navigasi konsisten.** Back, breadcrumb, active link, deep-link, dan state dipertahankan.
6. **Jangan menggandakan metrik** di halaman-halaman berbeda jika tidak mendukung keputusan di sana.

## 2. Struktur sidebar desktop — usulan MVP
```
BERANDA
  Beranda

PERSIAPAN
  Perjalanan
  Checklist
  Acara & Jadwal
  Kebutuhan
  Tamu
  Undangan

KEUANGAN
  Ringkasan
  Tabungan
  Anggaran
  Transaksi
  Akun

LAINNYA
  Pengaturan
```
**Catatan:** sidebar memuat 12 destinasi yang cukup berbeda; kategori hanya label visual, bukan parent yang membuka mega-page. Bila pengujian pengguna menemukan terlalu padat, gunakan bagian collapsible dengan label jelas, jangan sembunyikan fitur penting secara arbitrer.

**Next:** Vendor dapat ditambahkan sebagai menu Persiapan jika implementasi valid; Pembayaran boleh muncul sebagai subview Anggaran, bukan harus menu baru. **Later:** Kenangan hanya bila ada kebutuhan jelas; jangan menghidupkan modul lama tanpa validasi.

## 3. Kapan unified vs terpisah
| Area | Keputusan | Alasan |
|---|---|---|
| Perjalanan vs Checklist | **Terpisah**, saling taut | Timeline milestone ≠ daftar tugas harian |
| Kebutuhan vs Anggaran | **Terpisah**, relasi data | Item belanja ≠ rencana kategori biaya |
| Tabungan vs Akun | **Terpisah**, relasi alokasi | Tujuan menabung ≠ saldo rekening |
| Transaksi vs Akun | **Terpisah**, taut dari detail akun | Riwayat aktivitas ≠ daftar sumber dana |
| Tamu vs Undangan | **Terpisah**, deep link | Mengelola orang ≠ merancang undangan |
| Anggaran vs Pembayaran | **View/tab terpisah di area anggaran** | Rencana dan termin pembayaran saling terkait |
| Acara vs Jadwal | **Satu destinasi, dua view** | Data acara dan agenda yang sangat berkaitan |
| Vendor (Next) vs Anggaran | **Terpisah**, hubungan opsional | Kontak penyedia jasa ≠ catatan biaya |
| RSVP | **Subview Tamu** | Operasional daftar tamu |
| Public invitation | **Aplikasi publik terpisah** | Visual, privasi, dan navigasi berbeda |

## 4. Hierarki halaman dan route konseptual
*Path berikut adalah rekomendasi desain, bukan klaim route saat ini. Sesuaikan alias dan redirect saat implementasi.*

| Destinasi | Index/overview | Detail | Create/edit |
|---|---|---|---|
| Beranda | `/app/home` | — | Quick actions mengarah ke modul |
| Perjalanan | `/app/journey` | `/app/journey/:milestoneId` opsional | Modal milestone |
| Checklist | `/app/tasks` | `/app/tasks/:id` jika perlu | Drawer tugas |
| Acara & Jadwal | `/app/events` (tabs agenda/detail) | `/app/events/:id` | Page acara kompleks / modal agenda |
| Kebutuhan | `/app/needs` | `/app/needs/:id` | Drawer item |
| Tamu | `/app/guests` | `/app/guests/:id` opsional | Drawer tamu, modal impor |
| Undangan | `/app/invitations` | `/app/invitations/:id` preview | `/app/invitations/:id/edit` dedicated editor |
| Ringkasan | `/app/finance` | — | Quick actions mengarah ke modul |
| Tabungan | `/app/savings` | `/app/savings/:id` | Modal target, modal setoran |
| Anggaran | `/app/budgets` | `/app/budgets/:id` | Drawer kategori, modal pembayaran |
| Transaksi | `/app/transactions` | `/app/transactions/:id` opsional | Drawer transaksi |
| Akun | `/app/accounts` | `/app/accounts/:id` | Modal akun |
| Pengaturan | `/app/settings` (tab settings) | — | Inline fields khusus settings |
| Undangan publik | `/i/:slug` | — | RSVP inline dalam section publik |

## 5. Aturan template halaman
### A. Index — data management
Urutan ideal: heading + primary CTA → search/filter → tabel/list → pagination. Gunakan 0–3 ringkasan hanya jika mengubah keputusan. **Tidak ada form create/edit yang menetap di sisi daftar**.

### B. Detail — objek kompleks
Identitas objek + status → fakta paling relevan → histori/relasi/aktivitas → aksi kontekstual. Hindari mengulang tabel index.

### C. Create/edit
- Modal: 1–5 input sederhana, sedikit dependensi.
- Drawer: form menengah yang masih butuh konteks index.
- Dedicated page: editor undangan, pengaturan kompleks, form panjang.
- Wizard: onboarding atau flow berkondisi banyak langkah.
- Delete: dialog konfirmasi dengan konsekuensi aktual.

### D. Dashboard
Jangan sama dengan index. Tampilkan prioritas, progress, dan tindakan berikutnya; maksimal beberapa blok utama di atas fold.

### E. Public invitation
Tidak memakai sidebar aplikasi. Halaman full-bleed mobile-first, editorial, berurutan dan scroll alami.

## 6. Struktur navigasi mobile
- Bottom navigation berisi **Beranda, Persiapan, Keuangan, Tamu, Lainnya** *sebagai pintu navigasi*, bukan menyatukan seluruh fitur ke satu layar.
- Persiapan dan Keuangan membuka daftar destinasi yang jelas; tiap destinasi tetap dedicated page.
- Detail/form dibuka dengan back affordance dan tombol close/cancel jelas.
- Untuk mobile, tabel besar berubah menjadi list item berlabel; data kompleks tetap bisa diakses melalui detail.

## 7. Cross-links yang mengurangi perpindahan tak perlu
- Dari kebutuhan → kategori anggaran terkait; dari anggaran → kebutuhan yang menghabiskan anggaran.
- Dari detail akun → transaksi akun yang terfilter.
- Dari target tabungan → histori setoran; bila terkait, lihat kebutuhan yang dibiayai.
- Dari tamu → status RSVP; dari invitation library → penerima dan hasil RSVP.
- Dari dashboard → tugas dan tenggat terdekat (deep link).

## 8. Konten dan keputusan yang tidak boleh disembunyikan
- Tanggal/jam, lokasi acara, status undangan, dan konfirmasi RSVP jelas.
- Nominal, saldo, dan status pembayaran tidak disamarkan dengan kata-kata ambigu.
- Hapus/edit pada data sensitif punya permission dan konfirmasi.
- Tidak ada switch “Sederhana/Lanjutan” global; pengaturan tambahan tampil kontekstual bila benar-benar dibutuhkan.

## 9. Kriteria penilaian navigasi
- Pengguna mampu menemukan fitur utama dengan label yang familiar.
- Seluruh index, detail, create/edit, dan modal punya jalur kembali yang masuk akal.
- Tidak ada submenu yang hanya berisi satu halaman tanpa alasan.
- Tiap menu punya konten utama berbeda, bukan layout/tabungan sama dengan judul lain.
- Deep links lama dialihkan dengan aman bila route diubah.
