<script setup>
  import { watch } from 'vue';
  import { useFetchApi } from './composables/useFetchApi';
  import { usePolling } from './composables/usePolling';

  const props = defineProps({
    loginUrl: { type: String, default: null },
  });

  const { fetchApiToRef } = useFetchApi();

  const { data: getResult, error: getError, fetchNow } = fetchApiToRef({ url: '/foo' });
  const { data: postResult, error: postError } = fetchApiToRef({ url: '/foo', data: { id: 1 } });

  function handleError(err) {
    if (!err) return;
    if (err?.status === 401) {
      window.location.href = props.loginUrl;
    } else {
      console.error(err);
    }
  }

  watch(getError, handleError);
  watch(postError, handleError);

  usePolling(fetchNow);
</script>

<template>
  <h1>Builder</h1>

  <section>
    <h2>GET /api/v1/foo</h2>
    <pre v-if="getResult">{{ getResult }}</pre>
    <p v-else>Chargement...</p>
  </section>

  <section>
    <h2>POST /api/v1/foo</h2>
    <pre v-if="postResult">{{ postResult }}</pre>
    <p v-else>Chargement...</p>
  </section>
</template>

<style scoped>
  section {
    margin-top: 1rem;
  }
</style>