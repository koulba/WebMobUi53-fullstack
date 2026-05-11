<script setup>
  import { ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const { polls, deletePoll, startPoll } = usePollStore();
  const copiedId = ref(null);

  function shareUrl(token) {
    return `${window.location.origin}/polls/vote/${token}`;
  }

  async function copyLink(poll) {
    try {
      await navigator.clipboard.writeText(shareUrl(poll.secret_token));
      copiedId.value = poll.id;
      setTimeout(() => { copiedId.value = null; }, 1500);
    } catch (e) {
      window.prompt('Copie ce lien :', shareUrl(poll.secret_token));
    }
  }

  async function onDelete(id) {
    if (!confirm('Supprimer ce sondage ?')) return;
    await deletePoll(id);
  }

  async function onStart(id) {
    if (!confirm('Démarrer ce sondage maintenant ?')) return;
    await startPoll(id);
  }

  function statusLabel(poll) {
    if (poll.is_draft) return 'Brouillon';
    if (poll.ends_at && new Date(poll.ends_at) < new Date()) return 'Terminé';
    return 'En cours';
  }
</script>

<template>
  <p v-if="polls.length === 0" class="text-gray-500">Aucun sondage.</p>

  <div v-else class="overflow-x-auto">
    <table class="w-full border-collapse text-left text-sm">
      <thead>
        <tr class="bg-gray-100">
          <th class="border px-3 py-2">Question</th>
          <th class="border px-3 py-2">État</th>
          <th class="border px-3 py-2">Choix</th>
          <th class="border px-3 py-2">Résultats</th>
          <th class="border px-3 py-2">Fin</th>
          <th class="border px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="poll in polls" :key="poll.id">
          <td class="border px-3 py-2">
            <div class="font-medium">{{ poll.question }}</div>
            <div v-if="poll.title" class="text-xs text-gray-500">{{ poll.title }}</div>
          </td>
          <td class="border px-3 py-2">{{ statusLabel(poll) }}</td>
          <td class="border px-3 py-2">{{ poll.allow_multiple_choices ? 'Multiples' : 'Unique' }}</td>
          <td class="border px-3 py-2">{{ poll.results_public ? 'Publics' : 'Privés' }}</td>
          <td class="border px-3 py-2">{{ poll.ends_at || '—' }}</td>
          <td class="border px-3 py-2">
            <div class="flex flex-wrap gap-1">
              <button v-if="poll.is_draft" class="btn-start" @click="onStart(poll.id)">Démarrer</button>
              <button class="btn-copy" @click="copyLink(poll)">
                {{ copiedId === poll.id ? 'Copié !' : 'Copier lien' }}
              </button>
              <button class="btn-delete" @click="onDelete(poll.id)">Supp.</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
  button {
    color: white;
    padding: 0.25rem 0.5rem;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.8rem;
  }
  .btn-start { background-color: #2563eb; }
  .btn-copy { background-color: #6b7280; }
  .btn-delete { background-color: #e3342f; }
</style>
