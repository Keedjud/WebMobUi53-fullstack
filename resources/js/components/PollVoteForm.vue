<script setup>
  import { computed } from 'vue';

  const props = defineProps({
    poll: { type: Object, required: true },
    modelValue: { type: Array, default: () => [] },
    canVote: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
    error: { type: String, default: '' },
    notice: { type: String, default: '' },
    loginUrl: { type: String, default: '' },
  });

  const emit = defineEmits(['update:modelValue', 'vote', 'submit']);

  const isActive = computed(() => !!props.poll?.is_active);
  const isDraft = computed(() => !!props.poll?.is_draft);
  const isEnded = computed(() => !!props.poll?.is_ended);

  const statusLabel = computed(() => {
    if (isDraft.value) return 'Brouillon';
    if (isEnded.value) return 'Termine';
    if (isActive.value) return 'En cours';
    return 'A venir';
  });

  const statusHint = computed(() => {
    if (isDraft.value) return 'Ce sondage est en brouillon.';
    if (isEnded.value) return 'Ce sondage est termine.';
    if (!isActive.value) return 'Ce sondage n est pas encore ouvert.';
    return '';
  });

  const authorDisplay = computed(() => {
    if (props.poll?.author_name && props.poll?.author_username) {
      return `${props.poll.author_name} (@${props.poll.author_username})`;
    }
    if (props.poll?.author_name) return props.poll.author_name;
    if (props.poll?.author_username) return `@${props.poll.author_username}`;
    return '';
  });

  const authorProfileUrl = computed(() =>
    props.poll?.author_username ? `/@${props.poll.author_username}` : ''
  );

  const startedAt = computed(() => formatDateTime(props.poll?.started_at));
  const endsAt = computed(() => formatDateTime(props.poll?.ends_at));

  const choiceHint = computed(() =>
    props.poll?.allow_multiple_choices ? 'Choix multiples autorises.' : 'Un seul choix.'
  );

  const voteChangeHint = computed(() =>
    props.poll?.allow_vote_change ? 'Changement de vote autorise.' : 'Changement de vote interdit.'
  );

  const requiresSave = computed(() => !props.poll?.allow_vote_change);
  const hasSelection = computed(() => (props.modelValue || []).length > 0);
  const actionHint = computed(() =>
    requiresSave.value
      ? 'Selectionne les options puis enregistre ton vote.'
      : 'Clique sur une option pour voter.'
  );

  const saveLabel = computed(() => (props.locked ? 'Vote enregistre' : 'Enregistrer mon vote'));
  const saveDisabled = computed(() => isDisabled.value || !hasSelection.value);

  const isDisabled = computed(() => !isActive.value || !props.canVote || props.saving || props.locked);

  function formatDateTime(value) {
    if (!value) return '-';
    const match = String(value).match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!match) return '-';
    return `${match[3]}.${match[2]}.${match[1]} ${match[4]}:${match[5]}`;
  }

  function isSelected(optionId) {
    return (props.modelValue || []).includes(optionId);
  }

  function toggleOption(optionId) {
    if (isDisabled.value) return;
    const current = props.modelValue || [];

    if (props.poll?.allow_multiple_choices) {
      const next = current.includes(optionId)
        ? current.filter(id => id !== optionId)
        : [...current, optionId];
      emit('update:modelValue', next);
      if (!requiresSave.value && next.length > 0) emit('vote', next);
    } else {
      const next = [optionId];
      emit('update:modelValue', next);
      if (!requiresSave.value) emit('vote', next);
    }
  }

  function submit() {
    if (saveDisabled.value) return;
    emit('submit');
  }
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700 p-5">
    <header class="mb-4">
      <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Etat: {{ statusLabel }}</p>
      <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ poll.title || 'Sondage' }}</h2>
      <p v-if="authorDisplay" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        <span>Par </span>
        <a v-if="authorProfileUrl" :href="authorProfileUrl" class="hover:underline">{{ authorDisplay }}</a>
        <span v-else>{{ authorDisplay }}</span>
      </p>
      <p class="mt-2 text-lg text-gray-700 dark:text-gray-200">{{ poll.question }}</p>

      <div class="mt-3 text-sm text-gray-500 dark:text-gray-400 space-y-1">
        <p>Debut: {{ startedAt }}</p>
        <p>Fin: {{ endsAt }}</p>
        <p>{{ choiceHint }}</p>
        <p>{{ voteChangeHint }}</p>
      </div>
    </header>

    <p v-if="statusHint" class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ statusHint }}</p>

    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
      {{ actionHint }}
    </p>

    <div v-if="poll.options?.length" class="mt-3 space-y-2">
      <button
        v-for="option in poll.options"
        :key="option.id"
        type="button"
        class="w-full text-left rounded-md border px-3 py-2 transition"
        :class="isDisabled ? 'opacity-60 cursor-not-allowed border-gray-200 dark:border-slate-700' : 'border-gray-200 dark:border-slate-700 hover:border-teal-500'"
        :aria-pressed="isSelected(option.id)"
        @click="toggleOption(option.id)"
      >
        <span
          class="inline-flex items-center gap-2 text-gray-800 dark:text-gray-200"
        >
          <span
            class="h-4 w-4 rounded-full border border-gray-300 dark:border-slate-500 flex items-center justify-center"
            :class="isSelected(option.id) ? 'bg-teal-600 border-teal-600' : ''"
          >
            <span v-if="isSelected(option.id)" class="block h-2 w-2 rounded-full bg-white"></span>
          </span>
          {{ option.label }}
        </span>
      </button>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
      <button
        v-if="requiresSave"
        type="button"
        class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 disabled:opacity-50"
        :disabled="saveDisabled"
        @click="submit"
      >
        {{ saveLabel }}
      </button>

      <a
        v-if="!canVote && isActive && loginUrl"
        :href="loginUrl"
        class="text-sm text-teal-700 dark:text-teal-300 underline"
      >
        Se connecter
      </a>

      <p v-else-if="!canVote && isActive" class="text-sm text-gray-500 dark:text-gray-400">
        Connexion requise pour voter.
      </p>
    </div>

    <p v-if="notice" class="mt-3 text-sm text-emerald-600 dark:text-emerald-400">{{ notice }}</p>
    <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>
