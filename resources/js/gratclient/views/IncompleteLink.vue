<template>
  <div class="incomplete">
    <h1>Link incomplete</h1>
    <p>Please use the full link provided by your instructor (e.g., <code>/tbl/&lt;code&gt;</code>).</p>
    <p>If you’re missing the link, contact your instructor.</p>
  </div>
</template>

<script>
// Simple fallback for incomplete student links so routing failures are clear.
export default {
  name: 'IncompleteLink',
}
</script>

<style scoped>
.incomplete {
  max-width: 600px;
  margin: 80px auto;
  text-align: center;
  font-family: Arial, sans-serif;
  padding: 0 16px;
}
h1 {
  font-size: 28px;
  margin-bottom: 12px;
}
p {
  margin: 6px 0;
  color: #333;
}
code {
  background: #f2f2f2;
  padding: 2px 6px;
  border-radius: 4px;
}
</style>
