<script setup>
  import { computed, reactive, watch } from 'vue';

  const props = defineProps({
    initialPoll: { type: Object, required: true },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    submitLabel: { type: String, default: 'Sauvegarder' },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    showSubmit: { type: Boolean, default: true },
    shareUrl: { type: String, default: '' },
    errors: { type: Object, default: () => ({}) },
    formError: { type: String, default: '' },
  });

  const emit = defineEmits(['submit', 'cancel']);

  const form = reactive({
    title: '',
    question: '',
    is_draft: true,
    allow_multiple_choices: false,
    results_public: false,
    allow_vote_change: false,
    started_at: '',
    ends_at: '',
    options: [],
  });

  const formDisabled = computed(() => props.loading || props.saving || props.disabled);
  const canAddOption = computed(() => form.options.length < 20);
  const canRemoveOption = computed(() => form.options.length > 2);

  function toDatetimeLocal(value) {
    if (!value) return '';
    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    return normalized.slice(0, 16);
  }

  function normalizeOptions(options) {
    if (Array.isArray(options) && options.length > 0) {
      return options.map(option => ({
        id: option.id ?? null,
        label: option.label ?? '',
      }));
    }

    return [
      { id: null, label: '' },
      { id: null, label: '' },
    ];
  }

  function applyPoll(poll) {
    form.title = poll.title ?? '';
    form.question = poll.question ?? '';
    form.is_draft = !!poll.is_draft;
    form.allow_multiple_choices = !!poll.allow_multiple_choices;
    form.results_public = !!poll.results_public;
    form.allow_vote_change = !!poll.allow_vote_change;
    form.started_at = toDatetimeLocal(poll.started_at);
    form.ends_at = toDatetimeLocal(poll.ends_at);
    form.options = normalizeOptions(poll.options);
  }

  function addOption() {
    if (!canAddOption.value) return;
    form.options.push({ id: null, label: '' });
  }

  function removeOption(index) {
    if (!canRemoveOption.value) return;
    form.options.splice(index, 1);
  }

  function submit() {
    if (formDisabled.value) return;

    emit('submit', {
      title: form.title.trim() || null,
      question: form.question.trim(),
      is_draft: form.is_draft,
      allow_multiple_choices: form.allow_multiple_choices,
      results_public: form.results_public,
      allow_vote_change: form.allow_vote_change,
      started_at: form.started_at || null,
      ends_at: form.ends_at || null,
      options: form.options.map(option => ({
        id: option.id ?? null,
        label: option.label.trim(),
      })),
    });
  }

  watch(
    () => props.initialPoll,
    poll => {
      if (poll) applyPoll(poll);
    },
    { immediate: true, deep: true }
  );
</script>

<template>
  <article class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 mt-6">
    <header class="mb-6">
      <h2 class="text-2xl font-bold dark:text-white mb-2">{{ title }}</h2>
      <p v-if="description" class="dark:text-gray-300">{{ description }}</p>
      <p v-if="shareUrl" class="text-sm text-gray-500 dark:text-gray-400 mt-2">Lien public: {{ shareUrl }}</p>
    </header>

    <p v-if="loading" class="text-sm text-gray-600 dark:text-gray-300">Chargement du sondage...</p>
    <p v-else-if="formError" class="text-sm text-red-600 dark:text-red-400">{{ formError }}</p>

    <form v-if="!loading" @submit.prevent="submit">
      <div class="mb-4">
        <label for="poll-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Titre (optionnel)
        </label>
        <input
          id="poll-title"
          v-model="form.title"
          :disabled="formDisabled"
          type="text"
          class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
        >
        <p v-if="errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.title[0] }}</p>
      </div>

      <div class="mb-4">
        <label for="poll-question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Question
        </label>
        <input
          id="poll-question"
          v-model="form.question"
          :disabled="formDisabled"
          type="text"
          required
          class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
        >
        <p v-if="errors.question" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.question[0] }}</p>
      </div>

      <div class="mb-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          <input v-model="form.is_draft" :disabled="formDisabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
          Enregistrer en brouillon (decoche pour publier)
        </label>
        <p v-if="errors.is_draft" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.is_draft[0] }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          <input v-model="form.allow_multiple_choices" :disabled="formDisabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
          Choix multiples autorises
        </label>
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          <input v-model="form.results_public" :disabled="formDisabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
          Resultats publics
        </label>
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          <input v-model="form.allow_vote_change" :disabled="formDisabled" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
          Changement de vote autorise
        </label>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="poll-started-at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Date de debut (optionnel)
          </label>
          <input
            id="poll-started-at"
            v-model="form.started_at"
            :disabled="formDisabled"
            type="datetime-local"
            class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
          >
          <p v-if="errors.started_at" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.started_at[0] }}</p>
        </div>

        <div>
          <label for="poll-ends-at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Date de fin (optionnel)
          </label>
          <input
            id="poll-ends-at"
            v-model="form.ends_at"
            :disabled="formDisabled"
            type="datetime-local"
            class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
          >
          <p v-if="errors.ends_at" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.ends_at[0] }}</p>
        </div>
      </div>

      <section class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Options (min 2, max 20)</h3>
          <button
            type="button"
            class="px-3 py-1 bg-teal-600 dark:bg-purple-900 text-white rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 disabled:opacity-50"
            :disabled="!canAddOption || formDisabled"
            @click="addOption"
          >
            Ajouter une option
          </button>
        </div>

        <div class="space-y-3">
          <div v-for="(option, index) in form.options" :key="option.id ?? `new-${index}`">
            <div class="flex items-center gap-2">
              <input
                v-model="option.label"
                :disabled="formDisabled"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
                :placeholder="`Option ${index + 1}`"
              >
              <button
                type="button"
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50"
                :disabled="!canRemoveOption || formDisabled"
                @click="removeOption(index)"
              >
                Retirer
              </button>
            </div>
          </div>
        </div>

        <p v-if="errors.options" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ errors.options[0] }}</p>
      </section>

      <p v-if="formError" class="mb-4 text-sm text-red-600 dark:text-red-400">{{ formError }}</p>

      <footer class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <button
            type="button"
            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
            @click="$emit('cancel')"
          >
            Retour au tableau
          </button>

          <button
            v-if="showSubmit"
            type="submit"
            class="px-4 py-2 bg-teal-600 dark:bg-purple-900 text-white rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 disabled:opacity-50"
            :disabled="saving || formDisabled"
          >
            {{ saving ? 'Sauvegarde...' : submitLabel }}
          </button>
        </div>
      </footer>
    </form>
  </article>
</template>
