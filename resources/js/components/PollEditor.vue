<script setup>
  import { ref, onMounted } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';
  import { useFetchApi } from '@/composables/useFetchApi';

  const props = defineProps({
    poll: { type: Object, required: true },
  });
  const emit = defineEmits(['updated', 'closed']);

  const { updatePoll, addOption, renameOption, removeOption } = usePollStore();
  const { fetchApi } = useFetchApi();

  const title = ref(props.poll.title || '');
  const question = ref(props.poll.question);
  const allowMultiple = ref(!!props.poll.allow_multiple_choices);
  const allowVoteChange = ref(!!props.poll.allow_vote_change);
  const resultsPublic = ref(!!props.poll.results_public);
  const durationMinutes = ref(props.poll.duration ? Math.round(props.poll.duration / 60) : null);

  const options = ref([]);
  const newOptionLabel = ref('');
  const editingLabels = ref({});

  const savingMeta = ref(false);
  const error = ref(null);

  onMounted(async () => {
    try {
      const data = await fetchApi({ url: `polls/${props.poll.secret_token}` });
      if (data?.options) {
        options.value = data.options.map(o => ({ id: o.id, label: o.label }));
      }
    } catch (e) {
      error.value = 'Impossible de charger les options.';
    }
  });

  async function saveMeta() {
    error.value = null;
    savingMeta.value = true;
    try {
      const payload = {
        title: title.value.trim() || null,
        question: question.value.trim(),
        allow_multiple_choices: allowMultiple.value,
        allow_vote_change: allowVoteChange.value,
        results_public: resultsPublic.value,
        duration: durationMinutes.value ? durationMinutes.value * 60 : null,
      };
      const updated = await updatePoll(props.poll.id, payload);
      if (updated) emit('updated', updated);
    } catch (e) {
      error.value = e?.data?.message || 'Erreur lors de la mise à jour.';
    } finally {
      savingMeta.value = false;
    }
  }

  async function onAddOption() {
    error.value = null;
    const label = newOptionLabel.value.trim();
    if (!label) return;
    try {
      const opt = await addOption(props.poll.id, label);
      if (opt?.id) {
        options.value.push({ id: opt.id, label: opt.label });
        newOptionLabel.value = '';
      }
    } catch (e) {
      error.value = e?.data?.message || 'Erreur ajout option.';
    }
  }

  async function onRenameOption(optionId) {
    error.value = null;
    const newLabel = (editingLabels.value[optionId] || '').trim();
    if (!newLabel) return;
    try {
      const opt = await renameOption(props.poll.id, optionId, newLabel);
      if (opt?.id) {
        const idx = options.value.findIndex(o => o.id === optionId);
        if (idx >= 0) options.value[idx].label = opt.label;
        delete editingLabels.value[optionId];
      }
    } catch (e) {
      error.value = e?.data?.message || 'Erreur renommage.';
    }
  }

  async function onRemoveOption(optionId) {
    error.value = null;
    if (options.value.length <= 2) {
      error.value = 'Un sondage doit conserver au moins 2 options.';
      return;
    }
    if (!confirm('Supprimer cette option ?')) return;
    try {
      const result = await removeOption(props.poll.id, optionId);
      if (result) {
        options.value = options.value.filter(o => o.id !== optionId);
      }
    } catch (e) {
      error.value = e?.data?.message || 'Erreur suppression option.';
    }
  }

  function startEditingLabel(opt) {
    editingLabels.value[opt.id] = opt.label;
  }

  function cancelEditingLabel(opt) {
    delete editingLabels.value[opt.id];
  }
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6 space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
        Éditer le sondage <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(brouillon)</span>
      </h2>
      <button
        class="text-sm text-gray-500 dark:text-gray-400 hover:underline"
        @click="emit('closed')"
      >
        Fermer
      </button>
    </div>

    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titre</label>
        <input
          v-model="title"
          type="text"
          maxlength="255"
          class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question</label>
        <input
          v-model="question"
          type="text"
          maxlength="255"
          class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700 dark:text-gray-300">
        <label class="flex items-center gap-2"><input v-model="allowMultiple" type="checkbox" class="rounded" /> Choix multiples</label>
        <label class="flex items-center gap-2"><input v-model="resultsPublic" type="checkbox" class="rounded" /> Résultats publics</label>
        <label class="flex items-center gap-2"><input v-model="allowVoteChange" type="checkbox" class="rounded" /> Vote modifiable</label>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durée (minutes)</label>
        <input
          v-model.number="durationMinutes"
          type="number"
          min="1"
          class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
      </div>
      <button
        class="bg-teal-600 dark:bg-purple-900 text-white px-4 py-2 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 disabled:opacity-50 transition"
        :disabled="savingMeta"
        @click="saveMeta"
      >
        {{ savingMeta ? 'Enregistrement…' : 'Enregistrer les paramètres' }}
      </button>
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
      <h3 class="font-medium text-gray-900 dark:text-white mb-3">
        Options <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ options.length }})</span>
      </h3>
      <ul class="space-y-2">
        <li
          v-for="opt in options"
          :key="opt.id"
          class="flex gap-2 items-center"
        >
          <template v-if="editingLabels[opt.id] !== undefined">
            <input
              v-model="editingLabels[opt.id]"
              type="text"
              class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
            <button
              class="text-sm px-3 py-1 rounded-md bg-teal-600 text-white hover:bg-teal-700"
              @click="onRenameOption(opt.id)"
            >OK</button>
            <button
              class="text-sm px-3 py-1 rounded-md border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700"
              @click="cancelEditingLabel(opt)"
            >Annuler</button>
          </template>
          <template v-else>
            <span class="flex-1 text-gray-900 dark:text-gray-100">{{ opt.label }}</span>
            <button
              class="text-sm text-teal-700 dark:text-teal-400 hover:underline"
              @click="startEditingLabel(opt)"
            >Renommer</button>
            <button
              class="text-sm text-red-600 dark:text-red-400 hover:underline"
              @click="onRemoveOption(opt.id)"
            >Supprimer</button>
          </template>
        </li>
      </ul>

      <div class="flex gap-2 mt-3">
        <input
          v-model="newOptionLabel"
          type="text"
          maxlength="255"
          placeholder="Nouvelle option"
          class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
        />
        <button
          class="bg-teal-600 dark:bg-purple-900 text-white px-4 py-2 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition"
          @click="onAddOption"
        >
          Ajouter
        </button>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>
