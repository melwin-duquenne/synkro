<script setup lang="ts">
import { computed } from "vue";
import type { WorkloadData } from "@/composables/useWorkload";

const props = defineProps<{
  workload: WorkloadData | null;
  loading?: boolean;
  showDetails?: boolean;
}>();

const statusEmoji = computed(() => {
  if (!props.workload) return "📊";
  const emojis = {
    normal: "🟢",
    busy: "🟡",
    overloaded: "🔴",
  };
  return emojis[props.workload.status];
});

const statusColors = computed(() => {
  if (!props.workload)
    return {
      bg: "bg-white/5",
      text: "text-gray-400",
      border: "border-white/10",
      progress: "bg-gray-500",
      labelBg: "bg-gray-500/20",
    };

  const colors = {
    normal: {
      bg: "bg-emerald-500/10",
      text: "text-emerald-400",
      border: "border-emerald-500/30",
      progress: "bg-emerald-500",
      labelBg: "bg-emerald-500/20",
    },
    busy: {
      bg: "bg-amber-500/10",
      text: "text-amber-400",
      border: "border-amber-500/30",
      progress: "bg-amber-500",
      labelBg: "bg-amber-500/20",
    },
    overloaded: {
      bg: "bg-red-500/10",
      text: "text-red-400",
      border: "border-red-500/30",
      progress: "bg-red-500",
      labelBg: "bg-red-500/20",
    },
  };
  return colors[props.workload.status];
});

const progressWidth = computed(() => {
  if (!props.workload) return "0%";
  return `${Math.min(100, props.workload.percentage)}%`;
});
</script>

<template>
  <div v-if="loading" class="animate-pulse">
    <div class="h-20 bg-white/10 rounded-lg"></div>
  </div>

  <div
    v-else-if="workload"
    :class="[
      'rounded-xl border p-5 transition-all duration-300 hover:border-opacity-100',
      statusColors.bg,
      statusColors.border,
    ]"
  >
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-start gap-3 flex-1">
        <span class="text-3xl leading-none">{{ statusEmoji }}</span>
        <div class="flex-1">
          <h3
            :class="[
              'font-semibold text-sm uppercase tracking-wide',
              statusColors.text,
            ]"
          >
            {{ workload.statusLabel }}
          </h3>
          <p class="text-xs text-gray-500 mt-1">
            {{ workload.period === "day" ? "Aujourd'hui" : "Cette semaine" }}
          </p>
        </div>
      </div>
      <div class="text-right">
        <div :class="['text-2xl font-bold tabular-nums', statusColors.text]">
          {{ workload.percentage }}<span class="text-sm">%</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">
          {{ workload.totalHours }}h / {{ workload.standardHours }}h
        </p>
      </div>
    </div>

    <!-- Progress bar -->
    <div
      class="w-full bg-white/10 rounded-full h-2.5 mb-4 overflow-hidden border border-white/5"
    >
      <div
        :class="[
          'h-full rounded-full transition-all duration-500',
          statusColors.progress,
        ]"
        :style="{ width: progressWidth }"
      ></div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="bg-white/5 rounded-lg p-2.5 border border-white/5">
        <p class="text-xs text-gray-500 mb-1">Événements</p>
        <p class="text-lg font-semibold text-white">
          {{ workload.eventCount }}
        </p>
      </div>
      <div class="bg-white/5 rounded-lg p-2.5 border border-white/5">
        <p class="text-xs text-gray-500 mb-1">Réunions</p>
        <p class="text-lg font-semibold text-white">
          {{ workload.meetingCount }}
        </p>
      </div>
    </div>

    <!-- Warnings -->
    <div
      v-if="
        workload.modifiers.tooManyMeetings || workload.modifiers.shortBreaks
      "
      class="mt-3 pt-3 border-t border-white/10"
    >
      <p class="text-xs font-semibold text-amber-400 mb-2">⚠️ Alertes</p>
      <ul class="text-xs text-gray-400 space-y-1">
        <li v-if="workload.modifiers.tooManyMeetings">
          • Trop de réunions dans la journée
        </li>
        <li v-if="workload.modifiers.shortBreaks">
          • Pauses insuffisantes entre les événements
        </li>
      </ul>
    </div>

    <!-- Details -->
    <details
      v-if="showDetails && workload.events.length > 0"
      class="mt-3 pt-3 border-t border-white/10"
    >
      <summary
        class="text-xs font-semibold text-gray-400 cursor-pointer hover:text-gray-300 transition-colors"
      >
        📋 Détails des événements
      </summary>
      <ul class="mt-3 space-y-2">
        <li
          v-for="event in workload.events"
          :key="event.id"
          class="text-xs text-gray-400 flex justify-between items-center p-2 bg-white/5 rounded border border-white/5"
        >
          <span class="truncate">{{ event.title }}</span>
          <span class="text-gray-500 ml-2 shrink-0 font-medium"
            >{{ Math.round(event.duration / 60) }}h</span
          >
        </li>
      </ul>
    </details>
  </div>

  <div v-else class="text-gray-500 text-xs p-4 text-center">
    Aucune donnée disponible
  </div>
</template>
