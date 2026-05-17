<script setup>
  const props = defineProps({
    polls: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
  });

  function formatDateTime(value) {
    if (!value) return 'Sans date de fin';
    const match = String(value).match(/(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!match) return 'Sans date de fin';
    return `${match[3]}.${match[2]}.${match[1]} ${match[4]}:${match[5]}`;
  }

  function authorLabel(poll) {
    if (!poll) return '';
    if (poll.author_name && poll.author_username) {
      return `${poll.author_name} (@${poll.author_username})`;
    }
    if (poll.author_name) return poll.author_name;
    if (poll.author_username) return `@${poll.author_username}`;
    return '';
  }
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sondages en cours</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Choisis un sondage pour voter et suivre les resultats.
      </p>
    </header>

    <p v-if="loading" class="text-sm text-gray-600 dark:text-gray-300">Chargement des sondages...</p>
    <p v-else-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    <p v-else-if="polls.length === 0" class="text-sm text-gray-600 dark:text-gray-300">
      Aucun sondage en cours pour le moment.
    </p>

    <div v-else class="space-y-4">
      <a
        v-for="poll in polls"
        :key="poll.id"
        :href="poll.share_url"
        class="block"
      >
        <article class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 hover:shadow-lg transition">
          <header class="mb-3">
            <div class="flex items-start justify-between gap-4">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ poll.title || 'Sondage' }}
              </h2>
              <span class="text-xs uppercase tracking-wide text-teal-700 dark:text-teal-300">En cours</span>
            </div>
            <p v-if="authorLabel(poll)" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Par {{ authorLabel(poll) }}
            </p>
          </header>

          <p class="text-gray-700 dark:text-gray-200">{{ poll.question }}</p>

          <footer class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Echeance: {{ formatDateTime(poll.ends_at) }}
          </footer>
        </article>
      </a>
    </div>
  </section>
</template>
