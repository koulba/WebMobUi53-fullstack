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
  <form class="border rounded p-4 space-y-3" @submit.prevent="submit">
    <h2 class="text-lg font-semibold">Nouveau sondage</h2>

    <div>
      <label class="block text-sm font-medium">Titre (optionnel)</label>
      <input v-model="title" type="text" class="w-full border rounded px-2 py-1" maxlength="255" />
    </div>

    <div>
      <label class="block text-sm font-medium">Question *</label>
      <input v-model="question" type="text" class="w-full border rounded px-2 py-1" maxlength="255" required />
    </div>

    <div>
      <label class="block text-sm font-medium">Options *</label>
      <div v-for="(opt, i) in options" :key="i" class="flex gap-2 mb-1">
        <input v-model="options[i]" type="text" class="flex-1 border rounded px-2 py-1" :placeholder="`Option ${i + 1}`" maxlength="255" />
        <button type="button" class="px-2 text-sm" :disabled="options.length <= 2" @click="removeOption(i)">×</button>
      </div>
      <button type="button" class="text-sm underline" @click="addOption">+ Ajouter une option</button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
      <label class="flex items-center gap-2">
        <input v-model="allowMultiple" type="checkbox" /> Choix multiples
      </label>
      <label class="flex items-center gap-2">
        <input v-model="resultsPublic" type="checkbox" /> Résultats publics
      </label>
      <label class="flex items-center gap-2">
        <input v-model="allowVoteChange" type="checkbox" /> Vote modifiable
      </label>
      <label class="flex items-center gap-2">
        <input v-model="startNow" type="checkbox" /> Démarrer immédiatement
      </label>
    </div>

    <div>
      <label class="block text-sm font-medium">Durée (minutes, optionnel)</label>
      <input v-model.number="durationMinutes" type="number" min="1" class="w-full border rounded px-2 py-1" />
    </div>

    <p v-if="errors.form" class="text-red-600 text-sm">{{ errors.form }}</p>

    <div class="flex gap-2">
      <button type="submit" :disabled="!canSubmit || submitting" class="bg-blue-600 text-white px-3 py-1 rounded disabled:opacity-50">
        {{ submitting ? 'Création…' : 'Créer' }}
      </button>
      <button type="button" class="px-3 py-1 rounded border" @click="emit('cancel')">Annuler</button>
    </div>
  </form>
</template>
