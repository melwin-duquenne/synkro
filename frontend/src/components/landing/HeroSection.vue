<script setup lang="ts">
import { useRouter } from "vue-router";

const router = useRouter();

function scrollToSection(sectionId: string) {
  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ behavior: "smooth" });
  }
}
</script>

<template>
  <section
    id="hero"
    class="hero-section relative min-h-screen flex flex-col items-center justify-center overflow-hidden"
  >
    <!-- Grid background -->
    <div class="absolute inset-0 hero-grid pointer-events-none"></div>
    <!-- Radial glow center -->
    <div
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-225 h-150 bg-[#408ed6]/6 rounded-full blur-[140px] pointer-events-none"
    ></div>

    <!-- Top content -->
    <div class="relative z-10 max-w-5xl mx-auto px-6 text-center pt-28 pb-12">
      <!-- Announcement badge -->
      <button
        @click="scrollToSection('changelog')"
        class="announcement-badge group mb-8"
      >
        <span class="badge-new-pill">✦ Nouveau</span>
        <span
          class="text-sm text-[#6b8099] group-hover:text-[#b0c9e0] transition-colors"
          >Tableau blanc collaboratif disponible</span
        >
        <svg
          class="w-3.5 h-3.5 text-[#4a6072] group-hover:text-[#5ba3e8] group-hover:translate-x-0.5 transition-all"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>

      <!-- Main heading -->
      <h1 class="hero-title mb-5">
        La collaboration,<br />
        <span class="hero-gradient-text">réinventée.</span>
      </h1>

      <!-- Subtitle -->
      <p
        class="text-lg md:text-xl text-[#516070] max-w-xl mx-auto mb-10 leading-relaxed"
      >
        Éditeur temps réel, chat, tâches, tableau blanc et vidéo — tout dans un
        seul espace unifié pour votre équipe.
      </p>

      <!-- CTAs -->
      <div
        class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-10"
      >
        <button @click="router.push('/register')" class="hero-cta-primary">
          Commencer — c'est gratuit
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M13 7l5 5m0 0l-5 5m5-5H6"
            />
          </svg>
        </button>
        <button @click="scrollToSection('features')" class="hero-cta-secondary">
          Voir les fonctionnalités
        </button>
      </div>

      <!-- Social proof -->
      <div class="flex items-center justify-center gap-3 mb-16">
        <div class="flex items-center">
          <div class="avatar-pill" style="background: #4f6ecd">JD</div>
          <div
            class="avatar-pill"
            style="background: #9b59b6; margin-left: -9px"
          >
            ML
          </div>
          <div
            class="avatar-pill"
            style="background: #27ae60; margin-left: -9px"
          >
            AP
          </div>
          <div
            class="avatar-pill"
            style="background: #e67e22; margin-left: -9px"
          >
            RB
          </div>
          <div
            class="avatar-pill"
            style="background: #16a085; margin-left: -9px"
          >
            SK
          </div>
        </div>
        <div class="text-sm text-[#3d5060]">
          <span class="text-[#8ea4be] font-semibold">+500 équipes</span> nous
          font déjà confiance
        </div>
      </div>
    </div>

    <!-- Product screenshot mockup -->
    <div class="relative z-10 w-full max-w-6xl mx-auto px-6 pb-24">
      <!-- Fade-out gradient on sides -->
      <div
        class="absolute left-0 top-0 w-24 h-full bg-linear-to-r from-[#050a14] to-transparent z-20 pointer-events-none"
      ></div>
      <div
        class="absolute right-0 top-0 w-24 h-full bg-linear-to-l from-[#050a14] to-transparent z-20 pointer-events-none"
      ></div>

      <div class="mockup-window">
        <!-- Browser chrome bar -->
        <div class="mockup-bar">
          <div class="flex items-center gap-1.5 shrink-0">
            <span class="traffic-dot" style="background: #ff5f57"></span>
            <span class="traffic-dot" style="background: #ffbd2e"></span>
            <span class="traffic-dot" style="background: #28ca41"></span>
          </div>
          <div class="mockup-url-bar">
            <svg
              class="w-3 h-3 text-[#3d5060]"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
            <span class="text-[11px] text-[#3d5060]"
              >app.synkro.io / projet-design</span
            >
          </div>
        </div>

        <!-- App UI -->
        <div class="mockup-app">
          <!-- Left Sidebar -->
          <div class="mock-sidebar">
            <div class="mock-logo-row">
              <svg
                class="w-4 h-4 text-[#5ba3e8]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z"
                />
              </svg>
              <span class="text-[11px] font-bold text-[#c8daea]">Synkro</span>
            </div>
            <div class="mock-nav-item mock-nav-active">
              <div class="mock-nav-icon bg-[#5ba3e8]/20"></div>
              <span class="text-[10px] text-[#5ba3e8]">Dashboard</span>
            </div>
            <div class="mock-nav-item">
              <div class="mock-nav-icon bg-white/5"></div>
              <span class="text-[10px] text-[#3d5060]">Éditeur</span>
            </div>
            <div class="mock-nav-item">
              <div class="mock-nav-icon bg-white/5"></div>
              <span class="text-[10px] text-[#3d5060]">Tâches</span>
            </div>
            <div class="mock-nav-item">
              <div class="mock-nav-icon bg-white/5"></div>
              <span class="text-[10px] text-[#3d5060]">Calendrier</span>
            </div>
            <div class="mock-nav-item">
              <div class="mock-nav-icon bg-white/5"></div>
              <span class="text-[10px] text-[#3d5060]">Chat</span>
            </div>
            <div class="mt-auto">
              <div class="mock-nav-item">
                <div class="w-5 h-5 rounded-full bg-[#4F6ECD] shrink-0"></div>
                <span class="text-[10px] text-[#3d5060]">Jean D.</span>
              </div>
            </div>
          </div>

          <!-- Main Area -->
          <div class="mock-main-area">
            <!-- Top bar -->
            <div class="flex items-center justify-between mb-5">
              <div>
                <div class="h-3 w-36 rounded bg-white/12 mb-1.5"></div>
                <div class="h-2 w-24 rounded bg-white/5"></div>
              </div>
              <div class="flex items-center gap-2">
                <div class="flex items-center">
                  <div
                    class="w-5 h-5 rounded-full border-2 border-[#050a14] bg-[#4F6ECD]"
                  ></div>
                  <div
                    class="w-5 h-5 rounded-full border-2 border-[#050a14] bg-[#9B59B6] -ml-1.5"
                  ></div>
                  <div
                    class="w-5 h-5 rounded-full border-2 border-[#050a14] bg-[#27AE60] -ml-1.5"
                  ></div>
                </div>
                <div class="h-7 w-24 rounded-lg bg-[#408ed6]/80"></div>
              </div>
            </div>

            <!-- Content grid -->
            <div class="grid grid-cols-3 gap-3">
              <!-- Editor preview card -->
              <div class="mock-card col-span-2" style="min-height: 120px">
                <div class="flex items-center gap-2 mb-3">
                  <div class="h-2 w-20 rounded bg-white/12"></div>
                  <div class="h-4 w-px bg-white/8"></div>
                  <div class="h-2 w-14 rounded bg-white/6"></div>
                </div>
                <div class="space-y-1.5">
                  <div class="h-1.5 w-full rounded bg-white/8"></div>
                  <div class="h-1.5 w-5/6 rounded bg-white/6"></div>
                  <div class="h-1.5 w-4/5 rounded bg-white/8"></div>
                  <div class="h-1.5 w-full rounded bg-white/6"></div>
                </div>
                <!-- Cursor indicators -->
                <div class="flex gap-2 mt-4">
                  <span
                    class="inline-flex items-center gap-1 text-[9px] px-1.5 py-0.5 rounded bg-[#4F6ECD]/25 text-[#7b9cd4]"
                    >● Jean</span
                  >
                  <span
                    class="inline-flex items-center gap-1 text-[9px] px-1.5 py-0.5 rounded bg-[#27AE60]/20 text-[#6fdd9f]"
                    >● Alice</span
                  >
                </div>
              </div>

              <!-- Stats card -->
              <div
                class="mock-card flex flex-col items-start"
                style="min-height: 120px"
              >
                <div class="h-2 w-12 rounded bg-white/8 mb-2"></div>
                <div class="text-3xl font-bold text-[#5ba3e8] mb-1">7</div>
                <div class="h-1.5 w-16 rounded bg-white/6 mb-auto"></div>
                <div class="flex gap-1 mt-4">
                  <span class="w-2 h-2 rounded-full bg-[#6fdd9f]"></span>
                  <span class="w-2 h-2 rounded-full bg-[#5ba3e8]"></span>
                  <span class="w-2 h-2 rounded-full bg-[#f0a23e]"></span>
                </div>
              </div>

              <!-- Task card -->
              <div class="mock-card" style="min-height: 80px">
                <div class="h-2 w-14 rounded bg-white/10 mb-2"></div>
                <div class="space-y-1.5">
                  <div class="flex items-center gap-1.5">
                    <span
                      class="w-2.5 h-2.5 rounded-sm bg-[#6fdd9f]/30 border border-[#6fdd9f]/40 shrink-0"
                    ></span>
                    <div class="h-1.5 w-full rounded bg-white/6"></div>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span
                      class="w-2.5 h-2.5 rounded-sm bg-white/5 border border-white/10 shrink-0"
                    ></span>
                    <div class="h-1.5 w-4/5 rounded bg-white/6"></div>
                  </div>
                </div>
              </div>

              <!-- Calendar card -->
              <div class="mock-card" style="min-height: 80px">
                <div class="h-2 w-16 rounded bg-white/10 mb-2"></div>
                <div class="grid grid-cols-4 gap-1">
                  <div class="h-3 rounded bg-[#408ed6]/30"></div>
                  <div class="h-3 rounded bg-white/5"></div>
                  <div class="h-3 rounded bg-[#9B59B6]/25"></div>
                  <div class="h-3 rounded bg-white/5"></div>
                  <div class="h-3 rounded bg-white/5"></div>
                  <div class="h-3 rounded bg-[#27AE60]/25"></div>
                  <div class="h-3 rounded bg-white/5"></div>
                  <div class="h-3 rounded bg-[#E67E22]/25"></div>
                </div>
              </div>

              <!-- Chat card -->
              <div class="mock-card" style="min-height: 80px">
                <div class="space-y-1.5">
                  <div class="flex items-start gap-1.5">
                    <div
                      class="w-3.5 h-3.5 rounded-full bg-[#4F6ECD] shrink-0"
                    ></div>
                    <div class="h-2 w-20 rounded-full bg-white/8"></div>
                  </div>
                  <div class="flex items-start gap-1.5 justify-end">
                    <div class="h-2 w-16 rounded-full bg-[#408ed6]/40"></div>
                    <div
                      class="w-3.5 h-3.5 rounded-full bg-[#27AE60] shrink-0"
                    ></div>
                  </div>
                  <div class="flex items-start gap-1.5">
                    <div
                      class="w-3.5 h-3.5 rounded-full bg-[#9B59B6] shrink-0"
                    ></div>
                    <div class="h-2 w-24 rounded-full bg-white/8"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero-section {
  background: #050a14;
}

.hero-grid {
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
  background-size: 64px 64px;
}

.hero-title {
  font-size: clamp(2.8rem, 6.5vw, 5rem);
  font-weight: 800;
  line-height: 1.06;
  letter-spacing: -0.04em;
  color: #c8daea;
  margin-bottom: 1.25rem;
}

.hero-gradient-text {
  background: linear-gradient(130deg, #5ba3e8 0%, #3d7bc4 40%, #6fdd9f 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.announcement-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.3125rem 0.875rem 0.3125rem 0.4375rem;
  background: rgba(10, 22, 40, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 9999px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s;
  margin-bottom: 2rem;
}
.announcement-badge:hover {
  border-color: rgba(64, 142, 214, 0.3);
  background: rgba(15, 31, 53, 0.9);
}

.badge-new-pill {
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  background: linear-gradient(
    135deg,
    rgba(64, 142, 214, 0.3),
    rgba(111, 221, 159, 0.2)
  );
  border: 1px solid rgba(91, 163, 232, 0.3);
  color: #7ec4f0;
  border-radius: 9999px;
  letter-spacing: 0.02em;
}

.hero-cta-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.625rem;
  background: #408ed6;
  color: white;
  font-size: 0.9375rem;
  font-weight: 600;
  border-radius: 0.625rem;
  border: none;
  cursor: pointer;
  transition:
    background 0.2s,
    box-shadow 0.2s;
  box-shadow: 0 0 0 0 rgba(64, 142, 214, 0);
}
.hero-cta-primary:hover {
  background: #5ba3e8;
  box-shadow: 0 0 24px rgba(64, 142, 214, 0.4);
}

.hero-cta-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #6b8099;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 0.625rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  cursor: pointer;
  transition:
    color 0.2s,
    border-color 0.2s;
}
.hero-cta-secondary:hover {
  color: #b0c9e0;
  border-color: rgba(91, 163, 232, 0.25);
}

.avatar-pill {
  width: 26px;
  height: 26px;
  border-radius: 9999px;
  border: 2px solid #050a14;
  font-size: 0.6rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Mockup */
.mockup-window {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.07);
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(255, 255, 255, 0.03),
    0 30px 80px -10px rgba(0, 0, 0, 0.7),
    0 0 60px rgba(64, 142, 214, 0.06);
  background: #080f1c;
}

.mockup-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5625rem 1rem;
  background: #060d18;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.mockup-url-bar {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  flex: 1;
  max-width: 260px;
  margin: 0 auto;
  padding: 0.25rem 0.625rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 5px;
}

.traffic-dot {
  width: 10px;
  height: 10px;
  border-radius: 9999px;
}

.mockup-app {
  display: flex;
  height: 400px;
}

.mock-sidebar {
  width: 148px;
  min-width: 148px;
  border-right: 1px solid rgba(255, 255, 255, 0.04);
  padding: 0.75rem 0.625rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: #060c18;
}

.mock-logo-row {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.5rem 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  margin-bottom: 0.375rem;
}

.mock-nav-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3125rem 0.5rem;
  border-radius: 0.3125rem;
}

.mock-nav-active {
  background: rgba(91, 163, 232, 0.08);
}

.mock-nav-icon {
  width: 14px;
  height: 14px;
  border-radius: 3px;
  shrink: 0;
}

.mock-main-area {
  flex: 1;
  padding: 1.125rem;
  overflow: hidden;
  background: #080f1c;
}

.mock-card {
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.055);
  border-radius: 8px;
  padding: 0.75rem;
}
</style>
