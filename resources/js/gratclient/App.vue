<template>
  <div id="app">
    <a href="#client-main" class="skip-link">Skip to content</a>
    <main id="client-main" role="main">
      <router-view/>
    </main>
  </div>
</template>

<script>
export default {
  name: 'AppRoot'
}
</script>
<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}

:global(.md-body img) {
  max-width: 100%;
  height: auto;
}

.skip-link {
  position: absolute;
  top: -40px;
  left: 8px;
  padding: 8px 12px;
  background: #fff;
  color: #000;
  border: 1px solid #000;
  z-index: 10000;
}
.skip-link:focus {
  top: 8px;
}
</style>
