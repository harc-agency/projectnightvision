<template>
  <AuthenticatedLayout>

    <Card>
    <CardHeader>
      <CardTitle>Create a Dream</CardTitle>
      <CardDescription>
        Fill out the form below to create a new dream entry.
      </CardDescription>
    </CardHeader>
    <CardContent>
       <form
      @submit.prevent="form.post(route('dreams.store'))">


      <FormField name="title">
      <FormItem v-auto-animate>
        <FormLabel>Title</FormLabel>
        <FormControl>
          <Input type="text" v-bind="form.title" />
        </FormControl>
        <FormDescription>
          Enter the title of your dream.
        </FormDescription>
        <FormMessage />
      </FormItem>
    </FormField>



    <FormField  v-slot="{ componentField }"  name="dream_content">
        <FormItem v-auto-animate>
            <FormLabel>Dream Content</FormLabel>
            <FormControl>
                <Textarea v-model="form.dream_content"></Textarea>
            </FormControl>
            <FormDescription>
                Enter the content of your dream.
            </FormDescription>
            <FormMessage />
        </FormItem>
    </FormField>


        <div>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            type="submit">Submit</button>
        </div>

  </form>
    </CardContent>

  </Card>




  </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/Textarea'
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'

export default {
    components: {
        AuthenticatedLayout,

        Card,
        CardContent,
        CardDescription,

        CardHeader,
        CardTitle,

        FormControl,
        FormDescription,
        FormField,
        FormItem,
        FormLabel,
        FormMessage,

        Input,
        Textarea,
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

