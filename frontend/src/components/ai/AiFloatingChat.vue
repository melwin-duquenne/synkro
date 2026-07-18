<script setup lang="ts">
import { ref, nextTick } from "vue";
import { useAi } from "@/composables/useAi";

const { chat, loading, isAvailable } = useAi();

const open = ref(false);
const prompt = ref("");
const messagesEndRef = ref<HTMLDivElement | null>(null);

interface Message {
  role: "user" | "assistant";
  content: string;
}

const messages = ref<Message[]>([]);

const suggestedPrompts = [
  "Resumer ma journee",
  "Que dois-je prioriser ?",
  "Donne-moi un point rapide",
];

async function handleSend() {
  const text = prompt.value.trim();
  if (!text || loading.value) return;

  messages.value.push({ role: "user", content: text });
  prompt.value = "";
  await scrollToBottom();

  const response = await chat(text, "assistant");
  if (response) {
    messages.value.push({ role: "assistant", content: response });
    await scrollToBottom();
  }
}

async function scrollToBottom() {
  await nextTick();
  messagesEndRef.value?.scrollIntoView({ behavior: "smooth" });
}

function handleKeydown(e: KeyboardEvent) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    handleSend();
  }
}

function applySuggestion(text: string) {
  prompt.value = text;
}
</script>

<template>
  <div v-if="isAvailable()" class="chat-floating-root">
    <Transition name="chat-panel">
      <section v-if="open" class="chat-shell">
        <div class="chat-shell__glow"></div>

        <header class="chat-header">
          <div class="chat-header__meta">
            <span class="chat-header__badge">
              <span class="chat-header__badge-dot"></span>
              IA en ligne
            </span>
            <div>
              <p class="chat-header__title">Assistant Synkro</p>
              <p class="chat-header__subtitle">
                Reponses rapides, contexte equipe, aide operationnelle
              </p>
            </div>
          </div>
          <button
            class="chat-close"
            type="button"
            aria-label="Fermer le chat"
            @click="open = false"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18 18 6M6 6l12 12"
              />
            </svg>
          </button>
        </header>

        <div class="chat-body">
          <div v-if="messages.length === 0" class="chat-empty-state">
            <div class="chat-empty-state__icon">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M9.75 17 8 21l-1.75-4H3l3.25-2.5L5 10l4 1.75L12 8l3 3.75L19 10l-1.25 4.5L21 17h-3.25L16 21l-1.75-4h-4.5Z"
                />
              </svg>
            </div>
            <h3>Bonjour, je suis pret.</h3>
            <p>
              Pose une question, demande un resume ou lance une action rapide.
            </p>
            <div class="chat-suggestions">
              <button
                v-for="suggestion in suggestedPrompts"
                :key="suggestion"
                class="chat-suggestion"
                type="button"
                @click="applySuggestion(suggestion)"
              >
                {{ suggestion }}
              </button>
            </div>
          </div>

          <div
            v-for="(msg, i) in messages"
            :key="i"
            class="chat-message"
            :class="
              msg.role === 'user'
                ? 'chat-message--user'
                : 'chat-message--assistant'
            "
          >
            <div
              v-if="msg.role === 'assistant'"
              class="chat-avatar chat-avatar--assistant"
            >
              AI
            </div>
            <div class="chat-bubble-wrap">
              <p class="chat-message__label">
                {{ msg.role === "user" ? "Vous" : "Assistant IA" }}
              </p>
              <div
                class="chat-bubble"
                :class="
                  msg.role === 'user'
                    ? 'chat-bubble--user'
                    : 'chat-bubble--assistant'
                "
              >
                {{ msg.content }}
              </div>
            </div>
            <div
              v-if="msg.role === 'user'"
              class="chat-avatar chat-avatar--user"
            >
              VOUS
            </div>
          </div>

          <div v-if="loading" class="chat-message chat-message--assistant">
            <div class="chat-avatar chat-avatar--assistant">AI</div>
            <div class="chat-bubble-wrap">
              <p class="chat-message__label">Assistant IA</p>
              <div
                class="chat-bubble chat-bubble--assistant chat-bubble--typing"
              >
                <span class="chat-typing-dot"></span>
                <span class="chat-typing-dot"></span>
                <span class="chat-typing-dot"></span>
              </div>
            </div>
          </div>

          <div ref="messagesEndRef" />
        </div>

        <footer class="chat-composer">
          <div class="chat-composer__box">
            <textarea
              v-model="prompt"
              class="chat-input"
              placeholder="Posez votre question..."
              rows="2"
              @keydown="handleKeydown"
            />
            <button
              class="chat-send"
              type="button"
              :disabled="loading || !prompt.trim()"
              @click="handleSend"
            >
              <span>Envoyer</span>
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m5 12 14-7-4 14-3-5-7-2Z"
                />
              </svg>
            </button>
          </div>
        </footer>
      </section>
    </Transition>

    <button class="chat-trigger" type="button" @click="open = !open">
      <span class="chat-trigger__ring"></span>
      <span class="chat-trigger__label">IA</span>
      <svg
        v-if="!open"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-5 5v-5Z"
        />
      </svg>
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M6 18 18 6M6 6l12 12"
        />
      </svg>
    </button>
  </div>
</template>

<style scoped>
.chat-floating-root {
  position: fixed;
  right: 1.5rem;
  bottom: 1.5rem;
  z-index: 50;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.9rem;
}

.chat-shell {
  position: relative;
  width: min(24rem, calc(100vw - 2rem));
  height: 34rem;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 24px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.24),
      transparent 30%
    ),
    linear-gradient(180deg, rgba(10, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 24px 60px rgba(0, 0, 0, 0.45),
    0 0 0 1px rgba(255, 255, 255, 0.03) inset;
  backdrop-filter: blur(18px);
}

.chat-shell__glow {
  position: absolute;
  inset: -30% auto auto 55%;
  width: 15rem;
  height: 15rem;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(91, 163, 232, 0.28),
    transparent 68%
  );
  pointer-events: none;
}

.chat-header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1rem 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.chat-header__meta {
  min-width: 0;
}

.chat-header__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.42rem;
  margin-bottom: 0.55rem;
  padding: 0.28rem 0.6rem;
  border: 1px solid rgba(148, 212, 255, 0.18);
  border-radius: 999px;
  background: rgba(91, 163, 232, 0.12);
  color: #d8ecff;
  font-size: 0.68rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.chat-header__badge-dot {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 999px;
  background: #77f3b0;
  box-shadow: 0 0 10px rgba(119, 243, 176, 0.9);
}

.chat-header__title {
  margin: 0;
  color: #f3f8ff;
  font-size: 1rem;
  font-weight: 700;
}

.chat-header__subtitle {
  margin: 0.28rem 0 0;
  color: #91a6bf;
  font-size: 0.78rem;
  line-height: 1.45;
}

.chat-close {
  width: 2rem;
  height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.04);
  color: #d8e8f7;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.chat-close:hover {
  border-color: rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-1px);
}

.chat-close svg {
  width: 1rem;
  height: 1rem;
}

.chat-body {
  position: relative;
  z-index: 1;
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.chat-body::-webkit-scrollbar {
  width: 0.4rem;
}

.chat-body::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
}

.chat-empty-state {
  margin: auto 0;
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  text-align: center;
}

.chat-empty-state__icon {
  width: 3.25rem;
  height: 3.25rem;
  margin: 0 auto 0.9rem;
  display: grid;
  place-items: center;
  border-radius: 18px;
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.22),
    rgba(68, 205, 255, 0.12)
  );
  color: #d8eeff;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.chat-empty-state__icon svg {
  width: 1.35rem;
  height: 1.35rem;
}

.chat-empty-state h3 {
  margin: 0;
  color: #f4f8ff;
  font-size: 1rem;
  font-weight: 700;
}

.chat-empty-state p {
  margin: 0.5rem auto 0;
  max-width: 18rem;
  color: #8ea5be;
  font-size: 0.84rem;
  line-height: 1.5;
}

.chat-suggestions {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.55rem;
}

.chat-suggestion {
  padding: 0.5rem 0.72rem;
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.04);
  color: #cfdef0;
  font-size: 0.76rem;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    transform 0.2s ease;
}

.chat-suggestion:hover {
  border-color: rgba(91, 163, 232, 0.4);
  background: rgba(91, 163, 232, 0.12);
  transform: translateY(-1px);
}

.chat-message {
  display: flex;
  align-items: flex-end;
  gap: 0.65rem;
}

.chat-message--user {
  justify-content: flex-end;
}

.chat-bubble-wrap {
  max-width: min(82%, 18.5rem);
}

.chat-message__label {
  margin: 0 0 0.35rem;
  color: #70859d;
  font-size: 0.66rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.chat-message--user .chat-message__label {
  text-align: right;
}

.chat-bubble {
  padding: 0.78rem 0.9rem;
  border-radius: 18px;
  font-size: 0.88rem;
  line-height: 1.5;
  white-space: pre-wrap;
}

.chat-bubble--assistant {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.045);
  color: #edf5ff;
  border-bottom-left-radius: 6px;
}

.chat-bubble--user {
  background: linear-gradient(135deg, #5ba3e8, #3386d6);
  color: #f8fbff;
  box-shadow: 0 10px 24px rgba(51, 134, 214, 0.28);
  border-bottom-right-radius: 6px;
}

.chat-bubble--typing {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.chat-typing-dot {
  width: 0.42rem;
  height: 0.42rem;
  border-radius: 999px;
  background: #a7bfd8;
  animation: chatTyping 0.9s infinite ease-in-out;
}

.chat-typing-dot:nth-child(2) {
  animation-delay: 0.15s;
}

.chat-typing-dot:nth-child(3) {
  animation-delay: 0.3s;
}

.chat-avatar {
  width: 2rem;
  height: 2rem;
  flex: 0 0 2rem;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 0.56rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.chat-avatar--assistant {
  border: 1px solid rgba(91, 163, 232, 0.25);
  background: rgba(91, 163, 232, 0.12);
  color: #d9eeff;
}

.chat-avatar--user {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.06);
  color: #d8e8f8;
}

.chat-composer {
  position: relative;
  z-index: 1;
  padding: 0.9rem 1rem 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.chat-composer__box {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.035);
}

.chat-input {
  flex: 1;
  min-height: 4rem;
  resize: none;
  border: none;
  outline: none;
  background: transparent;
  color: #edf4ff;
  font-size: 0.88rem;
  line-height: 1.45;
}

.chat-input::placeholder {
  color: #70859d;
}

.chat-send {
  height: 2.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0 0.95rem;
  border: none;
  border-radius: 14px;
  background: linear-gradient(135deg, #5ba3e8, #3386d6);
  color: #f7fbff;
  font-size: 0.82rem;
  font-weight: 600;
  box-shadow: 0 10px 24px rgba(51, 134, 214, 0.28);
  transition:
    transform 0.2s ease,
    filter 0.2s ease,
    opacity 0.2s ease;
}

.chat-send:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.05);
}

.chat-send:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  box-shadow: none;
}

.chat-send svg {
  width: 1rem;
  height: 1rem;
}

.chat-trigger {
  position: relative;
  width: 4rem;
  height: 4rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 1.35rem;
  background: linear-gradient(
    135deg,
    rgba(12, 22, 38, 0.95),
    rgba(27, 69, 120, 0.95)
  );
  color: #eef7ff;
  box-shadow: 0 18px 34px rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(14px);
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease;
}

.chat-trigger:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 40px rgba(0, 0, 0, 0.42);
}

.chat-trigger svg {
  width: 1.4rem;
  height: 1.4rem;
  position: relative;
  z-index: 1;
}

.chat-trigger__ring {
  position: absolute;
  inset: -0.3rem;
  border-radius: 1.65rem;
  border: 1px solid rgba(91, 163, 232, 0.18);
}

.chat-trigger__label {
  position: absolute;
  top: -0.45rem;
  right: -0.35rem;
  min-width: 1.7rem;
  height: 1.7rem;
  padding: 0 0.38rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: linear-gradient(135deg, #9cd8ff, #5ba3e8);
  color: #06213b;
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  box-shadow: 0 10px 20px rgba(91, 163, 232, 0.4);
}

.chat-panel-enter-active,
.chat-panel-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.chat-panel-enter-from,
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.975);
}

@keyframes chatTyping {
  0%,
  80%,
  100% {
    opacity: 0.35;
    transform: translateY(0);
  }

  40% {
    opacity: 1;
    transform: translateY(-2px);
  }
}

@media (max-width: 640px) {
  .chat-floating-root {
    right: 1rem;
    left: 1rem;
    bottom: 1rem;
    align-items: stretch;
  }

  .chat-shell {
    width: 100%;
    height: min(34rem, calc(100vh - 6.5rem));
  }

  .chat-trigger {
    margin-left: auto;
  }
}
</style>
