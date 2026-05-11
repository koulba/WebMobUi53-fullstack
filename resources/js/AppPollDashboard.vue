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
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Mes sondages</h1>
      <button v-if="!showForm && !editingPoll" class="bg-blue-600 text-white px-3 py-1 rounded" @click="openCreate">
        + Nouveau sondage
      </button>
    </div>

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
