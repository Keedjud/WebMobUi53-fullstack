<script setup>
  import { onBeforeUnmount, onMounted, ref } from 'vue';
  import PollTable from './components/PollTable.vue';
  import PollCreateForm from './components/PollCreateForm.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    polls: { type: Array, default: () => [] },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
  });

  const { setPolls } = usePollStore();
  setPolls(props.polls);

  const currentView = ref('table');
  const editingPollId = ref(null);

  function normalizeView(value) {
    return ['table', 'create', 'edit'].includes(value) ? value : 'table';
  }

  function updateHash(view) {
    window.location.hash = `#${view}`;
  }

  function setCurrentViewFromHash() {
    const view = normalizeView(window.location.hash.replace(/^#/, ''));
    currentView.value = view;

    if (view === 'table' || view === 'create') {
      editingPollId.value = null;
    }

    if (window.location.hash !== `#${view}`) {
      updateHash(view);
    }
  }

  function navigateTo(view, pollId = null) {
    currentView.value = normalizeView(view);
    editingPollId.value = currentView.value === 'edit' ? pollId : null;
    updateHash(currentView.value);
  }

  function handleNavigate(event) {
    navigateTo(event?.view ?? 'table', event?.pollId ?? null);
  }

  function handlePollCreated() {
    navigateTo('table');
  }

  onMounted(() => {
    if (!window.location.hash) {
      updateHash('table');
    }

    setCurrentViewFromHash();
    window.addEventListener('hashchange', setCurrentViewFromHash);
  });

  onBeforeUnmount(() => {
    window.removeEventListener('hashchange', setCurrentViewFromHash);
  });
</script>

<template>
  <PollTable v-if="currentView === 'table'" @navigate="handleNavigate" />

  <PollCreateForm
    v-else-if="currentView === 'create'"
    @created="handlePollCreated"
    @cancel="navigateTo('table')"
  />

  <section
    v-else
    class="mt-6 bg-white dark:bg-slate-800 rounded-lg shadow-md p-6"
  >
    <h2 class="text-2xl font-bold dark:text-white mb-2">Edition du sondage</h2>
    <p class="dark:text-gray-300">
      Cette section sera implementee ensuite.
      <span v-if="editingPollId"> Sondage cible: #{{ editingPollId }}.</span>
    </p>
    <button
      type="button"
      class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
      @click="navigateTo('table')"
    >
      Retour au tableau
    </button>
  </section>
</template>
