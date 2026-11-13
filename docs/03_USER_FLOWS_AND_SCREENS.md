# MENUJU — User Flows, Screen Inventory & Interaction Map
**Versi:** 1.0 · **Status:** Proposed design requirements

## 1. Konvensi
- **P** = halaman penuh; **M** = modal; **D** = drawer; **S** = UI state; **PUBLIC** = layar tamu.
- Satu row = satu layar atau interaksi yang berbeda dan harus dirancang, bukan satu card generik.
- Data dan alur di sini adalah target desain; cocokkan dengan backend saat implementasi.

## 2. Master screen inventory — semua layar MVP
| ID | Area | Screen | Jenis | Tujuan / primary action |
|---|---|---|---|---|
| AU-01 | Auth | Welcome | P | Mulai |
| AU-02 | Auth | Login | P | Masuk |
| AU-03 | Auth | Register | P | Daftar |
| AU-04 | Auth | Forgot password | P | Kirim tautan |
| AU-05 | Auth | Reset password | P | Simpan sandi |
| ON-01 | Onboarding | Buat workspace | P | Lanjut |
| ON-02 | Onboarding | Detail pasangan & tanggal | P | Simpan & lanjut |
| ON-03 | Onboarding | Undang partner (skip allowed) | P | Kirim undangan |
| ON-04 | Onboarding | Selesai | P | Ke beranda |
| HM-01 | Beranda | Home populated | P | Buka prioritas teratas |
| HM-02 | Beranda | Home new workspace | S | Buat tugas pertama |
| JR-01 | Perjalanan | Timeline milestone | P | Lihat milestone |
| JR-02 | Perjalanan | Detail milestone | P | Lihat tugas terkait |
| JR-03 | Perjalanan | Add/edit milestone | M | Simpan |
| TK-01 | Checklist | Task index | P | Tambah tugas |
| TK-02 | Checklist | Add/edit task | D | Simpan |
| TK-03 | Checklist | Task detail | D | Ubah status |
| TK-04 | Checklist | Delete task confirmation | M | Hapus / batal |
| EV-01 | Acara & Jadwal | Event overview | P | Tambah acara/agenda |
| EV-02 | Acara & Jadwal | Event detail/editor | P | Tinjau kebutuhan acara dan simpan informasi |
| EV-03 | Acara & Jadwal | Agenda item form | M | Simpan agenda |
| ND-01 | Kebutuhan | Needs index/table | P | Filter kategori/acara dan tinjau subtotal estimasi/realisasi |
| ND-02 | Kebutuhan | Add/edit need | D | Pilih kategori/acara atau buat kategori baru, lalu simpan |
| ND-03 | Kebutuhan | Need detail | P | Catat pembelian |
| ND-04 | Kebutuhan | Record purchase | M | Konfirmasi pembelian |
| ND-05 | Kebutuhan | Delete confirmation | M | Hapus / batal |
| GU-01 | Tamu | Guest index | P | Tambah tamu |
| GU-02 | Tamu | Add/edit guest | D | Simpan |
| GU-03 | Tamu | Guest detail | D | Lihat status |
| GU-04 | Tamu | Import guests | P atau M besar | Preview & impor |
| GU-05 | Tamu | Import result | S | Lihat berhasil/gagal |
| GU-06 | Tamu | RSVP status filter | P state | Review respons |
| GU-07 | Tamu | Delete confirmation | M | Hapus / batal |
| IN-01 | Undangan | Invitation library | P | Buat undangan |
| IN-02 | Undangan | Choose theme | P | Pilih tema |
| IN-03 | Undangan | Invitation editor | P | Simpan draft |
| IN-04 | Undangan | Preview mobile/desktop | P | Kembali / publish |
| IN-05 | Undangan | Publish confirmation | M | Terbitkan |
| IN-06 | Undangan | Share dialog | M | Salin tautan |
| IN-07 | Undangan | Manage status | P/detail | Lihat draft/published |
| PF-01 | Undangan publik | Opening & salutation | PUBLIC | Buka undangan |
| PF-02 | Undangan publik | Story / events / gallery / closing | PUBLIC scroll sections | Jelajah acara |
| PF-03 | Undangan publik | RSVP form | PUBLIC section | Kirim RSVP |
| PF-04 | Undangan publik | RSVP success/error | S | Lihat hasil / ulang |
| FN-01 | Keuangan | Finance overview | P | Buka prioritas keuangan |
| SV-01 | Tabungan | Savings index | P | Buat target |
| SV-02 | Tabungan | Create/edit goal | M | Simpan |
| SV-03 | Tabungan | Savings detail | P | Catat setoran |
| SV-04 | Tabungan | Add deposit | M | Simpan setoran |
| SV-05 | Tabungan | Deposit history | P/detail tab | Filter histori |
| SV-06 | Tabungan | Edit/reverse deposit | M | Simpan perubahan |
| BG-01 | Anggaran | Budget index | P | Tambah kategori |
| BG-02 | Anggaran | Budget detail | P | Lihat alokasi/realisasi |
| BG-03 | Anggaran | Category create/edit | D | Simpan |
| BG-04 | Anggaran | Payment obligations list | P/tab | Lihat jatuh tempo |
| BG-05 | Anggaran | Record payment | M | Catat pembayaran |
| TR-01 | Transaksi | Transactions index | P | Tambah transaksi |
| TR-02 | Transaksi | Add/edit transaction | D | Simpan |
| TR-03 | Transaksi | Transaction detail | D | Lihat rincian |
| TR-04 | Transaksi | Transfer between accounts | M/D | Transfer |
| TR-05 | Transaksi | Delete/void confirmation | M | Konfirmasi |
| AC-01 | Akun | Account index | P | Tambah akun |
| AC-02 | Akun | Create/edit account | M | Simpan |
| AC-03 | Akun | Account detail | P | Lihat mutasi |
| AC-04 | Akun | Archive confirmation | M | Arsipkan |
| ST-01 | Pengaturan | Settings overview | P | Pilih setting |
| ST-02 | Pengaturan | Couple profile | P | Simpan |
| ST-03 | Pengaturan | Workspace & event settings | P | Simpan |
| ST-04 | Pengaturan | Partner access | P | Undang/cabut akses |
| ST-05 | Pengaturan | Account preferences | P | Simpan |
| ST-06 | Pengaturan | Privacy and sharing | P | Simpan |
| SYS-01 | Semua | Loading skeleton | S | Feedback |
| SYS-02 | Semua | Empty state | S | Primary action |
| SYS-03 | Semua | Error + retry | S | Coba lagi |
| SYS-04 | Semua | Unsaved changes | M | Tetap / keluar |
| SYS-05 | Semua | Generic destructive confirm | M | Batal / lanjut |
| SYS-06 | Semua | Not found/unauthorized | P | Kembali |

**Total minimum:** 77 entri interaksi/layar (termasuk shared states); jumlah final dapat bertambah saat spesifikasi aktual diverifikasi. Jangan menyamakan setiap entri dengan route yang harus ada.

## 3. Alur pengguna kritis
### Flow A — First-time setup
`AU-03 → ON-01 → ON-02 → ON-03 (skip opsional) → ON-04 → HM-02 → TK-02 → TK-01`
- Validasi email dan tanggal; workspace tidak duplikat akibat klik berkali-kali.
- Partner belum menerima undangan tidak boleh mengakses data.

### Flow B — Tabungan
`SV-01 → SV-02 → SV-01 → SV-03 → SV-04 → SV-03 → SV-05`
- Form goal hanya menanyakan nama, target nominal, target date (opsional), dan catatan (opsional).
- Menambah setoran tidak boleh membuat pemasukan/transaksi otomatis kecuali pengguna memang memilih sumber dana dan mekanisme backend mendukungnya.

### Flow C — Belanja hingga pembayaran
`ND-01 → ND-02 → ND-01 → ND-03 → ND-04 → TR-02 (jika real payment) → ND-03`
- Kebutuhan baru tetap berstatus rencana.
- Kebutuhan boleh ditautkan ke satu acara atau disimpan sebagai kebutuhan umum; filter acara dan detail EV-02 menampilkan kebutuhan beserta subtotalnya.
- Menghapus acara mengosongkan tautan kebutuhan (menjadi umum), tetapi tidak menghapus catatan kebutuhan.
- Pembelian/pembayaran harus jelas apakah sudah lunas, DP, atau belum dibayar; hindari pencatatan ganda.

### Flow D — Guest to RSVP
`GU-01 → GU-02 / GU-04 → IN-01 → IN-02 → IN-03 → IN-04 → IN-05 → IN-06 → PF-01 → PF-03 → PF-04 → GU-06`
- Draft tidak dapat diakses publik sebelum publish.
- Link undangan personal tidak memaparkan data tamu lain.

### Flow E — Checklist
`HM-01 → TK-01 → TK-02 → TK-01 → TK-03 → HM-01`
- Tugas dapat ditautkan secara opsional ke satu acara; yang tidak ditautkan tetap masuk ke Umum.
- Filter Checklist menyediakan Semua acara, acara tertentu, dan Umum / tanpa acara. Progres saat difilter mengikuti hasil acara tersebut.
- Detail acara menampilkan jumlah dan progres tugas terkait serta tautan ke Checklist yang sudah difilter. Pengelolaan tugas tetap dilakukan di Checklist.
- Menghapus acara melepas tautan tugas menjadi umum, tanpa menghapus tugas. Milestone tetap menjadi tahap persiapan yang terpisah.
- Deadline penting tampil di Home; tidak tampil sebagai form berulang.

## 4. Pola form standar
- Index/list tidak memuat create/edit form secara permanen.
- Drawer tetap membawa judul, deskripsi maksimal satu kalimat, input border, validasi, tombol Batal + Simpan; bila data kotor dan user menutup, tampilkan unsaved confirmation.
- Input uang: `Rp 1.500.000` secara visual, model numerik tanpa formatting, cursor terjaga, empty/zero dibedakan.
- Delete menunjukkan objek dan konsekuensi aktual, tidak menghapus otomatis.
- Impor: pilih berkas → validasi/preview → ringkasan duplikat/gagal → konfirmasi → hasil.

## 5. State matrix wajib per index
| State | UI | Aksi |
|---|---|---|
| Loading | Skeleton menyerupai konten | Tidak tampil angka palsu |
| Empty | Ikon sederhana, satu kalimat, CTA | Buat item |
| No search result | “Tidak ada yang cocok” | Bersihkan filter |
| API error | Pesan jelas, tidak bocor exception | Coba lagi |
| Populated | List/table/galllery sesuai data | Search, filter, row action |
| Permission denied | Pesan netral | Kembali |

## 6. Peta artefak desain untuk Stitch
- Buat **satu frame per screen utama**, dan modal/drawer sebagai frame variasi yang menampilkan konteks halaman di belakangnya.
- Label setiap frame dengan ID di tabel untuk mempermudah audit.
- Desktop minimal untuk seluruh halaman index/detail/editor. Mobile untuk alur kritis: onboarding, Home, Needs, Savings, Guests, Invitation, RSVP.
- Jangan tampilkan semua screen sebagai satu kanvas raksasa tanpa pengelompokan; kelompokkan per modul, tetap dengan design system sama.
- Bila batas tool membatasi jumlah output, lanjutkan batch dengan ID yang belum dibuat. Jangan mengklaim semua selesai.
