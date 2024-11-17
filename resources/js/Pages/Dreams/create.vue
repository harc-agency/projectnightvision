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
        <Form @submit.prevent="form.post(route('dreams.store'))">

          <FormField name="title">
            <FormItem >
              <FormLabel>Title</FormLabel>
                <Input type="text" v-model="form.title" />
                <FormDescription>
                    Enter the title of your dream.
                </FormDescription>
            </FormItem>
          </FormField>

          <FormField name="dream_content">
            <FormItem >
                <FormLabel>Dream Content</FormLabel>
                  <Textarea v-model="form.dream_content" />
                  <FormDescription>
                      Enter the content of your dream.
                  </FormDescription>
            </FormItem>
          </FormField>


          <FormField>
            <FormItem>
                <Button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Submit
                </Button>
            </FormItem>
          </FormField>
        </Form>
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
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

import { Input } from '@/components/ui/input'

import { Textarea } from '@/components/ui/textarea'

import {
  FormDescription,
  FormField,
  FormItem,
  FormLabel
} from '@/components/ui/form'

import { Button } from '@/components/ui/button'

export default {
    components: {
    AuthenticatedLayout,

    Button,

    Card,
    CardContent,
    CardDescription,

    CardHeader,
    CardTitle,

    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    // FormMessage,

    Input,
    Textarea,
    },
    props: {
    },
    data() {
        return {
            form: this.$inertia.form({
                user_id: this.$page.props.auth.user.id || '',
                title: '',
                dream_content: '',
            }),
        };
    },
};


</script>

<style scoped>

</style>

