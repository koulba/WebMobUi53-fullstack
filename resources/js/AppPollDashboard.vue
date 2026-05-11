<script setup>
  import { ref } from 'vue';
  import PollTable from './components/PollTable.vue';
  import PollForm from './components/PollForm.vue';
  import PollEditor from './components/PollEditor.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    polls: { type: Array, default: () => [] },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
  });

  const { setPolls } = usePollStore();
  setPolls(props.polls);

  const showForm = ref(false);
  const editingPoll = ref(null);

  function openCreate() {
    editingPoll.value = null;
    showForm.value = true;
  }

  function onCreated() {
    showForm.value = false;
  }

  function onEdit(poll) {
    showForm.value = false;
    editingPoll.value = poll;
  }

  function onUpdated() {
    editingPoll.value = null;
  }
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes sondages</h1>
      <button
        v-if="!showForm && !editingPoll"
        class="bg-teal-600 dark:bg-purple-900 text-white px-4 py-2 rounded-md hover:bg-teal-700 dark:hover:bg-purple-800 transition"
        @click="openCreate"
      >
        + Nouveau sondage
      </button>
    </header>

    <PollForm v-if="showForm" @created="onCreated" @cancel="showForm = false" />

    <PollEditor
      v-if="editingPoll"
      :poll="editingPoll"
      @updated="onUpdated"
      @closed="editingPoll = null"
    />

    <PollTable @edit="onEdit" />
  </div>
</template>
