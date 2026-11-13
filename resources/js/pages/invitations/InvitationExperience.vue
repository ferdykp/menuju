<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from "vue";
import InvitationOrnaments from "./InvitationOrnaments.vue";

const props = defineProps({
  invitation: { type: Object, required: true },
  guestToken: { type: String, default: "" },
  invalidGuestLink: { type: Boolean, default: false },
  result: { type: String, default: "" },
  formError: { type: String, default: "" },
  submitting: { type: Boolean, default: false },
  preview: { type: Boolean, default: false },
});
const emit = defineEmits(["submit-rsvp"]);
const opened = ref(false);
const musicEnabled = ref(false);
const musicUnavailable = ref(false);
const coverRef = ref(null);
const coverVisible = ref(true);
const heroRef = ref(null);
const storyRef = ref(null);
const lightboxIndex = ref(-1);
const contentRoot = ref(null);
let revealObserver = null;
let motionObserver = null;
let coverObserver = null;
let scrollFrame = 0;
let audioContext = null;
let musicInterval = null;
let musicStep = 0;
const form = reactive({ rsvp_status: "attending", plus_ones: 0 });
const motionPreset = computed(() => {
  const configured = props.invitation?.motion_preset || "theme";
  if (configured !== "theme") return configured;
  return (
    {
      botanical: "garden",
      classic: "editorial",
      modern: "minimal",
      terracotta: "cinematic",
    }[props.invitation?.theme_key] || "garden"
  );
});
const decorativeEffects = computed(
  () => props.invitation?.decorative_effects !== false,
);
const activeGalleryImage = computed(
  () => props.invitation?.gallery_images?.[lightboxIndex.value],
);
const coverArtwork = computed(() => ({
  botanical: "/images/invitations/enchanted-garden-background.webp",
  terracotta: "/images/invitations/blooming-promise-background.webp",
}[props.invitation?.theme_key] || ""));
const fallingLeaves = [
  { left: "8%", duration: "19s", delay: "-13s", drift: "72px", spin: "390deg" },
  { left: "23%", duration: "23s", delay: "-4s", drift: "-54px", spin: "-310deg" },
  { left: "39%", duration: "20s", delay: "-17s", drift: "88px", spin: "440deg" },
  { left: "57%", duration: "25s", delay: "-8s", drift: "-74px", spin: "-360deg" },
  { left: "73%", duration: "18s", delay: "-11s", drift: "62px", spin: "420deg" },
  { left: "91%", duration: "22s", delay: "-2s", drift: "-80px", spin: "-390deg" },
];
const coverEyebrow = computed(() =>
  props.invitation?.theme_key === "terracotta" ? "OUR ENGAGEMENT" : "THE WEDDING OF",
);
const daysUntil = computed(() => {
  if (!props.invitation?.wedding_date) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(`${props.invitation.wedding_date}T00:00:00`);
  return Math.max(0, Math.ceil((target - today) / 86400000));
});
const coverNames = computed(() =>
  String(props.invitation?.cover_title || "")
    .split(/\s*&\s*/)
    .filter(Boolean),
);
const coverEvent = computed(
  () =>
    props.invitation?.events?.find((event) =>
      ["ceremony", "reception"].includes(event.type),
    ) ||
    props.invitation?.events?.[0] ||
    null,
);
const coverDate = computed(
  () => coverEvent.value?.date || props.invitation?.wedding_date,
);
const coverLocation = computed(
  () =>
    coverEvent.value?.venue_name ||
    coverEvent.value?.address ||
    "Lokasi menyusul",
);
function dateLabel(value, options = {}) {
  return value
    ? new Date(`${String(value).slice(0, 10)}T12:00:00Z`).toLocaleDateString(
        "id-ID",
        {
          timeZone: "UTC",
          weekday: "long",
          day: "numeric",
          month: "long",
          year: "numeric",
          ...options,
        },
      )
    : "Tanggal menyusul";
}
function begin() {
  opened.value = true;
  void startAmbientMusic();
}
function playAmbientNote(context, frequency, volume = 0.024, duration = 3.1) {
  const now = context.currentTime;
  const oscillator = context.createOscillator();
  const overtone = context.createOscillator();
  const fundamentalGain = context.createGain();
  const overtoneGain = context.createGain();
  oscillator.type = "sine";
  overtone.type = "sine";
  oscillator.frequency.setValueAtTime(frequency, now);
  overtone.frequency.setValueAtTime(frequency * 2.01, now);
  fundamentalGain.gain.setValueAtTime(0.0001, now);
  fundamentalGain.gain.exponentialRampToValueAtTime(volume, now + 0.08);
  fundamentalGain.gain.exponentialRampToValueAtTime(0.0001, now + duration);
  overtoneGain.gain.setValueAtTime(0.0001, now);
  overtoneGain.gain.exponentialRampToValueAtTime(volume * 0.18, now + 0.08);
  overtoneGain.gain.exponentialRampToValueAtTime(0.0001, now + duration * 0.72);
  oscillator.connect(fundamentalGain).connect(context.destination);
  overtone.connect(overtoneGain).connect(context.destination);
  oscillator.start(now);
  overtone.start(now);
  oscillator.stop(now + duration);
  overtone.stop(now + duration * 0.72);
  oscillator.addEventListener("ended", () => {
    oscillator.disconnect();
    fundamentalGain.disconnect();
    overtone.disconnect();
    overtoneGain.disconnect();
  }, { once: true });
}
async function startAmbientMusic() {
  if (musicEnabled.value || audioContext) return;
  const AudioContextClass = window.AudioContext || window.webkitAudioContext;
  if (!AudioContextClass) {
    musicUnavailable.value = true;
    return;
  }
  musicUnavailable.value = false;
  let context;
  try {
    context = new AudioContextClass();
    audioContext = context;
    await context.resume();
    if (audioContext !== context) return;
    if (context.state !== "running") throw new Error("Audio context is suspended");
    const notes = [261.63, 293.66, 329.63, 392, 440, 523.25, 587.33, 659.25];
    const playNext = () => {
      if (audioContext !== context || context.state !== "running") return;
      const index = musicStep % notes.length;
      playAmbientNote(context, notes[index]);
      if (musicStep % 4 === 0) {
        playAmbientNote(context, notes[(index + 3) % notes.length] / 2, 0.009, 3.8);
      }
      musicStep += 1;
    };
    musicEnabled.value = true;
    musicStep = 0;
    playNext();
    musicInterval = window.setInterval(playNext, 1800);
  } catch {
    stopAmbientMusic();
    musicUnavailable.value = true;
  }
}
function stopAmbientMusic() {
  musicEnabled.value = false;
  if (musicInterval) window.clearInterval(musicInterval);
  musicInterval = null;
  const context = audioContext;
  audioContext = null;
  if (context && context.state !== "closed") {
    void context.close().catch(() => {});
  }
}
function toggleAmbientMusic() {
  if (musicEnabled.value) stopAmbientMusic();
  else void startAmbientMusic();
}
function onCoverBeforeEnter() {
  coverVisible.value = true;
}
function onCoverAfterLeave() {
  coverVisible.value = false;
}
function scrollToElement(element) {
  if (!element) return;
  element.scrollIntoView({
    behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
      ? "auto"
      : "smooth",
    block: "start",
  });
}
function scrollToSection(id) {
  scrollToElement(document.getElementById(id));
}
function returnToCover() {
  window.scrollTo(0, 0);
  opened.value = false;
}
function closeLightbox() {
  lightboxIndex.value = -1;
}
function onLightboxKey(event) {
  if (event.key === "Escape") closeLightbox();
}
function observeReveals() {
  if (!contentRoot.value) return;
  revealObserver?.disconnect();
  const targets = contentRoot.value.querySelectorAll(
    ".public-hero,.public-story,.public-countdown,.public-events,.public-gallery,.public-rsvp,.public-footer,.public-event,.public-story__portrait,.public-gallery__grid button,.public-agenda > div",
  );
  if (
    window.matchMedia("(prefers-reduced-motion: reduce)").matches ||
    !("IntersectionObserver" in window)
  ) {
    targets.forEach((target) => target.classList.add("is-in-view"));
    return;
  }
  revealObserver = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-in-view");
          revealObserver?.unobserve(entry.target);
        }
      }),
    { threshold: 0.12, rootMargin: "0px 0px -8% 0px" },
  );
  targets.forEach((target, index) => {
    target.style.setProperty("--reveal-index", Math.min(index % 6, 5));
    revealObserver.observe(target);
  });
}
function updateParallax() {
  scrollFrame = 0;
  if (
    !decorativeEffects.value ||
    window.matchMedia("(prefers-reduced-motion: reduce)").matches
  )
    return;
  const maxShift = window.innerWidth < 700 ? 5 : 11;
  contentRoot.value
    ?.querySelectorAll(".public-hero__image,.public-story__portrait img,.public-gallery__grid img")
    .forEach((image) => {
      const rect = image.getBoundingClientRect();
      const distance =
        (rect.top + rect.height / 2 - window.innerHeight / 2) /
        Math.max(window.innerHeight, 1);
      image.style.setProperty(
        "--photo-shift",
        `${Math.max(-maxShift, Math.min(maxShift, -distance * maxShift))}px`,
      );
    });
}
function onScroll() {
  if (!scrollFrame) scrollFrame = requestAnimationFrame(updateParallax);
}
function emitRsvp() {
  emit("submit-rsvp", {
    rsvp_status: form.rsvp_status,
    plus_ones: Number(form.plus_ones),
  });
}
onMounted(async () => {
  await nextTick();
  observeReveals();
  if ("IntersectionObserver" in window && coverRef.value) {
    coverObserver = new IntersectionObserver(
      ([entry]) => {
        coverVisible.value = entry.isIntersecting;
      },
      { threshold: 0.1 },
    );
    coverObserver.observe(coverRef.value);
  }
  if ("MutationObserver" in window && contentRoot.value) {
    motionObserver = new MutationObserver(() => {
      requestAnimationFrame(observeReveals);
    });
    motionObserver.observe(contentRoot.value, {
      childList: true,
      subtree: true,
    });
  }
  window.addEventListener("scroll", onScroll, { passive: true });
});
watch(
  () => props.invitation?.gallery_images?.length,
  async () => {
    await nextTick();
    observeReveals();
  },
);
watch(lightboxIndex, (index) => {
  if (index < 0) {
    window.removeEventListener("keydown", onLightboxKey);
    return;
  }
  window.addEventListener("keydown", onLightboxKey);
});
onBeforeUnmount(() => {
  stopAmbientMusic();
  revealObserver?.disconnect();
  coverObserver?.disconnect();
  motionObserver?.disconnect();
  window.removeEventListener("scroll", onScroll);
  window.removeEventListener("keydown", onLightboxKey);
  if (scrollFrame) cancelAnimationFrame(scrollFrame);
});
</script>
<template>
  <component
    :is="preview ? 'div' : 'main'"
    class="public-wedding"
    :class="[
      `public-wedding--${invitation.theme_key}`,
      `public-wedding--motion-${motionPreset}`,
      {
        'public-wedding--decorative': decorativeEffects,
        'public-wedding--preview': preview,
      },
    ]"
  >
      <div
        v-if="invitation.theme_key === 'botanical' && decorativeEffects"
        class="falling-leaves"
        aria-hidden="true"
      >
        <span
          v-for="(leaf, index) in fallingLeaves"
          :key="index"
          class="falling-leaf"
          :style="{
            left: leaf.left,
            '--leaf-duration': leaf.duration,
            '--leaf-delay': leaf.delay,
            '--leaf-drift': leaf.drift,
            '--leaf-spin': leaf.spin,
          }"
        ></span>
      </div>
      <Transition
        name="invitation-cover"
        @before-enter="onCoverBeforeEnter"
        @after-leave="onCoverAfterLeave"
      >
      <section
        v-if="!opened"
        ref="coverRef"
        id="undangan-sampul"
        class="public-cover"
      >
        <div v-if="opened" class="public-cover__page-turn" aria-hidden="true"></div>
        <img
          v-if="coverArtwork"
          class="public-cover__artwork"
          :src="coverArtwork"
          alt=""
          aria-hidden="true"
          fetchpriority="high"
        />
        <div class="public-cover__frame" aria-hidden="true"></div>
        <div
          v-if="decorativeEffects"
          class="public-cover__decoration"
          aria-hidden="true"
        >
          <InvitationOrnaments v-if="!coverArtwork" :theme="invitation.theme_key" placement="cover" />
          <span class="cover-speck cover-speck--one"></span>
          <span class="cover-speck cover-speck--two"></span>
          <span class="cover-speck cover-speck--three"></span>
          <span class="cover-petal cover-petal--one"></span>
          <span class="cover-petal cover-petal--two"></span>
          <span class="cover-petal cover-petal--three"></span>
          <span class="cover-petal cover-petal--four"></span>
          <span class="cover-petal cover-petal--five"></span>
          <span class="cover-petal cover-petal--six"></span>
        </div>
        <header class="public-cover__header">
          <div class="public-cover__monogram">m</div>
          <p class="public-cover__eyebrow">{{ coverEyebrow }}</p>
        </header>
        <div class="public-cover__content">
          <h1>
            <span>{{ coverNames[0] || invitation.cover_title }}</span
            ><em>&amp;</em><span>{{ coverNames[1] || "" }}</span>
          </h1>
          <p class="public-cover__date">
            <span>{{ dateLabel(coverDate, { weekday: "long" }) }}</span
            ><i>•</i><span>{{ coverLocation }}</span>
          </p>
          <section class="public-cover__salutation">
            <p>KEPADA YTH. BAPAK/IBU/SAUDARA/I</p>
            <h2>{{ invitation.guest_name || "Tamu Undangan" }}</h2>
            <span>di Tempat</span>
          </section>
          <button class="public-open-button" @click="begin">
            Buka undangan
            <FaIcon :icon="['fas', 'arrow-right']" />
          </button>
          <p class="public-cover__privacy">
            Undangan ini dibagikan secara pribadi.
          </p>
        </div>
      </section>
      </Transition>
    <div id="invitation-content" ref="contentRoot" class="public-content">
      <section
        ref="heroRef"
        id="undangan-hero"
        class="public-hero"
        :class="{ 'public-hero--with-image': invitation.cover_image_url }"
      >
        <img
          v-if="invitation.cover_image_url"
          class="public-hero__image"
          :src="invitation.cover_image_url"
          :alt="`Foto sampul ${invitation.cover_title}`"
          fetchpriority="high"
        />
        <div class="public-hero__wash" aria-hidden="true"></div>
        <InvitationOrnaments
          v-if="decorativeEffects && !['botanical', 'terracotta'].includes(invitation.theme_key)"
          :theme="invitation.theme_key"
          placement="hero"
        />
        <div class="public-hero__copy">
          <p class="public-hero__eyebrow">DENGAN PENUH KASIH</p>
          <h2>{{ invitation.cover_title }}</h2>
          <span class="public-hero__rule"></span>
          <p class="public-hero__date">{{ dateLabel(coverDate) }}</p>
          <p class="public-hero__venue">{{ coverLocation }}</p>
          <button class="public-hero__continue" type="button" @click="scrollToElement(storyRef)">
            Lanjutkan kisah <FaIcon :icon="['fas','arrow-down']" />
          </button>
        </div>
        <span class="public-hero__caption">A · & · B</span>
      </section>
      <section id="undangan-kisah" ref="storyRef" class="public-story">
        <InvitationOrnaments
          v-if="decorativeEffects"
          class="public-story__ornament"
          :theme="invitation.theme_key"
          placement="divider"
        />
        <p class="eyebrow">CERITA KAMI</p>
        <h2>{{ invitation.cover_title }}</h2>
        <div class="public-story__layout">
          <figure v-if="invitation.gallery_images?.length || invitation.cover_image_url" class="public-story__portrait">
            <img
              :src="invitation.gallery_images?.[0]?.url || invitation.cover_image_url"
              :alt="invitation.gallery_images?.[0]?.alt || `Kenangan ${invitation.cover_title}`"
              loading="lazy"
            />
            <figcaption><span>Awal cerita</span><strong>{{ coverNames[0] || '' }} <i>&amp;</i> {{ coverNames[1] || '' }}</strong></figcaption>
          </figure>
          <div class="public-story__words">
            <p v-if="invitation.greeting" class="public-greeting">{{ invitation.greeting }}</p>
            <p v-if="invitation.story || !invitation.greeting" class="public-story__copy">
              {{ invitation.story || "Dengan penuh rasa syukur, kami ingin berbagi kabar bahagia ini dengan keluarga dan sahabat." }}
            </p>
            <blockquote v-if="invitation.quote">{{ invitation.quote }}</blockquote>
          </div>
        </div>
        <InvitationOrnaments v-if="decorativeEffects" class="public-section-flourish" :theme="invitation.theme_key" placement="divider" />
      </section>
      <section v-if="daysUntil !== null" class="public-countdown">
        <InvitationOrnaments v-if="decorativeEffects" :theme="invitation.theme_key" placement="divider" />
        <p class="eyebrow">MENUJU HARI ISTIMEWA</p>
        <strong>{{ daysUntil }}</strong
        ><span>hari lagi</span>
        <p class="public-countdown__date">{{ dateLabel(coverDate) }}</p>
      </section>
      <section id="undangan-acara" class="public-events">
        <InvitationOrnaments v-if="decorativeEffects" class="public-events__ornament" :theme="invitation.theme_key" placement="divider" />
        <p class="eyebrow">DENGAN HORMAT, KAMI MENGUNDANG</p>
        <h2>Rangkaian acara</h2>
        <article
          v-for="event in invitation.events"
          :key="event.title"
          class="public-event"
        >
          <p class="public-event__type">
            {{
              {
                engagement: "Lamaran",
                ceremony: "Akad / Pemberkatan",
                reception: "Resepsi",
                family: "Acara keluarga",
                other: "Acara",
              }[event.type] || "Acara"
            }}
          </p>
          <h3>{{ event.title }}</h3>
          <p class="public-event__date">{{ dateLabel(event.date) }}</p>
          <p v-if="event.start_time" class="public-event__time">
            {{ event.start_time.slice(0, 5) }}<span v-if="event.end_time" class="public-event__time-range"> — {{ event.end_time.slice(0, 5) }}</span><span class="public-event__timezone">{{ invitation.timezone?.replace("_", " ") || "Asia/Jakarta" }}</span>
          </p>
          <p class="public-event__venue">
            {{ event.venue_name || "Lokasi akan diinformasikan" }}
          </p>
          <p v-if="event.address" class="public-event__address">
            {{ event.address }}
          </p>
          <div v-if="event.agenda?.length" class="public-agenda">
            <div v-for="item in event.agenda" :key="item.title">
              <time>{{ item.start_time?.slice(0, 5) || "—" }}</time
              ><span>{{ item.title }}</span>
            </div>
          </div>
        </article>
        <p v-if="!invitation.events?.length" class="public-no-events">
          Detail rangkaian acara akan segera diperbarui.
        </p>
      </section>
      <section v-if="invitation.gallery_images?.length" id="undangan-galeri" class="public-gallery">
        <InvitationOrnaments v-if="decorativeEffects" class="public-gallery__ornament" :theme="invitation.theme_key" placement="divider" />
        <p class="eyebrow">POTRET KAMI</p>
        <h2>Beberapa kenangan</h2>
        <div class="public-gallery__grid">
          <button
            v-for="(image, index) in invitation.gallery_images"
            :key="image.url"
            type="button"
            :aria-label="`Perbesar foto ${index + 1}`"
            @click="lightboxIndex = index"
          >
            <img
              :src="image.url"
              :alt="image.alt || 'Foto cerita pasangan'"
              loading="lazy"
            />
          </button>
        </div>
      </section>
      <section id="undangan-rsvp" class="public-rsvp">
        <InvitationOrnaments v-if="decorativeEffects" class="public-rsvp__ornament" :theme="invitation.theme_key" placement="divider" />
        <div v-if="invalidGuestLink" class="public-rsvp__invalid" role="alert">
          <span><FaIcon :icon="['fas', 'link']" /></span>
          <p class="eyebrow">TAUTAN RSVP</p>
          <h2>Tautan tidak berlaku.</h2>
          <p>
            Tautan RSVP ini tidak berlaku atau sudah tidak aktif. Minta pasangan
            mengirimkan tautan terbaru.
          </p>
        </div>
        <template v-else-if="result">
          <div class="public-rsvp__success">
            <span><FaIcon :icon="['fas', 'check']" /></span>
            <p class="eyebrow">KONFIRMASI TERCATAT</p>
            <h2>Terima kasih.</h2>
            <p>{{ result }}</p>
          </div>
        </template>
        <template v-else>
          <p class="eyebrow">KONFIRMASI KEHADIRAN</p>
          <h2>Kehadiran Anda</h2>
          <p v-if="invitation.guest_name" class="public-rsvp__hello">
            {{ invitation.guest_name }}, kami menantikan kabar dari Anda.
          </p>
          <p v-else class="public-rsvp__hello">
            Konfirmasi menggunakan tautan RSVP personal yang diberikan kepada
            Anda.
          </p>
          <form v-if="guestToken" @submit.prevent="emitRsvp">
            <label class="public-radio"
              ><input
                v-model="form.rsvp_status"
                type="radio"
                value="attending"
              /><span>Dengan senang hati, saya hadir</span></label
            >
            <label class="public-radio"
              ><input
                v-model="form.rsvp_status"
                type="radio"
                value="declined"
              /><span>Maaf, saya belum dapat hadir</span></label
            >
            <label
              v-if="
                form.rsvp_status === 'attending' && invitation.guest_plus_ones
              "
              class="public-plusones"
              >Jumlah pendamping<MotionSelect v-model.number="form.plus_ones"
                ><option
                  v-for="count in invitation.guest_plus_ones + 1"
                  :key="count"
                  :value="count - 1"
                >
                  {{ count - 1 }}
                </option></MotionSelect
              ></label
            >
            <p v-if="formError" class="public-rsvp__error" role="alert">
              {{ formError }}
            </p>
            <button class="public-submit" type="submit" :disabled="submitting">
              {{ submitting ? "Mengirim…" : "Kirim konfirmasi" }}
            </button>
          </form>
        </template>
      </section>
      <footer class="public-footer">
        <InvitationOrnaments v-if="decorativeEffects" class="public-footer__ornament" :theme="invitation.theme_key" placement="closing" />
        <p>{{ invitation.closing }}</p>
        <span>{{ invitation.cover_title }}</span
        ><small>Dengan penuh kasih · MENUJU</small>
      </footer>
    </div>
    <Teleport to="body">
      <Transition name="cover-return">
        <button
          v-if="opened && !coverVisible"
          class="cover-return-button"
          type="button"
          @click="returnToCover"
        >
          <FaIcon :icon="['fas', 'arrow-up']" /> Lihat sampul
        </button>
      </Transition>
    </Teleport>
    <Teleport to="body">
      <Transition name="invitation-music">
        <button
          v-if="opened"
          class="public-music-toggle"
          type="button"
          :aria-pressed="musicEnabled"
          :aria-label="musicEnabled ? 'Matikan backsound' : 'Nyalakan backsound'"
          :title="musicUnavailable ? 'Audio tidak tersedia di browser ini' : musicEnabled ? 'Matikan backsound' : 'Nyalakan backsound'"
          @click="toggleAmbientMusic"
        >
          <FaIcon :icon="['fas', musicEnabled ? 'volume-high' : 'volume-xmark']" />
          <span>{{ musicUnavailable ? 'Audio tidak tersedia' : musicEnabled ? 'Musik menyala' : 'Nyalakan musik' }}</span>
        </button>
      </Transition>
    </Teleport>
    <Teleport to="body">
      <Transition name="invitation-dock">
        <nav v-if="opened" class="invitation-dock" aria-label="Navigasi undangan">
          <button type="button" @click="scrollToSection('undangan-kisah')"><FaIcon :icon="['fas','heart']" /><span>Kisah</span></button>
          <button type="button" @click="scrollToSection('undangan-acara')"><FaIcon :icon="['fas','calendar-days']" /><span>Acara</span></button>
          <button v-if="invitation.gallery_images?.length" type="button" @click="scrollToSection('undangan-galeri')"><FaIcon :icon="['fas','images']" /><span>Galeri</span></button>
          <button type="button" @click="scrollToSection('undangan-rsvp')"><FaIcon :icon="['fas','envelope-open-text']" /><span>RSVP</span></button>
        </nav>
      </Transition>
    </Teleport>
    <Teleport to="body"
      ><Transition name="invitation-lightbox"
        ><div
          v-if="activeGalleryImage"
          class="public-lightbox"
          role="dialog"
          aria-modal="true"
          aria-label="Pratinjau foto galeri"
          @click.self="closeLightbox"
        >
          <button
            type="button"
            class="public-lightbox__close"
            aria-label="Tutup foto"
            @click="closeLightbox"
          >
            <FaIcon :icon="['fas', 'xmark']" /></button
          ><img
            :src="activeGalleryImage.url"
            :alt="activeGalleryImage.alt || 'Foto cerita pasangan'"
          /><button
            type="button"
            class="public-lightbox__previous"
            aria-label="Foto sebelumnya"
            :disabled="lightboxIndex <= 0"
            @click="lightboxIndex--"
          >
            <FaIcon :icon="['fas', 'chevron-left']" /></button
          ><button
            type="button"
            class="public-lightbox__next"
            aria-label="Foto berikutnya"
            :disabled="lightboxIndex >= invitation.gallery_images.length - 1"
            @click="lightboxIndex++"
          >
            <FaIcon :icon="['fas', 'chevron-right']" />
          </button>
          <p>
            {{ lightboxIndex + 1 }} / {{ invitation.gallery_images.length }}
          </p>
        </div></Transition
      ></Teleport
    >
  </component>
</template>
<style scoped>
.public-wedding {
  --paper: #f5f3ed;
  --ink: #364532;
  --accent: #64775c;
  --line: #d9ded4;
  min-height: 100vh;
  background: var(--paper);
  color: var(--ink);
  font-family: var(--font-body);
}
.public-wedding--classic {
  --paper: #f4eee3;
  --ink: #70543f;
  --accent: #98754e;
  --line: #ddd0bc;
}
.public-wedding--modern {
  --paper: #f5f4f1;
  --ink: #3c4540;
  --accent: #738077;
  --line: #d9dedb;
}
.public-wedding--terracotta {
  --paper: #f1e6de;
  --ink: #835340;
  --accent: #b36f54;
  --line: #e0c9bc;
}
.public-cover {
  min-height: 100svh;
  padding: max(30px, env(safe-area-inset-top)) 22px
    max(32px, env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(ellipse at center, #fff8 0%, transparent 68%), var(--paper);
  transition: opacity 0.5s;
  text-align: center;
}
.public-cover__header,
.public-cover__content {
  position: relative;
  z-index: 1;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.public-cover__header {
  gap: 2px;
}
.public-cover__content {
  margin-top: auto;
}
.public-cover__eyebrow,
.eyebrow {
  font-size: 10px;
  letter-spacing: 0.24em;
  color: var(--accent);
}
.public-cover__eyebrow {
  color: #f0d7a9;
}
.public-cover__ornament {
  font: 32px var(--font-display);
  color: #e6c994;
  margin: 0 0 2px;
}
.public-cover__content h1 {
  font: 400 clamp(42px, 11vw, 62px)/1.03 var(--font-display);
  letter-spacing: 0.035em;
  color: #f9f6f0;
  text-shadow: 0 3px 14px #0008;
  margin: 0 0 15px;
}
.public-cover__content h1 span {
  display: block;
}
.public-cover__content h1 em {
  display: block;
  color: #e6c994;
  font-size: 0.52em;
  font-weight: 400;
  line-height: 1.2;
}
.public-cover__date {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 9px;
  color: #eee9df;
  font-size: 12px;
  letter-spacing: 0.03em;
  margin: 0 0 11px;
}
.public-cover__date i {
  font-size: 10px;
  color: #e6c994;
  font-style: normal;
}
.public-cover__salutation {
  width: min(340px, 100%);
  padding: 15px 16px;
  margin: 8px auto 12px;
  border: 1px solid #ffffff42;
  border-radius: 18px;
  background: #161c1785;
  backdrop-filter: blur(14px);
  box-shadow: 0 12px 32px #0006;
  color: #eee;
}
.public-cover__salutation p {
  margin: 0;
  color: #e2ddd2;
  font-size: 9px;
  letter-spacing: 0.12em;
}
.public-cover__salutation h2 {
  margin: 5px 0 2px;
  font: 600 22px var(--font-display);
  letter-spacing: 0.02em;
}
.public-cover__salutation span {
  font: italic 13px var(--font-display);
  color: #e2ddd2;
}
.public-open-button,
.public-submit {
  margin-top: 3px;
  border: 1px solid #f5f2ec;
  border-radius: 999px;
  background: #f5f2ec;
  color: #20261f;
  padding: 14px 22px;
  font: 600 12px var(--font-body);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 0 12px 28px #0007;
  width: min(340px, 100%);
}
.public-open-button svg {
  margin-left: 9px;
}
.public-cover__privacy {
  margin: 12px 0 0;
  font-size: 9px;
  color: #e8e3d8c9;
  letter-spacing: 0.04em;
}
.public-cover--with-image {
  --accent: #f4eddc;
  --line: #ffffffa8;
  background-color: #141d17;
  background-image:
    linear-gradient(180deg, #0008 0%, #0002 30%, #0b0f17df 100%),
    var(--cover-image);
  background-position: center;
  background-size: cover;
  color: #fff;
}
.public-cover--with-image .public-cover__date {
  color: #f1ece3;
}
.public-cover--opened .public-open-button {
  filter: brightness(0.97);
}
.public-content {
  max-width: 760px;
  margin: 0 auto;
  padding: 0 22px;
}
.public-story,
.public-events,
.public-rsvp {
  text-align: center;
  padding: 90px 16px;
  border-bottom: 1px solid var(--line);
}
.public-ornament {
  font: 44px var(--font-display);
  margin: 15px;
}
.public-story h2,
.public-events > h2,
.public-rsvp h2 {
  font: 500 clamp(34px, 8vw, 48px)/1.05 var(--font-display);
  margin: 12px 0 24px;
}
.public-greeting {
  max-width: 560px;
  margin: 0 auto 18px;
  font: italic 20px/1.65 var(--font-display);
  color: var(--accent);
  white-space: pre-wrap;
}
.public-story__copy {
  max-width: 530px;
  margin: 0 auto;
  white-space: pre-wrap;
  font-size: 14px;
  line-height: 1.9;
  color: #656d60;
}
.public-story blockquote {
  font: italic 23px/1.45 var(--font-display);
  margin: 30px auto;
  max-width: 490px;
}
.public-event {
  max-width: 500px;
  margin: 27px auto 0;
  padding: 27px 18px;
  border: 1px solid var(--line);
  background: #fff4;
}
.public-event__type {
  font-size: 9px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--accent);
}
.public-event h3 {
  font: 500 30px var(--font-display);
  margin: 9px 0;
}
.public-event__date {
  font-size: 12px;
}
.public-event__time {
  font-size: 13px;
  font-weight: 600;
}
.public-event__venue {
  font-size: 13px;
  margin-top: 18px;
}
.public-event__address,
.public-no-events {
  font-size: 11px;
  color: #777d73;
  line-height: 1.6;
}
.public-agenda {
  border-top: 1px solid var(--line);
  margin-top: 18px;
  padding-top: 13px;
  text-align: left;
}
.public-agenda > div {
  display: flex;
  gap: 18px;
  padding: 9px 0;
  font-size: 11px;
}
.public-agenda time {
  color: var(--accent);
  font-weight: 700;
}
.public-countdown {
  padding: 43px 18px;
  border-block: 1px solid var(--line);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}
.public-countdown strong {
  font: 500 72px/0.95 var(--font-display);
  margin-top: 8px;
}
.public-countdown span {
  font: italic 18px var(--font-display);
  color: var(--accent);
}
.public-gallery {
  text-align: center;
  padding: 76px 0;
  border-bottom: 1px solid var(--line);
}
.public-gallery h2 {
  font: 500 clamp(34px, 8vw, 48px)/1.05 var(--font-display);
  margin: 12px 0 25px;
}
.public-gallery__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.public-gallery__grid img {
  display: block;
  width: 100%;
  height: 260px;
  object-fit: cover;
  border-radius: 3px;
}
.public-rsvp__hello {
  font-size: 12px;
  color: #747a70;
}
.public-rsvp form {
  max-width: 440px;
  margin: 24px auto 0;
  text-align: left;
}
.public-radio {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px;
  border: 1px solid var(--line);
  margin: 9px 0;
  background: #fff5;
  font-size: 12px;
  cursor: pointer;
}
.public-radio input {
  accent-color: var(--accent);
}
.public-plusones {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 14px;
  font-size: 12px;
}
.public-plusones select {
  padding: 8px 14px;
  border: 1px solid var(--line);
  background: #fff;
}
.public-rsvp__error {
  padding: 10px;
  background: #fbe8e4;
  color: #953e36;
  font-size: 12px;
}
.public-submit {
  width: 100%;
  border-radius: 5px;
  background: var(--ink);
  border-color: var(--ink);
  color: #fff;
}
.public-submit:disabled {
  opacity: 0.6;
}
.public-rsvp__success span {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid var(--accent);
  display: grid;
  place-items: center;
  margin: 0 auto;
  font-size: 23px;
}
.public-footer {
  text-align: center;
  padding: 55px 14px 70px;
}
.public-footer p {
  font: italic 21px/1.4 var(--font-display);
  max-width: 460px;
  margin: 0 auto 22px;
}
.public-footer span {
  display: block;
  font: 500 30px var(--font-display);
}
.public-footer small {
  display: block;
  margin-top: 17px;
  font-size: 9px;
  letter-spacing: 1.4px;
  color: #8c9187;
}
@media (max-width: 600px) {
  .public-cover {
    padding: 20px 18px max(24px, env(safe-area-inset-bottom));
  }
  .public-cover__content h1 {
    font-size: 52px;
  }
  .public-cover__salutation {
    padding: 13px 14px;
  }
  .public-story,
  .public-events,
  .public-rsvp {
    padding: 66px 5px;
  }
  .public-gallery {
    padding: 60px 0;
  }
  .public-gallery__grid {
    gap: 8px;
  }
  .public-gallery__grid img {
    height: 180px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .public-cover {
    transition: none;
  }
}
</style>

<style scoped>
.public-cover__page-turn {
  position: absolute;
  z-index: 3;
  inset: 0;
  pointer-events: none;
  transform-origin: left center;
  transform-style: preserve-3d;
  backface-visibility: hidden;
  border-right: 1px solid #f5edddaa;
  background: linear-gradient(90deg, #f8f5e9f2 0%, #eee9dbed 82%, #d5cdbd);
  box-shadow: 18px 0 34px #1115;
  animation: cover-page-turn 0.9s cubic-bezier(0.65, 0, 0.35, 1) both;
}
.cover-return-button {
  position: fixed;
  z-index: 9998;
  top: max(16px, env(safe-area-inset-top));
  left: 50%;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  min-height: 42px;
  padding: 0 17px;
  border: 1px solid #ffffffa8;
  border-radius: 999px;
  background: #fffffff0;
  box-shadow: 0 8px 28px #1d281d24;
  color: var(--ink);
  font: 600 11px var(--font-body);
  cursor: pointer;
  transform: translateX(-50%);
  backdrop-filter: blur(14px);
  transition: box-shadow 0.22s, background-color 0.22s, transform 0.22s;
}
.cover-return-button:hover { background: #fff; box-shadow: 0 12px 30px #1d281d30; transform: translateX(-50%) translateY(-2px); }
.cover-return-enter-active,.cover-return-leave-active { transition: opacity .25s ease, transform .3s cubic-bezier(.2,.8,.2,1); }
.cover-return-enter-from,.cover-return-leave-to { opacity: 0; transform: translate(-50%,-8px); }
.public-content { position: relative; isolation: isolate; }
.public-content::before { position: absolute; z-index: -1; inset: 0 -30vw; content: ""; pointer-events: none; background: radial-gradient(ellipse at 50% 8%,color-mix(in srgb,var(--accent) 7%,transparent),transparent 48%); }
.public-story { position: relative; margin: 42px 0; padding: clamp(46px,8vw,78px) clamp(22px,5vw,48px); overflow: hidden; border: 1px solid color-mix(in srgb,var(--line) 75%,white); border-radius: 24px; background: linear-gradient(145deg,#fff9 0%,#fff4 100%); box-shadow: 0 20px 55px #252d2010; }
.public-story::before,.public-story::after { position:absolute; width:92px; height:92px; border:1px solid color-mix(in srgb,var(--accent) 22%,transparent); border-radius:50%; content:""; pointer-events:none; }
.public-story::before { top:-45px; left:-42px; box-shadow:0 0 0 9px color-mix(in srgb,var(--accent) 4%,transparent),0 0 0 18px color-mix(in srgb,var(--accent) 3%,transparent); }
.public-story::after { right:-45px; bottom:-48px; }
.public-story .public-ornament { color:var(--accent); opacity:.78; }
.public-story blockquote { position:relative; padding:18px 22px; border-block:1px solid color-mix(in srgb,var(--accent) 22%,transparent); color:var(--ink); }
.public-events { padding-top:75px; }
.public-event { position:relative; overflow:hidden; border-radius:18px; background:linear-gradient(145deg,#fff9,#fff5); box-shadow:0 14px 35px #252d200c; transition:border-color .25s,box-shadow .25s,transform .3s; }
.public-event::before { position:absolute; top:0; bottom:0; left:0; width:4px; content:""; background:linear-gradient(var(--accent),color-mix(in srgb,var(--accent) 25%,white)); opacity:.8; }
.public-event:hover { border-color:color-mix(in srgb,var(--accent) 35%,var(--line)); box-shadow:0 19px 42px #252d2015; transform:translateY(-3px); }
.public-event__type { display:inline-flex; align-items:center; min-height:25px; padding:0 10px; border:1px solid color-mix(in srgb,var(--accent) 20%,transparent); border-radius:99px; background:color-mix(in srgb,var(--accent) 8%,white); font-weight:700; }
.public-event__date { color:var(--accent); font-weight:600; }
.public-event__venue { font-weight:700; }
.public-agenda > div { position:relative; border-bottom:1px solid color-mix(in srgb,var(--line) 65%,transparent); }
.public-agenda > div:last-child { border-bottom:0; }
.public-agenda time { min-width:52px; font-variant-numeric:tabular-nums; }
.public-countdown { margin:0 -5px; background:linear-gradient(90deg,transparent,color-mix(in srgb,var(--accent) 4%,transparent),transparent); }
.public-countdown strong { color:var(--ink); text-shadow:0 8px 28px color-mix(in srgb,var(--accent) 16%,transparent); }
.public-gallery__grid img { border-radius:12px; box-shadow:0 12px 30px #1e271b20; }
.public-gallery__grid button { border-radius:12px; }
.public-rsvp { position:relative; margin-top:30px; border:1px solid var(--line); border-radius:22px; background:linear-gradient(145deg,#fff9,#fff4); box-shadow:0 15px 42px #252d200d; }
.public-footer { position:relative; padding-top:78px; }
.public-footer::before { position:absolute; top:33px; left:50%; width:56px; height:1px; content:""; background:var(--accent); opacity:.5; transform:translateX(-50%); }
.public-footer span { color:var(--ink); }
@keyframes cover-page-turn { 0% { opacity:0; transform:perspective(1800px) rotateY(0deg); } 14% { opacity:1; } 100% { opacity:0; transform:perspective(1800px) rotateY(-104deg); } }
@media(max-width:600px) {
  .public-story { margin:25px 0; padding:48px 22px; border-radius:19px; }
  .public-story blockquote { padding:15px 11px; }
  .public-events { padding-top:58px; }
  .public-event { padding:23px 17px 23px 20px; }
  .public-rsvp { border-radius:18px; }
  .cover-return-button { min-height:40px; padding:0 14px; }
}
@media(prefers-reduced-motion:reduce) {
  .public-cover__page-turn { animation:none; display:none; }
  .cover-return-button,.cover-return-enter-active,.cover-return-leave-active { transition:none!important; }
  .public-event:hover,.cover-return-button:hover { transform:none; }
}
</style>

<style scoped>
.public-rsvp__invalid {
  max-width: 470px;
  margin: 24px auto 0;
  padding: 25px 22px;
  border: 1px solid #ead8d2;
  border-radius: 12px;
  background: #fff9f7;
  color: #4f423e;
  text-align: center;
}
.public-rsvp__invalid > span {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  margin: 0 auto 14px;
  border-radius: 50%;
  background: #f5e8e3;
  color: #945348;
}
.public-rsvp__invalid .eyebrow {
  margin: 0 0 7px;
  color: #8d574b;
  font-size: 9px;
  letter-spacing: 1.4px;
}
.public-rsvp__invalid h2 {
  margin: 0 0 9px;
  font: 500 27px/1.1 var(--font-display);
}
.public-rsvp__invalid > p:last-child {
  margin: 0;
  color: #65625c;
  font-size: 12px;
  line-height: 1.7;
}
</style>

<style scoped>
.public-wedding {
  overflow: clip;
}
.public-cover {
  isolation: isolate;
  transform-origin: center top;
  will-change: transform, opacity, clip-path;
}
.public-cover__decoration {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 0;
}
.public-cover__decoration span {
  position: absolute;
  display: block;
  border: 1px solid #e6c99455;
  border-radius: 50%;
  animation: cover-orbit 14s ease-in-out infinite alternate;
}
.public-cover__decoration span:nth-child(1) {
  width: min(78vw, 650px);
  aspect-ratio: 1;
  top: 12%;
  left: 50%;
  transform: translateX(-50%);
}
.public-cover__decoration span:nth-child(2) {
  width: min(90vw, 780px);
  aspect-ratio: 1;
  top: 7%;
  left: 50%;
  transform: translateX(-50%);
  animation-delay: -4s;
}
.public-cover__decoration span:nth-child(3) {
  width: min(58vw, 470px);
  aspect-ratio: 1;
  top: 20%;
  left: 50%;
  transform: translateX(-50%);
  animation-delay: -8s;
}
.public-cover__header {
  animation: cover-copy-in 0.85s cubic-bezier(0.2, 0.75, 0.25, 1) both;
}
.public-cover__content h1 span {
  animation: cover-copy-in 0.8s cubic-bezier(0.2, 0.75, 0.25, 1) both;
}
.public-cover__content h1 span:nth-child(2) {
  animation-delay: 0.1s;
}
.public-cover__content h1 em {
  animation: cover-copy-in 0.65s 0.08s cubic-bezier(0.2, 0.75, 0.25, 1) both;
}
.public-cover__date,
.public-cover__salutation,
.public-open-button,
.public-cover__privacy {
  animation: cover-copy-in 0.7s 0.18s cubic-bezier(0.2, 0.75, 0.25, 1) both;
}
.invitation-cover-leave-active {
  transition:
    opacity 0.72s cubic-bezier(0.22, 0.75, 0.24, 1),
    transform 0.9s cubic-bezier(0.16, 1, 0.3, 1),
    clip-path 0.9s cubic-bezier(0.16, 1, 0.3, 1);
  pointer-events: none;
}
.invitation-cover-leave-to {
  opacity: 0;
}
.public-wedding--motion-editorial .invitation-cover-leave-to {
  clip-path: inset(0 0 100% 0);
  transform: translateY(-2.5vh);
}
.public-wedding--motion-garden .invitation-cover-leave-to {
  clip-path: ellipse(0% 0% at 50% 50%);
  transform: scale(0.96);
}
.public-wedding--motion-cinematic .invitation-cover-leave-to {
  transform: scale(1.06);
  filter: blur(5px);
}
.public-wedding--motion-minimal .invitation-cover-leave-to {
  transform: translateY(-10px);
  transition-duration: 0.36s;
}
.public-story,
.public-countdown,
.public-events,
.public-gallery,
.public-rsvp,
.public-footer,
.public-event,
.public-agenda > div {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 0.72s cubic-bezier(0.2, 0.75, 0.25, 1),
    transform 0.72s cubic-bezier(0.2, 0.75, 0.25, 1);
  transition-delay: calc(var(--reveal-index, 0) * 55ms);
}
.public-countdown {
  transform: translateY(15px) scale(0.985);
}
.public-event,
.public-agenda > div {
  transform: translateY(16px);
}
.public-story.is-in-view,
.public-countdown.is-in-view,
.public-events.is-in-view,
.public-gallery.is-in-view,
.public-rsvp.is-in-view,
.public-footer.is-in-view,
.public-event.is-in-view,
.public-agenda > div.is-in-view {
  opacity: 1;
  transform: translateY(0) scale(1);
}
.public-wedding--motion-garden .public-story.is-in-view,
.public-wedding--motion-garden .public-events.is-in-view,
.public-wedding--motion-garden .public-gallery.is-in-view {
  animation: soft-bloom 0.75s both;
}
.public-wedding--motion-cinematic .public-story,
.public-wedding--motion-cinematic .public-events,
.public-wedding--motion-cinematic .public-gallery {
  transform: translateY(30px) scale(0.985);
}
.public-wedding--motion-cinematic .public-story.is-in-view,
.public-wedding--motion-cinematic .public-events.is-in-view,
.public-wedding--motion-cinematic .public-gallery.is-in-view {
  transform: translateY(0) scale(1);
}
.public-wedding--motion-minimal .public-story,
.public-wedding--motion-minimal .public-countdown,
.public-wedding--motion-minimal .public-events,
.public-wedding--motion-minimal .public-gallery,
.public-wedding--motion-minimal .public-rsvp,
.public-wedding--motion-minimal .public-footer {
  transition-duration: 0.42s;
  transform: translateY(12px);
}
.public-wedding--decorative .public-gallery__grid img {
  transform: translateY(var(--photo-shift, 0px)) scale(1.035);
  transition:
    transform 0.7s cubic-bezier(0.2, 0.75, 0.25, 1),
    filter 0.5s;
}
.public-gallery__grid button {
  border: 0;
  padding: 0;
  overflow: hidden;
  background: transparent;
  cursor: zoom-in;
}
.public-gallery__grid button:focus-visible {
  outline: 3px solid var(--accent);
  outline-offset: 3px;
}
.public-gallery__grid button:hover img {
  filter: saturate(1.08);
}
.public-gallery__grid img {
  display: block;
  transition:
    transform 0.7s cubic-bezier(0.2, 0.75, 0.25, 1),
    clip-path 0.8s cubic-bezier(0.2, 0.75, 0.25, 1);
  clip-path: inset(5% 5% 5% 5%);
}
.public-gallery.is-in-view .public-gallery__grid img {
  clip-path: inset(0);
}
.public-wedding--motion-editorial
  .public-gallery.is-in-view
  .public-gallery__grid
  img {
  transform: translateY(var(--photo-shift, 0px)) scale(1);
  clip-path: inset(0);
}
.public-gallery__grid button:hover img {
  transform: translateY(var(--photo-shift, 0px)) scale(1.07);
}
.public-lightbox {
  position: fixed;
  z-index: 10000;
  inset: 0;
  display: grid;
  place-items: center;
  padding: clamp(20px, 5vw, 60px);
  background: #10140fef;
  backdrop-filter: blur(12px);
}
.public-lightbox > img {
  max-width: min(100%, 1100px);
  max-height: 82svh;
  object-fit: contain;
  border-radius: 5px;
  box-shadow: 0 25px 90px #0008;
}
.public-lightbox button {
  position: absolute;
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1px solid #ffffff45;
  border-radius: 50%;
  background: #ffffff12;
  color: #fff;
  cursor: pointer;
  transition:
    background 0.2s,
    transform 0.2s;
}
.public-lightbox button:hover:not(:disabled) {
  background: #ffffff30;
  transform: scale(1.06);
}
.public-lightbox button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.public-lightbox__close {
  top: max(18px, env(safe-area-inset-top));
  right: 20px;
}
.public-lightbox__previous {
  left: clamp(12px, 4vw, 50px);
  top: 50%;
}
.public-lightbox__next {
  right: clamp(12px, 4vw, 50px);
  top: 50%;
}
.public-lightbox > p {
  position: absolute;
  bottom: max(15px, env(safe-area-inset-bottom));
  margin: 0;
  color: #fff;
  font-size: 11px;
  letter-spacing: 0.1em;
}
.invitation-lightbox-enter-active,
.invitation-lightbox-leave-active {
  transition:
    opacity 0.28s ease,
    backdrop-filter 0.3s ease;
}
.invitation-lightbox-enter-active > img,
.invitation-lightbox-leave-active > img {
  transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.invitation-lightbox-enter-from,
.invitation-lightbox-leave-to {
  opacity: 0;
}
.invitation-lightbox-enter-from > img,
.invitation-lightbox-leave-to > img {
  transform: scale(0.96);
}
.public-radio,
.public-open-button,
.public-submit {
  transition:
    transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1),
    box-shadow 0.22s,
    background-color 0.22s;
}
.public-radio:hover {
  transform: translateX(3px);
  border-color: var(--accent);
}
.public-open-button:hover,
.public-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 32px #0004;
}
.public-rsvp__success,
.public-rsvp__invalid,
.public-rsvp__error {
  animation: feedback-enter 0.45s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
.public-rsvp__success > span {
  animation: success-pop 0.5s 0.08s cubic-bezier(0.2, 0.9, 0.25, 1.3) both;
}
@keyframes cover-copy-in {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@keyframes cover-orbit {
  from {
    margin-top: -5px;
    opacity: 0.5;
  }
  to {
    margin-top: 7px;
    opacity: 0.9;
  }
}
@keyframes soft-bloom {
  from {
    filter: blur(3px);
  }
  to {
    filter: blur(0);
  }
}
@keyframes feedback-enter {
  from {
    opacity: 0;
    transform: translateY(9px) scale(0.99);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
@keyframes success-pop {
  from {
    opacity: 0;
    transform: scale(0.7);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
@media (max-width: 600px) {
  .public-lightbox {
    padding: 16px;
  }
  .public-lightbox > img {
    max-height: 76svh;
  }
  .public-lightbox__previous {
    left: 9px;
    top: auto;
    bottom: max(18px, env(safe-area-inset-bottom));
  }
  .public-lightbox__next {
    right: 9px;
    top: auto;
    bottom: max(18px, env(safe-area-inset-bottom));
  }
  .public-lightbox > p {
    bottom: max(33px, calc(env(safe-area-inset-bottom) + 18px));
  }
}
@media (prefers-reduced-motion: reduce) {
  .public-cover__decoration span,
  .public-cover__header,
  .public-cover__content h1 span,
  .public-cover__content h1 em,
  .public-cover__date,
  .public-cover__salutation,
  .public-open-button,
  .public-cover__privacy,
  .public-rsvp__success,
  .public-rsvp__invalid,
  .public-rsvp__error,
  .public-rsvp__success > span {
    animation: none !important;
  }
  .invitation-cover-leave-active,
  .public-story,
  .public-countdown,
  .public-events,
  .public-gallery,
  .public-rsvp,
  .public-footer,
  .public-event,
  .public-agenda > div,
  .public-gallery__grid img,
  .public-lightbox,
  .public-lightbox > img,
  .public-radio,
  .public-open-button,
  .public-submit {
    transition: none !important;
    transform: none !important;
    filter: none !important;
  }
  .public-story,
  .public-countdown,
  .public-events,
  .public-gallery,
  .public-rsvp,
  .public-footer,
  .public-event,
  .public-agenda > div {
    opacity: 1 !important;
  }
  .public-gallery__grid img {
    clip-path: none !important;
  }
}
</style>

<style scoped>
.public-wedding {
  --paper: #f3f0e4;
  --ink: #344536;
  --accent: #71835f;
  --line: #d8d1bd;
  --gold: #c9a96b;
  --rose: #dba899;
  --ornament-flower: #dba899;
  background-color: var(--paper);
  background-image: radial-gradient(ellipse at 13% 21%, #d9d8c422, transparent 32%), radial-gradient(ellipse at 89% 76%, #e3c9b322, transparent 36%);
}
.public-wedding--classic { --paper:#f1e6d2;--ink:#51283b;--accent:#96695e;--line:#d9c7a7;--gold:#bd9957;--rose:#d7aa9c;--ornament-flower:#e5caa3; }
.public-wedding--modern { --paper:#eee9ed;--ink:#282a42;--accent:#af5c65;--line:#d5c8cd;--gold:#d3ad71;--rose:#d78991;--ornament-flower:#db8790; }
.public-wedding--terracotta { --paper:#f3e1d3;--ink:#6e3d31;--accent:#b5694c;--line:#ddc1ae;--gold:#c99754;--rose:#d89474;--ornament-flower:#e5a071; }
.public-cover {
  min-height:100svh;
  padding:max(32px,env(safe-area-inset-top)) clamp(24px,6vw,70px) max(36px,env(safe-area-inset-bottom));
  justify-content:space-between;
  background:radial-gradient(ellipse at 50% 44%,#fff9 0%,transparent 47%),linear-gradient(142deg,color-mix(in srgb,var(--paper) 84%,white),var(--paper) 62%,color-mix(in srgb,var(--rose) 16%,var(--paper)));
  isolation:isolate;
}
.public-cover::before { content:"";position:absolute;inset:13px;border:1px solid color-mix(in srgb,var(--gold) 68%,transparent);border-radius:3px;pointer-events:none; }
.public-cover::after { content:"";position:absolute;inset:20px;border:1px solid color-mix(in srgb,var(--gold) 27%,transparent);border-radius:2px;pointer-events:none; }
.public-cover__frame { position:absolute;z-index:0;inset:30px;pointer-events:none;border:1px solid color-mix(in srgb,var(--accent) 26%,transparent);mask-image:linear-gradient(#000,transparent 19%,transparent 81%,#000); }
.public-cover__frame::before,.public-cover__frame::after { position:absolute;left:50%;width:80px;height:15px;content:"❧";color:var(--gold);font:italic 25px var(--font-display);text-align:center;background:var(--paper);transform:translateX(-50%); }
.public-cover__frame::before { top:-10px; }.public-cover__frame::after { bottom:-10px;transform:translateX(-50%) rotate(180deg); }
.public-cover__decoration { z-index:0;overflow:hidden; }
.public-cover__decoration > .invitation-ornament--cover { position:absolute;inset:0;width:100%;height:100%;margin:auto;opacity:.83; }
.cover-speck { position:absolute!important;display:block;width:4px!important;height:4px!important;border:0!important;border-radius:50%;background:var(--gold);box-shadow:0 0 14px color-mix(in srgb,var(--gold) 55%,transparent);animation:spark-float 7s ease-in-out infinite alternate!important; }
.cover-speck--one { left:19%;top:28%; }.cover-speck--two { right:17%;top:39%;animation-delay:-3.2s!important; }.cover-speck--three { left:28%;bottom:20%;animation-delay:-5.1s!important; }
.public-cover__header { gap:9px;margin:auto 0 20px; }
.public-cover__monogram { display:grid;place-items:center;width:44px;height:44px;border:1px solid color-mix(in srgb,var(--gold) 65%,transparent);border-radius:50%;color:var(--accent);font:italic 28px var(--font-display);box-shadow:0 0 0 5px color-mix(in srgb,var(--gold) 10%,transparent); }
.public-cover__eyebrow { color:var(--accent);font-size:9px;font-weight:700;letter-spacing:.3em; }
.public-cover__content { max-width:560px;margin:auto; }
.public-cover__content h1 { color:var(--ink);font-size:clamp(48px,10vw,82px);line-height:.92;letter-spacing:.012em;text-shadow:0 7px 28px #746c5420; }
.public-cover__content h1 em { margin:6px 0;color:var(--gold);font-size:.42em; }
.public-cover__date { flex-wrap:wrap;gap:8px;color:color-mix(in srgb,var(--ink) 76%,var(--paper));font-size:11px;letter-spacing:.04em; }
.public-cover__date i { color:var(--gold); }
.public-cover__salutation { width:min(340px,100%);margin:14px auto 17px;padding:15px 22px;border:1px solid color-mix(in srgb,var(--gold) 62%,transparent);border-radius:2px;background:linear-gradient(135deg,#fff8,#fff3);box-shadow:0 10px 28px #483d2614;color:var(--ink);backdrop-filter:blur(8px); }
.public-cover__salutation::before,.public-cover__salutation::after { content:"✦";display:block;color:var(--gold);font-size:9px; }
.public-cover__salutation p,.public-cover__salutation span { color:color-mix(in srgb,var(--ink) 70%,white); }
.public-cover__salutation h2 { color:var(--ink);font-size:25px; }
.public-open-button { position:relative;overflow:hidden;width:auto;min-width:235px;padding:15px 28px;border:1px solid var(--gold);border-radius:2px;background:linear-gradient(110deg,color-mix(in srgb,var(--ink) 92%,black),var(--ink));color:#fff9ed;box-shadow:0 10px 28px color-mix(in srgb,var(--ink) 24%,transparent);letter-spacing:.15em;transition:transform .28s,box-shadow .3s; }
.public-open-button::after { position:absolute;top:0;bottom:0;left:-55%;width:34%;content:"";background:linear-gradient(90deg,transparent,#fff7,transparent);transform:skewX(-24deg);animation:button-sheen 6.4s 1.2s ease-in-out infinite; }
.public-open-button:hover { transform:translateY(-3px);box-shadow:0 15px 36px color-mix(in srgb,var(--ink) 30%,transparent); }
.public-cover__privacy { color:color-mix(in srgb,var(--ink) 58%,var(--paper));font-size:9px; }
.public-cover--classic { background:radial-gradient(ellipse at center,#fff8 0%,transparent 46%),repeating-linear-gradient(45deg,#b8903d08 0 1px,transparent 1px 9px),linear-gradient(145deg,#f9f1df,#e9dcc4); }
.public-cover--classic::before { inset:25px;border:2px double #b8955e;border-radius:16px; }.public-cover--classic::after { inset:33px;border:1px solid #b8955e77;border-radius:12px; }
.public-cover--modern { align-items:flex-start;text-align:left;background:radial-gradient(ellipse at 80% 20%,#d9919840,transparent 36%),linear-gradient(132deg,#f1eaeb 0%,#e9e8ee 54%,#d9e0e3 100%); }
.public-cover--modern .public-cover__header,.public-cover--modern .public-cover__content { align-items:flex-start;text-align:left; }.public-cover--modern .public-cover__content h1 { font-size:clamp(56px,12vw,100px); }.public-cover--modern .public-cover__date { justify-content:flex-start; }.public-cover--modern .public-cover__salutation { margin-left:0; }.public-cover--modern .public-cover__frame { border-width:0 0 1px 1px;mask-image:none; }
.public-cover--terracotta { background:radial-gradient(ellipse at 50% 20%,#fff8,transparent 38%),linear-gradient(165deg,#f5e8d9 0%,#efcfb4 58%,#dba68b 100%); }
.public-cover--terracotta::before { border-radius:48% 48% 2px 2px; }.public-cover--terracotta::after { inset:26px;border-radius:47% 47% 2px 2px; }
.public-cover--opened .public-open-button { filter:none; }
.public-cover__page-turn { transform-origin:left center;background:linear-gradient(105deg,#fffef7f5 0%,color-mix(in srgb,var(--paper) 95%,white) 79%,color-mix(in srgb,var(--gold) 30%,var(--paper)));border-right:1px solid var(--gold);box-shadow:26px 0 55px #221a1830; }
.public-content { width:min(100%,1280px);max-width:1280px;padding:0 clamp(18px,4vw,58px); }
.public-hero { position:relative;isolation:isolate;display:grid;place-items:center;min-height:min(86svh,940px);margin:0 auto 55px;overflow:hidden;border-radius:4px;background:radial-gradient(ellipse at 50% 45%,color-mix(in srgb,var(--rose) 22%,var(--paper)),var(--ink));color:#fff; }
.public-hero__image { position:absolute;z-index:-2;inset:-5%;width:110%;height:110%;object-fit:cover;object-position:center 42%;transform:translateY(var(--photo-shift,0px)) scale(1.04);filter:saturate(1.04) brightness(1.04); }
.public-hero__wash { position:absolute;z-index:-1;inset:0;background:radial-gradient(ellipse at 50% 44%,#fff0 18%,color-mix(in srgb,var(--rose) 18%,transparent) 100%),linear-gradient(180deg,#fff2 0%,#f8eedb18 48%,#f5e7d475 100%); }
.public-hero:not(.public-hero--with-image) .public-hero__wash { background:radial-gradient(ellipse at 50% 40%,color-mix(in srgb,var(--rose) 24%,transparent),transparent 52%),linear-gradient(145deg,color-mix(in srgb,var(--ink) 93%,black),var(--ink)); }
.public-hero>.invitation-ornament--hero { position:absolute;z-index:-1;top:-9%;right:-7%;width:min(60%,650px);height:110%;color:var(--gold);opacity:.48;filter:drop-shadow(0 8px 18px #0003); }
.public-hero__copy { position:relative;width:min(760px,90%);padding:clamp(38px,6vw,62px) clamp(22px,5vw,62px) 43px;border:1px solid color-mix(in srgb,var(--gold) 72%,white);border-radius:46% 46% 4px 4px / 28% 28% 4px 4px;background:linear-gradient(155deg,color-mix(in srgb,var(--paper) 88%,white),color-mix(in srgb,var(--paper) 74%,var(--rose)));box-shadow:0 24px 65px color-mix(in srgb,var(--ink) 12%,transparent),inset 0 0 0 8px color-mix(in srgb,var(--gold) 8%,transparent);text-align:center;text-shadow:none;backdrop-filter:blur(9px); }
.public-hero__copy::before { position:absolute;inset:9px;border:1px solid color-mix(in srgb,var(--gold) 36%,transparent);border-radius:inherit;content:"";pointer-events:none; }
.public-hero__eyebrow { color:var(--accent);font-size:9px;font-weight:700;letter-spacing:.35em; }
.public-hero__copy h2 { margin:17px 0 12px;color:var(--ink);font:500 clamp(48px,10vw,102px)/.97 var(--font-display);text-wrap:balance; }
.public-hero__rule { display:block;width:90px;height:1px;margin:23px auto;background:linear-gradient(90deg,transparent,var(--gold),transparent); }
.public-hero__date,.public-hero__venue { margin:6px 0;color:color-mix(in srgb,var(--ink) 82%,white);font-size:12px;letter-spacing:.08em; }
.public-hero__venue { font-size:11px; }
.public-hero__continue { display:inline-flex;align-items:center;gap:10px;margin-top:24px;padding:11px 17px;border:1px solid color-mix(in srgb,var(--gold) 75%,white);border-radius:99px;background:color-mix(in srgb,var(--paper) 88%,white);color:var(--ink);font:700 10px var(--font-body);letter-spacing:.1em;text-transform:uppercase;cursor:pointer;transition:background .25s,transform .25s,box-shadow .25s; }
.public-hero__continue:hover { background:#fff;transform:translateY(2px);box-shadow:0 8px 22px color-mix(in srgb,var(--ink) 12%,transparent); }
.public-hero__caption { position:absolute;right:20px;bottom:20px;color:var(--paper);font:italic 14px var(--font-display);letter-spacing:.25em;text-shadow:0 1px 10px color-mix(in srgb,var(--ink) 32%,transparent); }
.public-story { position:relative;margin:80px auto 68px;padding:clamp(42px,7vw,84px) clamp(20px,5vw,62px);border:0;border-radius:0;background:linear-gradient(145deg,#fff7,color-mix(in srgb,var(--rose) 8%,transparent));box-shadow:0 28px 75px color-mix(in srgb,var(--ink) 9%,transparent); }
.public-story::before,.public-story::after { width:120px;height:120px;border-color:color-mix(in srgb,var(--gold) 65%,transparent); }
.public-story::before { top:-37px;left:-37px;border-width:1px 0 0 1px;border-radius:0;box-shadow:none; }
.public-story::after { right:-37px;bottom:-37px;border-width:0 1px 1px 0;border-radius:0; }
.public-story>.eyebrow,.public-events>.eyebrow,.public-gallery>.eyebrow,.public-rsvp>.eyebrow { color:var(--accent);font-size:9px;font-weight:700;letter-spacing:.3em; }
.public-story h2 { color:var(--ink);font-size:clamp(40px,6vw,68px); }
.public-story__layout { display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.08fr);align-items:center;gap:clamp(25px,5vw,64px);max-width:950px;margin:35px auto 0;text-align:left; }
.public-story__portrait { position:relative;min-width:0;margin:0;padding:12px 12px 48px;background:#fff;box-shadow:0 20px 45px #342c221e;transform:rotate(-2.5deg); }
.public-story__portrait::before { position:absolute;z-index:-1;inset:-8px;border:1px solid color-mix(in srgb,var(--gold) 68%,transparent);content:"";transform:rotate(4deg); }
.public-story__portrait img { display:block;width:100%;height:clamp(240px,34vw,410px);object-fit:cover; }
.public-story__portrait figcaption { display:flex;flex-direction:column;gap:3px;padding:12px 5px 0;color:var(--ink);font:italic 13px var(--font-display);text-align:center; }
.public-story__portrait figcaption span { color:var(--accent);font:700 8px var(--font-body);letter-spacing:.18em;text-transform:uppercase; }
.public-story__portrait figcaption strong { font:500 21px var(--font-display); }.public-story__portrait figcaption i { color:var(--gold);font-style:normal; }
.public-story__words { position:relative;z-index:1; }.public-greeting { color:var(--accent);font-size:clamp(19px,2vw,24px); }.public-story__copy { color:color-mix(in srgb,var(--ink) 78%,white);font-size:13px; }
.public-story blockquote { position:relative;padding:21px 20px;border:0;color:var(--ink);font-size:25px; }
.public-story blockquote::before,.public-story blockquote::after { color:var(--gold);font:36px var(--font-display); }.public-story blockquote::before { content:"“"; }.public-story blockquote::after { content:"”"; }
.public-story__ornament { position:absolute;right:-22px;top:12%;width:160px;height:180px;opacity:.13;pointer-events:none; }
.public-section-flourish { width:120px;height:44px;margin:30px auto 0;opacity:.65; }
.public-countdown { position:relative;isolation:isolate;display:flex;min-height:240px;margin:40px auto;padding:45px 25px;border:1px solid color-mix(in srgb,var(--gold) 62%,transparent);border-radius:3px;background:linear-gradient(135deg,#fff7,color-mix(in srgb,var(--rose) 9%,transparent)); }
.public-countdown>.invitation-ornament--divider { position:absolute;z-index:-1;inset:6px;width:calc(100% - 12px);height:calc(100% - 12px);opacity:.25; }
.public-countdown .eyebrow { color:var(--accent);font-weight:700;letter-spacing:.3em; }.public-countdown strong { color:var(--ink);font-size:clamp(70px,12vw,120px);line-height:.82;text-shadow:0 8px 26px color-mix(in srgb,var(--gold) 25%,transparent); }
.public-countdown span { color:var(--accent);font-size:22px; }.public-countdown__date { margin:13px 0 0;color:color-mix(in srgb,var(--ink) 72%,white);font-size:10px;letter-spacing:.12em;text-transform:uppercase; }
.public-events { position:relative;overflow:visible;padding:82px 12px 66px;border:0;text-align:center; }
.public-events__ornament { position:absolute;z-index:-1;top:1%;left:50%;width:170px;height:70px;opacity:.5;transform:translateX(-50%); }
.public-events>.eyebrow { margin-top:18px; }.public-events>h2 { color:var(--ink);font-size:clamp(39px,6vw,66px); }
.public-event { position:relative;max-width:700px;margin:26px auto 0;padding:35px clamp(20px,5vw,55px);border:1px solid color-mix(in srgb,var(--gold) 44%,var(--line));border-radius:2px;background:linear-gradient(145deg,#fff9,#fff4);box-shadow:0 18px 46px color-mix(in srgb,var(--ink) 9%,transparent); }
.public-event::before { left:10px;top:10px;bottom:10px;width:auto;border:1px solid color-mix(in srgb,var(--accent) 23%,transparent);background:none;pointer-events:none; }
.public-event::after { position:absolute;top:4px;left:50%;width:92px;height:12px;padding:0 8px;content:"✦  ✧  ✦";background:var(--paper);color:var(--gold);font-size:9px;letter-spacing:.3em;transform:translateX(-50%); }
.public-event__type { padding:7px 14px;border-color:color-mix(in srgb,var(--accent) 25%,transparent);background:color-mix(in srgb,var(--accent) 8%,white);color:var(--accent);letter-spacing:.2em; }
.public-event h3 { margin:14px 0;color:var(--ink);font-size:clamp(30px,5vw,42px); }.public-event__date { color:var(--accent);font-family:var(--font-display);font-size:15px; }
.public-event__time { display:inline-flex;margin:3px auto 0;padding:9px 14px;border-block:1px solid color-mix(in srgb,var(--gold) 54%,transparent);color:var(--ink);font-size:11px;letter-spacing:.04em; }
.public-event__venue { color:var(--ink);font:500 18px var(--font-display); }.public-event__address { max-width:420px;margin:5px auto;color:color-mix(in srgb,var(--ink) 68%,white); }
.public-agenda { max-width:470px;margin:22px auto 0;border-top:1px solid var(--line); }.public-agenda>div { padding:12px 5px;border-bottom:1px solid color-mix(in srgb,var(--line) 62%,transparent); }
.public-gallery { position:relative;padding:80px 10px 90px;border-bottom:0; }.public-gallery__ornament { position:absolute;right:1%;top:3%;width:155px;height:155px;opacity:.16; }
.public-gallery>.eyebrow { position:relative;z-index:1; }.public-gallery>h2 { position:relative;z-index:1;color:var(--ink);font-size:clamp(38px,6vw,64px); }
.public-gallery__grid { grid-template-columns:repeat(12,minmax(0,1fr));gap:17px;align-items:center;max-width:940px;margin:30px auto 0; }
.public-gallery__grid button { grid-column:span 5;border-radius:2px;transform:rotate(-2deg); }.public-gallery__grid button:nth-child(2n) { grid-column:span 7;margin-top:46px;transform:rotate(2deg); }.public-gallery__grid button:nth-child(3n) { grid-column:2 / span 6;margin-top:-16px;transform:rotate(1deg); }
.public-gallery__grid img { height:clamp(200px,32vw,390px);border:9px solid #fff;border-bottom-width:24px;border-radius:1px;box-shadow:0 16px 42px color-mix(in srgb,var(--ink) 18%,transparent); }
.public-gallery__grid button:hover img { transform:translateY(var(--photo-shift,0px)) scale(1.055); }
.public-rsvp { position:relative;isolation:isolate;overflow:hidden;margin:45px auto;padding:78px clamp(20px,6vw,74px);border:1px solid color-mix(in srgb,var(--gold) 58%,transparent);border-radius:2px;background:linear-gradient(145deg,#fff9,color-mix(in srgb,var(--rose) 12%,transparent));box-shadow:0 25px 70px color-mix(in srgb,var(--ink) 10%,transparent); }
.public-rsvp__ornament { position:absolute;z-index:-1;right:-30px;bottom:-57px;width:240px;height:260px;opacity:.16; }
.public-rsvp>.eyebrow,.public-rsvp>h2,.public-rsvp>p { position:relative;z-index:1; }.public-rsvp>h2 { color:var(--ink);font-size:clamp(38px,6vw,59px); }
.public-rsvp form { max-width:520px; }.public-radio { border-color:color-mix(in srgb,var(--gold) 47%,var(--line));border-radius:2px;background:#fff9;transition:transform .25s,box-shadow .25s,border-color .25s; }
.public-radio:hover { border-color:var(--accent);box-shadow:0 9px 24px color-mix(in srgb,var(--ink) 9%,transparent);transform:translateX(4px); }
.public-radio input { width:17px;height:17px; }.public-submit { border-radius:2px;background:linear-gradient(110deg,var(--ink),color-mix(in srgb,var(--ink) 74%,var(--accent)));border-color:var(--gold);letter-spacing:.12em; }
.public-footer { position:relative;isolation:isolate;overflow:hidden;padding:110px 18px 90px;background:linear-gradient(180deg,transparent,color-mix(in srgb,var(--accent) 9%,transparent)); }
.public-footer__ornament { position:absolute;z-index:-1;top:1px;left:50%;width:min(78vw,470px);height:285px;opacity:.28;transform:translateX(-50%); }
.public-footer::before { top:39px;width:86px;background:var(--gold); }.public-footer p { color:var(--accent);font-size:24px; }.public-footer span { margin-top:20px;color:var(--ink);font-size:clamp(36px,6vw,55px); }.public-footer small { color:var(--accent); }
.public-wedding--classic .public-hero { border:10px solid #bd995788;outline:1px solid #bd9957;outline-offset:5px; }.public-wedding--classic .public-story { border-inline:1px solid #bd995744; }.public-wedding--classic .public-story__portrait { transform:rotate(0);border:5px double #bd9957;background:#f8f0df; }.public-wedding--classic .public-event { border:3px double #bd9957; }.public-wedding--classic .public-event::before { border-style:double; }.public-wedding--classic .public-gallery__grid button,.public-wedding--classic .public-gallery__grid button:nth-child(2n) { transform:rotate(0); }
.public-wedding--modern .public-hero { min-height:83svh;border-radius:0 0 0 170px; }.public-wedding--modern .public-hero__copy { margin-left:8%;text-align:left; }.public-wedding--modern .public-hero__rule { margin-left:0; }.public-wedding--modern .public-hero__date,.public-wedding--modern .public-hero__venue { text-align:left; }.public-wedding--modern .public-story__layout { grid-template-columns:minmax(0,1.2fr) minmax(0,1fr); }.public-wedding--modern .public-story__portrait { order:2;transform:rotate(0);padding:0 0 18px;background:transparent;box-shadow:none; }.public-wedding--modern .public-story__portrait img { height:440px;border-radius:150px 150px 4px 4px; }.public-wedding--modern .public-story__portrait::before { inset:10px;border:1px solid var(--gold);border-radius:150px 150px 4px 4px; }.public-wedding--modern .public-event { border-radius:22px 2px 22px 2px; }.public-wedding--modern .public-gallery__grid button:nth-child(2n) { transform:translateY(32px) rotate(3deg); }
.public-wedding--terracotta .public-hero { min-height:88svh;border-radius:48% 48% 3px 3px / 13% 13% 3px 3px; }.public-wedding--terracotta .public-story { border-radius:110px 2px 110px 2px;background:linear-gradient(145deg,#fff8,#d994731c); }.public-wedding--terracotta .public-story__portrait { transform:rotate(2deg); }.public-wedding--terracotta .public-event { border-radius:42px 2px 42px 2px;background:linear-gradient(150deg,#fff9,#edc5a655); }.public-wedding--terracotta .public-countdown { border-radius:80px 2px; }
.public-wedding--motion-garden .public-hero__image { animation:hero-breathe 15s ease-in-out infinite alternate; }.public-wedding--motion-cinematic .public-hero__image { animation:hero-breathe 18s ease-in-out infinite alternate; }.public-wedding--motion-editorial .public-hero__copy h2 { animation:hero-type-reveal 1s cubic-bezier(.2,.8,.2,1) both; }
.public-hero,.public-story,.public-countdown,.public-events,.public-gallery,.public-rsvp,.public-footer,.public-event,.public-story__portrait,.public-gallery__grid button,.public-agenda>div { clip-path:inset(0 0 0 0); }
.public-hero,.public-story,.public-countdown,.public-events,.public-gallery,.public-rsvp,.public-footer,.public-event,.public-story__portrait,.public-gallery__grid button,.public-agenda>div { will-change:opacity,transform; }
.public-hero:not(.is-in-view),.public-story:not(.is-in-view),.public-countdown:not(.is-in-view),.public-events:not(.is-in-view),.public-gallery:not(.is-in-view),.public-rsvp:not(.is-in-view),.public-footer:not(.is-in-view),.public-event:not(.is-in-view),.public-story__portrait:not(.is-in-view),.public-gallery__grid button:not(.is-in-view),.public-agenda>div:not(.is-in-view) { clip-path:inset(0 0 0 0); }
.public-wedding--motion-editorial .public-hero:not(.is-in-view) .public-hero__copy h2 { clip-path:inset(0 100% 0 0); }.public-wedding--motion-editorial .public-hero.is-in-view .public-hero__copy h2 { animation:hero-type-reveal 1.05s cubic-bezier(.2,.8,.2,1) both; }
.public-wedding--decorative .public-hero__image,.public-wedding--decorative .public-story__portrait img,.public-wedding--decorative .public-gallery__grid img { will-change:transform; }
@keyframes spark-float { 0%{transform:translate3d(0,2px,0);opacity:.38} 100%{transform:translate3d(8px,-13px,0);opacity:.86} }
@keyframes button-sheen { 0%,55%{left:-55%} 72%,100%{left:130%} }
@keyframes hero-breathe { 0%{transform:translateY(var(--photo-shift,0px)) scale(1.035)} 100%{transform:translateY(var(--photo-shift,0px)) scale(1.095)} }
@keyframes hero-type-reveal { from{opacity:0;clip-path:inset(0 100% 0 0);transform:translateY(18px)} to{opacity:1;clip-path:inset(0);transform:translateY(0)} }
@media(max-width:700px) {
  .public-cover { min-height:92svh;padding:24px 23px max(30px,env(safe-area-inset-bottom)); }
  .public-cover::before { inset:9px; }.public-cover::after { inset:15px; }.public-cover__frame { inset:24px; }
  .public-cover__content h1 { font-size:clamp(48px,14vw,72px); }.public-cover__date { font-size:10px; }
  .public-content { padding:0 15px; }.public-hero { width:100%;min-height:76svh;margin-bottom:40px;border-radius:3px; }
  .public-hero>.invitation-ornament--hero { top:-4%;right:-22%;width:86%;height:85%;opacity:.36; }
  .public-hero__copy { width:94%;padding:35px 5px; }.public-hero__copy h2 { font-size:clamp(44px,12vw,70px); }
  .public-hero__caption { right:13px;bottom:12px;font-size:11px; }
  .public-story { margin:52px auto 48px;padding:45px 18px; }.public-story__layout { grid-template-columns:1fr;gap:24px;margin-top:25px; }
  .public-story__portrait { width:min(82%,370px);margin:0 auto; }.public-story__portrait img { height:clamp(260px,82vw,380px); }
  .public-story__words { padding:0 3px; }.public-story blockquote { margin:20px auto;padding:15px 10px;font-size:22px; }
  .public-story__ornament { right:-30px;top:5%;width:112px;height:140px; }.public-section-flourish { width:95px;height:36px; }
  .public-countdown { min-height:215px;margin:28px 0;padding:38px 15px; }.public-countdown strong { font-size:86px; }
  .public-events { padding:62px 0 45px; }.public-event { margin-top:20px;padding:30px 20px; }.public-event::before { left:7px;top:8px;bottom:8px; }
  .public-gallery { padding:58px 0 66px; }.public-gallery__grid { gap:10px; }.public-gallery__grid button { grid-column:span 6; }.public-gallery__grid button:nth-child(2n) { grid-column:span 6;margin-top:28px; }.public-gallery__grid button:nth-child(3n) { grid-column:2 / span 8;margin-top:-5px; }
  .public-gallery__grid img { height:clamp(170px,52vw,270px);border-width:6px;border-bottom-width:17px; }
  .public-rsvp { margin:30px 0;padding:55px 17px; }.public-rsvp__ornament { right:-50px;bottom:-38px;width:185px;height:190px; }
  .public-footer { padding:95px 12px 75px; }
  .public-wedding--modern .public-hero { min-height:76svh;border-radius:0 0 0 92px; }.public-wedding--modern .public-hero__copy { margin:0;padding:32px 17px;text-align:left; }.public-wedding--modern .public-hero__rule { margin-left:0; }.public-wedding--modern .public-hero__date,.public-wedding--modern .public-hero__venue { text-align:left; }
  .public-wedding--modern .public-story__layout { grid-template-columns:1fr; }.public-wedding--modern .public-story__portrait { order:0;width:90%; }.public-wedding--modern .public-story__portrait img { height:340px; }
  .public-wedding--terracotta .public-hero { min-height:77svh;border-radius:48% 48% 2px 2px / 10% 10% 2px 2px; }
  .public-wedding--terracotta .public-story { border-radius:65px 2px 65px 2px; }
}
@media(prefers-reduced-motion:reduce) {
  .public-cover__decoration .cover-speck,.public-open-button::after { animation:none!important; }
  .public-hero__image,.public-story__portrait img,.public-gallery__grid img { animation:none!important; }
  .public-hero:not(.is-in-view) .public-hero__copy h2 { clip-path:none; }
}
</style>

<style scoped>
/* Warm, paper-lit hero treatment replaces the former charcoal haze. */
.public-hero { color:var(--ink);background:radial-gradient(ellipse at 50% 55%,color-mix(in srgb,var(--rose) 28%,var(--paper)),var(--paper)); }
.public-hero__wash { background:linear-gradient(155deg,#fff3 0%,color-mix(in srgb,var(--paper) 22%,transparent) 45%,color-mix(in srgb,var(--rose) 22%,transparent) 100%); }
.public-hero:not(.public-hero--with-image) .public-hero__wash { background:radial-gradient(ellipse at 50% 42%,color-mix(in srgb,var(--rose) 22%,transparent),transparent 62%),linear-gradient(145deg,color-mix(in srgb,var(--paper) 70%,white),var(--paper)); }
.public-hero__copy { animation:hero-plaque-arrive 1.15s cubic-bezier(.2,.75,.25,1) both; }
.public-hero__copy h2 { animation:hero-title-settle 1.1s .12s cubic-bezier(.2,.8,.2,1) both; }
.public-hero>.invitation-ornament--hero { opacity:.82;filter:none; }
.public-story__ornament { opacity:.28; }
.public-gallery__ornament { opacity:.3; }
.public-rsvp__ornament { opacity:.26; }
.public-footer__ornament { opacity:.4; }
.public-cover__page-turn { box-shadow:20px 0 45px color-mix(in srgb,var(--gold) 20%,transparent); }
.public-story__portrait img { clip-path:inset(1% 1% 1% 1% round 48% 48% 3px 3px);transition:clip-path 1.1s cubic-bezier(.2,.75,.25,1),transform .8s cubic-bezier(.2,.75,.25,1); }
.public-wedding--classic .public-story__portrait img { clip-path:inset(1% round 2px); }
.public-wedding--modern .public-story__portrait img { clip-path:inset(0 round 48% 48% 6px 6px); }
.public-wedding--terracotta .public-story__portrait img { clip-path:inset(1% round 40% 40% 48% 48%); }
.public-hero,.public-story,.public-countdown,.public-events,.public-gallery,.public-rsvp,.public-footer,.public-event,.public-agenda>div { transition-duration:1s;transition-timing-function:cubic-bezier(.2,.75,.25,1); }
.public-wedding--motion-garden .public-cover__decoration>.invitation-ornament--cover,.public-wedding--motion-garden .public-footer__ornament { animation:ornamental-breeze 11s ease-in-out infinite alternate; }
.public-wedding--motion-cinematic .public-cover__decoration>.invitation-ornament--cover { animation:ornamental-breeze 13s ease-in-out infinite alternate-reverse; }
@keyframes hero-plaque-arrive { from { opacity:0;transform:translateY(28px) scale(.97); } to { opacity:1;transform:translateY(0) scale(1); } }
@keyframes hero-title-settle { from { opacity:0;clip-path:inset(0 0 50% 0);transform:translateY(15px); } to { opacity:1;clip-path:inset(0);transform:translateY(0); } }
@keyframes ornamental-breeze { from { translate:0 0;rotate:-.7deg; } to { translate:0 -8px;rotate:.7deg; } }
@media(max-width:700px) {
  .public-hero__copy { width:94%;padding:34px 14px 32px;border-radius:48% 48% 4px 4px / 18% 18% 4px 4px; }
  .public-hero__copy h2 { font-size:clamp(43px,11.5vw,70px); }
  .public-hero>.invitation-ornament--hero { opacity:.65; }
}
@media(prefers-reduced-motion:reduce) {
  .public-hero__copy,.public-hero__copy h2,.public-wedding--motion-garden .public-cover__decoration>.invitation-ornament--cover,.public-wedding--motion-garden .public-footer__ornament,.public-wedding--motion-cinematic .public-cover__decoration>.invitation-ornament--cover { animation:none!important; }
  .public-story__portrait img { clip-path:none; }
}
</style>

<style scoped>
.public-cover--botanical,.public-cover--terracotta { background-image:none!important;background-color:var(--paper); }
.public-wedding--botanical { --invitation-artwork:url('/images/invitations/enchanted-garden-background.webp'); }
.public-wedding--terracotta { --invitation-artwork:url('/images/invitations/blooming-promise-background.webp'); }
.public-wedding--botanical .public-story,.public-wedding--botanical .public-events,.public-wedding--botanical .public-gallery,.public-wedding--botanical .public-rsvp,.public-wedding--botanical .public-footer,.public-wedding--terracotta .public-story,.public-wedding--terracotta .public-events,.public-wedding--terracotta .public-gallery,.public-wedding--terracotta .public-rsvp,.public-wedding--terracotta .public-footer { background-image:linear-gradient(180deg,color-mix(in srgb,var(--paper) 72%,transparent),color-mix(in srgb,var(--paper) 86%,transparent)),var(--invitation-artwork);background-size:cover;background-position:center;background-color:var(--paper); }
.public-cover__artwork { position:absolute;z-index:0;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;animation:cover-art-breathe 26s ease-in-out infinite alternate; }
.public-event__timezone { margin-left:5px; }
.public-hero--with-image .public-hero__image { filter:saturate(1.02) brightness(1.12); }
.public-hero--with-image .public-hero__wash { background:linear-gradient(155deg,color-mix(in srgb,var(--paper) 25%,transparent) 0%,color-mix(in srgb,var(--paper) 18%,transparent) 52%,color-mix(in srgb,var(--rose) 25%,transparent) 100%); }
.public-hero__copy { box-shadow:0 18px 44px color-mix(in srgb,var(--ink) 6%,transparent),inset 0 0 0 8px color-mix(in srgb,var(--gold) 8%,transparent); }
.public-cover__frame { z-index:1;inset:19px;border-color:color-mix(in srgb,var(--gold) 45%,transparent);mask-image:none; }
.public-cover__frame::before,.public-cover__frame::after { background:transparent; }
.public-cover__decoration { z-index:1;opacity:.25; }
.public-cover__header,.public-cover__content { z-index:2; }
.public-cover__monogram { background:color-mix(in srgb,var(--paper) 88%,white); }
.public-cover__salutation { background:color-mix(in srgb,var(--paper) 88%,white);border-color:color-mix(in srgb,var(--gold) 68%,white);backdrop-filter:blur(7px); }
.public-cover__content h1 { text-shadow:0 1px 12px #fff9; }
.public-cover__date { color:color-mix(in srgb,var(--ink) 90%,black); }
.public-cover__privacy { color:var(--ink); }
.public-cover__page-turn { z-index:4; }
.public-cover--botanical .public-cover__decoration>.invitation-ornament--cover,.public-cover--terracotta .public-cover__decoration>.invitation-ornament--cover,.public-hero>.invitation-ornament--hero { display:none; }
.public-wedding--botanical {
  width:100%;max-width:none;margin:0;border:0;
  background-color:var(--paper);
  background-image:linear-gradient(180deg,color-mix(in srgb,var(--paper) 84%,transparent),color-mix(in srgb,var(--paper) 76%,transparent)),url('/images/invitations/enchanted-garden-background.webp');
  background-position:center top;background-size:cover;background-repeat:no-repeat;background-attachment:fixed;
}
.public-wedding--botanical .public-story,.public-wedding--botanical .public-events,.public-wedding--botanical .public-gallery,.public-wedding--botanical .public-rsvp,.public-wedding--botanical .public-footer {
  margin-block:clamp(24px,4vw,44px);border:1px solid color-mix(in srgb,var(--gold) 24%,transparent);border-radius:18px;
  background:linear-gradient(145deg,color-mix(in srgb,var(--paper) 84%,white),color-mix(in srgb,var(--paper) 74%,white));box-shadow:0 12px 32px #59452a0a;
}
.public-wedding--botanical .public-story__layout { grid-template-columns:minmax(0,1fr) minmax(0,1.08fr); }
.public-wedding--botanical .public-cover { margin:0; }
.public-wedding:not(.public-wedding--preview) > .public-cover { position:fixed;z-index:10000;inset:0;width:100%;height:100svh;min-height:100svh;margin:0; }
.public-wedding--preview .public-cover { position:relative; }
.public-wedding--botanical .invitation-cover-enter-active,.public-wedding--botanical .invitation-cover-leave-active { transform-origin:left center;transform-style:preserve-3d;backface-visibility:hidden;transition:opacity 1.25s cubic-bezier(.2,.75,.25,1),transform 1.45s cubic-bezier(.2,.75,.25,1),filter 1.2s ease; }
.public-wedding--botanical .invitation-cover-enter-from { opacity:0;filter:brightness(.92);transform:perspective(1500px) rotateY(68deg); }
.public-wedding--botanical .invitation-cover-leave-to { opacity:0;filter:brightness(.92);transform:perspective(1500px) rotateY(-68deg);clip-path:inset(0); }
.public-wedding--botanical .public-hero { margin:0 auto clamp(28px,5vw,52px);border-radius:0;background:transparent; }
.public-wedding--botanical .public-hero__image { inset:0;width:100%;height:100%;transform:scale(1.04);mask-image:linear-gradient(180deg,transparent 0%,#000 13%,#000 87%,transparent 100%); }
.public-wedding--botanical .public-hero__wash { mask-image:linear-gradient(180deg,transparent 0%,#000 13%,#000 87%,transparent 100%); }
.public-wedding--botanical .public-cover__page-turn { animation-duration:1.25s; }
.public-wedding--botanical .public-story__portrait { animation:portrait-float 10s 1s ease-in-out infinite alternate; }
.public-wedding--botanical .public-event.is-in-view { animation:card-float 9s calc(var(--reveal-index,0) * 1.2s) ease-in-out infinite alternate; }
.public-wedding--botanical .public-hero__copy { animation:hero-plaque-arrive 1.2s cubic-bezier(.2,.75,.25,1) both,plaque-breathe 10s 1.35s ease-in-out infinite alternate; }
.public-wedding--botanical .public-hero__image { animation-duration:24s; }
.public-wedding--botanical .public-story,.public-wedding--botanical .public-countdown,.public-wedding--botanical .public-events,.public-wedding--botanical .public-gallery,.public-wedding--botanical .public-rsvp,.public-wedding--botanical .public-footer,.public-wedding--botanical .public-event,.public-wedding--botanical .public-agenda>div { transition-duration:1.2s;transition-timing-function:cubic-bezier(.2,.75,.25,1); }
.public-wedding--botanical .invitation-dock-enter-active,.public-wedding--botanical .invitation-dock-leave-active { transition-duration:.75s; }
.falling-leaves { position:fixed;z-index:6;inset:0;overflow:hidden;pointer-events:none; }
.falling-leaf { position:absolute;top:-8vh;width:13px;height:24px;border:1px solid #71835f52;border-radius:0 100% 0 100%;background:linear-gradient(145deg,#b4be86c9,#71835f9c);opacity:0;transform-origin:center;animation:falling-leaf var(--leaf-duration) var(--leaf-delay) linear infinite; }
.falling-leaf::after { position:absolute;top:3px;left:5px;width:1px;height:16px;background:#f6f0ce91;content:"";transform:rotate(-18deg); }
.cover-petal { position:absolute!important;display:block;width:12px!important;height:7px!important;border:0!important;border-radius:90% 2% 90% 2%!important;background:color-mix(in srgb,var(--rose) 82%,white)!important;box-shadow:0 2px 7px color-mix(in srgb,var(--rose) 42%,transparent);opacity:0;animation:rose-petal-flight 11s ease-in-out infinite!important; }
.cover-petal--one { left:14%;top:20%;animation-delay:-1s!important; }.cover-petal--two { left:78%;top:27%;animation-delay:-7s!important; }.cover-petal--three { left:9%;top:59%;animation-delay:-4s!important; }.cover-petal--four { right:14%;top:69%;animation-delay:-9s!important; }.cover-petal--five { left:30%;top:15%;animation-delay:-5.5s!important; }.cover-petal--six { right:31%;top:79%;animation-delay:-2.6s!important; }
.public-cover--botanical .public-cover__frame,.public-cover--terracotta .public-cover__frame { inset:11px;border:2px solid color-mix(in srgb,var(--gold) 62%,white);outline:1px solid color-mix(in srgb,var(--gold) 25%,transparent);outline-offset:5px; }
.public-cover--terracotta .public-cover__frame { inset:15px;border-radius:48% 48% 5px 5px / 14% 14% 5px 5px; }
.invitation-dock { --accent:#71835f;--ink:#344536;--gold:#c9a96b;position:fixed;z-index:9997;left:50%;bottom:max(12px,env(safe-area-inset-bottom));display:flex;align-items:center;justify-content:center;gap:clamp(8px,3vw,25px);width:max-content;max-width:calc(100vw - 20px);padding:9px 16px;border:1px solid color-mix(in srgb,var(--gold) 46%,white);border-radius:999px;background:#fffef4ed;box-shadow:0 12px 36px #59452a26;backdrop-filter:blur(18px);transform:translateX(-50%); }
.invitation-dock button { display:flex;min-width:48px;flex-direction:column;align-items:center;gap:4px;border:0;background:transparent;color:var(--accent);font:600 9px var(--font-body);cursor:pointer;transition:color .22s,transform .22s; }
.invitation-dock button:hover { color:var(--ink);transform:translateY(-2px); }
.invitation-dock button svg { font-size:15px; }
.public-music-toggle { position:fixed;z-index:9999;top:max(14px,env(safe-area-inset-top));right:max(14px,env(safe-area-inset-right));display:inline-flex;align-items:center;gap:9px;min-height:42px;padding:0 15px;border:1px solid color-mix(in srgb,var(--gold) 45%,white);border-radius:999px;background:#fffef4ed;color:var(--ink);box-shadow:0 8px 24px #59452a18;backdrop-filter:blur(14px);font:600 11px var(--font-body);cursor:pointer;transition:background .35s,border-color .35s,transform .35s; }
.public-music-toggle:hover { background:#fff;transform:translateY(-2px); }
.public-music-toggle svg { color:var(--accent);font-size:13px; }
.invitation-music-enter-active,.invitation-music-leave-active { transition:opacity .6s ease,transform .7s cubic-bezier(.2,.75,.25,1); }
.invitation-music-enter-from,.invitation-music-leave-to { opacity:0;transform:translateY(-8px); }
.invitation-dock-enter-active,.invitation-dock-leave-active { transition:opacity .38s ease,transform .5s cubic-bezier(.2,.8,.2,1); }
.invitation-dock-enter-from,.invitation-dock-leave-to { opacity:0;transform:translate(-50%,18px); }
@keyframes cover-art-breathe { from { transform:scale(1); } to { transform:scale(1.035); } }
@keyframes rose-petal-flight { 0%,100% { opacity:0;transform:translate3d(0,-14px,0) rotate(-25deg); } 15%,78% { opacity:.78; } 50% { transform:translate3d(18px,32px,0) rotate(88deg); } }
@keyframes falling-leaf { 0% { opacity:0;transform:translate3d(0,-8vh,0) rotate(0); } 10% { opacity:.34; } 88% { opacity:.25; } 100% { opacity:0;transform:translate3d(var(--leaf-drift),112vh,0) rotate(var(--leaf-spin)); } }
@keyframes portrait-float { from { translate:0 0; } to { translate:0 -7px; } }
@keyframes card-float { from { translate:0 0; } to { translate:0 -3px; } }
@keyframes plaque-breathe { from { translate:0 0; } to { translate:0 -5px; } }
@media(max-width:420px) {
  .public-wedding--botanical .public-story__layout { grid-template-columns:1fr; }
  .public-wedding--botanical .public-story__portrait { width:min(100%,430px);margin-inline:auto; }
  .public-wedding--botanical { background-attachment:scroll; }
  .public-cover__artwork { object-position:center; }
  .public-cover__frame { inset:9px; }
  .public-cover__header { margin-bottom:12px; }
  .public-cover__salutation { width:min(320px,100%);padding:12px 13px;margin:9px auto 10px; }
  .invitation-dock { gap:clamp(5px,2vw,12px);padding:8px 11px; }
  .invitation-dock button { min-width:43px;font-size:8px; }
  .public-music-toggle { min-height:39px;padding-inline:12px;font-size:10px; }
}
@media(prefers-reduced-motion:reduce) {
  .public-wedding:not(.public-wedding--preview) > .public-cover { transition:none!important; }
  .public-wedding--botanical .invitation-cover-enter-active,.public-wedding--botanical .invitation-cover-leave-active { transition:none!important;transform:none!important;filter:none!important; }
  .public-cover__artwork,.cover-petal { animation:none!important; }
  .falling-leaves,.public-wedding--botanical .public-story__portrait,.public-wedding--botanical .public-event.is-in-view { animation:none!important;display:none!important; }
  .public-wedding--botanical .public-hero__copy { animation:none!important; }
  .invitation-dock,.invitation-dock-enter-active,.invitation-dock-leave-active { transition:none!important; }
  .public-music-toggle,.invitation-music-enter-active,.invitation-music-leave-active { transition:none!important; }
}
</style>
