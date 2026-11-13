<script setup>
import { useId } from "vue";

const gradientPrefix = useId();
defineProps({
  theme: { type: String, default: "botanical" },
  placement: { type: String, default: "cover" },
});
</script>

<template>
  <svg
    class="invitation-ornament"
    :class="[`invitation-ornament--${theme}`, `invitation-ornament--${placement}`]"
    viewBox="0 0 520 520"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <defs>
      <radialGradient :id="`${gradientPrefix}-flower-center`" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(0 0) rotate(90) scale(18)">
        <stop stop-color="var(--flower-center, #f6dc9f)" />
        <stop offset="1" stop-color="var(--flower-center, #f6dc9f)" stop-opacity=".18" />
      </radialGradient>
      <linearGradient :id="`${gradientPrefix}-leaf-wash`" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="var(--leaf-light, #91a77a)" />
        <stop offset="1" stop-color="var(--leaf-dark, #547356)" />
      </linearGradient>
    </defs>

    <g v-if="theme === 'botanical'" class="ornament-art ornament-art--botanical" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path class="garden-vine garden-vine--left" d="M-14 514C72 468 116 396 91 333c-15-38-65-61-92-32-29 31-9 81 28 83 25 1 42-18 36-40m4 174c19-58 52-98 103-112 59-17 89-66 68-105-17-31-56-35-75-8-18 25-1 55 25 53m-108-31c-14-39-13-78 6-112 20-35 12-69-14-89m147 316c20-43 55-67 99-60 44 8 78-20 71-53-6-27-38-38-57-21" />
      <path class="garden-vine garden-vine--right" d="M534 514c-86-46-130-118-105-181 15-38 65-61 92-32 29 31 9 81-28 83-25 1-42-18-36-40m-4 174c-19-58-52-98-103-112-59-17-89-66-68-105 17-31 56-35 75-8 18 25 1 55-25 53m108-31c14-39 13-78-6-112-20-35-12-69 14-89m-147 316c-20-43-55-67-99-60-44 8-78-20-71-53 6-27 38-38 57-21" />
      <g class="garden-leaves">
        <path class="garden-leaf leaf-a" d="M28 410C-2 394-11 370 1 343c28 11 39 34 27 67Zm48-83c-29-17-35-42-18-67 26 14 32 37 18 67Zm46-116c-26-21-28-47-7-69 23 18 26 41 7 69Zm-9 228c10-32 32-44 61-34-9 29-30 42-61 34Zm108-81c-2-32 16-51 46-49-1 30-17 48-46 49Zm77-99c-2-30 15-47 43-45-1 28-16 44-43 45Z" />
        <path class="garden-leaf leaf-b" d="M492 410c30-16 39-40 27-67-28 11-39 34-27 67Zm-48-83c29-17 35-42 18-67-26 14-32 37-18 67Zm-46-116c26-21 28-47 7-69-23 18-26 41-7 69Zm9 228c-10-32-32-44-61-34 9 29 30 42 61 34Zm-108-81c2-32-16-51-46-49 1 30 17 48 46 49Zm-77-99c2-30-15-47-43-45 1 28 16 44 43 45Z" />
      </g>
      <g class="bloom bloom--rose" transform="translate(91 155)">
        <ellipse v-for="petal in 8" :key="`rose-${petal}`" class="bloom-petal" cx="0" cy="-22" rx="11" ry="21" :transform="`rotate(${petal * 45})`" />
        <circle r="18" :fill="`url(#${gradientPrefix}-flower-center)`" /><circle r="5" class="bloom-heart" />
      </g>
      <g class="bloom bloom--peony" transform="translate(425 166) scale(.72)">
        <ellipse v-for="petal in 8" :key="`peony-${petal}`" class="bloom-petal" cx="0" cy="-22" rx="12" ry="23" :transform="`rotate(${petal * 45})`" />
        <circle r="18" :fill="`url(#${gradientPrefix}-flower-center)`" /><circle r="5" class="bloom-heart" />
      </g>
      <g class="bloom bloom--blush" transform="translate(426 414) scale(.56)">
        <ellipse v-for="petal in 8" :key="`blush-${petal}`" class="bloom-petal" cx="0" cy="-22" rx="11" ry="21" :transform="`rotate(${petal * 45})`" />
        <circle r="18" :fill="`url(#${gradientPrefix}-flower-center)`" /><circle r="5" class="bloom-heart" />
      </g>
      <g class="drifting-petals" aria-hidden="true">
        <path class="drifting-petal petal-a" d="M244 107c12-14 26-5 22 7-2 8-18 15-18 15s-13-12-4-22Z" />
        <path class="drifting-petal petal-b" d="M337 331c10-13 24-6 20 6-3 8-17 14-17 14s-12-11-3-20Z" />
        <path class="drifting-petal petal-c" d="M163 273c9-12 21-5 18 5-2 7-15 13-15 13s-11-10-3-18Z" />
        <path class="sparkle sparkle-a" d="M300 65v28m-14-14h28m96 108v20m-10-10h20M196 438v22m-11-11h22" />
      </g>
    </g>

    <g v-else-if="theme === 'classic'" class="ornament-art ornament-art--classic" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path class="royal-frame" d="M36 494V154C36 78 91 26 165 26h190c74 0 129 52 129 128v340M52 494V155C52 87 101 42 165 42h190c64 0 113 45 113 113v339" />
      <path class="royal-corner" d="M37 158c43-3 69-27 74-75 43 2 69-24 67-58m305 133c-43-3-69-27-74-75-43 2-69-24-67-58M37 362c43 3 69 27 74 75 43-2 69 24 67 58m305-133c-43 3-69 27-74 75-43-2-69 24-67 58" />
      <path class="royal-scroll" d="M118 111c-26-32-9-61 20-54 22 5 24 32 7 42-13 7-27-4-20-16m277 28c26-32 9-61-20-54-22 5-24 32-7 42 13 7 27-4 20-16M118 409c-26 32-9 61 20 54 22-5 24-32 7-42-13-7-27 4-20 16m277-28c26 32 9 61-20 54-22-5-24-32-7-42 13-7 27 4 20 16" />
      <g class="classic-rosette" transform="translate(260 260)">
        <circle r="144" class="rosette-ring" /><circle r="128" class="rosette-ring rosette-ring--inner" />
        <ellipse v-for="petal in 12" :key="`classic-${petal}`" class="rosette-petal" cx="0" cy="-88" rx="13" ry="30" :transform="`rotate(${petal * 30})`" />
        <circle r="34" class="rosette-center" /><circle r="11" class="rosette-center-dot" />
      </g>
      <path class="royal-star" d="M260 17v44m-22-22h44m-22 420v44m-22-22h44" />
    </g>

    <g v-else-if="theme === 'modern'" class="ornament-art ornament-art--modern" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path class="modern-shape modern-shape--one" d="M40 131 151 31l98 106-105 95L40 131Zm241 254 96-92 102 109-105 97-93-114Z" />
      <path class="modern-heart" d="M418 61c-22-25-59-10-59 21 0 37 59 72 59 72s59-35 59-72c0-31-37-46-59-21Z" />
      <path class="modern-flower" d="M87 302c27-25 67-14 68 21 0 31-45 60-45 60s-48-27-48-59c0-24 14-37 25-22Zm230-220 12 36 36 12-36 12-12 36-12-36-36-12 36-12 12-36Zm-95 324 9 28 28 9-28 9-9 28-9-28-28-9 28-9 9-28Z" />
      <circle cx="260" cy="260" r="120" class="modern-ring" /><circle cx="260" cy="260" r="141" class="modern-ring modern-ring--dotted" />
      <path class="modern-orbit" d="M260 92c93 0 168 75 168 168s-75 168-168 168S92 353 92 260 167 92 260 92Z" />
      <circle class="orbit-spark" cx="423" cy="259" r="8" />
    </g>

    <g v-else class="ornament-art ornament-art--terracotta" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <path class="sunset-branch sunset-branch--left" d="M-8 511c65-72 104-160 120-263 5-32 8-64 9-97m-54 267c-34-7-49-30-39-61 30 6 44 27 39 61Zm35-98c-35-10-48-34-32-65 30 11 42 35 32 65Zm16-77c-28-25-29-54-3-78 23 21 25 49 3 78Zm3 217c23-31 52-44 89-37 37 7 63-15 56-43-6-25-36-32-51-15" />
      <path class="sunset-branch sunset-branch--right" d="M528 511c-65-72-104-160-120-263-5-32-8-64-9-97m54 267c34-7 49-30 39-61-30 6-44 27-39 61Zm-35-98c35-10 48-34 32-65-30 11-42 35-32 65Zm-16-77c28-25 29-54 3-78-23 21-25 49-3 78Zm-3 217c-23-31-52-44-89-37-37 7-63-15-56-43 6-25 36-32 51-15" />
      <g class="sunset-bloom" transform="translate(260 215)">
        <circle r="88" class="sunset-halo" /><circle r="68" class="sunset-halo sunset-halo--inner" />
        <ellipse v-for="petal in 10" :key="`sunset-${petal}`" class="sunset-petal" cx="0" cy="-24" rx="13" ry="27" :transform="`rotate(${petal * 36})`" />
        <circle r="20" class="sunset-center" /><circle r="6" class="sunset-center-dot" />
      </g>
      <path class="sunset-star" d="M260 76v38m-19-19h38m-19 340v40m-20-20h40" />
      <g class="drifting-petals" aria-hidden="true">
        <path class="drifting-petal petal-a" d="M170 112c12-14 25-5 21 7-2 8-17 15-17 15s-13-12-4-22Z" />
        <path class="drifting-petal petal-b" d="M365 330c10-13 23-6 20 6-3 8-17 14-17 14s-11-11-3-20Z" />
      </g>
    </g>
  </svg>
</template>

<style scoped>
.invitation-ornament{display:block;overflow:visible;color:var(--ornament-ink,var(--accent,#536a54))}
.ornament-art{transform-box:fill-box;transform-origin:center}
.ornament-art--botanical{--leaf-light:#b4bd82;--leaf-dark:#587858;--flower-center:#f2d37e}
.garden-vine{stroke-width:2;opacity:.74;stroke-dasharray:1100;animation:vine-breathe 18s ease-in-out infinite alternate}
.garden-vine--right{animation-delay:-8s;animation-direction:alternate-reverse}
.garden-leaf{fill:color-mix(in srgb,currentColor 26%,white);fill-opacity:.85;stroke-width:1.15;stroke:color-mix(in srgb,currentColor 82%,white);transform-box:fill-box;transform-origin:center;animation:leaf-sway 7.2s ease-in-out infinite alternate}
.leaf-b{animation-delay:-3.6s}
.bloom-petal{fill:var(--ornament-flower,#dba899);fill-opacity:.84;stroke:color-mix(in srgb,var(--ornament-flower,#dba899) 40%,white);stroke-width:1.35;transform-box:fill-box;transform-origin:center;animation:petal-breathe 7.8s ease-in-out infinite alternate}
.bloom-heart{fill:#d5a64e;stroke:#fff6d7;stroke-width:2}
.drifting-petal{fill:var(--ornament-flower,#dba899);fill-opacity:.8;stroke:#fff9e9;stroke-width:1;transform-box:fill-box;transform-origin:center;animation:petal-drift 13s ease-in-out infinite}
.petal-b{animation-delay:-4.7s}.petal-c{animation-delay:-8.4s}
.sparkle{stroke:#d2aa58;stroke-width:2.2;opacity:.9;animation:sparkle-glow 4s ease-in-out infinite alternate}
.sparkle-a{animation-delay:-2s}
.ornament-art--classic{--ornament-flower:#d9b77d;stroke-width:1.6}.royal-frame{stroke:color-mix(in srgb,currentColor 65%,#d6b777);stroke-width:2.2}.royal-corner,.royal-scroll{stroke:var(--ornament-flower);stroke-width:2.4;opacity:.9}
.rosette-ring{stroke:var(--ornament-flower);stroke-width:1.6;stroke-dasharray:1 5}.rosette-ring--inner{stroke-width:2.3;stroke-dasharray:none;opacity:.65}
.rosette-petal{fill:color-mix(in srgb,var(--ornament-flower) 55%,white);fill-opacity:.85;stroke:var(--ornament-flower);stroke-width:1.5;transform-box:fill-box;transform-origin:center;animation:classic-bloom 12s ease-in-out infinite alternate}
.rosette-center{fill:#fff6e5;stroke:var(--ornament-flower);stroke-width:2}.rosette-center-dot{fill:#c59b5f;stroke:#fff2d1;stroke-width:3}.royal-star{stroke:#c59b5f;stroke-width:2.5}
.ornament-art--modern{--ornament-flower:#dc8592;stroke-width:2}.modern-shape{fill:#d989922c;stroke:#af6572;stroke-width:2.5}.modern-shape--one{animation:shape-drift 13s ease-in-out infinite alternate}
.modern-heart{fill:#dc859250;stroke:#a95869;stroke-width:2.5}.modern-flower{fill:#d99b7428;stroke:#c18b68;stroke-width:2}.modern-ring{stroke:#8e758b;stroke-width:1.5}.modern-ring--dotted{stroke-dasharray:2 8;opacity:.72}.modern-orbit{stroke:#be7780;stroke-width:1.6;stroke-dasharray:4 9;transform-box:fill-box;transform-origin:center;animation:orbit-drift 28s linear infinite}.orbit-spark{fill:#e0b96e;stroke:#fff4d4;stroke-width:3;animation:sparkle-glow 3.8s ease-in-out infinite alternate}
.ornament-art--terracotta{--ornament-flower:#df9b70;--leaf-light:#d5aa77;--leaf-dark:#9a634d;stroke-width:1.8}.sunset-branch{stroke:#9b694f;stroke-width:2;opacity:.8}.sunset-halo{fill:#e9b7791c;stroke:#c98c60;stroke-width:1.6}.sunset-halo--inner{fill:none;stroke:#d4a15e;stroke-dasharray:2 7}.sunset-petal{fill:#e7a27c;fill-opacity:.62;stroke:#b87457;stroke-width:1.6;transform-box:fill-box;transform-origin:center;animation:petal-breathe 9s ease-in-out infinite alternate}.sunset-petal:nth-of-type(2n){animation-delay:-3s}.sunset-center{fill:#f5d391;stroke:#b87457;stroke-width:1.7}.sunset-center-dot{fill:#b87457;stroke:#f8dca4;stroke-width:2}.sunset-star{stroke:#c78b56;stroke-width:2}
@keyframes vine-breathe{from{stroke-dashoffset:0;opacity:.65}to{stroke-dashoffset:55;opacity:.9}}
@keyframes leaf-sway{from{transform:rotate(-5deg) translateY(1px)}to{transform:rotate(5deg) translateY(-3px)}}
@keyframes petal-breathe{from{transform:scale(.96) rotate(-2deg)}to{transform:scale(1.04) rotate(3deg)}}
@keyframes flower-sway{from{transform:translateY(1px) rotate(-3deg)}to{transform:translateY(-4px) rotate(4deg)}}
@keyframes petal-drift{0%,100%{opacity:.2;transform:translate3d(-4px,8px,0) rotate(-25deg)}50%{opacity:.9;transform:translate3d(8px,-17px,0) rotate(35deg)}}
@keyframes classic-bloom{from{transform:scale(.98) rotate(-2deg)}to{transform:scale(1.03) rotate(2deg)}}
@keyframes shape-drift{from{transform:translateY(0) rotate(-1deg)}to{transform:translateY(-8px) rotate(2deg)}}
@keyframes orbit-drift{to{transform:rotate(360deg)}}
@keyframes sparkle-glow{from{opacity:.42}to{opacity:1}}
@media(prefers-reduced-motion:reduce){.ornament-art,.ornament-art *{animation:none!important}}
</style>
