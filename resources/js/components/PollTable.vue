<script setup>
  import { ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['navigate']);

  const { polls, deletePoll, publishPoll } = usePollStore();
  const toastMessage = ref('');
  const toastType = ref('success');
  let toastTimer = null;

  async function delPoll(poll) {
    const label = poll?.title || poll?.question || `#${poll?.id}`;
    if (!confirm(`Supprimer le sondage "${label}" ?`)) {
      return;
    }

    await deletePoll(poll.id);
  }

  function editPoll(id) {
    emit('navigate', { view: 'edit', pollId: id });
  }

  function createPoll() {
    emit('navigate', { view: 'create' });
  }

  function showToast(message, type = 'success') {
    toastMessage.value = message;
    toastType.value = type;

    if (toastTimer) {
      clearTimeout(toastTimer);
    }

    toastTimer = setTimeout(() => {
      toastMessage.value = '';
      toastTimer = null;
    }, 2000);
  }

  async function copyShareLink(poll) {
    if (!poll?.share_url) {
      showToast('Lien indisponible.', 'error');
      return;
    }

    try {
      await navigator.clipboard.writeText(poll.share_url);
      showToast(`Lien copie pour le sondage #${poll.id}.`, 'success');
    } catch (error) {
      showToast('Impossible de copier le lien.', 'error');
    }
  }

  async function publishDraft(poll) {
    if (!poll?.id) return;
    try {
      await publishPoll(poll.id);
      showToast('Sondage publie.', 'success');
    } catch (error) {
      showToast('Impossible de publier le sondage.', 'error');
    }
  }

  function getStatusLabel(poll) {
    if (poll?.is_draft) return 'Brouillon';
    const now = new Date();
    const startsAt = parseDateTime(poll?.started_at);
    const endsAt = parseDateTime(poll?.ends_at);

    if (endsAt && endsAt < now) return 'Termine';
    if (startsAt && startsAt > now) return 'A venir';
    return 'En cours';
  }

  function parseDateTime(value) {
    if (!value) return null;
    const match = String(value).match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!match) return null;
    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4]), Number(match[5]));
  }

  function formatDateTime(value) {
    if (!value) return '-';
    const match = String(value).match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!match) return '-';
    return `${match[3]}.${match[2]}.${match[1]} ${match[4]}:${match[5]}`;
  }
</script>

<template>
  <div class="poll-table-wrapper">
    <button
      type="button"
      class="mt-6 block w-full px-4 py-2 bg-teal-600 dark:bg-purple-900 text-white rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 text-center"
      @click="createPoll"
    >
      Nouveau sondage
    </button>

    <p v-if="polls.length === 0">Aucun sondage.</p>

    <table v-else class="w-full border-collapse text-left">
      <thead>
        <tr>
          <th class="border px-3 py-2">Actions</th>
          <th class="border px-3 py-2">ID</th>
          <th class="border px-3 py-2">Titre</th>
          <th class="border px-3 py-2">Question</th>
          <th class="border px-3 py-2">Etat</th>
          <th class="border px-3 py-2">Debut</th>
          <th class="border px-3 py-2">Fin</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id">
          <td class="border px-3 py-2">
              <button
                v-if="poll.is_draft"
                type="button"
                class="btn-edit"
                @click="editPoll(poll.id)"
              >
                Modifier
              </button>
              <button
                v-if="poll.is_draft"
                type="button"
                class="btn-publish"
                @click="publishDraft(poll)"
              >
                Publier
              </button>
              <a
                v-if="poll.share_url"
                :href="poll.share_url"
                class="btn-results"
                target="_blank"
                rel="noopener"
              >
                Resultats
              </a>
              <button type="button" class="btn-delete" @click="delPoll(poll)">Supp.</button>
              <button type="button" class="btn-copy" @click="copyShareLink(poll)">Copier lien</button>
          </td>
          <td class="border px-3 py-2">{{ poll.id }}</td>
          <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.question }}</td>
          <td class="border px-3 py-2">{{ getStatusLabel(poll) }}</td>
          <td class="border px-3 py-2">{{ formatDateTime(poll.started_at) }}</td>
          <td class="border px-3 py-2">{{ formatDateTime(poll.ends_at) }}</td>
        </tr>
      </tbody>
    </table>

    <div v-if="toastMessage" class="toast" :class="toastType">
      {{ toastMessage }}
    </div>
  </div>
</template>

<style scoped>
  .poll-table-wrapper {
    width: 100%;
  }

  button {
    color: white;
    padding: 0.25rem 0.5rem;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
  }

  .btn-edit {
    background-color: #2563eb;
  }

  .btn-delete {
    background-color: #e3342f;
  }

  .btn-publish {
    background-color: #0f766e;
    margin-left: 0.25rem;
  }

  .btn-results {
    background-color: #475569;
    margin-left: 0.25rem;
    text-decoration: none;
    display: inline-block;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
  }

  .btn-copy {
    background-color: #16a34a;
    margin-left: 0.25rem;
  }

  .toast {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    padding: 0.4rem 0.6rem;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    color: #f8fafc;
    background: rgba(15, 23, 42, 0.75);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.2);
    opacity: 0.9;
    z-index: 50;
    pointer-events: none;
  }

  .toast.success {
    background: rgba(22, 163, 74, 0.75);
  }

  .toast.error {
    background: rgba(239, 68, 68, 0.75);
  }
</style>
