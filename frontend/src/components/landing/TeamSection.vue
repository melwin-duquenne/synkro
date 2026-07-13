<script setup lang="ts">
import { ref } from "vue";

interface TeamMember {
  name: string;
  role: string;
  avatar: string;
  linkedin?: string;
  github?: string;
  twitter?: string;
}

const team = ref<TeamMember[]>([
  {
    name: "Kyllian Diochon",
    role: "Chef de projet",
    avatar:
      "M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
    linkedin: "https://linkedin.com",
    github: "https://github.com",
    twitter: "https://twitter.com",
  },
  {
    name: "Calista Martinez",
    role: "Ingénieure DevOps",
    avatar: "M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4",
    linkedin: "https://linkedin.com",
    github: "https://github.com",
  },
  {
    name: "Melwin Dusquenne",
    role: "Lead Developer",
    avatar:
      "M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01",
    linkedin: "https://linkedin.com",
    twitter: "https://twitter.com",
  },
  {
    name: "Jérémy Gross",
    role: "UI/UX Designer",
    avatar:
      "M12 3a9 9 0 100 18h1a3 3 0 000-6h-1a1 1 0 110-2h1a3 3 0 003-3 1 1 0 011-1h1a3 3 0 000-6h-5zM7.5 10.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3z",
    linkedin: "https://linkedin.com",
  },
]);
</script>

<template>
  <section
    id="team"
    class="py-20 lg:py-32 bg-transparent border-t border-white/6"
  >
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-bold mb-4 text-[#e7f0fa]">
          L'équipe derrière Synkro
        </h2>
        <p class="text-xl text-[#8ea4be] max-w-2xl mx-auto">
          Une équipe passionnée dédiée à révolutionner la collaboration
        </p>
      </div>

      <!-- Team Grid -->
      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto"
      >
        <div
          v-for="(member, index) in team"
          :key="index"
          class="card team-card shadow-lg hover:shadow-2xl transition-all duration-300 group"
        >
          <div class="card-body items-center text-center">
            <!-- Avatar -->
            <div class="avatar placeholder mb-4">
              <div
                class="w-24 h-24 rounded-full bg-[#408ed6]/20 flex items-center justify-center group-hover:scale-110 transition-transform"
              >
                <svg
                  class="w-12 h-12 text-[#5ba3e8]"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    :d="member.avatar"
                  />
                </svg>
              </div>
            </div>

            <!-- Info -->
            <h3 class="card-title text-lg text-[#e7f0fa]">{{ member.name }}</h3>
            <p class="text-[#5ba3e8] font-medium mb-4">{{ member.role }}</p>

            <!-- Social Links -->
            <div class="flex gap-2">
              <a
                v-if="member.linkedin"
                :href="member.linkedin"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-circle btn-ghost btn-sm hover:bg-[#408ed6] hover:text-white"
                title="LinkedIn"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"
                  />
                </svg>
              </a>
              <a
                v-if="member.github"
                :href="member.github"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-circle btn-ghost btn-sm hover:bg-[#408ed6] hover:text-white"
                title="GitHub"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                  />
                </svg>
              </a>
              <a
                v-if="member.twitter"
                :href="member.twitter"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-circle btn-ghost btn-sm hover:bg-[#408ed6] hover:text-white"
                title="Twitter"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"
                  />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Join Team CTA -->
      <div class="text-center mt-16">
        <div class="card cta-card max-w-2xl mx-auto">
          <div class="card-body items-center text-center">
            <h3 class="card-title text-2xl mb-2 text-[#e7f0fa]">
              Rejoignez l'aventure !
            </h3>
            <p class="text-[#8ea4be] mb-4">
              Nous sommes toujours à la recherche de talents passionnés pour
              agrandir notre équipe.
            </p>
            <button
              class="btn gap-2 bg-[#408ed6] border-[#408ed6] text-white hover:bg-[#5ba3e8] hover:border-[#5ba3e8]"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              Voir les offres d'emploi
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.team-card {
  background: rgba(15, 31, 53, 0.76);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 14px;
}

.team-card:hover {
  border-color: rgba(64, 142, 214, 0.35);
}

.team-card :deep(.btn-ghost) {
  background: rgba(17, 35, 56, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #dbe6f2;
}

.cta-card {
  background: linear-gradient(
    135deg,
    rgba(26, 58, 82, 0.55),
    rgba(15, 31, 53, 0.7)
  );
  border: 1px solid rgba(64, 142, 214, 0.28);
}
</style>
