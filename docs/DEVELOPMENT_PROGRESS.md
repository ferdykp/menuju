# MENUJU — Development Progress

> Baseline created 11 November 2026. All statuses start as **Not started**; mapped references indicate available handoff files, not implemented or verified screens. See [`03_USER_FLOWS_AND_SCREENS.md`](03_USER_FLOWS_AND_SCREENS.md) for requirements.

## Project foundation

| Workstream | Status | Evidence / next step |
|---|---|---|
| Laravel project scaffold | Implemented | Laravel 13.42 installed; PHP syntax and route registration checked; SQLite-backed schema test passes. MySQL migration not run because Docker daemon and local MySQL service are unavailable. |
| Vue 3 JavaScript + Vite + Tailwind | Implemented | Vue 3, Vite 7, Tailwind 4, Font Awesome and local fonts installed; production asset build passes. |
| Design tokens and shared shell | In progress | Warm ivory/sage palette, responsive navigation, local font packages and reduced-motion baseline added; narrow preview rendered. Wide-desktop comparison against Stitch is still pending. |
| Authentication and onboarding | Not started | — |
| Screen-level implementation | 0 / 77 implemented | Per-screen rows below. |
| Functional verification | 0 / 77 verified | Foundation suite: 3 tests passed (health endpoint, Vue shell response, workspace schema/relations); no Screen ID feature flow is implemented or verified yet. |

## Screen inventory coverage

| Screen ID | Screen | Stitch HTML / PNG mapping | Design review | Implementation route | Desktop QA | Mobile QA | Function QA | Status | Issue / next action |
|---|---|---|---|---|---|---|---|---|---|
| AU-01 | Auth — Welcome | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| AU-02 | Auth — Login | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| AU-03 | Auth — Register | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| AU-04 | Auth — Forgot password | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| AU-05 | Auth — Reset password | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ON-01 | Onboarding — Buat workspace | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ON-02 | Onboarding — Detail pasangan & tanggal | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ON-03 | Onboarding — Undang partner (skip allowed) | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ON-04 | Onboarding — Selesai | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| HM-01 | Beranda — Home populated | [hm_01_beranda](../references/hm_01_beranda/code.html) · [PNG](../references/hm_01_beranda/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| HM-02 | Beranda — Home new workspace | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| JR-01 | Perjalanan — Timeline milestone | [jr_01_perjalanan_timeline_milestone](../references/jr_01_perjalanan_timeline_milestone/code.html) · [PNG](../references/jr_01_perjalanan_timeline_milestone/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| JR-02 | Perjalanan — Detail milestone | [jr_02_detail_milestone_relasi_tugas](../references/jr_02_detail_milestone_relasi_tugas/code.html) · [PNG](../references/jr_02_detail_milestone_relasi_tugas/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| JR-03 | Perjalanan — Add/edit milestone | [jr_03_form_tambah_milestone_modal](../references/jr_03_form_tambah_milestone_modal/code.html) · [PNG](../references/jr_03_form_tambah_milestone_modal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TK-01 | Checklist — Task index | [tk_01_checklist_persiapan](../references/tk_01_checklist_persiapan/code.html) · [PNG](../references/tk_01_checklist_persiapan/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TK-02 | Checklist — Add/edit task | [tk_02_tambah_tugas_drawer](../references/tk_02_tambah_tugas_drawer/code.html) · [PNG](../references/tk_02_tambah_tugas_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TK-03 | Checklist — Task detail | [tk_03_detail_tugas_checklist_drawer](../references/tk_03_detail_tugas_checklist_drawer/code.html) · [PNG](../references/tk_03_detail_tugas_checklist_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TK-04 | Checklist — Delete task confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| EV-01 | Acara & Jadwal — Event overview | [ev_01_acara_jadwal](../references/ev_01_acara_jadwal/code.html) · [PNG](../references/ev_01_acara_jadwal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| EV-02 | Acara & Jadwal — Event detail/editor | [ev_02_detail_acara_sesi_editor](../references/ev_02_detail_acara_sesi_editor/code.html) · [PNG](../references/ev_02_detail_acara_sesi_editor/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| EV-03 | Acara & Jadwal — Agenda item form | [ev_03_form_tambah_agenda_acara_modal](../references/ev_03_form_tambah_agenda_acara_modal/code.html) · [PNG](../references/ev_03_form_tambah_agenda_acara_modal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| ND-01 | Kebutuhan — Needs index/table | [nd_01_kebutuhan_belanja](../references/nd_01_kebutuhan_belanja/code.html) · [PNG](../references/nd_01_kebutuhan_belanja/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| ND-02 | Kebutuhan — Add/edit need | [nd_02_tambah_kebutuhan_drawer](../references/nd_02_tambah_kebutuhan_drawer/code.html) · [PNG](../references/nd_02_tambah_kebutuhan_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| ND-03 | Kebutuhan — Need detail | [nd_03_detail_kebutuhan](../references/nd_03_detail_kebutuhan/code.html) · [PNG](../references/nd_03_detail_kebutuhan/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| ND-04 | Kebutuhan — Record purchase | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ND-05 | Kebutuhan — Delete confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| GU-01 | Tamu — Guest index | [gu_01_daftar_tamu](../references/gu_01_daftar_tamu/code.html) · [PNG](../references/gu_01_daftar_tamu/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| GU-02 | Tamu — Add/edit guest | [gu_02_tambah_tamu_drawer](../references/gu_02_tambah_tamu_drawer/code.html) · [PNG](../references/gu_02_tambah_tamu_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| GU-03 | Tamu — Guest detail | [gu_03_detail_tamu_status_rsvp_drawer](../references/gu_03_detail_tamu_status_rsvp_drawer/code.html) · [PNG](../references/gu_03_detail_tamu_status_rsvp_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| GU-04 | Tamu — Import guests | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| GU-05 | Tamu — Import result | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| GU-06 | Tamu — RSVP status filter | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| GU-07 | Tamu — Delete confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| IN-01 | Undangan — Invitation library | [in_01_perpustakaan_undangan](../references/in_01_perpustakaan_undangan/code.html) · [PNG](../references/in_01_perpustakaan_undangan/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| IN-02 | Undangan — Choose theme | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| IN-03 | Undangan — Invitation editor | [in_03_studio_editor_undangan](../references/in_03_studio_editor_undangan/code.html) · [PNG](../references/in_03_studio_editor_undangan/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| IN-04 | Undangan — Preview mobile/desktop | [in_03_studio_editor_undangan](../references/in_03_studio_editor_undangan/code.html) · [PNG](../references/in_03_studio_editor_undangan/screen.png) (preview shared/inferred) | Not started | — | — | — | — | Not started | Closest reference mapped for context; dedicated frame missing; confirm design coverage. |
| IN-05 | Undangan — Publish confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| IN-06 | Undangan — Share dialog | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| IN-07 | Undangan — Manage status | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| PF-01 | Undangan publik — Opening & salutation | [pf_01_sampul_sapaan_tamu_mobile](../references/pf_01_sampul_sapaan_tamu_mobile/code.html) · [PNG](../references/pf_01_sampul_sapaan_tamu_mobile/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| PF-02 | Undangan publik — Story / events / gallery / closing | [pf_02_rangkaian_acara_cerita_mobile](../references/pf_02_rangkaian_acara_cerita_mobile/code.html) · [PNG](../references/pf_02_rangkaian_acara_cerita_mobile/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| PF-03 | Undangan publik — RSVP form | [pf_03_formulir_rsvp_doa_restu_mobile](../references/pf_03_formulir_rsvp_doa_restu_mobile/code.html) · [PNG](../references/pf_03_formulir_rsvp_doa_restu_mobile/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| PF-04 | Undangan publik — RSVP success/error | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| FN-01 | Keuangan — Finance overview | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SV-01 | Tabungan — Savings index | [sv_01_tabungan_bersama](../references/sv_01_tabungan_bersama/code.html) · [PNG](../references/sv_01_tabungan_bersama/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| SV-02 | Tabungan — Create/edit goal | [sv_02_buat_target_tabungan_modal](../references/sv_02_buat_target_tabungan_modal/code.html) · [PNG](../references/sv_02_buat_target_tabungan_modal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| SV-03 | Tabungan — Savings detail | [sv_01_tabungan_bersama](../references/sv_01_tabungan_bersama/code.html) · [PNG](../references/sv_01_tabungan_bersama/screen.png) (detail belum ada frame khusus) | Not started | — | — | — | — | Not started | Closest reference mapped for context; dedicated frame missing; confirm design coverage. |
| SV-04 | Tabungan — Add deposit | [sv_04_catat_setoran_modal](../references/sv_04_catat_setoran_modal/code.html) · [PNG](../references/sv_04_catat_setoran_modal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| SV-05 | Tabungan — Deposit history | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SV-06 | Tabungan — Edit/reverse deposit | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| BG-01 | Anggaran — Budget index | [bg_01_alokasi_realisasi_anggaran](../references/bg_01_alokasi_realisasi_anggaran/code.html) · [PNG](../references/bg_01_alokasi_realisasi_anggaran/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| BG-02 | Anggaran — Budget detail | [bg_01_alokasi_realisasi_anggaran](../references/bg_01_alokasi_realisasi_anggaran/code.html) · [PNG](../references/bg_01_alokasi_realisasi_anggaran/screen.png) (detail belum ada frame khusus) | Not started | — | — | — | — | Not started | Closest reference mapped for context; dedicated frame missing; confirm design coverage. |
| BG-03 | Anggaran — Category create/edit | [bg_03_tambah_kategori_anggaran_drawer](../references/bg_03_tambah_kategori_anggaran_drawer/code.html) · [PNG](../references/bg_03_tambah_kategori_anggaran_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| BG-04 | Anggaran — Payment obligations list | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| BG-05 | Anggaran — Record payment | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| TR-01 | Transaksi — Transactions index | [tr_01_catatan_transaksi_mutasi_kas](../references/tr_01_catatan_transaksi_mutasi_kas/code.html) · [PNG](../references/tr_01_catatan_transaksi_mutasi_kas/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TR-02 | Transaksi — Add/edit transaction | [tr_02_catat_transaksi_baru_drawer](../references/tr_02_catat_transaksi_baru_drawer/code.html) · [PNG](../references/tr_02_catat_transaksi_baru_drawer/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| TR-03 | Transaksi — Transaction detail | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| TR-04 | Transaksi — Transfer between accounts | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| TR-05 | Transaksi — Delete/void confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| AC-01 | Akun — Account index | [ac_01_rekening_akun_kas](../references/ac_01_rekening_akun_kas/code.html) · [PNG](../references/ac_01_rekening_akun_kas/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| AC-02 | Akun — Create/edit account | [ac_02_tambah_akun_rekening_modal](../references/ac_02_tambah_akun_rekening_modal/code.html) · [PNG](../references/ac_02_tambah_akun_rekening_modal/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| AC-03 | Akun — Account detail | [ac_01_rekening_akun_kas](../references/ac_01_rekening_akun_kas/code.html) · [PNG](../references/ac_01_rekening_akun_kas/screen.png) (detail belum ada frame khusus) | Not started | — | — | — | — | Not started | Closest reference mapped for context; dedicated frame missing; confirm design coverage. |
| AC-04 | Akun — Archive confirmation | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ST-01 | Pengaturan — Settings overview | [st_01_pengaturan_workspace](../references/st_01_pengaturan_workspace/code.html) · [PNG](../references/st_01_pengaturan_workspace/screen.png) | Not started | — | — | — | — | Not started | Reference frame mapped; review visual and behavior before implementation. |
| ST-02 | Pengaturan — Couple profile | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ST-03 | Pengaturan — Workspace & event settings | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ST-04 | Pengaturan — Partner access | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ST-05 | Pengaturan — Account preferences | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| ST-06 | Pengaturan — Privacy and sharing | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-01 | Semua — Loading skeleton | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-02 | Semua — Empty state | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-03 | Semua — Error + retry | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-04 | Semua — Unsaved changes | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-05 | Semua — Generic destructive confirm | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |
| SYS-06 | Semua — Not found/unauthorized | — | Not started | — | — | — | — | Not started | No matching Stitch frame in supplied handoff; design/behavior needs review. |

## Reference inventory audit

Available Stitch page frames were mapped to their closest Screen IDs above. In this checkout the HTML/PNG screen folders are directly under `references/` (only `DESIGN.md` is inside `references/stitch/`), so the tracker links the files where they were supplied. There are 31 `code.html` + `screen.png` page pairs (28 desktop/application and 3 public mobile invitation frames) plus 2 standalone wedding-photo assets; several inventory entries have no dedicated frame, and some detail/preview views only have a contextual nearest match. The auth, onboarding, shared system states, and remaining missing screens are not represented as dedicated HTML/PNG pairs.

| Reference artifact | Screen ID | Mapping note |
|---|---|---|
| [`ac_01_rekening_akun_kas`](../references/ac_01_rekening_akun_kas/code.html) + [`PNG`](../references/ac_01_rekening_akun_kas/screen.png) | AC-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`ac_02_tambah_akun_rekening_modal`](../references/ac_02_tambah_akun_rekening_modal/code.html) + [`PNG`](../references/ac_02_tambah_akun_rekening_modal/screen.png) | AC-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`bg_01_alokasi_realisasi_anggaran`](../references/bg_01_alokasi_realisasi_anggaran/code.html) + [`PNG`](../references/bg_01_alokasi_realisasi_anggaran/screen.png) | BG-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`bg_03_tambah_kategori_anggaran_drawer`](../references/bg_03_tambah_kategori_anggaran_drawer/code.html) + [`PNG`](../references/bg_03_tambah_kategori_anggaran_drawer/screen.png) | BG-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`ev_01_acara_jadwal`](../references/ev_01_acara_jadwal/code.html) + [`PNG`](../references/ev_01_acara_jadwal/screen.png) | EV-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`ev_02_detail_acara_sesi_editor`](../references/ev_02_detail_acara_sesi_editor/code.html) + [`PNG`](../references/ev_02_detail_acara_sesi_editor/screen.png) | EV-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`ev_03_form_tambah_agenda_acara_modal`](../references/ev_03_form_tambah_agenda_acara_modal/code.html) + [`PNG`](../references/ev_03_form_tambah_agenda_acara_modal/screen.png) | EV-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`gu_01_daftar_tamu`](../references/gu_01_daftar_tamu/code.html) + [`PNG`](../references/gu_01_daftar_tamu/screen.png) | GU-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`gu_02_tambah_tamu_drawer`](../references/gu_02_tambah_tamu_drawer/code.html) + [`PNG`](../references/gu_02_tambah_tamu_drawer/screen.png) | GU-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`gu_03_detail_tamu_status_rsvp_drawer`](../references/gu_03_detail_tamu_status_rsvp_drawer/code.html) + [`PNG`](../references/gu_03_detail_tamu_status_rsvp_drawer/screen.png) | GU-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`hm_01_beranda`](../references/hm_01_beranda/code.html) + [`PNG`](../references/hm_01_beranda/screen.png) | HM-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`in_01_perpustakaan_undangan`](../references/in_01_perpustakaan_undangan/code.html) + [`PNG`](../references/in_01_perpustakaan_undangan/screen.png) | IN-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`in_03_studio_editor_undangan`](../references/in_03_studio_editor_undangan/code.html) + [`PNG`](../references/in_03_studio_editor_undangan/screen.png) | IN-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`jr_01_perjalanan_timeline_milestone`](../references/jr_01_perjalanan_timeline_milestone/code.html) + [`PNG`](../references/jr_01_perjalanan_timeline_milestone/screen.png) | JR-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`jr_02_detail_milestone_relasi_tugas`](../references/jr_02_detail_milestone_relasi_tugas/code.html) + [`PNG`](../references/jr_02_detail_milestone_relasi_tugas/screen.png) | JR-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`jr_03_form_tambah_milestone_modal`](../references/jr_03_form_tambah_milestone_modal/code.html) + [`PNG`](../references/jr_03_form_tambah_milestone_modal/screen.png) | JR-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`nd_01_kebutuhan_belanja`](../references/nd_01_kebutuhan_belanja/code.html) + [`PNG`](../references/nd_01_kebutuhan_belanja/screen.png) | ND-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`nd_02_tambah_kebutuhan_drawer`](../references/nd_02_tambah_kebutuhan_drawer/code.html) + [`PNG`](../references/nd_02_tambah_kebutuhan_drawer/screen.png) | ND-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`nd_03_detail_kebutuhan`](../references/nd_03_detail_kebutuhan/code.html) + [`PNG`](../references/nd_03_detail_kebutuhan/screen.png) | ND-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`pf_01_sampul_sapaan_tamu_mobile`](../references/pf_01_sampul_sapaan_tamu_mobile/code.html) + [`PNG`](../references/pf_01_sampul_sapaan_tamu_mobile/screen.png) | PF-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`pf_02_rangkaian_acara_cerita_mobile`](../references/pf_02_rangkaian_acara_cerita_mobile/code.html) + [`PNG`](../references/pf_02_rangkaian_acara_cerita_mobile/screen.png) | PF-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`pf_03_formulir_rsvp_doa_restu_mobile`](../references/pf_03_formulir_rsvp_doa_restu_mobile/code.html) + [`PNG`](../references/pf_03_formulir_rsvp_doa_restu_mobile/screen.png) | PF-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`st_01_pengaturan_workspace`](../references/st_01_pengaturan_workspace/code.html) + [`PNG`](../references/st_01_pengaturan_workspace/screen.png) | ST-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`sv_01_tabungan_bersama`](../references/sv_01_tabungan_bersama/code.html) + [`PNG`](../references/sv_01_tabungan_bersama/screen.png) | SV-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`sv_02_buat_target_tabungan_modal`](../references/sv_02_buat_target_tabungan_modal/code.html) + [`PNG`](../references/sv_02_buat_target_tabungan_modal/screen.png) | SV-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`sv_04_catat_setoran_modal`](../references/sv_04_catat_setoran_modal/code.html) + [`PNG`](../references/sv_04_catat_setoran_modal/screen.png) | SV-04 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`tk_01_checklist_persiapan`](../references/tk_01_checklist_persiapan/code.html) + [`PNG`](../references/tk_01_checklist_persiapan/screen.png) | TK-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`tk_02_tambah_tugas_drawer`](../references/tk_02_tambah_tugas_drawer/code.html) + [`PNG`](../references/tk_02_tambah_tugas_drawer/screen.png) | TK-02 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`tk_03_detail_tugas_checklist_drawer`](../references/tk_03_detail_tugas_checklist_drawer/code.html) + [`PNG`](../references/tk_03_detail_tugas_checklist_drawer/screen.png) | TK-03 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`tr_01_catatan_transaksi_mutasi_kas`](../references/tr_01_catatan_transaksi_mutasi_kas/code.html) + [`PNG`](../references/tr_01_catatan_transaksi_mutasi_kas/screen.png) | TR-01 | One supplied frame pair; inspect against full Screen ID requirements. |
| [`tr_02_catat_transaksi_baru_drawer`](../references/tr_02_catat_transaksi_baru_drawer/code.html) + [`PNG`](../references/tr_02_catat_transaksi_baru_drawer/screen.png) | TR-02 | One supplied frame pair; inspect against full Screen ID requirements. |

Standalone assets (not UI screens): `atmospheric_indonesian_wedding_portrait_of_bride_and_groom_in_modern_warm/screen.png` and `fine_art_wedding_couple_holding_hands_walking_in_a_quiet_botanical_garden/screen.png`. They are visual references only, not user content or production defaults.

## Status definitions

- **Not started**: no implementation evidence yet.
- **In progress**: implementation exists but required function/visual evidence is incomplete.
- **Implemented**: route and UI exist; still not necessarily tested or visually verified.
- **Verified**: functional and desktop/mobile acceptance evidence is recorded.
- **Blocked**: dependency or decision blocks progress; record the specific cause.
