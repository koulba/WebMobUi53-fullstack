<script setup>
  import { ref, computed } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['edit']);

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

  function pollStatus(poll) {
    if (poll.is_draft) return 'draft';
    if (poll.ends_at && new Date(poll.ends_at) < new Date()) return 'ended';
    return 'live';
  }

  const statusStyles = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    live: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200',
    ended: 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200',
  };

  const statusLabels = {
    draft: 'Brouillon',
    live: 'En cours',
    ended: 'Terminé',
  };
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
    <p v-if="polls.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-6">
      Aucun sondage pour l'instant.
    </p>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
            <th class="px-3 py-2">Question</th>
            <th class="px-3 py-2">État</th>
            <th class="px-3 py-2">Choix</th>
            <th class="px-3 py-2">Résultats</th>
            <th class="px-3 py-2">Fin</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="poll in polls"
            :key="poll.id"
            class="hover:bg-gray-50 dark:hover:bg-slate-700/40"
          >
            <td class="px-3 py-3">
              <div class="font-medium text-gray-900 dark:text-white">{{ poll.question }}</div>
              <div v-if="poll.title" class="text-xs text-gray-500 dark:text-gray-400">{{ poll.title }}</div>
            </td>
            <td class="px-3 py-3">
              <span :class="['inline-block px-2 py-0.5 rounded-full text-xs font-medium', statusStyles[pollStatus(poll)]]">
                {{ statusLabels[pollStatus(poll)] }}
              </span>
            </td>
            <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
              {{ poll.allow_multiple_choices ? 'Multiples' : 'Unique' }}
            </td>
            <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
              {{ poll.results_public ? 'Publics' : 'Privés' }}
            </td>
            <td class="px-3 py-3 text-gray-500 dark:text-gray-400 text-xs">
              {{ poll.ends_at || '—' }}
            </td>
            <td class="px-3 py-3">
              <div class="flex flex-wrap gap-1 justify-end">
                <button
                  v-if="poll.is_draft"
                  class="px-3 py-1 rounded-md text-xs font-medium bg-amber-500 text-white hover:bg-amber-600 transition"
                  @click="emit('edit', poll)"
                >
                  Éditer
                </button>
                <button
                  v-if="poll.is_draft"
                  class="px-3 py-1 rounded-md text-xs font-medium bg-teal-600 dark:bg-purple-900 text-white hover:bg-teal-700 dark:hover:bg-purple-800 transition"
                  @click="onStart(poll.id)"
                >
                  Démarrer
                </button>
                <button
                  class="px-3 py-1 rounded-md text-xs font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition"
                  @click="copyLink(poll)"
                >
                  {{ copiedId === poll.id ? 'Copié !' : 'Copier lien' }}
                </button>
                <button
                  class="px-3 py-1 rounded-md text-xs font-medium bg-red-600 text-white hover:bg-red-700 transition"
                  @click="onDelete(poll.id)"
                >
                  Supp.
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
