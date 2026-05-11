<script setup>
import { ref } from 'vue';
import { onMounted } from 'vue';
import { useFetchApi } from './composables/useFetchApi';

const props = defineProps({ token: String });
const poll = ref (null);
const loading = ref(true);
const error = ref(null);

onMounted(async() =>{
  const {fetchApi} = useFetchApi();
  const data = await fetchApi({url: 'polls/' + props.token});
  if (data) {
    poll.value = data;
  }else {
    error.value = 'Sondage introuvable';
  }
  loading.value = false;
});

</script>
<template>
<p v-if="loading">Chargement</p>
<p v-else-if="error">{{ error }}</p>
<div v-else>
  <h1>{{ poll.question }}</h1>
  <ul>
    <li v-for="opt in poll.options" :key="opt.id">
     {{ opt.label }} — {{ opt.votes_count }} votes
    </li>
  </ul>
</div>
</template>