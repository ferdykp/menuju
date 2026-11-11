# MENUJU — Design System & UX Writing
**Versi:** 1.0 · **Status:** Art direction proposal, bukan hasil desain final

## 1. Creative direction
**Nama arah desain:** *Warm Editorial Utility*.

Produk harus terasa seperti pendamping persiapan pernikahan—terpercaya, hangat, cerdas, rapi—bukan corporate ERP dan bukan juga undangan penuh dekorasi di setiap halaman. Pisahkan bahasa visual **aplikasi perencanaan** (efisien) dan **undangan publik** (emosional, sinematik) melalui sistem brand yang masih serumpun.

Kata kunci: warm, focused, timeless, contemporary, effortless, human, understated premium.

**Anti-pattern:** semua informasi dalam card, banyak border dalam border, form berdampingan dengan table, teks dekoratif berlebihan, header berganda, gradient terus-menerus, animasi tanpa makna, konten serupa pada menu berbeda.

## 2. Foundations — usulan token awal, boleh disesuaikan lewat visual testing
| Token | Nilai awal | Fungsi |
|---|---|---|
| `--canvas` | `#F8F6F2` | Background warm ivory |
| `--surface` | `#FFFFFF` | Form, panel, modal |
| `--surface-soft` | `#F1EEE8` | Alternatif ringan |
| `--text-primary` | `#242821` | Teks utama |
| `--text-secondary` | `#676E64` | Deskripsi |
| `--accent` | `#55644D` | CTA muted botanical |
| `--accent-hover` | `#43503D` | Hover |
| `--border` | `#C9CDC6` | Outline field yang terlihat |
| `--danger` | `#A43636` | Error/destructive |
| `--focus` | `#536A54` | Focus outline |

Warna ini **starting point**, bukan warna final yang dipaksakan. Verifikasi kontras untuk teks, border, status, dan interactive states. Warna tidak boleh jadi satu-satunya penanda status.

## 3. Typography
- Headline/hero: editorial serif elegan (misalnya Cormorant Garamond atau yang cocok setelah uji); hanya untuk headline terpilih.
- UI text: sans-serif yang terbaca tinggi (misalnya Inter/Plus Jakarta Sans).
- Maksimal dua keluarga font; gunakan font subset/weights seperlunya.
- Skala awal: display 44–60 desktop / 34–42 mobile; H1 28–34; H2 22–26; H3 18–20; body 15–16; caption 13–14. Jangan pernah mengecilkan field dan tabel demi density.
- Angka keuangan: tabular numerals; format `id-ID` dan minus jelas.
- Hindari heading panjang ALL CAPS, italic di teks operasional, dan deskripsi dua kali mengulang judul.

## 4. Layout & density
- Desktop max content width sekitar 1120–1280px tergantung modul; editor undangan bisa memakai workspace lebih luas.
- 8px spacing rhythm, dengan token 4/8/12/16/24/32/48.
- Sidebar tetap ringkas dan berlabel; active state tidak hanya bergantung pada warna.
- Index: header → toolbar → data → pagination. Ringkasan maksimal 0–3 metrik penting.
- Detail: heading + status → informasi inti → aktivitas/riwayat. Jangan ulang semua konten index.
- Gunakan table untuk transaksi, tamu, kebutuhan; gunakan timeline untuk perjalanan; galeri untuk invitation library; dashboard untuk beranda/ringkasan.
- Card hanya saat mengelompokkan informasi, bukan sebagai container untuk setiap baris.

## 5. Component catalog
| Component | Pedoman |
|---|---|
| PageHeader | Satu H1; deskripsi hanya bila memberi konteks; CTA kanan atau bawah di mobile |
| DataTable | Sticky header jika perlu, row hover, action icons, sort/filter yang jelas |
| Search/Filter | Search prominen; filter dapat dibuka, ada clear filters |
| Button | Primary tunggal per konteks; secondary subtle; destructive jelas |
| IconButton | Font Awesome; aria-label, tooltip, target sentuh nyaman |
| TextField | Label permanen, border visible, focus ring, error label |
| MoneyField | Prefix Rp, pemisah ribuan Indonesia, nilai internal numerik |
| Modal | Kegiatan singkat, tombol dan close accessible, tidak nested |
| Drawer | Create/edit menengah, unsaved guard, max-width readable |
| DetailPage | Objek, status, histori dan aksi terkait |
| EmptyState | Pesan pendek + satu CTA; bukan form full page |
| Toast | Umpan balik singkat; jangan gantikan error field |
| ConfirmDialog | Nama objek dan akibat tindakan, tombol Batal + destruktif |
| Tabs | Hanya untuk sibling view dalam konteks sama, tidak untuk memaksa fitur berbeda menyatu |

## 6. Form & money specifications
- Input height awal 44–48px, radius moderat 10–12px, border minimal 1px kontras; hover lebih tegas; focus ring visible tanpa layout shift.
- Label sebelum input, helper text hanya saat perlu, error per-field, `required` ditandai secara konsisten.
- Formatting uang saat input harus mempertahankan caret, backspace, paste, kosong vs 0; gunakan decimal/integer sesuai backend; tanpa perubahan presisi.
- Date/time memakai zona waktu acara; tidak mengasumsikan timezone device untuk peristiwa.
- Semua form punya feedback loading dan guard untuk data belum tersimpan.

## 7. Iconography
- Utamakan **Font Awesome** sebagai library UI. Pilih satu style dominan (Solid/Regular sesuai ketersediaan), mapping semantik konsisten.
- Icon-only untuk edit, hapus, view, tutup ketika konteks jelas; tooltip dan accessible name wajib.
- Icon + label untuk CTA utama: Tambah tamu, Buat target, Catat transaksi, Terbitkan undangan.
- Hindari emoji untuk icon UI dan ikon ornamental pada setiap kalimat.

## 8. UX Writing: bahasa Indonesia yang manusiawi
**Voice:** jelas, hangat, tidak terlalu formal; tidak sok akrab di konteks keuangan. Gunakan kata kerja aktif dan istilah konsisten.

| Konteks | Gunakan | Hindari |
|---|---|---|
| CTA | Tambah tamu | Inisialisasi profil undangan |
| Loading error | Tamu belum berhasil dimuat. Coba lagi. | Terjadi kesalahan proses internal |
| Empty | Belum ada kebutuhan. Tambahkan yang pertama. | Data list kebutuhan masih kosong pada workspace ini |
| Tabungan | Sudah terkumpul | Actual savings |
| Status | Belum dibeli, Sudah dibeli | Pending, Completed dalam daftar campuran bahasa |
| Konfirmasi | Hapus “Rina”? Data tamu ini tidak bisa dipulihkan. | Apakah Anda yakin hendak mengeksekusi penghapusan? |
| Financial | Sisa anggaran | Available allocated residual budget |

Teks keamanan, pembayaran, dan peringatan harus akurat; jangan disingkat hingga menyesatkan.

## 9. Motion system — aplikasi
- Motion memberikan feedback, bukan atraksi. Duration biasa 120–240ms, easing lembut.
- Drawer/modal enter/exit, row inserted, success state, progress change; jangan seluruh halaman bergerak saat scroll.
- Hormati `prefers-reduced-motion` dan keyboard focus; jangan mengunci scroll modal secara salah.

## 10. Invitation public — art direction tersendiri
**Signature theme:** *Cinematic Garden Editorial* (contoh tema pertama).
- Cover: foto pasangan full-bleed, nama editorial, CTA buka (tanpa delay panjang), sapaan tamu personal bila ada.
- Long-scroll: opening → pasangan → cerita singkat → jadwal/lokasi → countdown → galeri → RSVP → penutup.
- Selective parallax di media, mask reveal pada foto, subtle floral motion, smooth section transitions. Tidak semua elemen bergerak.
- Scroll harus native/natural; motion ringan di mobile dan hilang jika reduced-motion.
- Foto dipilih pasangan, fallback tanpa foto harus tetap bermartabat; jangan ambil aset stok sebagai data pengguna.
- RSVP dan lokasi selalu jelas, tidak ditutup animasi; autoplay audio bersuara dilarang tanpa tindakan pengguna.
- Preview Studio harus menggunakan rendering publik yang sama agar hasil preview konsisten.

## 11. Responsive decisions
- >=1024 desktop: sidebar, table, drawer.
- 768–1023 tablet: sidebar ringkas, grid adaptif, table bila terbaca.
- <=767 mobile: menu navigasi ringkas, structured list untuk table padat, full-screen sheet untuk form panjang, CTA bisa sticky bila tidak menutupi konten.
- Jangan menaruh dua kolom form yang memaksa zoom di mobile.

## 12. Design QA rules
- Periksa semua screen pada populated/empty/error dan desktop/mobile.
- Screenshot visual bukan hanya build sukses.
- Evaluasi scanability: dalam 5 detik pengguna tahu tujuan halaman dan satu tindakan utama.
- Table rows punya density terbaca, tidak penuh badges/CTA; form tidak menempel di index.
- Color contrast, focus states, tab order, labels, zoom 200%, keyboard dan reduced motion diuji.
