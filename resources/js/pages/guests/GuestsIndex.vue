<script setup>
import axios from "axios";
import { computed, onMounted, reactive, ref, watch } from "vue";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import RecordDetailHero from "../../components/RecordDetailHero.vue";
import { useConfirmDialog } from "../../composables/useConfirmDialog";
import { useUnsavedForm } from "../../composables/useUnsavedForm";
import { parseGuestCsv } from "../../utils/guestCsv";

const props = defineProps({
  workspaceId: { type: [String, Number], required: true },
});
const {
  open: confirmOpen,
  options: confirmOptions,
  confirm: askConfirm,
  finish: finishConfirm,
} = useConfirmDialog();
const guests = ref([]);
const summary = ref({ total: 0, attending: 0, pending: 0, declined: 0 });
const loading = ref(true);
const error = ref("");
const saving = ref(false);
const showForm = ref(false);
const selected = ref(null);
const editId = ref(null);
const filters = reactive({ q: "", side: "", rsvp_status: "" });
const form = reactive(blankForm());
const formErrors = ref({});
const savedForm = ref("");
const importRows = ref([]);
const importInvalid = ref([]);
const importDuplicates = ref([]);
const importOpen = ref(false);
const importChecking = ref(false);
const importPreviewReady = ref(false);
const importSaving = ref(false);
const importResult = ref(null);
const importError = ref("");
const searchTimer = ref(null);
const guestFormDirty = computed(() => JSON.stringify(form) !== savedForm.value);
const importDirty = computed(
  () =>
    importOpen.value &&
    (importRows.value.length > 0 ||
      importInvalid.value.length > 0 ||
      importDuplicates.value.length > 0) &&
    !importResult.value,
);
const guestFormGuard = useUnsavedForm({
  isOpen: showForm,
  isDirty: guestFormDirty,
  confirm: askConfirm,
  onDiscard: () => {
    showForm.value = false;
  },
  title: "Perubahan data tamu belum disimpan",
});
const guestImportGuard = useUnsavedForm({
  isOpen: importOpen,
  isDirty: importDirty,
  confirm: askConfirm,
  onDiscard: () => {
    importOpen.value = false;
  },
  title: "Pratinjau impor belum selesai",
  message: "Baris tamu yang sudah dipilih akan hilang jika pratinjau ditutup.",
});
const categories = computed(() =>
  [
    ...new Set(guests.value.map((guest) => guest.group_name).filter(Boolean)),
  ].sort(),
);

function blankForm() {
  return {
    name: "",
    email: "",
    phone: "",
    group_name: "",
    side: "shared",
    invitation_status: "not_sent",
    rsvp_status: "pending",
    plus_ones: 0,
    notes: "",
  };
}
function sideName(side) {
  return (
    { bride: "Pihak pertama", groom: "Pihak kedua", shared: "Bersama" }[side] ??
    side
  );
}
function rsvpName(status) {
  return (
    { pending: "Menunggu", attending: "Hadir", declined: "Tidak hadir" }[
      status
    ] ?? status
  );
}
function clearForm() {
  Object.assign(form, blankForm());
  formErrors.value = {};
}
function openCreate() {
  selected.value = null;
  editId.value = null;
  clearForm();
  savedForm.value = JSON.stringify(form);
  showForm.value = true;
}
function openEdit(guest) {
  selected.value = null;
  editId.value = guest.id;
  Object.assign(form, {
    ...blankForm(),
    ...guest,
    email: guest.email ?? "",
    phone: guest.phone ?? "",
    group_name: guest.group_name ?? "",
    notes: guest.notes ?? "",
  });
  savedForm.value = JSON.stringify(form);
  formErrors.value = {};
  showForm.value = true;
}

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const { data } = await axios.get(
      `/api/workspaces/${props.workspaceId}/guests`,
      {
        params: Object.fromEntries(
          Object.entries(filters).filter(([, value]) => value),
        ),
      },
    );
    guests.value = data.data;
    summary.value = data.meta.summary;
  } catch {
    error.value = "Daftar tamu belum berhasil dimuat. Coba lagi.";
  } finally {
    loading.value = false;
  }
}

function searchChanged() {
  window.clearTimeout(searchTimer.value);
  searchTimer.value = window.setTimeout(load, 250);
}
watch(() => [filters.side, filters.rsvp_status], load);

async function save() {
  saving.value = true;
  formErrors.value = {};
  const payload = {
    ...form,
    email: form.email.trim() || null,
    phone: form.phone.trim() || null,
    group_name: form.group_name.trim() || null,
    notes: form.notes.trim() || null,
    plus_ones: Number(form.plus_ones),
  };
  try {
    if (editId.value) await axios.patch(`/api/guests/${editId.value}`, payload);
    else
      await axios.post(`/api/workspaces/${props.workspaceId}/guests`, payload);
    showForm.value = false;
    await load();
  } catch (failure) {
    formErrors.value = failure.response?.data?.errors ?? {
      form: [
        failure.response?.data?.message ??
          "Tamu belum berhasil disimpan. Coba lagi.",
      ],
    };
  } finally {
    saving.value = false;
  }
}

async function openDetail(guest) {
  try {
    selected.value = (await axios.get(`/api/guests/${guest.id}`)).data.guest;
  } catch {
    selected.value = guest;
  }
}

async function remove(guest) {
  if (
    !(await askConfirm({
      title: `Hapus ${guest.name}?`,
      message:
        "Tamu ini akan dihapus dari daftar pasangan. Status RSVP dan tautan undangannya tidak dapat digunakan lagi.",
      confirmLabel: "Hapus tamu",
    }))
  )
    return;
  try {
    await axios.delete(`/api/guests/${guest.id}`);
    selected.value = null;
    await load();
  } catch {
    error.value = "Tamu belum berhasil dihapus. Coba lagi.";
  }
}

async function updateRsvp(guest, rsvp_status) {
  try {
    await axios.patch(`/api/guests/${guest.id}`, { rsvp_status });
    await load();
    if (selected.value?.id === guest.id)
      selected.value = { ...selected.value, rsvp_status };
  } catch {
    error.value = "Status RSVP belum berhasil diperbarui.";
  }
}

async function previewImport() {
  if (!importRows.value.length) {
    importPreviewReady.value = true;
    return;
  }
  importChecking.value = true;
  importPreviewReady.value = false;
  importError.value = "";
  try {
    const { data } = await axios.post(
      `/api/workspaces/${props.workspaceId}/guests/import/preview`,
      { guests: importRows.value },
    );
    importRows.value = data.eligible;
    importDuplicates.value = data.duplicates;
    importPreviewReady.value = true;
  } catch (failure) {
    importError.value =
      Object.values(failure.response?.data?.errors ?? {}).flat()[0] ??
      failure.response?.data?.message ??
      "Pratinjau belum berhasil diperiksa. Coba lagi.";
  } finally {
    importChecking.value = false;
  }
}

async function readCsv(event) {
  const file = event.target.files?.[0];
  event.target.value = "";
  if (!file) return;
  importRows.value = [];
  importInvalid.value = [];
  importDuplicates.value = [];
  importResult.value = null;
  importError.value = "";
  importPreviewReady.value = false;
  importOpen.value = true;

  try {
    if (!/\.csv$/i.test(file.name))
      throw new Error("Pilih file berformat CSV.");
    if (file.size > 2 * 1024 * 1024)
      throw new Error("Ukuran file maksimal 2 MB.");
    const parsed = parseGuestCsv(await file.text());
    importRows.value = parsed.eligible;
    importInvalid.value = parsed.invalid;
    if (importRows.value.length) await previewImport();
    else importPreviewReady.value = true;
  } catch (failure) {
    importError.value =
      failure.message ?? "CSV belum berhasil dibaca. Periksa format file.";
  }
}

async function importGuests() {
  if (!importPreviewReady.value || !importRows.value.length) return;
  importSaving.value = true;
  importError.value = "";
  try {
    const { data } = await axios.post(
      `/api/workspaces/${props.workspaceId}/guests/import`,
      { guests: importRows.value },
    );
    importResult.value = {
      ...data,
      duplicates: [...importDuplicates.value, ...data.duplicates],
      invalidCount: importInvalid.value.length,
    };
    await load();
  } catch (failure) {
    importError.value =
      Object.values(failure.response?.data?.errors ?? {}).flat()[0] ??
      failure.response?.data?.message ??
      "Impor belum berhasil. Coba lagi.";
  } finally {
    importSaving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <section class="guests-page" aria-labelledby="guests-title">
    <div class="page-breadcrumb">
      <span>Persiapan pernikahan</span><span>/</span><span>Tamu</span>
    </div>
    <header class="guests-header">
      <div>
        <p class="eyebrow">
          <span class="eyebrow__dot"></span> DAFTAR ORANG TERSAYANG
        </p>
        <h1 id="guests-title">Tamu undangan</h1>
        <p>Atur daftar tamu dan pantau konfirmasi kehadiran kalian.</p>
      </div>
      <div class="guest-head-actions">
        <label class="button button--quiet"
          >Impor CSV<input
            class="sr-only"
            type="file"
            accept=".csv,text/csv"
            @change="readCsv" /></label
        ><button
          class="button button--primary"
          type="button"
          @click="openCreate"
        >
          <FaIcon :icon="['fas', 'plus']" /> Tambah tamu
        </button>
      </div>
    </header>

    <div
      v-if="loading && !guests.length"
      class="home-skeleton"
      aria-label="Memuat tamu"
    >
      <div class="skeleton-line skeleton-line--short"></div>
      <div class="skeleton-line skeleton-line--title"></div>
      <div class="skeleton-block"></div>
    </div>
    <div v-else-if="error && !summary.total" class="page-error" role="alert">
      <p>{{ error }}</p>
      <button class="button button--quiet" @click="load">Coba lagi</button>
    </div>
    <template v-else>
      <section class="guest-metrics" aria-label="Ringkasan tamu">
        <article>
          <span>DAFTAR TAMU</span><strong>{{ summary.total }}</strong
          ><small>orang tercatat</small>
        </article>
        <article>
          <span>AKAN HADIR</span><strong>{{ summary.attending }}</strong
          ><small>termasuk pendamping</small>
        </article>
        <article>
          <span>MENUNGGU RSVP</span><strong>{{ summary.pending }}</strong
          ><small>belum memberi jawaban</small>
        </article>
        <article>
          <span>TIDAK HADIR</span><strong>{{ summary.declined }}</strong
          ><small>telah mengabari</small>
        </article>
      </section>
      <div v-if="error" class="guest-error" role="alert">{{ error }}</div>
      <section class="guest-table-card">
        <div class="guest-toolbar">
          <label class="guest-search"
            ><FaIcon :icon="['fas', 'magnifying-glass']" /><span class="sr-only"
              >Cari tamu</span
            ><input
              v-model.trim="filters.q"
              placeholder="Cari nama, email, atau kelompok"
              @input="searchChanged" /></label
          ><MotionSelect v-model="filters.side" aria-label="Filter pihak"
            ><option value="">Semua pihak</option>
            <option value="bride">Pihak pertama</option>
            <option value="groom">Pihak kedua</option>
            <option value="shared">Bersama</option></MotionSelect
          ><MotionSelect v-model="filters.rsvp_status" aria-label="Filter RSVP"
            ><option value="">Semua RSVP</option>
            <option value="pending">Menunggu</option>
            <option value="attending">Hadir</option>
            <option value="declined">Tidak hadir</option></MotionSelect
          ><button
            class="filter-reset"
            type="button"
            aria-label="Bersihkan filter"
            @click="
              Object.assign(filters, { q: '', side: '', rsvp_status: '' });
              load();
            "
          >
            <FaIcon :icon="['fas', 'rotate']" />
          </button>
        </div>
        <div v-if="loading" class="guest-loading">Memuat daftar tamu…</div>
        <div v-else-if="!guests.length" class="guest-empty">
          <span class="guest-empty__mark"
            ><FaIcon :icon="['fas', 'people-group']"
          /></span>
          <h2>
            {{
              filters.q || filters.side || filters.rsvp_status
                ? "Tidak ada tamu yang cocok."
                : "Mulai susun daftar tamu kalian."
            }}
          </h2>
          <p>
            {{
              filters.q || filters.side || filters.rsvp_status
                ? "Coba ubah kata pencarian atau filter."
                : "Simpan nama dan kelompok tamu di satu tempat. Kalian bisa mengundang mereka nanti."
            }}
          </p>
          <button
            v-if="!filters.q && !filters.side && !filters.rsvp_status"
            class="button button--primary"
            @click="openCreate"
          >
            <FaIcon :icon="['fas', 'plus']" /> Tambah tamu pertama
          </button>
        </div>
        <div v-else class="guest-list">
          <div class="guest-list__head">
            <span>Nama tamu</span><span>Kelompok</span><span>Pihak</span
            ><span>Status RSVP</span><span></span>
          </div>
          <TransitionGroup name="list" tag="div" class="guest-list__items"
            ><article v-for="guest in guests" :key="guest.id" class="guest-row">
              <button class="guest-row__identity" @click="openDetail(guest)">
                <span class="guest-avatar">{{
                  guest.name.slice(0, 1).toUpperCase()
                }}</span
                ><span
                  ><strong>{{ guest.name }}</strong
                  ><small>{{
                    guest.email || guest.phone || "Belum ada kontak"
                  }}</small></span
                ></button
              ><span class="guest-group">{{ guest.group_name || "—" }}</span
              ><span class="guest-side">{{ sideName(guest.side) }}</span
              ><span
                class="guest-status"
                :class="`guest-status--${guest.rsvp_status}`"
                ><i></i>{{ rsvpName(guest.rsvp_status) }}</span
              ><button
                class="guest-row__more"
                :aria-label="`Lihat ${guest.name}`"
                @click="openDetail(guest)"
              >
                <FaIcon :icon="['fas', 'chevron-right']" />
              </button></article
          ></TransitionGroup>
        </div>
        <footer v-if="guests.length" class="guest-list-footer">
          Menampilkan {{ guests.length }} tamu
          <span>CSV: name, email, phone, group_name, side</span>
        </footer>
      </section>
    </template>

    <Transition name="overlay" appear>
      <div
        v-if="showForm"
        class="sheet-backdrop"
        @click.self="guestFormGuard.requestClose()"
      >
        <aside
          class="guest-sheet"
          role="dialog"
          aria-modal="true"
          aria-labelledby="guest-form-title"
        >
          <header>
            <div>
              <p class="eyebrow">DAFTAR TAMU</p>
              <h2 id="guest-form-title">
                {{ editId ? "Ubah tamu" : "Tambah tamu" }}
              </h2>
            </div>
            <button
              class="icon-button"
              aria-label="Tutup form"
              @click="guestFormGuard.requestClose()"
            >
              <FaIcon :icon="['fas', 'xmark']" />
            </button>
          </header>
          <form @submit.prevent="save">
            <label
              >Nama lengkap<input
                v-model.trim="form.name"
                maxlength="160"
                required
                placeholder="Nama tamu"
              /><small v-if="formErrors.name">{{
                formErrors.name[0]
              }}</small></label
            >
            <div class="guest-form-row">
              <label
                >Email <em>Opsional</em
                ><input
                  v-model.trim="form.email"
                  type="email"
                  placeholder="nama@email.com"
                /><small v-if="formErrors.email">{{
                  formErrors.email[0]
                }}</small></label
              ><label
                >Nomor telepon <em>Opsional</em
                ><input
                  v-model.trim="form.phone"
                  type="tel"
                  placeholder="08xx xxxx xxxx"
              /></label>
            </div>
            <div class="guest-form-row">
              <label
                >Kelompok <em>Opsional</em
                ><input
                  v-model.trim="form.group_name"
                  placeholder="Keluarga, teman, rekan kerja" /></label
              ><label
                >Dari pihak<MotionSelect v-model="form.side"
                  ><option value="shared">Bersama</option>
                  <option value="bride">Pihak pertama</option>
                  <option value="groom">Pihak kedua</option></MotionSelect
                ></label
              >
            </div>
            <div class="guest-form-row">
              <label
                >Status RSVP<MotionSelect v-model="form.rsvp_status"
                  ><option value="pending">Menunggu</option>
                  <option value="attending">Hadir</option>
                  <option value="declined">Tidak hadir</option></MotionSelect
                ></label
              ><label
                >Jumlah pendamping<input
                  v-model.number="form.plus_ones"
                  type="number"
                  min="0"
                  max="10"
              /></label>
            </div>
            <label
              >Catatan <em>Opsional</em
              ><textarea
                v-model.trim="form.notes"
                rows="3"
                placeholder="Catatan untuk tim kalian"
              ></textarea>
            </label>
            <p v-if="formErrors.form" class="guest-error" role="alert">
              {{ formErrors.form[0] }}
            </p>
            <footer>
              <button
                class="button button--quiet"
                type="button"
                @click="guestFormGuard.requestClose()"
              >
                Batal</button
              ><button class="button button--primary" :disabled="saving">
                {{ saving ? "Menyimpan…" : "Simpan tamu" }}
              </button>
            </footer>
          </form>
        </aside>
      </div>
    </Transition>

    <Transition name="overlay" appear>
      <div
        v-if="importOpen"
        class="sheet-backdrop"
        @click.self="guestImportGuard.requestClose()"
      >
        <aside
          class="guest-sheet guest-import-sheet"
          role="dialog"
          aria-modal="true"
          aria-labelledby="guest-import-title"
        >
          <header>
            <div>
              <p class="eyebrow">IMPOR CSV · GU-04</p>
              <h2 id="guest-import-title">Pratinjau tamu</h2>
            </div>
            <button
              class="icon-button"
              aria-label="Tutup impor"
              @click="guestImportGuard.requestClose()"
            >
              <FaIcon :icon="['fas', 'xmark']" />
            </button>
          </header>
          <p class="import-help">
            Periksa nama, email, dan kelompok sebelum ditambahkan. Duplikat
            email dan baris tidak valid tidak akan diimpor.
          </p>
          <div v-if="importError" class="guest-error" role="alert">
            {{ importError }}
          </div>
          <div v-if="importChecking" class="import-checking" role="status">
            Memeriksa email yang sudah tercatat…
          </div>
          <div v-if="importResult" class="import-result" role="status">
            <strong>{{ importResult.created }} tamu ditambahkan</strong>
            <span
              >{{ importResult.duplicates.length }} duplikat dilewati ·
              {{ importResult.invalidCount }} baris tidak valid</span
            >
            <ul v-if="importResult.duplicates.length">
              <li
                v-for="item in importResult.duplicates"
                :key="`${item.row}-${item.email}`"
              >
                Baris {{ item.row }} · {{ item.email }} — {{ item.reason }}
              </li>
            </ul>
          </div>
          <div v-else class="import-preview">
            <div class="import-preview__summary">
              <strong>{{ importRows.length }} baris siap diimpor</strong
              ><span
                >{{ importDuplicates.length }} duplikat dilewati ·
                {{ importInvalid.length }} baris perlu diperbaiki</span
              >
            </div>
            <p
              v-if="
                !importRows.length &&
                !importDuplicates.length &&
                !importInvalid.length &&
                !importError
              "
            >
              Belum ada baris untuk ditinjau.
            </p>
            <article
              v-for="row in importRows.slice(0, 8)"
              :key="`${row.source_row}-${row.name}-${row.email}`"
            >
              <span
                >{{ row.name
                }}<small>{{
                  row.email || row.group_name || "Tanpa email"
                }}</small></span
              ><small>Baris {{ row.source_row }}</small>
            </article>
            <p v-if="importRows.length > 8" class="import-more">
              dan {{ importRows.length - 8 }} baris lain
            </p>
            <section v-if="importDuplicates.length" class="import-issues">
              <h3>Duplikat yang dilewati</h3>
              <ul>
                <li
                  v-for="item in importDuplicates"
                  :key="`${item.row}-${item.email}`"
                >
                  Baris {{ item.row }} · {{ item.email }} — {{ item.reason }}
                </li>
              </ul>
            </section>
            <section
              v-if="importInvalid.length"
              class="import-issues import-issues--invalid"
            >
              <h3>Perlu diperbaiki</h3>
              <ul>
                <li
                  v-for="item in importInvalid.slice(0, 8)"
                  :key="`${item.row}-${item.name}`"
                >
                  Baris {{ item.row }} · {{ item.name }} — {{ item.reason }}
                </li>
              </ul>
              <p v-if="importInvalid.length > 8">
                dan {{ importInvalid.length - 8 }} baris lain
              </p>
            </section>
          </div>
          <footer v-if="!importResult" class="import-actions">
            <label class="button button--quiet"
              >Pilih CSV lain<input
                class="sr-only"
                type="file"
                accept=".csv,text/csv"
                @change="readCsv"
            /></label>
            <button
              class="button button--quiet"
              type="button"
              @click="guestImportGuard.requestClose()"
            >
              Batal
            </button>
            <button
              v-if="importRows.length && !importPreviewReady"
              class="button button--quiet"
              type="button"
              :disabled="importChecking"
              @click="previewImport"
            >
              {{ importChecking ? "Memeriksa…" : "Coba periksa lagi" }}
            </button>
            <button
              v-if="importRows.length && importPreviewReady"
              class="button button--primary"
              type="button"
              :disabled="importSaving"
              @click="importGuests"
            >
              {{
                importSaving ? "Mengimpor…" : `Impor ${importRows.length} tamu`
              }}
            </button>
          </footer>
          <footer v-else>
            <button
              class="button button--primary"
              type="button"
              @click="guestImportGuard.requestClose()"
            >
              Selesai
            </button>
          </footer>
        </aside>
      </div>
    </Transition>

    <Transition name="overlay" appear>
      <div v-if="selected" class="sheet-backdrop" @click.self="selected = null">
        <aside
          class="guest-sheet"
          role="dialog"
          aria-modal="true"
          aria-labelledby="guest-detail-title"
        >
          <RecordDetailHero
            id="guest-detail-title"
            heading-tag="h2"
            eyebrow="DETAIL TAMU"
            :title="selected.name"
            :subtitle="
              [selected.group_name, sideName(selected.side)]
                .filter(Boolean)
                .join(' · ')
            "
            ><template #meta
              ><span
                class="record-detail-chip"
                :class="
                  selected.rsvp_status === 'declined'
                    ? 'record-detail-chip--danger'
                    : selected.rsvp_status === 'attending'
                      ? ''
                      : 'record-detail-chip--warning'
                "
                >{{ rsvpName(selected.rsvp_status) }}</span
              ><span
                class="record-detail-chip"
                :class="
                  selected.invitation_status === 'sent'
                    ? ''
                    : 'record-detail-chip--muted'
                "
                >{{
                  selected.invitation_status === "sent"
                    ? "Undangan terkirim"
                    : "Undangan belum dikirim"
                }}</span
              ></template
            ><template #actions
              ><button
                class="icon-button"
                aria-label="Tutup detail"
                @click="selected = null"
              >
                <FaIcon :icon="['fas', 'xmark']" /></button></template
          ></RecordDetailHero>
          <dl class="guest-detail-list">
            <div>
              <dt>Kelompok</dt>
              <dd>{{ selected.group_name || "Belum dikelompokkan" }}</dd>
            </div>
            <div>
              <dt>Pihak</dt>
              <dd>{{ sideName(selected.side) }}</dd>
            </div>
            <div>
              <dt>Email</dt>
              <dd>{{ selected.email || "Belum dicatat" }}</dd>
            </div>
            <div>
              <dt>Nomor telepon</dt>
              <dd>{{ selected.phone || "Belum dicatat" }}</dd>
            </div>
            <div>
              <dt>Jumlah hadir</dt>
              <dd>{{ 1 + Number(selected.plus_ones || 0) }} orang</dd>
            </div>
            <div>
              <dt>Undangan</dt>
              <dd>
                {{
                  selected.invitation_status === "sent"
                    ? "Terkirim"
                    : "Belum dikirim"
                }}
              </dd>
            </div>
          </dl>
          <p class="guest-detail-notes">
            {{ selected.notes || "Belum ada catatan untuk tamu ini." }}
          </p>
          <div class="guest-detail-actions">
            <button
              class="button button--quiet guest-delete"
              @click="remove(selected)"
            >
              Hapus</button
            ><button class="button button--quiet" @click="openEdit(selected)">
              Ubah data</button
            ><button
              v-if="selected.rsvp_status !== 'attending'"
              class="button button--primary"
              @click="updateRsvp(selected, 'attending')"
            >
              Tandai hadir
            </button>
          </div>
        </aside>
      </div>
    </Transition>
    <ConfirmDialog
      :open="confirmOpen"
      :title="confirmOptions.title"
      :message="confirmOptions.message"
      :confirm-label="confirmOptions.confirmLabel"
      @cancel="finishConfirm(false)"
      @confirm="finishConfirm(true)"
    />
  </section>
</template>

<style scoped>
.guests-page {
  max-width: 1230px;
  margin: auto;
  padding: 38px clamp(20px, 4vw, 56px) 72px;
  color: var(--text-primary);
}
.guests-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
  margin: 18px 0 28px;
}
.guests-header h1 {
  font: 500 clamp(42px, 5vw, 58px)/1 var(--font-display);
  letter-spacing: -1px;
  margin: 15px 0 9px;
}
.guests-header p:last-child {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
}
.guest-metrics {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 20px;
}
.guest-metrics article {
  background: var(--surface);
  border: 1px solid var(--champagne);
  border-radius: 15px;
  padding: 17px 20px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.guest-metrics article span {
  font-size: 10px;
  letter-spacing: 1.1px;
  color: var(--text-secondary);
  font-weight: 700;
}
.guest-metrics strong {
  font: 500 30px/1.1 var(--font-display);
  color: var(--accent);
}
.guest-metrics small {
  font-size: 11px;
  color: #82877f;
}
.guest-table-card {
  background: var(--surface);
  border: 1px solid var(--champagne);
  border-radius: 17px;
  overflow: hidden;
}
.guest-toolbar {
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid var(--champagne);
}
.guest-search {
  flex: 1;
  min-width: 190px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 12px;
  border: 1px solid var(--border);
  border-radius: 9px;
  color: var(--text-secondary);
}
.guest-search input {
  height: 40px;
  border: 0;
  outline: 0;
  flex: 1;
  min-width: 0;
  font: 13px inherit;
  color: var(--text-primary);
}
.guest-toolbar select {
  height: 40px;
  max-width: 190px;
  border: 1px solid var(--border);
  border-radius: 9px;
  padding: 0 12px;
  background: var(--surface);
  color: var(--text-secondary);
  font-size: 12px;
}
.guest-empty {
  text-align: center;
  padding: 58px 24px;
}
.guest-empty__mark {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: var(--surface-soft);
  color: var(--accent);
  display: grid;
  place-items: center;
  margin: 0 auto 16px;
}
.guest-empty h2 {
  font: 500 30px var(--font-display);
  margin: 0 0 8px;
}
.guest-empty p {
  font-size: 13px;
  color: var(--text-secondary);
  max-width: 460px;
  line-height: 1.6;
  margin: 0 auto 20px;
}
.guest-list__head,
.guest-row {
  display: grid;
  grid-template-columns: minmax(240px, 1.7fr) 1fr 0.85fr 1fr 30px;
  gap: 16px;
  align-items: center;
  padding: 12px 20px;
}
.guest-list__head {
  font-size: 10px;
  letter-spacing: 0.9px;
  text-transform: uppercase;
  font-weight: 700;
  color: var(--text-secondary);
  background: var(--surface);
  border-bottom: 1px solid var(--champagne);
}
.guest-row {
  min-height: 72px;
  border-bottom: 1px solid #f0f0eb;
}
.guest-row:last-child {
  border-bottom: 0;
}
.guest-row__identity {
  display: flex;
  align-items: center;
  gap: 11px;
  text-align: left;
  border: 0;
  background: none;
  padding: 0;
  cursor: pointer;
  color: inherit;
  min-width: 0;
}
.guest-avatar {
  width: 37px;
  height: 37px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--surface-soft);
  color: var(--accent);
  font: 500 18px var(--font-display);
}
.guest-row__identity > span:last-child {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.guest-row__identity strong {
  font-size: 13px;
}
.guest-row__identity small {
  font-size: 11px;
  color: #80867d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.guest-group,
.guest-side {
  font-size: 12px;
  color: var(--text-secondary);
}
.guest-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  color: #6d7568;
}
.guest-status i {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #d4a34d;
}
.guest-status--attending i {
  background: #5d8053;
}
.guest-status--declined i {
  background: #ae6660;
}
.guest-row__more {
  width: 28px;
  height: 28px;
  border: 0;
  background: none;
  color: var(--text-secondary);
  cursor: pointer;
}
.guest-list-footer {
  padding: 14px 20px;
  border-top: 1px solid var(--champagne);
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: var(--text-secondary);
}
.guest-list-footer span {
  color: #969a92;
}
.guest-loading {
  padding: 28px;
  text-align: center;
  color: #777;
  font-size: 13px;
}
.guest-error {
  padding: 12px 14px;
  color: #9c3733;
  background: #fff0ee;
  border-radius: 9px;
  font-size: 13px;
  margin-bottom: 12px;
}
.guest-sheet {
  background: var(--surface);
  width: min(560px, 100%);
  height: 100%;
  overflow: auto;
  padding: 30px clamp(20px, 3vw, 36px);
  box-shadow: -12px 0 50px #14171030;
}
.guest-sheet > header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid var(--champagne);
  padding-bottom: 20px;
}
.guest-sheet h2 {
  font: 500 34px var(--font-display);
  margin: 6px 0;
}
.guest-sheet form {
  padding-top: 23px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.guest-sheet label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.guest-sheet label em {
  font-weight: 400;
  color: var(--text-secondary);
  font-style: normal;
  font-size: 10px;
}
.guest-sheet input,
.guest-sheet textarea,
.guest-sheet select {
  font: 13px inherit;
  color: var(--text-primary);
  border: 1px solid var(--border);
  background: var(--surface);
  border-radius: 9px;
  padding: 12px;
  outline: none;
}
.guest-sheet input:focus,
.guest-sheet textarea:focus,
.guest-sheet select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px #74856c1c;
}
.guest-sheet textarea {
  resize: vertical;
}
.guest-sheet label small {
  color: #a33c34;
}
.guest-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.guest-sheet form footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 6px;
}
.guest-detail-list {
  margin: 24px 0;
}
.guest-detail-list > div {
  padding: 13px 0;
  border-bottom: 1px solid var(--champagne);
  display: flex;
  justify-content: space-between;
  gap: 14px;
  font-size: 13px;
}
.guest-detail-list dt {
  color: var(--text-secondary);
}
.guest-detail-list dd {
  margin: 0;
  text-align: right;
  font-weight: 600;
}
.guest-detail-notes {
  padding: 14px;
  border-radius: 10px;
  background: var(--surface-soft);
  font-size: 12px;
  line-height: 1.7;
  color: var(--text-secondary);
}
.guest-detail-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  border-top: 1px solid var(--champagne);
  padding-top: 18px;
  margin-top: 20px;
}
.guest-delete {
  margin-right: auto;
  color: #a33c34 !important;
}
@media (max-width: 780px) {
  .guests-page {
    padding: 26px 17px 54px;
  }
  .guests-header {
    align-items: flex-start;
    flex-direction: column;
  }
  .guests-header h1 {
    font-size: 46px;
  }
  .guest-metrics {
    grid-template-columns: 1fr 1fr;
    gap: 9px;
  }
  .guest-metrics article {
    padding: 14px;
  }
  .guest-toolbar {
    flex-wrap: wrap;
  }
  .guest-search {
    flex-basis: 100%;
  }
  .guest-toolbar select {
    flex: 1;
    max-width: none;
  }
  .guest-list__head {
    display: none;
  }
  .guest-row {
    grid-template-columns: minmax(0, 1fr) auto 28px;
    gap: 9px;
    padding: 13px;
  }
  .guest-group {
    display: none;
  }
  .guest-row__identity {
    grid-column: 1;
  }
  .guest-side {
    display: none;
  }
  .guest-status {
    grid-column: 2;
    grid-row: 1;
  }
  .guest-row__more {
    grid-column: 3;
    grid-row: 1;
  }
  .guest-list-footer {
    flex-direction: column;
    gap: 6px;
  }
  .guest-form-row {
    grid-template-columns: 1fr;
  }
  .guest-detail-actions {
    flex-wrap: wrap;
  }
  .guest-detail-actions .button {
    font-size: 11px;
    padding: 10px;
  }
  .guest-delete {
    width: 100%;
    justify-content: flex-start;
  }
}
.guest-head-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}
.guest-head-actions > label {
  cursor: pointer;
}
.import-help {
  font-size: 12px;
  line-height: 1.65;
  color: var(--text-secondary);
}
.import-preview,
.import-result {
  margin: 18px 0;
  padding: 14px;
  border: 1px solid var(--champagne);
  border-radius: 10px;
  background: var(--surface);
}
.import-preview > strong,
.import-result > strong {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
}
.import-preview article {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 0;
  border-top: 1px solid var(--champagne);
  font-size: 11px;
}
.import-preview small,
.import-result span {
  color: var(--text-secondary);
}
.import-result span {
  display: block;
  font-size: 11px;
}
.import-result ul {
  max-height: 150px;
  overflow: auto;
  font-size: 11px;
  color: #71776d;
}
.import-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  flex-wrap: wrap;
}
.import-actions > label {
  cursor: pointer;
}
.guest-import-sheet {
  max-width: 620px;
}
.import-checking {
  background: var(--surface-soft);
  border-radius: 8px;
  color: var(--text-secondary);
  font-size: 12px;
  margin: 12px 0;
  padding: 11px 13px;
}
.import-preview__summary {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 7px;
}
.import-preview__summary strong {
  font-size: 12px;
}
.import-preview__summary span,
.import-more,
.import-issues p {
  color: var(--text-secondary);
  font-size: 10px;
  margin: 0;
}
.import-preview article > span {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.import-issues {
  border-top: 1px solid var(--champagne);
  margin-top: 11px;
  padding-top: 10px;
}
.import-issues h3 {
  color: #836328;
  font-size: 10px;
  margin: 0 0 5px;
}
.import-issues--invalid h3 {
  color: #a33c34;
}
.import-issues ul {
  color: #6f746d;
  font-size: 10px;
  line-height: 1.6;
  margin: 0;
  max-height: 108px;
  overflow: auto;
  padding-left: 17px;
}
.import-more {
  margin-top: 6px;
}
</style>
