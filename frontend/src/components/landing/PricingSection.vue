<script setup lang="ts">
import { ref } from "vue";

const annual = ref(false);

const plans = [
  {
    name: "Freemium",
    desc: "Pour découvrir Synkro",
    monthly: 0,
    annual: 0,
    cta: "Commencer gratuitement",
    ctaVariant: "outline",
    color: "default",
    features: [
      "Jusqu'à 3 membres",
      "3 espaces de travail",
      "5 Go de stockage",
      "Éditeur collaboratif",
      "Chat instantané",
    ],
    missing: ["Modules avancés", "Support prioritaire", "Intégrations"],
  },
  {
    name: "Premium",
    desc: "Pour les équipes en croissance",
    monthly: 12,
    annual: 9,
    cta: "Essai gratuit 14 jours",
    ctaVariant: "primary",
    color: "blue",
    popular: true,
    features: [
      "Membres illimités",
      "Espaces illimités",
      "100 Go de stockage",
      "Tous les modules",
      "Support prioritaire",
      "Intégrations avancées",
      "Analytics d'équipe",
    ],
    missing: [],
  },
  {
    name: "Enterprise",
    desc: "Pour les grandes organisations",
    monthly: null,
    annual: null,
    cta: "Nous contacter",
    ctaVariant: "outline",
    color: "default",
    features: [
      "Tout Premium inclus",
      "Stockage illimité",
      "SLA 99.9%",
      "Account Manager dédié",
      "SSO & SAML",
      "On-premise disponible",
      "Formation personnalisée",
    ],
    missing: [],
  },
];

const faqs = [
  {
    q: "Puis-je changer d'offre à tout moment ?",
    a: "Oui, vous pouvez upgrader ou downgrader à tout moment. Les changements prennent effet immédiatement et sont proratisés.",
  },
  {
    q: "Y a-t-il un engagement minimum ?",
    a: "Non. Toutes nos offres sont sans engagement. Annulez à tout moment.",
  },
  {
    q: "Quels moyens de paiement acceptez-vous ?",
    a: "Visa, Mastercard, American Express et virement bancaire pour Enterprise.",
  },
  {
    q: "Que se passe-t-il si je dépasse mes limites ?",
    a: "Vous recevrez une notification et pourrez upgrader ou acheter du stockage additionnel à 2€ / 10 Go / mois.",
  },
];

const openFaq = ref<number | null>(null);
</script>

<template>
  <section id="pricing" class="pricing-section py-28 lg:py-36">
    <div class="max-w-6xl mx-auto px-6">
      <!-- Header -->
      <div class="text-center mb-16">
        <div
          class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#6fdd9f]/20 bg-[#6fdd9f]/5 text-[#6fdd9f] text-xs font-semibold uppercase tracking-widest mb-4"
        >
          Tarifs
        </div>
        <h2
          class="text-4xl md:text-5xl font-bold text-[#c8daea] mb-4"
          style="letter-spacing: -0.03em"
        >
          Simple et transparent
        </h2>
        <p class="text-[#516070] text-lg max-w-lg mx-auto mb-8">
          Pas de frais cachés. Changez d'offre à tout moment.
        </p>

        <!-- Toggle -->
        <div
          class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-[#0a1220] border border-white/6"
        >
          <span
            class="text-sm font-medium"
            :class="!annual ? 'text-[#c8daea]' : 'text-[#3d5060]'"
            >Mensuel</span
          >
          <button
            @click="annual = !annual"
            class="relative w-10 h-5 rounded-full transition-colors duration-200"
            :class="annual ? 'bg-[#408ed6]' : 'bg-white/10'"
          >
            <span
              class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white transition-transform duration-200"
              :class="annual ? 'translate-x-5' : 'translate-x-0'"
            ></span>
          </button>
          <span
            class="text-sm font-medium"
            :class="annual ? 'text-[#c8daea]' : 'text-[#3d5060]'"
            >Annuel</span
          >
          <span
            class="text-xs px-2 py-0.5 rounded-full bg-[#6fdd9f]/15 text-[#6fdd9f] font-semibold"
            >−25%</span
          >
        </div>
      </div>

      <!-- Pricing cards -->
      <div class="pricing-grid mb-20">
        <div
          v-for="plan in plans"
          :key="plan.name"
          class="pricing-card"
          :class="plan.popular ? 'pricing-card-popular' : ''"
        >
          <!-- Popular badge -->
          <div v-if="plan.popular" class="popular-badge">✦ Populaire</div>

          <div class="pricing-card-header">
            <h3 class="plan-name">{{ plan.name }}</h3>
            <p class="plan-desc">{{ plan.desc }}</p>
          </div>

          <div class="plan-price">
            <template v-if="plan.monthly !== null">
              <span class="price-amount"
                >{{ annual ? plan.annual : plan.monthly }}€</span
              >
              <span class="price-period">/ utilisateur / mois</span>
            </template>
            <template v-else>
              <span class="price-custom">Sur mesure</span>
            </template>
          </div>

          <button
            class="plan-cta"
            :class="
              plan.ctaVariant === 'primary'
                ? 'plan-cta-primary'
                : 'plan-cta-outline'
            "
          >
            {{ plan.cta }}
          </button>

          <div class="plan-divider"></div>

          <ul class="plan-features">
            <li
              v-for="f in plan.features"
              :key="f"
              class="plan-feature plan-feature-included"
            >
              <svg
                class="feature-check"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              {{ f }}
            </li>
            <li
              v-for="f in plan.missing"
              :key="f"
              class="plan-feature plan-feature-missing"
            >
              <svg
                class="feature-cross"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              {{ f }}
            </li>
          </ul>
        </div>
      </div>

      <!-- FAQ -->
      <div class="max-w-2xl mx-auto">
        <h3
          class="text-2xl font-bold text-[#c8daea] text-center mb-8"
          style="letter-spacing: -0.02em"
        >
          Questions fréquentes
        </h3>
        <div class="space-y-2">
          <div
            v-for="(faq, i) in faqs"
            :key="i"
            class="faq-item"
            :class="openFaq === i ? 'faq-open' : ''"
          >
            <button
              class="faq-question"
              @click="openFaq = openFaq === i ? null : i"
            >
              <span>{{ faq.q }}</span>
              <svg
                class="faq-icon"
                :class="openFaq === i ? 'rotate-45' : ''"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                />
              </svg>
            </button>
            <div v-if="openFaq === i" class="faq-answer">
              {{ faq.a }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.pricing-section {
  background: #050a14;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  align-items: start;
}

@media (max-width: 900px) {
  .pricing-grid {
    grid-template-columns: 1fr;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
  }
}

.pricing-card {
  background: rgba(10, 18, 30, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 16px;
  padding: 2rem;
  position: relative;
  transition: border-color 0.25s;
}
.pricing-card:hover {
  border-color: rgba(91, 163, 232, 0.2);
}

.pricing-card-popular {
  background: rgba(12, 22, 40, 0.95);
  border-color: rgba(64, 142, 214, 0.4);
  box-shadow:
    0 0 0 1px rgba(64, 142, 214, 0.15),
    0 20px 60px rgba(0, 0, 0, 0.4),
    0 0 40px rgba(64, 142, 214, 0.08);
}
.pricing-card-popular:hover {
  border-color: rgba(91, 163, 232, 0.5);
}

.popular-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.2rem 0.875rem;
  background: #408ed6;
  color: white;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  border-radius: 9999px;
  white-space: nowrap;
}

.pricing-card-header {
  margin-bottom: 1.25rem;
}
.plan-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: #c8daea;
  margin-bottom: 0.25rem;
}
.plan-desc {
  font-size: 0.8125rem;
  color: #3d5060;
}

.plan-price {
  margin-bottom: 1.5rem;
}
.price-amount {
  font-size: 2.5rem;
  font-weight: 800;
  color: #e7f0fa;
  letter-spacing: -0.04em;
}
.price-period {
  font-size: 0.8125rem;
  color: #3d5060;
  margin-left: 0.25rem;
}
.price-custom {
  font-size: 1.75rem;
  font-weight: 700;
  color: #8ea4be;
  letter-spacing: -0.03em;
}

.plan-cta {
  width: 100%;
  padding: 0.6875rem 1rem;
  border-radius: 0.625rem;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 1.5rem;
}
.plan-cta-primary {
  background: #408ed6;
  color: white;
  border: none;
}
.plan-cta-primary:hover {
  background: #5ba3e8;
  box-shadow: 0 0 20px rgba(64, 142, 214, 0.35);
}
.plan-cta-outline {
  background: transparent;
  color: #6b8099;
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.plan-cta-outline:hover {
  border-color: rgba(91, 163, 232, 0.3);
  color: #b0c9e0;
}

.plan-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.05);
  margin-bottom: 1.25rem;
}

.plan-features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}
.plan-feature {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.8125rem;
}
.plan-feature-included {
  color: #8ea4be;
}
.plan-feature-missing {
  color: #2a3a48;
}

.feature-check {
  width: 14px;
  height: 14px;
  color: #6fdd9f;
  shrink: 0;
  margin-top: 1px;
}
.feature-cross {
  width: 14px;
  height: 14px;
  color: #2a3a48;
  shrink: 0;
  margin-top: 1px;
}

/* FAQ */
.faq-item {
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
  transition: border-color 0.2s;
}
.faq-open {
  border-color: rgba(91, 163, 232, 0.2);
}
.faq-question {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: rgba(10, 18, 30, 0.8);
  color: #8ea4be;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: color 0.2s;
}
.faq-question:hover {
  color: #c8daea;
}

.faq-icon {
  width: 16px;
  height: 16px;
  shrink: 0;
  color: #3d5060;
  transition: transform 0.2s;
}

.faq-answer {
  padding: 0.875rem 1.25rem 1rem;
  background: rgba(8, 15, 28, 0.6);
  color: #516070;
  font-size: 0.8125rem;
  line-height: 1.65;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}
</style>
