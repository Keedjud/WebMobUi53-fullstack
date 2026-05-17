<script setup>
  import { computed } from 'vue';

  const props = defineProps({
    poll: { type: Object, required: true },
    results: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    allowed: { type: Boolean, default: true },
  });

  const totalVotes = computed(() => {
    if (typeof props.results?.total_votes === 'number') return props.results.total_votes;
    const options = props.results?.options || [];
    return options.reduce((sum, option) => sum + (option.votes_count || 0), 0);
  });

  const items = computed(() => {
    const options = props.results?.options || [];
    const total = totalVotes.value || 0;

    return options.map(option => {
      const count = option.votes_count || 0;
      const percent = total > 0 ? Math.round((count / total) * 100) : 0;
      return {
        id: option.id,
        label: option.label || '-',
        count,
        percent,
      };
    });
  });
</script>

<template>
  <section class="bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700 p-5">
    <header class="mb-4">
      <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Resultats</h3>
    </header>

    <p v-if="loading" class="text-sm text-gray-600 dark:text-gray-300">Chargement des resultats...</p>
    <p v-else-if="!allowed" class="text-sm text-gray-500 dark:text-gray-400">
      Resultats non publics.
    </p>
    <p v-else-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    <div v-else-if="results" class="space-y-4">
      <p class="text-sm text-gray-600 dark:text-gray-300">Total votes: {{ totalVotes }}</p>

      <div v-for="item in items" :key="item.id" class="space-y-1">
        <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
          <span>{{ item.label }}</span>
          <span>{{ item.count }} ({{ item.percent }}%)</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-slate-700">
          <div
            class="h-2 rounded-full bg-teal-500"
            :style="{ width: item.percent + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <p v-else class="text-sm text-gray-500 dark:text-gray-400">Resultats indisponibles.</p>
  </section>
</template>
