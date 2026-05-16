<script setup>
  import { ref } from 'vue';
  import PollForm from './PollForm.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['created', 'cancel']);
  const { createPoll } = usePollStore();

  const saving = ref(false);
  const formError = ref('');
  const errors = ref({});

  const initialPoll = {
    title: '',
    question: '',
    is_draft: true,
    allow_multiple_choices: false,
    results_public: false,
    allow_vote_change: false,
    started_at: '',
    ends_at: '',
    options: [
      { id: null, label: '' },
      { id: null, label: '' },
    ],
  };

  async function submit(formData) {
    saving.value = true;
    formError.value = '';
    errors.value = {};

    const payload = {
      title: formData.title,
      question: formData.question,
      is_draft: formData.is_draft,
      allow_multiple_choices: formData.allow_multiple_choices,
      results_public: formData.results_public,
      allow_vote_change: formData.allow_vote_change,
      started_at: formData.started_at,
      ends_at: formData.ends_at,
      options: formData.options.map(option => option.label),
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
  <PollForm
    :initial-poll="initialPoll"
    title="Creer un nouveau sondage"
    description="Definis la question, les options et les dates si besoin."
    submit-label="Creer le sondage"
    :saving="saving"
    :errors="errors"
    :form-error="formError"
    @submit="submit"
    @cancel="$emit('cancel')"
  />
</template>
