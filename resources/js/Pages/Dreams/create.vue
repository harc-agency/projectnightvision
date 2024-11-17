<template>
  <AuthenticatedLayout>
    <div class="sm:w-[640px] flex flex-col justify-center mx-auto bg-gray-800 rounded-lg   shadow">
      <div class="px-4 py-5 sm:p-6">
        <Card>
          <CardHeader>
            <CardTitle>Create a Dream</CardTitle>
            <CardDescription>
              Fill out the form below to create a new dream entry.
            </CardDescription>
          </CardHeader>

          <CardContent>

            <form @submit.prevent="form.post(route('dreams.store'))">
              <div class="flex justify-between space-x-4 mb-6">
                <FormField name="title">
                  <FormItem>
                    <Input type="text" placeholder="Title" v-model="form.title" />
                  </FormItem>
                </FormField>

                <FormField name="dream_date">
                  <FormItem>
                    <Popover>
                      <PopoverTrigger as-child>
                        <Button variant="outline" :class="[
                          'w-[180px] justify-start text-left font-normal',
                          !form.dream_date && 'text-muted-foreground',
                        ]">
                          <CalendarIcon class="mr-2 h-4 w-4" />
                          {{ form.dream_date ? new Date(form.dream_date).toLocaleDateString() : "Dream Date" }}
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent class="w-auto p-0">

                        <Calendar v-model="form.dream_date" initial-focus />
                      </PopoverContent>
                    </Popover>

                  </FormItem>
                </FormField>
              </div>
              <FormField name="dream_content">
                <FormItem>
                  <FormLabel for="dream_content">Dream Content</FormLabel>
                  <Textarea v-model="form.dream_content" />
                  <FormDescription>
                    Enter the content of your dream.
                  </FormDescription>
                </FormItem>
              </FormField>


              <div class="flex justify-between mt-6">
                <Button>
                  Submit
                </Button>
                <Button variant="secondary">
                  Cancel
                </Button>
              </div>


            </form>
          </CardContent>
        </Card>
      </div>
    </div>

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
} from '@/Components/ui/card'

import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'

import { Button } from '@/Components/ui/button'
import { Calendar } from '@/Components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'

import { DateFormatter, getLocalTimeZone } from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'

import {
  FormDescription,
  FormField,
  FormItem,
  FormLabel
} from '@/Components/ui/form'




export default {
  components: {
    AuthenticatedLayout,

    Button,
    Calendar,

    Card,
    CardContent,
    CardDescription,

    CardHeader,
    CardTitle,

    FormDescription,
    FormField,
    FormItem,
    FormLabel,


    Button,
    Calendar,
    Popover,
    PopoverContent,
    PopoverTrigger,
    CalendarIcon,


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
        dream_date: '',
      }),
      df: new DateFormatter('en-US', {
        dateStyle: 'long',
      }),
    };
  },
};


</script>

<style scoped></style>
