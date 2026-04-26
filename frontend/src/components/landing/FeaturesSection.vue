<script setup lang="ts">
import { ref, onMounted } from 'vue'

const sectionRef = ref<HTMLElement | null>(null)
const visible = ref(false)

onMounted(() => {
  const observer = new IntersectionObserver(
    ([entry]) => { if (entry.isIntersecting) visible.value = true },
    { threshold: 0.05 }
  )
  if (sectionRef.value) observer.observe(sectionRef.value)
})
</script>

<template>
  <section id="features" ref="sectionRef" class="features-section py-28 lg:py-36">
    <div class="max-w-7xl mx-auto px-6">

      <!-- Section label + heading -->
      <div class="mb-16" :class="visible ? 'section-enter' : 'section-enter-init'">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#5ba3e8]/20 bg-[#5ba3e8]/5 text-[#5ba3e8] text-xs font-semibold uppercase tracking-widest mb-4">
          Fonctionnalités
        </div>
        <h2 class="text-4xl md:text-5xl font-bold text-[#c8daea] leading-tight tracking-tight mb-4 max-w-2xl" style="letter-spacing:-0.03em">
          Tout ce dont votre équipe a besoin, réuni.
        </h2>
        <p class="text-[#516070] text-lg max-w-xl">
          Sept modules intégrés, un seul espace de travail. Passez d'un outil à l'autre sans perdre le contexte.
        </p>
      </div>

      <!-- Bento grid -->
      <div class="bento-grid" :class="visible ? 'section-enter' : 'section-enter-init'">

        <!-- Large: Éditeur collaboratif -->
        <div class="bento-card bento-large bento-editor">
          <div class="bento-card-content">
            <div class="bento-tag bento-tag-blue">Temps réel</div>
            <h3 class="bento-title">Éditeur collaboratif</h3>
            <p class="bento-desc">Écrivez ensemble simultanément. Voyez les curseurs de vos collègues en direct et synchronisez chaque frappe instantanément.</p>
          </div>
          <!-- Editor mock -->
          <div class="editor-mock">
            <div class="editor-line">
              <span class="editor-line-num">1</span>
              <span class="editor-text"><span class="t-kw"># </span><span class="t-hl">Spécifications Projet Alpha</span></span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">2</span>
              <span class="editor-text"></span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">3</span>
              <span class="editor-text"><span class="t-kw">## </span>Contexte</span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">4</span>
              <span class="editor-text t-muted">Notre objectif est de livrer la v2...</span>
              <span class="cursor-indicator cursor-blue">Jean</span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">5</span>
              <span class="editor-text t-muted">avant la fin du trimestre avec...</span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">6</span>
              <span class="editor-text"></span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">7</span>
              <span class="editor-text"><span class="t-kw">## </span>Objectifs clés</span>
              <span class="cursor-indicator cursor-green">Alice</span>
            </div>
            <div class="editor-line">
              <span class="editor-line-num">8</span>
              <span class="editor-text t-muted">- Réduire le time-to-market de 40%</span>
            </div>
          </div>
        </div>

        <!-- Chat -->
        <div class="bento-card bento-chat">
          <div class="bento-tag bento-tag-purple">Instantané</div>
          <h3 class="bento-title">Chat d'équipe</h3>
          <p class="bento-desc">Messagerie en temps réel, fils de discussion, mentions et fichiers partagés.</p>
          <div class="chat-mock">
            <div class="chat-msg chat-msg-left">
              <div class="chat-avatar" style="background:#4F6ECD">J</div>
              <div class="chat-bubble chat-bubble-left">On se call à 14h ? 👋</div>
            </div>
            <div class="chat-msg chat-msg-right">
              <div class="chat-bubble chat-bubble-right">Oui, j'envoie le lien 🔗</div>
              <div class="chat-avatar" style="background:#27AE60">A</div>
            </div>
            <div class="chat-msg chat-msg-left">
              <div class="chat-avatar" style="background:#9B59B6">M</div>
              <div class="chat-bubble chat-bubble-left">Je serai là ✅</div>
            </div>
          </div>
        </div>

        <!-- Tâches -->
        <div class="bento-card bento-tasks">
          <div class="bento-tag bento-tag-green">Organisation</div>
          <h3 class="bento-title">Gestion des tâches</h3>
          <p class="bento-desc">Kanban, assignations, priorités et suivi de progression en un coup d'œil.</p>
          <div class="tasks-mock">
            <div class="task-item task-done">
              <span class="task-check done">✓</span>
              <span class="task-text task-text-done">Maquette validée</span>
            </div>
            <div class="task-item task-in-progress">
              <span class="task-check in-progress"></span>
              <span class="task-text">Intégration API</span>
              <span class="task-assignee" style="background:#4F6ECD">J</span>
            </div>
            <div class="task-item">
              <span class="task-check"></span>
              <span class="task-text">Tests unitaires</span>
              <span class="task-assignee" style="background:#27AE60">A</span>
            </div>
            <div class="task-item">
              <span class="task-check"></span>
              <span class="task-text">Documentation</span>
            </div>
          </div>
        </div>

        <!-- Stats band -->
        <div class="bento-card bento-stats">
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-number text-[#5ba3e8]">99.9%</div>
              <div class="stat-label">Disponibilité</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-number text-[#6fdd9f]">7</div>
              <div class="stat-label">Modules intégrés</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-number text-[#c084fc]">&lt;50ms</div>
              <div class="stat-label">Latence sync</div>
            </div>
          </div>
        </div>

        <!-- Calendrier -->
        <div class="bento-card bento-calendar">
          <div class="bento-tag bento-tag-orange">Planification</div>
          <h3 class="bento-title">Calendrier partagé</h3>
          <p class="bento-desc">Vue unifiée de tous les événements, réunions et deadlines de l'équipe.</p>
          <div class="calendar-mock">
            <div class="cal-header">
              <span class="cal-day">Lun</span><span class="cal-day">Mar</span><span class="cal-day">Mer</span><span class="cal-day">Jeu</span><span class="cal-day">Ven</span>
            </div>
            <div class="cal-events">
              <div class="cal-event cal-event-blue" style="grid-column:1/3">Stand-up</div>
              <div class="cal-event cal-event-purple" style="grid-column:3/5">Sprint Review</div>
              <div class="cal-event cal-event-green" style="grid-column:5/6">1:1</div>
              <div class="cal-event cal-event-orange" style="grid-column:2/4">Design Sync</div>
              <div class="cal-event cal-event-blue" style="grid-column:4/6">Demo Client</div>
            </div>
          </div>
        </div>

        <!-- Tableau blanc -->
        <div class="bento-card bento-whiteboard">
          <div class="bento-tag bento-tag-pink">Créativité</div>
          <h3 class="bento-title">Tableau blanc</h3>
          <p class="bento-desc">Canvas infini pour brainstormer, schématiser et visualiser vos idées ensemble.</p>
          <div class="wb-mock">
            <svg width="100%" height="80" viewBox="0 0 240 80" fill="none">
              <rect x="10" y="15" width="60" height="30" rx="6" fill="rgba(64,142,214,0.15)" stroke="rgba(91,163,232,0.4)" stroke-width="1"/>
              <text x="40" y="35" text-anchor="middle" fill="#5ba3e8" font-size="8">Idée A</text>
              <rect x="90" y="10" width="60" height="30" rx="6" fill="rgba(111,221,159,0.1)" stroke="rgba(111,221,159,0.35)" stroke-width="1"/>
              <text x="120" y="30" text-anchor="middle" fill="#6fdd9f" font-size="8">Idée B</text>
              <rect x="170" y="20" width="55" height="30" rx="6" fill="rgba(192,132,252,0.1)" stroke="rgba(192,132,252,0.35)" stroke-width="1"/>
              <text x="197" y="40" text-anchor="middle" fill="#c084fc" font-size="8">Idée C</text>
              <line x1="70" y1="30" x2="90" y2="25" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3,2"/>
              <line x1="150" y1="25" x2="170" y2="32" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3,2"/>
              <circle cx="40" cy="68" r="4" fill="rgba(64,142,214,0.5)"/>
              <circle cx="80" cy="65" r="4" fill="rgba(111,221,159,0.4)"/>
              <circle cx="180" cy="68" r="4" fill="rgba(192,132,252,0.4)"/>
              <path d="M44 68 Q60 55 76 65" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" fill="none"/>
            </svg>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
.features-section {
  background: #050a14;
}

.section-enter-init {
  opacity: 0;
  transform: translateY(24px);
}
.section-enter {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}

/* Bento grid */
.bento-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: auto;
  gap: 1rem;
}

@media (max-width: 1024px) {
  .bento-grid {
    grid-template-columns: 1fr 1fr;
  }
  .bento-large { grid-column: 1 / -1; }
  .bento-stats { grid-column: 1 / -1; }
}

@media (max-width: 640px) {
  .bento-grid {
    grid-template-columns: 1fr;
  }
  .bento-large { grid-column: 1; }
  .bento-stats { grid-column: 1; }
}

.bento-large {
  grid-column: 1 / 3;
  grid-row: 1;
}
.bento-chat {
  grid-column: 3;
  grid-row: 1;
}
.bento-tasks {
  grid-column: 1;
  grid-row: 2;
}
.bento-stats {
  grid-column: 2 / 4;
  grid-row: 2;
}
.bento-calendar {
  grid-column: 1 / 3;
  grid-row: 3;
}
.bento-whiteboard {
  grid-column: 3;
  grid-row: 3;
}

.bento-card {
  background: rgba(10, 18, 30, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  padding: 1.5rem;
  overflow: hidden;
  transition: border-color 0.25s, background 0.25s;
}
.bento-card:hover {
  border-color: rgba(91, 163, 232, 0.2);
  background: rgba(12, 22, 38, 0.95);
}

.bento-card-content {
  margin-bottom: 1.25rem;
}

.bento-tag {
  display: inline-block;
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 0.2rem 0.625rem;
  border-radius: 9999px;
  margin-bottom: 0.75rem;
}
.bento-tag-blue   { background: rgba(64,142,214,0.12); color: #5ba3e8; border: 1px solid rgba(91,163,232,0.2); }
.bento-tag-purple { background: rgba(192,132,252,0.1); color: #c084fc; border: 1px solid rgba(192,132,252,0.2); }
.bento-tag-green  { background: rgba(111,221,159,0.1); color: #6fdd9f; border: 1px solid rgba(111,221,159,0.2); }
.bento-tag-orange { background: rgba(240,162,62,0.1); color: #f0a23e; border: 1px solid rgba(240,162,62,0.2); }
.bento-tag-pink   { background: rgba(249,115,187,0.1); color: #f973bb; border: 1px solid rgba(249,115,187,0.2); }

.bento-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #c8daea;
  margin-bottom: 0.375rem;
  letter-spacing: -0.02em;
}

.bento-desc {
  font-size: 0.875rem;
  color: #3d5060;
  line-height: 1.55;
}

/* Editor mock */
.editor-mock {
  background: #060c18;
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 8px;
  padding: 0.875rem;
  font-family: 'SF Mono', 'Fira Code', monospace;
  font-size: 11px;
  line-height: 1.7;
}
.editor-line {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}
.editor-line-num {
  color: rgba(255,255,255,0.12);
  width: 16px;
  text-align: right;
  user-select: none;
}
.editor-text {
  color: #8ea4be;
  flex: 1;
}
.t-kw { color: #5ba3e8; }
.t-hl { color: #e7f0fa; font-weight: 600; }
.t-muted { color: #516070; }

.cursor-indicator {
  font-size: 9px;
  font-family: sans-serif;
  padding: 0.1rem 0.375rem;
  border-radius: 3px;
  font-weight: 600;
  white-space: nowrap;
}
.cursor-blue  { background: rgba(64,142,214,0.3); color: #5ba3e8; border: 1px solid rgba(91,163,232,0.4); }
.cursor-green { background: rgba(111,221,159,0.2); color: #6fdd9f; border: 1px solid rgba(111,221,159,0.3); }

/* Chat mock */
.chat-mock {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
  margin-top: 1rem;
}
.chat-msg {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
}
.chat-msg-right { flex-direction: row-reverse; }
.chat-avatar {
  width: 24px;
  height: 24px;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  shrink: 0;
}
.chat-bubble {
  font-size: 0.75rem;
  padding: 0.375rem 0.75rem;
  border-radius: 12px;
  max-width: 80%;
  line-height: 1.4;
}
.chat-bubble-left  { background: rgba(255,255,255,0.05); color: #8ea4be; border-radius: 4px 12px 12px 12px; }
.chat-bubble-right { background: rgba(64,142,214,0.2); color: #b0d4f0; border-radius: 12px 4px 12px 12px; }

/* Tasks mock */
.tasks-mock {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 1rem;
}
.task-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.task-check {
  width: 14px;
  height: 14px;
  border-radius: 3px;
  border: 1.5px solid rgba(255,255,255,0.12);
  shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
}
.task-check.done { background: rgba(111,221,159,0.2); border-color: rgba(111,221,159,0.5); color: #6fdd9f; }
.task-check.in-progress { border-color: rgba(64,142,214,0.6); background: rgba(64,142,214,0.1); }
.task-text { flex: 1; font-size: 0.8125rem; color: #6b8099; }
.task-text-done { text-decoration: line-through; color: #3d5060; }
.task-assignee {
  width: 18px;
  height: 18px;
  border-radius: 9999px;
  font-size: 0.5625rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Stats */
.bento-stats {
  padding: 1.75rem 2.5rem;
}
.stats-grid {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  height: 100%;
  min-height: 90px;
}
.stat-item {
  flex: 1;
  text-align: center;
}
.stat-number {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1;
  margin-bottom: 0.375rem;
}
.stat-label {
  font-size: 0.75rem;
  color: #3d5060;
  font-weight: 500;
}
.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(255,255,255,0.06);
}

/* Calendar mock */
.calendar-mock {
  margin-top: 1rem;
}
.cal-header {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
  margin-bottom: 6px;
}
.cal-day {
  text-align: center;
  font-size: 0.625rem;
  color: #3d5060;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.cal-events {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
  grid-auto-rows: 24px;
}
.cal-event {
  border-radius: 4px;
  font-size: 0.5625rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  padding: 0 6px;
  overflow: hidden;
  white-space: nowrap;
}
.cal-event-blue   { background: rgba(64,142,214,0.2); color: #5ba3e8; }
.cal-event-purple { background: rgba(192,132,252,0.15); color: #c084fc; }
.cal-event-green  { background: rgba(111,221,159,0.15); color: #6fdd9f; }
.cal-event-orange { background: rgba(240,162,62,0.15); color: #f0a23e; }

/* Whiteboard mock */
.wb-mock {
  margin-top: 1rem;
  background: rgba(6, 12, 24, 0.6);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 8px;
  overflow: hidden;
}
</style>
