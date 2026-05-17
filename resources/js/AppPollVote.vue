<script setup>
  import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
  import { useFetchApi } from '@/composables/useFetchApi';
  import { usePolling } from '@/composables/usePolling';
  import PollVoteForm from './components/PollVoteForm.vue';
  import PollResultsPanel from './components/PollResultsPanel.vue';
  import PollPublicList from './components/PollPublicList.vue';

  const props = defineProps({
    token: { type: String, default: '' },
    loginUrl: { type: String, default: '' },
  });

  const { fetchApi } = useFetchApi();

  const hasToken = computed(() => !!props.token);

  const publicPolls = ref([]);
  const publicLoading = ref(false);
  const publicError = ref('');

  const poll = ref(null);
  const loadingPoll = ref(true);
  const pollError = ref('');

  const selectedOptionIds = ref([]);
  const voteSaving = ref(false);
  const voteError = ref('');
  const voteNotice = ref('');
  const voteLocked = ref(false);
  const pendingVoteIds = ref(null);
  let voteTimer = null;

  const results = ref(null);
  const loadingResults = ref(false);
  const resultsError = ref('');
  const resultsAllowed = ref(true);
  const resultsRefreshing = ref(false);

  const canVote = computed(() => !!poll.value?.can_vote);

  async function loadPublicPolls() {
    publicLoading.value = true;
    publicError.value = '';

    try {
      const data = await fetchApi({ url: 'polls/public' });
      publicPolls.value = Array.isArray(data) ? data : [];
    } catch (error) {
      publicError.value = 'Impossible de charger les sondages publics.';
    } finally {
      publicLoading.value = false;
    }
  }

  async function loadPoll() {
    loadingPoll.value = true;
    pollError.value = '';
    resultsAllowed.value = true;

    try {
      const data = await fetchApi({ url: `polls/${props.token}` });
      poll.value = data;
      selectedOptionIds.value = Array.isArray(data?.user_option_ids) ? data.user_option_ids : [];
      voteLocked.value = !!data?.user_option_ids?.length && !data?.allow_vote_change;
    } catch (error) {
      if (error?.status === 404) {
        pollError.value = 'Sondage introuvable.';
      } else {
        pollError.value = 'Impossible de charger le sondage.';
      }
    } finally {
      loadingPoll.value = false;
    }

    if (poll.value) {
      await loadResults();
    }
  }

  async function loadResults({ silent = false } = {}) {
    if (!hasToken.value || !poll.value || !resultsAllowed.value || resultsRefreshing.value) return;

    resultsRefreshing.value = true;
    if (!silent) loadingResults.value = true;
    resultsError.value = '';

    try {
      const data = await fetchApi({ url: `polls/${props.token}/results` });
      results.value = data;
      resultsAllowed.value = true;

      if (poll.value) {
        poll.value = {
          ...poll.value,
          is_active: data.is_active,
          is_ended: data.is_ended,
          results_public: data.results_public,
        };
      }
    } catch (error) {
      if (error?.status === 403) {
        resultsAllowed.value = false;
        resultsError.value = 'Resultats non publics.';
      } else if (error?.status === 404) {
        resultsError.value = 'Resultats indisponibles.';
      } else {
        resultsError.value = 'Impossible de charger les resultats.';
      }
    } finally {
      if (!silent) loadingResults.value = false;
      resultsRefreshing.value = false;
    }
  }

  async function submitVote(optionIds) {
    voteError.value = '';
    voteNotice.value = '';

    if (!poll.value) return;

    if (voteLocked.value) {
      voteError.value = 'Vote deja enregistre.';
      return;
    }

    if (!poll.value.is_active) {
      voteError.value = 'Ce sondage est ferme.';
      return;
    }

    if (!canVote.value) {
      voteError.value = 'Connecte-toi pour voter.';
      return;
    }

    if (!Array.isArray(optionIds) || optionIds.length === 0) {
      voteError.value = 'Selectionne au moins une option.';
      return;
    }

    voteSaving.value = true;
    selectedOptionIds.value = optionIds;

    try {
      await fetchApi({
        url: `polls/${props.token}/votes`,
        method: 'POST',
        data: { option_ids: optionIds },
      });
      voteNotice.value = poll.value.allow_vote_change
        ? 'Vote enregistre. Tu peux encore le modifier.'
        : 'Vote enregistre.';
      if (!poll.value.allow_vote_change) {
        voteLocked.value = true;
      }
      await loadResults({ silent: true });
    } catch (error) {
      if (error?.status === 401) {
        voteError.value = 'Connecte-toi pour voter.';
      } else if (error?.status === 403) {
        voteError.value = error?.data?.message || 'Sondage non ouvert.';
      } else if (error?.status === 409) {
        voteError.value = 'Vote deja enregistre.';
        voteLocked.value = true;
      } else if (error?.data?.errors?.option_ids?.length) {
        voteError.value = error.data.errors.option_ids[0];
      } else {
        voteError.value = 'Impossible d envoyer le vote.';
      }
    } finally {
      voteSaving.value = false;
    }
  }

  function queueVote(optionIds) {
    if (!poll.value) return;

    if (poll.value.allow_multiple_choices && !poll.value.allow_vote_change) {
      pendingVoteIds.value = optionIds;

      if (voteTimer) clearTimeout(voteTimer);

      voteTimer = setTimeout(() => {
        if (pendingVoteIds.value) submitVote(pendingVoteIds.value);
        pendingVoteIds.value = null;
        voteTimer = null;
      }, 400);
      return;
    }

    submitVote(optionIds);
  }

  function handleSaveVote() {
    submitVote(selectedOptionIds.value);
  }

  watch(selectedOptionIds, () => {
    voteError.value = '';
  });

  onMounted(() => {
    if (hasToken.value) {
      loadPoll();
    } else {
      loadPublicPolls();
    }
  });

  onBeforeUnmount(() => {
    if (voteTimer) clearTimeout(voteTimer);
  });

  usePolling(() => {
    if (!hasToken.value || !poll.value || !resultsAllowed.value) return;
    loadResults({ silent: true });
  }, 5000);
</script>

<template>
  <main class="max-w-5xl mx-auto px-4 py-10">
    <template v-if="!hasToken">
      <PollPublicList
        :polls="publicPolls"
        :loading="publicLoading"
        :error="publicError"
      />
    </template>

    <template v-else>
      <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sondage public</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400">Vote et consulte les resultats en temps reel.</p>
        </div>
        <a
          href="/polls"
          class="inline-flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
        >
          Retour aux sondages
        </a>
      </header>

      <section class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <p v-if="loadingPoll" class="text-sm text-gray-600 dark:text-gray-300">Chargement du sondage...</p>
        <p v-else-if="pollError" class="text-sm text-red-600 dark:text-red-400">{{ pollError }}</p>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <PollVoteForm
            :poll="poll"
            v-model="selectedOptionIds"
            :can-vote="canVote"
            :saving="voteSaving"
            :locked="voteLocked"
            :error="voteError"
            :notice="voteNotice"
            :login-url="loginUrl"
            @vote="queueVote"
            @submit="handleSaveVote"
          />

          <PollResultsPanel
            :poll="poll"
            :results="results"
            :loading="loadingResults"
            :error="resultsError"
            :allowed="resultsAllowed"
          />
        </div>
      </section>
    </template>
  </main>
</template>
