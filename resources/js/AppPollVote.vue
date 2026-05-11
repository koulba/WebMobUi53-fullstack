<script setup>
  import { ref, computed, onMounted } from 'vue';
  import { useFetchApi } from './composables/useFetchApi';
  import { usePolling } from './composables/usePolling';
  import PollResultsChart from './components/PollResultsChart.vue';

  const props = defineProps({
    token: { type: String, required: true },
    isAuthenticated: { type: Boolean, default: false },
    loginUrl: { type: String, default: '/auth/login' },
  });

  const { fetchApi } = useFetchApi();

  const poll = ref(null);
  const results = ref(null);
  const loading = ref(true);
  const error = ref(null);
  const submitting = ref(false);
  const selectedIds = ref([]);
  const justVoted = ref(false);

  const isDraft = computed(() => !!poll.value?.is_draft);
  const isEnded = computed(() => {
    if (results.value?.is_ended) return true;
    return !!poll.value?.ends_at && new Date(poll.value.ends_at) < new Date();
  });
  const hasVoted = computed(() => !!poll.value?.user_has_voted || justVoted.value);
  const canSeeResults = computed(() => !!(poll.value?.results_public || poll.value?.is_owner));
  const canVote = computed(() =>
    props.isAuthenticated &&
    poll.value &&
    !isDraft.value &&
    !isEnded.value &&
    !hasVoted.value
  );

  async function loadPoll() {
    try {
      const data = await fetchApi({ url: `polls/${props.token}` });
      poll.value = data;
    } catch (e) {
      error.value = e?.status === 404 ? 'Sondage introuvable.' : 'Erreur de chargement.';
    } finally {
      loading.value = false;
    }
  }

  async function loadResults() {
    if (!poll.value || !canSeeResults.value) return;
    try {
      const data = await fetchApi({ url: `polls/${props.token}/results` });
      results.value = data;
    } catch (e) {
      // Silent: results may be temporarily unavailable, the poll page still works.
    }
  }

  onMounted(async () => {
    await loadPoll();
    await loadResults();
  });

  usePolling(loadResults, 5000);

  function toggleOption(id) {
    if (poll.value.allow_multiple_choices) {
      const idx = selectedIds.value.indexOf(id);
      if (idx >= 0) selectedIds.value.splice(idx, 1);
      else selectedIds.value.push(id);
    } else {
      selectedIds.value = [id];
    }
  }

  async function submitVote() {
    error.value = null;
    if (selectedIds.value.length === 0) {
      error.value = 'Sélectionne au moins une option.';
      return;
    }
    submitting.value = true;
    try {
      await fetchApi({
        url: `polls/${props.token}/vote`,
        method: 'POST',
        data: { option_ids: selectedIds.value },
      });
      justVoted.value = true;
      selectedIds.value = [];
      await loadPoll();
      await loadResults();
    } catch (e) {
      error.value = e?.data?.message || 'Erreur lors du vote.';
    } finally {
      submitting.value = false;
    }
  }
</script>

<template>
  <div class="max-w-xl mx-auto p-4 space-y-4">
    <p v-if="loading">Chargement…</p>

    <div v-else-if="error" class="text-red-600">{{ error }}</div>

    <div v-else-if="poll">
      <h1 class="text-xl font-semibold">{{ poll.question }}</h1>
      <p v-if="poll.title" class="text-sm text-gray-500">{{ poll.title }}</p>

      <p v-if="isDraft" class="mt-2 p-2 bg-yellow-100 text-yellow-800 rounded">
        Ce sondage n'a pas encore démarré.
      </p>

      <p v-else-if="isEnded" class="mt-2 p-2 bg-red-100 text-red-800 rounded">
        Ce sondage est terminé, le vote n'est plus possible.
      </p>

      <p v-else-if="!isAuthenticated" class="mt-2 p-2 bg-blue-100 text-blue-800 rounded">
        <a :href="loginUrl" class="underline">Connecte-toi</a> pour pouvoir voter.
      </p>

      <p v-else-if="hasVoted" class="mt-2 p-2 bg-green-100 text-green-800 rounded">
        Tu as déjà voté pour ce sondage.
      </p>

      <form v-if="canVote" class="mt-4 space-y-2" @submit.prevent="submitVote">
        <p class="text-sm text-gray-600">
          {{ poll.allow_multiple_choices ? 'Plusieurs choix possibles' : 'Un seul choix' }}
        </p>
        <label
          v-for="opt in poll.options"
          :key="opt.id"
          class="flex items-center gap-2 p-2 border rounded cursor-pointer"
        >
          <input
            :type="poll.allow_multiple_choices ? 'checkbox' : 'radio'"
            :checked="selectedIds.includes(opt.id)"
            @change="toggleOption(opt.id)"
          />
          <span>{{ opt.label }}</span>
        </label>
        <button
          type="submit"
          :disabled="submitting"
          class="bg-blue-600 text-white px-3 py-2 rounded disabled:opacity-50"
        >
          {{ submitting ? 'Envoi…' : 'Voter' }}
        </button>
      </form>

      <section v-if="canSeeResults && results" class="mt-6 pt-4 border-t">
        <div class="flex items-center justify-between mb-2">
          <h2 class="font-semibold">Résultats en direct</h2>
          <span class="text-xs text-gray-500">{{ results.total_votes }} vote{{ results.total_votes > 1 ? 's' : '' }}</span>
        </div>
        <PollResultsChart :options="results.options" />
        <ul class="mt-3 space-y-1 text-sm">
          <li v-for="opt in results.options" :key="opt.id" class="flex justify-between">
            <span>{{ opt.label }}</span>
            <span class="text-gray-600">{{ opt.votes_count }}</span>
          </li>
        </ul>
      </section>

      <p v-else-if="(hasVoted || isEnded) && !canSeeResults" class="mt-4 text-sm text-gray-500">
        Les résultats de ce sondage ne sont pas publics.
      </p>
    </div>
  </div>
</template>
