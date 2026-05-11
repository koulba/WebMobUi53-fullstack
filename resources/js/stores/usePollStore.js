import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function loadPolls() {
    const data = await fetchApi({ url: 'polls' });
    if (Array.isArray(data)) polls.value = data;
  }

  async function createPoll(payload) {
    const created = await fetchApi({ url: 'polls', method: 'POST', data: payload });
    if (created) polls.value = [created, ...polls.value];
    return created;
  }

  async function deletePoll(id) {
    const result = await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
    if (result) polls.value = polls.value.filter(p => p.id !== id);
  }

  async function startPoll(id) {
    const updated = await fetchApi({ url: `polls/${id}/start`, method: 'POST' });
    if (updated) replaceInList(updated);
    return updated;
  }

  async function updatePoll(id, payload) {
    const updated = await fetchApi({ url: `polls/${id}`, method: 'PUT', data: payload });
    if (updated) replaceInList(updated);
    return updated;
  }

  async function addOption(pollId, label) {
    return await fetchApi({ url: `polls/${pollId}/options`, method: 'POST', data: { label } });
  }

  async function renameOption(pollId, optionId, label) {
    return await fetchApi({ url: `polls/${pollId}/options/${optionId}`, method: 'PUT', data: { label } });
  }

  async function removeOption(pollId, optionId) {
    return await fetchApi({ url: `polls/${pollId}/options/${optionId}`, method: 'DELETE' });
  }

  function replaceInList(poll) {
    polls.value = polls.value.map(p => p.id === poll.id ? poll : p);
  }

  return {
    polls,
    setPolls,
    loadPolls,
    createPoll,
    deletePoll,
    startPoll,
    updatePoll,
    addOption,
    renameOption,
    removeOption,
  };
}
