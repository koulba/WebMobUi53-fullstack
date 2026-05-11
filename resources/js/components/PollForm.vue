<script setup>
  import { ref, computed } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['created', 'cancel']);
  const { createPoll } = usePollStore();

  const title = ref('');
  const question = ref('');
  const options = ref(['', '']);
  const allowMultiple = ref(false);
  const allowVoteChange = ref(false);
  const resultsPublic = ref(false);
  const durationMinutes = ref(null);
  const startNow = ref(false);

  const submitting = ref(false);
  const errors = ref({});

  const canSubmit = computed(() =>
    question.value.trim().length > 0 &&
    options.value.filter(o => o.trim().length > 0).length >= 2
  );

  function addOption() {
    options.value.push('');
  }

  function removeOption(index) {
    if (options.value.length <= 2) return;
    options.value.splice(index, 1);
  }

  async function submit() {
    errors.value = {};
    if (!canSubmit.value) {
      errors.value.form = 'Question et au moins 2 options non vides sont requises.';
      return;
    }
    submitting.value = true;
    try {
      const payload = {
        title: title.value.trim() || null,
        question: question.value.trim(),
        options: options.value.map(o => o.trim()).filter(o => o.length > 0),
        allow_multiple_choices: allowMultiple.value,
        allow_vote_change: allowVoteChange.value,
        results_public: resultsPublic.value,
        duration: durationMinutes.value ? durationMinutes.value * 60 : null,
        start_now: startNow.value,
      };
      const created = await createPoll(payload);
      if (created) {
        emit('created', created);
        reset();
      }
    } catch (e) {
      errors.value.form = e?.data?.message || 'Erreur lors de la création.';
    } finally {
      submitting.value = false;
    }
  }

  function reset() {
    title.value = '';
    question.value = '';
    options.value = ['', ''];
    allowMultiple.value = false;
    allowVoteChange.value = false;
    resultsPublic.value = false;
    durationMinutes.value = null;
    startNow.value = false;
    errors.value = {};
  }
</script>

<template>
  <form
    class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 space-y-4"
    @submit.prevent="submit"
  >
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nouveau sondage</h2>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titre (optionnel)</label>
      <input
        v-model="title"
        type="text"
        maxlength="255"
        class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question *</label>
      <input
        v-model="question"
        type="text"
        maxlength="255"
        required
        class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
      />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Options *</label>
      <div v-for="(opt, i) in options" :key="i" class="flex gap-2 mt-1">
        <input
          v-model="options[i]"
          type="text"
          maxlength="255"
          :placeholder="`Option ${i + 1}`"
          class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
        <button
          type="button"
          class="px-3 rounded-md border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-slate-700"
          :disabled="options.length <= 2"
          @click="removeOption(i)"
        >×</button>
      </div>
      <button
        type="button"
        class="mt-2 text-sm text-teal-700 dark:text-teal-400 hover:underline"
        @click="addOption"
      >
        + Ajouter une option
      </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700 dark:text-gray-300">
      <label class="flex items-center gap-2"><input v-model="allowMultiple" type="checkbox" class="rounded" /> Choix multiples</label>
      <label class="flex items-center gap-2"><input v-model="resultsPublic" type="checkbox" class="rounded" /> Résultats publics</label>
      <label class="flex items-center gap-2"><input v-model="allowVoteChange" type="checkbox" class="rounded" /> Vote modifiable</label>
      <label class="flex items-center gap-2"><input v-model="startNow" type="checkbox" class="rounded" /> Démarrer immédiatement</label>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durée (minutes, optionnel)</label>
      <input
        v-model.number="durationMinutes"
        type="number"
        min="1"
        class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
      />
    </div>

    <p v-if="errors.form" class="text-sm text-red-600 dark:text-red-400">{{ errors.form }}</p>

    <div class="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
      <button
        type="submit"
        :disabled="!canSubmit || submitting"
        class="bg-teal-600 dark:bg-purple-900 text-white px-4 py-2 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 disabled:opacity-50 transition"
      >
        {{ submitting ? 'Création…' : 'Créer' }}
      </button>
      <button
        type="button"
        class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition"
        @click="emit('cancel')"
      >
        Annuler
      </button>
    </div>
  </form>
</template>
