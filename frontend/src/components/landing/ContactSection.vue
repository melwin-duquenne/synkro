<script setup lang="ts">
import { ref } from "vue";

const form = ref({
  name: "",
  email: "",
  subject: "",
  message: "",
});

const isSubmitting = ref(false);
const submitStatus = ref<"idle" | "success" | "error">("idle");
const errorMessage = ref("");

const errors = ref({
  name: "",
  email: "",
  subject: "",
  message: "",
});

function validateEmail(email: string): boolean {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

function validateForm(): boolean {
  let isValid = true;
  errors.value = { name: "", email: "", subject: "", message: "" };

  if (!form.value.name.trim()) {
    errors.value.name = "Requis";
    isValid = false;
  }
  if (!form.value.email.trim()) {
    errors.value.email = "Requis";
    isValid = false;
  } else if (!validateEmail(form.value.email)) {
    errors.value.email = "Email invalide";
    isValid = false;
  }
  if (!form.value.subject.trim()) {
    errors.value.subject = "Requis";
    isValid = false;
  }
  if (!form.value.message.trim()) {
    errors.value.message = "Requis";
    isValid = false;
  } else if (form.value.message.trim().length < 10) {
    errors.value.message = "10 caractères minimum";
    isValid = false;
  }

  return isValid;
}

async function submitForm() {
  if (!validateForm()) return;

  isSubmitting.value = true;
  submitStatus.value = "idle";
  errorMessage.value = "";

  try {
    await new Promise((resolve) => setTimeout(resolve, 1500));
    submitStatus.value = "success";
    form.value = { name: "", email: "", subject: "", message: "" };
    setTimeout(() => {
      submitStatus.value = "idle";
    }, 5000);
  } catch (error: unknown) {
    submitStatus.value = "error";
    errorMessage.value =
      error instanceof Error ? error.message : "Une erreur est survenue.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <section id="contact" class="contact-section py-28 lg:py-36">
    <div class="max-w-6xl mx-auto px-6">
      <!-- Header -->
      <div class="mb-16">
        <div
          class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#c084fc]/20 bg-[#c084fc]/5 text-[#c084fc] text-xs font-semibold uppercase tracking-widest mb-4"
        >
          Contact
        </div>
        <h2
          class="text-4xl md:text-5xl font-bold text-[#c8daea] mb-4"
          style="letter-spacing: -0.03em"
        >
          Parlons de votre projet.
        </h2>
        <p class="text-[#516070] text-lg max-w-lg">
          Une question, une suggestion ou envie de collaborer ? On vous répond
          sous 24h.
        </p>
      </div>

      <!-- Layout: info left + form right -->
      <div class="contact-grid">
        <!-- Left: contact info -->
        <div class="contact-info">
          <!-- Info items -->
          <div class="info-item">
            <div
              class="info-icon-wrap"
              style="
                background: rgba(64, 142, 214, 0.1);
                border-color: rgba(91, 163, 232, 0.2);
              "
            >
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
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
            </div>
            <div>
              <p class="info-label">Email général</p>
              <a href="mailto:contact@synkro.io" class="info-value info-link"
                >contact@synkro.io</a
              >
            </div>
          </div>

          <div class="info-item">
            <div
              class="info-icon-wrap"
              style="
                background: rgba(111, 221, 159, 0.1);
                border-color: rgba(111, 221, 159, 0.2);
              "
            >
              <svg
                class="w-4 h-4 text-[#6fdd9f]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </div>
            <div>
              <p class="info-label">Support technique</p>
              <a
                href="mailto:support@synkro.io"
                class="info-value info-link"
                style="color: #6fdd9f"
                >support@synkro.io</a
              >
            </div>
          </div>

          <div class="info-item">
            <div
              class="info-icon-wrap"
              style="
                background: rgba(240, 162, 62, 0.1);
                border-color: rgba(240, 162, 62, 0.2);
              "
            >
              <svg
                class="w-4 h-4 text-[#f0a23e]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <div>
              <p class="info-label">Temps de réponse</p>
              <p class="info-value">Moins de 24h en semaine</p>
            </div>
          </div>

          <!-- Divider -->
          <div class="h-px bg-white/5 my-2"></div>

          <!-- Social links -->
          <p
            class="text-xs text-[#2a3a48] font-semibold uppercase tracking-wider mb-3"
          >
            Nous suivre
          </p>
          <div class="flex items-center gap-3">
            <a
              href="https://github.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-pill"
              aria-label="GitHub"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                />
              </svg>
              GitHub
            </a>
            <a
              href="https://linkedin.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-pill"
              aria-label="LinkedIn"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                />
              </svg>
              LinkedIn
            </a>
          </div>
        </div>

        <!-- Right: form -->
        <div class="contact-form-wrap">
          <!-- Success state -->
          <div v-if="submitStatus === 'success'" class="success-state">
            <div class="success-icon">
              <svg
                class="w-6 h-6 text-[#6fdd9f]"
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
            </div>
            <h3 class="text-lg font-bold text-[#c8daea] mb-1">
              Message envoyé !
            </h3>
            <p class="text-[#3d5060] text-sm">
              Nous vous répondrons dans les plus brefs délais.
            </p>
          </div>

          <form
            v-else
            @submit.prevent="submitForm"
            class="contact-form"
            novalidate
          >
            <!-- Row: name + email -->
            <div class="form-row">
              <div class="field-group">
                <label class="field-label">
                  Nom
                  <span class="field-required">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Jean Dupont"
                  class="field-input"
                  :class="{ 'field-input-error': errors.name }"
                  :disabled="isSubmitting"
                  autocomplete="name"
                />
                <span v-if="errors.name" class="field-error">{{
                  errors.name
                }}</span>
              </div>

              <div class="field-group">
                <label class="field-label">
                  Email
                  <span class="field-required">*</span>
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="jean@exemple.com"
                  class="field-input"
                  :class="{ 'field-input-error': errors.email }"
                  :disabled="isSubmitting"
                  autocomplete="email"
                />
                <span v-if="errors.email" class="field-error">{{
                  errors.email
                }}</span>
              </div>
            </div>

            <!-- Subject -->
            <div class="field-group">
              <label class="field-label">
                Sujet
                <span class="field-required">*</span>
              </label>
              <input
                v-model="form.subject"
                type="text"
                placeholder="Comment pouvons-nous vous aider ?"
                class="field-input"
                :class="{ 'field-input-error': errors.subject }"
                :disabled="isSubmitting"
              />
              <span v-if="errors.subject" class="field-error">{{
                errors.subject
              }}</span>
            </div>

            <!-- Message -->
            <div class="field-group">
              <label class="field-label">
                Message
                <span class="field-required">*</span>
              </label>
              <textarea
                v-model="form.message"
                placeholder="Décrivez votre demande en détail..."
                class="field-textarea"
                :class="{ 'field-input-error': errors.message }"
                :disabled="isSubmitting"
                rows="5"
              ></textarea>
              <div class="flex items-center justify-between">
                <span v-if="errors.message" class="field-error">{{
                  errors.message
                }}</span>
                <span v-else class="field-hint">Min. 10 caractères</span>
                <span
                  class="field-counter"
                  :class="
                    form.message.length >= 10
                      ? 'text-[#3d8c6a]'
                      : 'text-[#2a3a48]'
                  "
                >
                  {{ form.message.length }}
                </span>
              </div>
            </div>

            <!-- Error alert -->
            <div v-if="submitStatus === 'error'" class="error-alert">
              <svg
                class="w-4 h-4 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{ errorMessage }}
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="submit-spinner"></span>
              <span v-if="!isSubmitting">
                Envoyer le message
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                  />
                </svg>
              </span>
              <span v-else class="text-sm">Envoi en cours...</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.contact-section {
  background: #050a14;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.contact-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 3rem;
  align-items: start;
}

@media (max-width: 768px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

/* Info panel */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
}

.info-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid;
  display: flex;
  align-items: center;
  justify-content: center;
  shrink: 0;
}

.info-label {
  font-size: 0.6875rem;
  font-weight: 600;
  color: #2a3a48;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.125rem;
}

.info-value {
  font-size: 0.8125rem;
  color: #516070;
}

.info-link {
  color: #5ba3e8;
  text-decoration: none;
  transition: color 0.15s;
}
.info-link:hover {
  color: #9ec6ec;
  text-decoration: underline;
}

.social-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(10, 18, 30, 0.8);
  font-size: 0.75rem;
  color: #3d5060;
  text-decoration: none;
  transition:
    color 0.15s,
    border-color 0.15s;
}
.social-pill:hover {
  color: #8ea4be;
  border-color: rgba(91, 163, 232, 0.25);
}

/* Form wrapper */
.contact-form-wrap {
  background: rgba(10, 18, 30, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  padding: 2rem;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

@media (max-width: 540px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.field-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #6b8099;
}

.field-required {
  color: #5ba3e8;
  margin-left: 2px;
}

.field-input,
.field-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  background: rgba(5, 10, 20, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 8px;
  color: #c8daea;
  font-size: 0.875rem;
  outline: none;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  font-family: inherit;
}

.field-textarea {
  resize: vertical;
  min-height: 120px;
  line-height: 1.6;
}

.field-input::placeholder,
.field-textarea::placeholder {
  color: #2a3a48;
}

.field-input:focus,
.field-textarea:focus {
  border-color: rgba(64, 142, 214, 0.5);
  box-shadow: 0 0 0 3px rgba(64, 142, 214, 0.08);
}

.field-input:disabled,
.field-textarea:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.field-input-error {
  border-color: rgba(248, 113, 113, 0.4) !important;
}
.field-input-error:focus {
  box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.08) !important;
}

.field-error {
  font-size: 0.75rem;
  color: #f87171;
}

.field-hint {
  font-size: 0.6875rem;
  color: #2a3a48;
}

.field-counter {
  font-size: 0.6875rem;
  font-weight: 500;
  transition: color 0.2s;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: rgba(248, 113, 113, 0.08);
  border: 1px solid rgba(248, 113, 113, 0.2);
  color: #f87171;
  font-size: 0.8125rem;
}

.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1.5rem;
  background: #408ed6;
  color: white;
  font-size: 0.9375rem;
  font-weight: 600;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition:
    background 0.2s,
    box-shadow 0.2s;
}
.submit-btn:hover:not(:disabled) {
  background: #5ba3e8;
  box-shadow: 0 0 24px rgba(64, 142, 214, 0.35);
}
.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 9999px;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Success state */
.success-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.success-icon {
  width: 56px;
  height: 56px;
  border-radius: 9999px;
  background: rgba(111, 221, 159, 0.1);
  border: 1px solid rgba(111, 221, 159, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
}
</style>
