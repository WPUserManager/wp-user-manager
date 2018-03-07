<template>
	<div id="wpum-login-form-wrapper">
		<div v-if="loading">loading</div>
		<div v-html="form" v-on:click.capture="handleClick"></div>
  	</div>
</template>

<script>

</script>
