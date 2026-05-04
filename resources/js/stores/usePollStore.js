import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function deletePoll(id) {
    const result = await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
    if (result) {
      polls.value = polls.value.filter(p => p.id !== id);
    }
  }

  async function createPoll(payload) {
    const poll = await fetchApi({ url: 'polls', method: 'POST', data: payload });
    polls.value = [poll, ...polls.value];
    return poll;
  }

  return { polls, setPolls, deletePoll, createPoll };
}
