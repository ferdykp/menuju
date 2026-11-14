# MENUJU — Screen Acceptance, Design Handoff & QA Gate
**Versi:** 1.1 · **Status:** Release/design checklist

## 1. Definisi selesai
Sebuah halaman **tidak selesai** hanya karena mockup sudah ada atau aplikasi berhasil build. Harus melalui: **Designed → Reviewed → Implemented → Functionally tested → Visually verified**. Status boleh **Blocked**, tidak boleh diberi PASS tanpa bukti.

## 2. Screen inventory tracking template
Gunakan satu baris untuk **setiap ID** di `03_USER_FLOWS_AND_SCREENS.md`.

| Screen ID | Stitch link/frame | Design review | Implementation route | Desktop QA | Mobile QA | Function QA | Status | Issue |
|---|---|---|---|---|---|---|---|---|
| AU-01 | — | Not started | — | — | — | — | Not started | — |
| HM-01 | — | Not started | — | — | — | — | Not started | — |
| SV-01 | — | Not started | — | — | — | — | Not started | — |
| ND-01 | — | Not started | — | — | — | — | Not started | — |
| IN-03 | — | Not started | — | — | — | — | Not started | — |
| PF-01 | — | Not started | — | — | — | — | Not started | — |

**Prinsip:** tabel ini hanya contoh awal; *wajib* diperluas ke seluruh ID inventaris, bukan hanya baris di atas. Jangan mengubah “Not started” menjadi “Verified” tanpa hasil uji.

## 3. Checklist UI per index
- [ ] Filter acara mendukung semua acara, acara tertentu, dan tugas umum; ringkasan progres mengikuti acara yang dipilih.
- [ ] H1 jelas, tidak diulang di card besar.
- [ ] Satu primary action tampak dalam 5 detik.
- [ ] List/table/gallery menjadi konten utama sesuai domain.
- [ ] Tidak ada create/edit form permanen pada index.
- [ ] Search/filter hanya jika bermanfaat, bekerja dan dapat direset.
- [ ] Tabel memiliki action yang jelas dan pagination bila dibutuhkan.
- [ ] Data dummy tidak ada di UI produksi.
- [ ] Tidak mengulang content yang sama dari halaman lain tanpa alasan.
- [ ] Empty/loading/error/populated dapat ditinjau.

## 4. Checklist form/modals/drawers
- [ ] Form tugas dapat mengaitkan tugas ke acara secara opsional dan mempertahankan pilihan pada edit.
- [ ] Form terpisah dari index (kecuali settings atau workflow dengan alasan jelas).
- [ ] Input memiliki label, border, focus, error dan disabled state.
- [ ] Save, cancel, close bekerja; unsaved changes diproteksi.
- [ ] Semua button icon-only punya accessible name.
- [ ] Keyboard tab, Escape, focus trap/focus restore sesuai jenis dialog.
- [ ] Validation client dan server konsisten.
- [ ] Error API tidak menampilkan stack trace ke end user.
- [ ] Aksi destructive meminta konfirmasi akurat.

## Checklist per acara
- [ ] Satu workspace dapat mengelola beberapa acara; tanggal, jadwal, progres, dan hubungan kebutuhan/tugas tiap acara tidak tercampur.
- [ ] Detail acara menampilkan tugas terkait, jumlah selesai, dan persentase progres; tugas umum tidak ikut dihitung.
- [ ] Tombol dari detail acara membuka Checklist terfilter dan tombol tambah membuka form dengan acara tersebut terpilih.
- [ ] Menghapus acara melepas tugas ke Umum tanpa menghapus tugasnya.

## 5. Checklist keuangan
- [ ] Seluruh field uang pakai `id-ID` dengan `Rp` dan separator ribuan.
- [ ] Nilai API tidak menyimpan string berformat mata uang.
- [ ] `0`, kosong, nilai negatif, desimal domain, paste, dan delete telah diuji.
- [ ] Jumlah dalam dashboard sesuai data sumber.
- [ ] Setoran ≠ pemasukan otomatis kecuali workflow jelas.
- [ ] Kebutuhan yang direncanakan ≠ transaksi yang sudah dibayar.
- [ ] Setiap kebutuhan dapat ditugaskan kepada anggota aktif workspace; ringkasan menampilkan rencana dan realisasi per penanggung jawab secara terpisah.
- [ ] Filter penanggung jawab membatasi daftar kebutuhan tanpa mengubah ringkasan workspace; anggota aktif tanpa kebutuhan tetap terlihat dengan total nol.
- [ ] Kategori kebutuhan dipilih dari daftar workspace; tambah kategori baru tersedia dari form tanpa mengetik ulang label untuk setiap item.
- [ ] Filter kategori menampilkan jumlah item, estimasi belum dibeli, dan realisasi pembelian kategori tersebut.
- [ ] Form kebutuhan menyediakan pilihan satu acara aktif pada workspace atau “Umum / tanpa acara”; filter acara membatasi daftar kebutuhan dan dapat direset.
- [ ] Detail acara menunjukkan kebutuhan tertaut, jumlah item, subtotal estimasi belum dibeli dan realisasi pembelian; keadaan tanpa item memiliki pesan dan CTA yang jelas.
- [ ] Menghapus acara tidak menghapus kebutuhan; kebutuhan terkait menjadi “Umum / tanpa acara”, termasuk catatan yang sudah diarsipkan.
- [ ] Transfer rekening tidak dihitung sebagai pengeluaran total.
- [ ] Penghapusan/pembatalan transaksi mengikuti aturan audit.
- [ ] User tidak melihat akun/workspace lain.
- [ ] Nilai aset non-tunai ditampilkan terpisah dari saldo tunai, transaksi, dan target tabungan.
- [ ] Perubahan nilai aset menyimpan histori nilai dan tanggal; catatan lama tidak ditimpa.
- [ ] Jumlah alokasi aset tidak dapat melampaui jumlah tersedia; lepaskan alokasi sebelum menghapus aset.
- [ ] Kebutuhan yang disiapkan dari aset tidak menulis transaksi pembelian atau mengurangi saldo.
- [ ] Beranda menyarankan prioritas hanya dari kondisi data aktual, memberi alasan dan tautan ke layar terkait.
- [ ] Menunda prioritas berlaku untuk pengguna yang menundanya selama tujuh hari; kondisi yang sudah terselesaikan tidak ditampilkan lagi.
- [ ] Aktivitas dan keputusan dibatasi ke workspace; riwayat tidak menyimpan dump data sensitif seperti email, telepon, atau catatan penuh.

## 6. Checklist undangan publik
- [ ] Publish/unpublish dan preview sesuai state.
- [ ] Cover dan foto opsional punya fallback baik.
- [ ] Jam/lokasi/tanggal benar pada zona waktu acara.
- [ ] Long-scroll nyaman, tidak scroll-jacking.
- [ ] RSVP dapat digunakan tanpa login dan terproteksi rate limit/validasi.
- [ ] Konten publik tidak membuka email/telepon tamu lain.
- [ ] Animasi dipangkas untuk reduced motion / device lambat.
- [ ] Media optimized; tidak ada elemen melompat setelah load.
- [ ] Audio hanya diputar setelah gesture pengguna.

## 7. Matrix QA minimal
| Lingkungan | Layar yang wajib diperiksa | Catatan |
|---|---|---|
| Desktop 1440×900 | Semua index, detail, editor, modal/drawer | Periksa hierarki dan ruang |
| Laptop 1280×800 | Semua index dan form kompleks | Periksa overflow |
| Tablet 768×1024 | Navigasi, table, drawer, editor | Adaptasi kolom |
| Mobile 390×844 | Semua journey kritis | Touch & keyboard |
| Mobile 360×800 | Form, undangan publik, checkout-like flows | Jangan terpotong |
| Keyboard only | Auth, modal, form, table actions | Focus sequence |
| Reduced motion | Invitation, modal, progress | Konten tetap aksesibel |

## 8. Review rubric — nilai 0 sampai 3 per dimensi
- **Clarity:** pengguna langsung paham tujuan screen.
- **Hierarchy:** informasi primer dan sekunder jelas.
- **Flow:** ada jalan maju, kembali, selesai yang masuk akal.
- **Visual quality:** detail tipografi, spacing, contrast, tidak generik.
- **Accessibility:** label, focus, keyboard, target sentuh.
- **Responsiveness:** mobile bukan desktop yang dikecilkan.
- **Data integrity:** aksi memuat dan menulis data dengan aman.

Interpretasi: 0 = gagal, 1 = bermasalah, 2 = layak dengan perbaikan, 3 = matang. Screen **tidak disetujui** bila Clarity, Flow, Accessibility, atau Data integrity <2.

## 9. Pengujian user journey E2E
1. Onboarding: workspace dibuat dan pengguna diarahkan ke beranda.
2. Checklist: buat → edit → tandai selesai → lihat progres.
3. Tabungan: buat target → setor → lihat histori dan sisa target.
4. Kebutuhan: buat item → ubah estimasi → catat pembelian → tinjau transaksi.
5. Tamu: tambah/impor → kirim undangan → RSVP → status berubah.
6. Undangan: pilih tema → edit → preview → publish → buka link publik.
7. Keuangan: opening balance → pemasukan/pengeluaran → transfer → saldo tepat.
8. Security: akses lintas-workspace ditolak.
9. Aset: catat emas → nilai ulang tanpa menghapus histori → alokasikan ke kebutuhan mahar → pastikan saldo tunai dan transaksi tidak berubah.
10. Kolaborasi: buat keputusan terkait acara → tandai diputuskan → lihat jejak perubahan oleh anggota workspace.

## 10. Handoff Stitch → engineering
- Sertakan link/frame dan ID screen.
- Jelaskan behavior setiap CTA, bukan hanya tampilan.
- Sertakan state empty/populated/error serta screenshot mobile.
- Cantumkan data schema yang dibutuhkan; jika backend belum mendukung, tandai sebagai gap, jangan dummy permanen.
- Sertakan design tokens dan variasi komponen.
- Komponen reusable dibangun berdasarkan pola yang telah disetujui, bukan menyalin markup mockup secara buta.

## 11. Release gate
- [ ] Semua screen inventory memiliki status dan owner review.
- [ ] Tidak ada screen kritis yang hilang dari desain.
- [ ] Semua main journey lolos functional QA.
- [ ] Screenshot desktop/mobile diambil dan dibandingkan dengan rancangan.
- [ ] Tidak ada bug keuangan atau otorisasi terbuka.
- [ ] Seluruh blocked/unverified dicatat; tidak diklaim selesai.

## 12. Format laporan akhir
Laporkan jumlah layar yang didesain, diimplementasi, diverifikasi, tertunda; perubahan IA; hasil visual dan E2E; bug keamanan/finansial; daftar issue dengan prioritas, pemilik, dan status.
