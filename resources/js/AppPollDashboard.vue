<script setup>
  import { onBeforeUnmount, onMounted, ref } from 'vue';
  import PollTable from './components/PollTable.vue';
  import PollCreateForm from './components/PollCreateForm.vue';
  import PollEditForm from './components/PollEditForm.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    polls: { type: Array, default: () => [] },
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

  <PollEditForm
    v-else
    :poll-id="editingPollId"
    @updated="navigateTo('table')"
    @cancel="navigateTo('table')"
  />
</template>
