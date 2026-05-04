<script setup>
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['navigate']);

  const { polls, deletePoll } = usePollStore();

  async function delPoll(id) {
    console.log('delete Poll ID:', id);
    await deletePoll(id);
  }

  function editPoll(id) {
    emit('navigate', { view: 'edit', pollId: id });
  }

  function createPoll() {
    emit('navigate', { view: 'create' });
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
          <th class="border px-3 py-2">Brouillon</th>
          <th class="border px-3 py-2">Debut</th>
          <th class="border px-3 py-2">Fin</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id">
          <td class="border px-3 py-2">
              <button type="button" class="btn-edit" @click="editPoll(poll.id)">Modifier</button>
              <button type="button" class="btn-delete" @click="delPoll(poll.id)">Supp.</button>
          </td>
          <td class="border px-3 py-2">{{ poll.id }}</td>
          <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.question }}</td>
          <td class="border px-3 py-2">{{ poll.is_draft ? 'Oui' : 'Non' }}</td>
          <td class="border px-3 py-2">{{ poll.started_at || '-' }}</td>
          <td class="border px-3 py-2">{{ poll.ends_at || '-' }}</td>
        </tr>
      </tbody>
    </table>
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
</style>
