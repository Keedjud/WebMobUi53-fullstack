<script setup>
  import { computed, ref, watch } from 'vue';
  import PollForm from './PollForm.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    pollId: { type: [Number, String], required: true },
  });

  const emit = defineEmits(['updated', 'cancel']);

  const { polls, updatePoll, fetchPollByToken, extractTokenFromShareUrl } = usePollStore();

  const loading = ref(false);
  const saving = ref(false);
  const formError = ref('');
  const errors = ref({});
  const canEdit = ref(true);
  const shareUrl = ref('');
  const initialPoll = ref({
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
  });
  const originalOptions = ref([]);
  const formDisabled = computed(() => loading.value || saving.value || !canEdit.value);

  async function loadPoll() {
    loading.value = true;
    formError.value = '';
    errors.value = {};

    const id = Number(props.pollId);
    const basePoll = polls.value.find(poll => poll.id === id);

    if (!basePoll) {
      formError.value = 'Sondage introuvable.';
      loading.value = false;
      return;
    }

    shareUrl.value = basePoll.share_url || '';

    let detailedPoll = basePoll;
    if (!Array.isArray(basePoll.options)) {
      const token = extractTokenFromShareUrl(basePoll.share_url);
      if (!token) {
        formError.value = 'Impossible de charger les options du sondage.';
        loading.value = false;
        return;
      }

      try {
        detailedPoll = await fetchPollByToken(token);
        detailedPoll.share_url = basePoll.share_url;
      } catch (error) {
        formError.value = 'Impossible de charger le sondage.';
        loading.value = false;
        return;
      }
    }

    canEdit.value = !!detailedPoll.is_draft;
    if (!canEdit.value) {
      formError.value = 'Ce sondage est deja publie et ne peut plus etre modifie.';
    }

    originalOptions.value = Array.isArray(detailedPoll.options)
      ? detailedPoll.options.map(option => ({ id: option.id, label: option.label }))
      : [];

    initialPoll.value = {
      title: detailedPoll.title ?? '',
      question: detailedPoll.question ?? '',
      is_draft: !!detailedPoll.is_draft,
      allow_multiple_choices: !!detailedPoll.allow_multiple_choices,
      results_public: !!detailedPoll.results_public,
      allow_vote_change: !!detailedPoll.allow_vote_change,
      started_at: detailedPoll.started_at,
      ends_at: detailedPoll.ends_at,
      options: originalOptions.value.map(option => ({
        id: option.id,
        label: option.label,
      })),
    };
    loading.value = false;
  }

  async function submit(formData) {
    if (formDisabled.value) return;
    saving.value = true;
    formError.value = '';
    errors.value = {};

    const originalMap = new Map(originalOptions.value.map(option => [option.id, option.label]));
    const currentOptions = formData.options || [];
    const currentIds = new Set(currentOptions.filter(option => option.id).map(option => option.id));

    const optionsCreate = currentOptions
      .filter(option => !option.id)
      .map(option => ({ label: option.label }));

    const optionsUpdate = currentOptions
      .filter(option => option.id)
      .map(option => ({ id: option.id, label: option.label }))
      .filter(option => originalMap.get(option.id) !== option.label);

    const optionsDelete = originalOptions.value
      .filter(option => !currentIds.has(option.id))
      .map(option => option.id);

    const payload = {
      title: formData.title,
      question: formData.question,
      is_draft: formData.is_draft,
      allow_multiple_choices: formData.allow_multiple_choices,
      results_public: formData.results_public,
      allow_vote_change: formData.allow_vote_change,
      started_at: formData.started_at,
      ends_at: formData.ends_at,
      options_create: optionsCreate,
      options_update: optionsUpdate,
      options_delete: optionsDelete,
    };

    try {
      const updatedPoll = await updatePoll(props.pollId, payload);
      emit('updated', updatedPoll);
    } catch (error) {
      if (error?.data?.errors) {
        errors.value = error.data.errors;
      } else {
        formError.value = 'Impossible de mettre a jour le sondage.';
      }
    } finally {
      saving.value = false;
    }
  }

  watch(
    () => props.pollId,
    () => {
      if (props.pollId != null) {
        loadPoll();
      }
    },
    { immediate: true }
  );
</script>

<template>
  <PollForm
    :initial-poll="initialPoll"
    title="Editer le sondage"
    description="Modifie la question, les options et les parametres."
    submit-label="Mettre a jour"
    :loading="loading"
    :saving="saving"
    :disabled="!canEdit"
    :show-submit="canEdit"
    :share-url="shareUrl"
    :errors="errors"
    :form-error="formError"
    @submit="submit"
    @cancel="$emit('cancel')"
  />
</template>
