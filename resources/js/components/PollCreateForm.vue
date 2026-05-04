<script setup>
  import { computed, reactive, ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['created', 'cancel']);
  const { createPoll } = usePollStore();

  const saving = ref(false);
  const formError = ref('');
  const errors = ref({});

  const form = reactive({
    title: '',
    question: '',
    is_draft: true,
    started_at: '',
    ends_at: '',
    options: ['', ''],
  });

  const canAddOption = computed(() => form.options.length < 20);
  const canRemoveOption = computed(() => form.options.length > 2);

  function addOption() {
    if (!canAddOption.value) return;
    form.options.push('');
  }

  function removeOption(index) {
    if (!canRemoveOption.value) return;
    form.options.splice(index, 1);
  }

  async function submit() {
    saving.value = true;
    formError.value = '';
    errors.value = {};

    const payload = {
      title: form.title.trim() || null,
      question: form.question.trim(),
      is_draft: form.is_draft,
      started_at: form.started_at || null,
      ends_at: form.ends_at || null,
      options: form.options.map(option => option.trim()),
    };

    try {
      const createdPoll = await createPoll(payload);
      emit('created', createdPoll);
    } catch (error) {
      if (error?.data?.errors) {
        errors.value = error.data.errors;
      } else {
        formError.value = 'Impossible de creer le sondage pour le moment.';
      }
    } finally {
      saving.value = false;
    }
  }
</script>

<template>
  <article class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 mt-6">
    <header class="mb-6">
      <h2 class="text-2xl font-bold dark:text-white mb-2">Creer un nouveau sondage</h2>
      <p class="mt-4 dark:text-gray-300">Definis la question, les options et les dates si besoin.</p>
    </header>

    <form @submit.prevent="submit">
      <div class="mb-4">
        <label for="poll-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Titre (optionnel)
        </label>
        <input
          id="poll-title"
          v-model="form.title"
          type="text"
          class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
          placeholder="Donne un titre au sondage"
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
          type="text"
          required
          class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
          placeholder="Ex: Quelle fonctionnalite veux-tu en premier ?"
        >
        <p v-if="errors.question" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.question[0] }}</p>
      </div>

      <div class="mb-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          <input v-model="form.is_draft" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
          Enregistrer en brouillon (decoche pour publier directement)
        </label>
        <p v-if="errors.is_draft" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.is_draft[0] }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="poll-started-at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Date de debut (optionnel)
          </label>
          <input
            id="poll-started-at"
            v-model="form.started_at"
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
            :disabled="!canAddOption"
            @click="addOption"
          >
            Ajouter une option
          </button>
        </div>

        <div class="space-y-3">
          <div v-for="(option, index) in form.options" :key="index">
            <div class="flex items-center gap-2">
              <input
                v-model="form.options[index]"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:border-transparent focus:ring-teal-500 dark:focus:ring-purple-500"
                :placeholder="`Option ${index + 1}`"
              >
              <button
                type="button"
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50"
                :disabled="!canRemoveOption"
                @click="removeOption(index)"
              >
                Retirer
              </button>
            </div>
            <p v-if="errors[`options.${index}`]" class="mt-1 text-sm text-red-600 dark:text-red-400">
              {{ errors[`options.${index}`][0] }}
            </p>
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
            type="submit"
            class="px-4 py-2 bg-teal-600 dark:bg-purple-900 text-white rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 disabled:opacity-50"
            :disabled="saving"
          >
            {{ saving ? 'Creation...' : 'Creer le sondage' }}
          </button>
        </div>
      </footer>
    </form>
  </article>
</template>
