<template>
  <AuthenticatedLayout>
    <h1 class="text-2xl font-semibold text-white">Create a Dream</h1>

      <form
      @submit.prevent="form.post(route('dreams.store'))">

        <div>
            <label for="title">Title</label>
              <input type="text" v-model="form.title">
        </div>

        <div>
            <label for="dream_content">Dream Content</label>
              <textarea v-model="form.dream_content"></textarea>
        </div>
        <div>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            type="submit">Submit</button>
        </div>

  </form>

  </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

export default {
    components: {
        AuthenticatedLayout,
    },
    props: {
    },
    data() {
        return {
            form: this.$inertia.form({
                user_id: this.$page.props.auth.user.id,
                title: '',
                dream_content: '',
            }),
        };
    },
};


</script>

<style scoped>

</style>

