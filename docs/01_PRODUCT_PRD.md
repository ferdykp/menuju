# MENUJU — Product Requirements Document
**Versi:** 1.0 · **Status:** Proposed product baseline · **Tanggal:** 11 November 2026

> Dokumen ini adalah rancangan produk BARU, bukan deskripsi fitur yang sudah tersedia di repository. Semua keputusan diberi label **MVP**, **Next**, atau **Later**. Jangan mengklaim telah diimplementasikan.

## 1. Product vision
**MENUJU** adalah pendamping perencanaan pernikahan untuk pasangan Indonesia: membantu mengetahui apa yang harus dilakukan berikutnya, seberapa siap dananya, serta siapa yang perlu dihubungi—tanpa membuat persiapan terasa seperti mengelola software kantor.

**Janji produk:** *Rencanakan hari spesial dengan lebih tenang, bersama.*

**Prinsip:** fokus pada satu pekerjaan per layar; informasi keuangan dapat dipercaya; kolaborasi tanpa kebingungan; desktop efektif dan mobile terasa alami; kata-kata sederhana; undangan publik punya identitas visual tersendiri.

## 2. Masalah yang ingin diselesaikan
1. Rencana tersebar antara chat, spreadsheet, catatan, dan aplikasi berbeda.
2. Pasangan kesulitan membedakan *rencana biaya*, *uang tersedia*, *uang terkumpul*, dan *uang benar-benar keluar*.
3. Tugas, jadwal, vendor, undangan, dan tamu tidak terhubung secara praktis.
4. Antarmuka yang menampilkan semua form sekaligus membebani pengguna.
5. Undangan digital terasa generik; pasangan butuh pengalaman publik yang menarik tanpa menyulitkan pengelolaan.

## 3. Persona, kebutuhan, dan hak akses
| Persona | Tujuan | Kebutuhan utama |
|---|---|---|
| Pasangan pemilik workspace | Menyiapkan acara bersama | Ringkasan, keputusan, kolaborasi, privasi |
| Pasangan yang diundang berkolaborasi | Membantu persiapan | Tugas milik masing-masing, akses data yang disepakati |
| Tamu undangan | Melihat acara dan konfirmasi | Link cepat, mobile-first, RSVP tanpa akun |

**Role MVP:** Owner dan Partner dalam satu workspace. Permission per aksi wajib diperiksa server-side. Viewer/keluarga/WO: **Later**, jangan dianggap wajib pada MVP.

## 4. Lingkup produk
### MVP — bangun dahulu
- Autentikasi, onboarding ringkas, workspace pasangan, undangan partner.
- Beranda sebagai *next-action hub*.
- Checklist tugas; perjalanan milestone (view terpisah, terhubung).
- Acara & jadwal (tanggal, waktu, lokasi, agenda ringkas).
- Tabungan: target, setoran, histori.
- Akun kas/bank dan transaksi.
- Anggaran kategori, rencana biaya, pembayaran.
- Kebutuhan & belanja: item, estimasi, status, realisasi pembelian.
- Tamu: daftar, grup, impor CSV terkontrol.
- Undangan: library, editor terpisah, preview, publish, RSVP, tema unggulan.
- Pengaturan workspace, profil, akses partner.

### Next — setelah MVP tervalidasi
- Vendor directory dengan kontak, penawaran, relasi ke anggaran.
- Reminder, pengingat pembayaran, kalender ekspor.
- Rekap RSVP & seating plan sederhana.
- Financial readiness proyeksi dengan asumsi yang transparan.
- Pilihan tema undangan tambahan, galeri foto publik, guestbook termoderasi.

### Later — hanya setelah kebutuhan terbukti
- Manajemen hari-H untuk banyak kru, integrasi pembayaran, marketplace vendor, template AI, automasi eksternal, advanced analytics.

**Tidak wajib untuk MVP:** modul Kenangan terpisah, CRM vendor kompleks, kontrol Sederhana/Lanjutan, aplikasi enterprise dengan banyak role, dan otomasi berat.

## 5. Prinsip batas fitur dan definisi data
- **Tabungan:** tujuan dana dan kontribusi/setoran. Setoran tidak otomatis sama dengan pendapatan baru; sumber dana harus eksplisit jika memengaruhi saldo akun.
- **Akun:** sumber saldo nyata (kas/bank) dan opening balance.
- **Transaksi:** catatan mutasi riil, transfer antar-akun tidak dihitung sebagai pemasukan/pengeluaran eksternal dua kali.
- **Anggaran:** rencana alokasi biaya per kategori/event; bukan bukti pengeluaran.
- **Kebutuhan:** daftar item/jasa yang ingin dibeli; estimasi tidak mengurangi saldo.
- Setiap kebutuhan dapat ditautkan ke satu acara aktif di workspace atau dibiarkan sebagai kebutuhan umum. Detail acara menampilkan jumlah item, estimasi kebutuhan yang belum dibeli, dan realisasi kebutuhan yang telah dibeli. Menghapus acara melepas relasinya tanpa menghapus kebutuhan.
- **Pembayaran:** kewajiban/termin dan realisasi, dihubungkan ke transaksi saat pembayaran benar-benar terjadi.
- **Vendor:** penyedia jasa, bukan kategori anggaran; data dapat direlasikan.
- **Tamu:** identitas dan status undangan/RSVP; akses publik tidak membuka data tamu lain.

### Persamaan dasar (contoh konsep, perlu dikunci di spesifikasi backend)
- Progres tabungan = dana tercatat untuk target / nominal target, ditampilkan maksimal 100% untuk bar; angka kelebihan tetap terlihat jika ada.
- Sisa target = max(target - dana tercatat, 0).
- Sisa anggaran = rencana anggaran - realisasi biaya; nilai negatif diperbolehkan sebagai overbudget.
- Total saldo akun harus dihitung dari opening balance + mutasi valid, bukan angka hasil format UI.

**Ketentuan finansial:** nilai numerik backend tetap numerik; format `Rp 1.500.000` hanya di lapisan tampilan. Gunakan decimal/integer sesuai unit dan kontrak backend, bukan floating-point tanpa pengamanan.

## 6. Kebutuhan fungsional per area
| ID | Kemampuan MVP | Kondisi berhasil |
|---|---|---|
| AUTH-01 | Daftar, login, reset password | Validasi dan status jelas, sesi aman |
| WS-01 | Membuat workspace & mengundang partner | Pengguna tahu siapa punya akses |
| HOME-01 | Ringkasan prioritas | Menjawab “apa selanjutnya?” tanpa lebih dari beberapa blok utama |
| TASK-01 | CRUD tugas, deadline, assignee, dan tautan tugas ke acara | Checklist dapat difilter menurut acara atau tugas umum; progres acara menghitung tugas acara tersebut saja |
| JOURNEY-01 | Timeline milestone | Tidak menggandakan halaman checklist |
| EVENT-01 | Kelola detail acara dan jadwal | Tanggal, timezone, lokasi terlihat jelas |
| SAVE-01 | CRUD target, setoran, histori | Rekonsiliasi target dan catatan konsisten |
| CASH-01 | CRUD akun & transaksi | Saldo/transfer tidak double counted |
| BUD-01 | Kelola kategori & anggaran | Planned vs actual jelas |
| NEED-01 | CRUD kebutuhan, kategori workspace, filter dan rekap per kategori/acara | Estimasi dan realisasi terpisah; item pada satu kategori atau acara dapat dijumlahkan |
| GUEST-01 | CRUD tamu, cari/filter, impor | Isolasi workspace dan duplicate handling |
| INV-01 | Buat/edit/preview/publish undangan | Preview mirip hasil publik, unpublished privat |
| RSVP-01 | Tamu RSVP melalui token/link aman | Status terkirim tanpa akses data lain |
| SETTINGS-01 | Profil, workspace, undang/cabut akses | Otorisasi dan konfirmasi tersedia |

## 7. Non-functional requirements
- **Accessibility:** keyboard access, focus visible, meaningful labels, contrast WCAG AA sebagai target; prefers-reduced-motion.
- **Performance:** mobile prioritas; kompres gambar; lazy load konten non-kritis; animasi transform/opacity jika memungkinkan.
- **Security:** scoped queries per workspace, CSRF/auth protection, rate limiting RSVP, validasi server, audit aksi penting.
- **Privacy:** data kontak tamu dan keuangan tidak boleh terpapar lewat URL publik; tautan invitation dibatasi data publik yang memang dipublikasikan.
- **Resilience:** error/retry state, offline handling secara bertanggung jawab bila PWA tersedia; form tidak hilang tanpa peringatan.
- **Responsiveness:** desktop 1440/1280, tablet 768, mobile 390/360 sebagai QA baseline, bukan ukuran eksklusif.

## 8. Prioritas user journey
1. Daftar → buat workspace → isi nama pasangan/tanggal → undang partner → beranda.
2. Buat target tabungan → catat setoran → lihat detail dan histori.
3. Buat kebutuhan → pilih anggaran terkait → tandai pembelian → catat transaksi bila dibayar.
4. Tambah tamu → buat undangan → preview → publish → kirim → RSVP.
5. Buat checklist → tetapkan deadline → selesaikan → beranda memperbarui prioritas.

## 9. Product success measures (target awal, bukan data aktual)
- Pengguna baru dapat menyelesaikan onboarding tanpa bantuan.
- Aksi utama pada tiap halaman ditemukan tanpa tutorial.
- Tidak ada input nominal yang tersimpan dengan parsing keliru.
- Tamu bisa melakukan RSVP di mobile dengan alur singkat.
- Pengguna bisa kembali dari detail/form ke konteks index tanpa kehilangan data.
- Screen inventory dan pengujian visual lengkap sebelum rilis.

## 10. Keputusan yang masih perlu divalidasi sebelum produksi
- Kebijakan undangan partner dan pembagian kewenangan keuangan.
- Apakah satu workspace hanya satu acara atau mendukung beberapa agenda.
- Model rekonsiliasi setoran dengan transaksi kas/bank.
- Aturan perubahan RSVP dan batas plus-one.
- Storage dan privasi foto undangan.
- Cara memigrasikan data dari modul lama jika struktur domain berubah.

**Aturan pengembangan:** Jangan menambah fitur di luar scope sebagai fakta; tandai sebagai usulan dan minta validasi produk sebelum mengubah data model atau aturan keuangan.
