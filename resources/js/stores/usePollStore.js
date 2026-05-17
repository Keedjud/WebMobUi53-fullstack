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

  function extractTokenFromShareUrl(shareUrl) {
    if (!shareUrl) return null;
    try {
      const url = new URL(shareUrl);
      const parts = url.pathname.split('/').filter(Boolean);
      return parts[parts.length - 1] || null;
    } catch (error) {
      const parts = shareUrl.split('/').filter(Boolean);
      return parts[parts.length - 1] || null;
    }
  }

  async function fetchPollByToken(token) {
    return await fetchApi({ url: `polls/${token}` });
  }

  async function updatePoll(id, payload) {
    const poll = await fetchApi({ url: `polls/${id}`, method: 'PATCH', data: payload });
    polls.value = polls.value.map(item => (item.id === poll.id ? { ...item, ...poll } : item));
    return poll;
  }

  async function publishPoll(id) {
    const poll = await fetchApi({ url: `polls/${id}/publish`, method: 'PATCH' });
    polls.value = polls.value.map(item => (item.id === poll.id ? { ...item, ...poll } : item));
    return poll;
  }

  return {
    polls,
    setPolls,
    deletePoll,
    createPoll,
    updatePoll,
    publishPoll,
    fetchPollByToken,
    extractTokenFromShareUrl,
  };
}
