<template>
  <AuthenticatedLayout>
    <div class="pnv-shell">
      <div class="pnv-header">
        <div>
          <p class="pnv-eyebrow">Submission</p>
          <h1 class="pnv-title">Submit a Dream</h1>
          <p class="pnv-subtitle">
            Add dream text directly, or record audio and transcribe before submit.
          </p>
        </div>
      </div>

      <Card class="pnv-panel text-gray-100">
        <CardHeader>
          <CardTitle>Dream Intake</CardTitle>
          <CardDescription class="text-gray-300">
            Write your dream or record audio and transcribe it before submitting.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="submitDream" class="space-y-6">
            <FormField name="title">
              <FormItem>
                <FormLabel for="title">Title (Optional)</FormLabel>
                <Input id="title" v-model="form.title" placeholder="Untitled dream..." />
              </FormItem>
            </FormField>

            <div class="rounded-lg border border-slate-700 bg-slate-950/80 p-4">
              <label class="flex items-start gap-3">
                <input
                  v-model="form.is_public"
                  type="checkbox"
                  class="mt-1 h-4 w-4 rounded border-slate-500 bg-slate-900 text-sky-400 focus:ring-sky-500"
                />
                <span>
                  <span class="block text-sm font-medium text-slate-100">Share in the Public Library</span>
                  <span class="mt-1 block text-xs text-slate-400">
                    Enable this to include your dream in the community library and globe.
                  </span>
                </span>
              </label>
            </div>

            <div class="rounded-lg border border-slate-700 bg-slate-950/80 p-4">
              <p class="mb-3 text-sm font-medium text-gray-200">Audio Submission</p>

              <div class="flex flex-wrap gap-2">
                <Button
                  type="button"
                  variant="secondary"
                  :disabled="isRecording"
                  @click="startRecording"
                >
                  Record
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  :disabled="!isRecording"
                  @click="stopRecording"
                >
                  Stop
                </Button>
                <Button
                  type="button"
                  :disabled="!audioBlob || isTranscribing"
                  @click="transcribeRecording"
                >
                  {{ isTranscribing ? 'Transcribing...' : 'Transcribe Audio' }}
                </Button>
              </div>

              <p class="mt-3 text-xs text-gray-400">
                {{ recordingStatus }}
              </p>

              <audio
                v-if="recordedAudioUrl"
                :src="recordedAudioUrl"
                controls
                class="mt-4 w-full"
              />

              <p v-if="audioError" class="mt-3 text-sm text-red-400">
                {{ audioError }}
              </p>
            </div>

            <FormField name="dream_content">
              <FormItem>
                <FormLabel for="dream_content">Dream Content</FormLabel>
                <Textarea
                  id="dream_content"
                  v-model="form.dream_content"
                  rows="10"
                  placeholder="Describe your dream..."
                />
                <FormDescription class="text-gray-400">
                  Your dream text will be analyzed and linked to symbols in the library.
                </FormDescription>
                <p v-if="form.errors.dream_content" class="mt-2 text-sm text-red-400">
                  {{ form.errors.dream_content }}
                </p>
              </FormItem>
            </FormField>

            <div class="flex justify-end gap-3">
              <Button type="button" variant="secondary" as-child>
                <Link :href="route('dreams.index')">Cancel</Link>
              </Button>
              <Button type="submit" :disabled="form.processing || isTranscribing">
                {{ form.processing ? 'Submitting...' : 'Submit Dream' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { onBeforeUnmount, ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import {
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
} from '@/Components/ui/form'

const form = useForm({
  title: '',
  dream_content: '',
  dream_audio: null,
  is_public: false,
})

const isRecording = ref(false)
const isTranscribing = ref(false)
const audioBlob = ref(null)
const audioChunks = ref([])
const recordedAudioUrl = ref('')
const audioError = ref('')
const mediaRecorder = ref(null)
const mediaStream = ref(null)

const recordingStatus = computed(() => {
  if (isRecording.value) {
    return 'Recording in progress...'
  }

  if (audioBlob.value) {
    return 'Recording ready. Transcribe to fill dream content.'
  }

  return 'Press Record to capture an audio dream submission.'
})

const startRecording = async () => {
  audioError.value = ''
  audioBlob.value = null
  form.dream_audio = null

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    audioError.value = 'Audio recording is not supported in this browser.'
    return
  }

  try {
    mediaStream.value = await navigator.mediaDevices.getUserMedia({ audio: true })
    audioChunks.value = []

    const recorder = new MediaRecorder(mediaStream.value)
    mediaRecorder.value = recorder

    recorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        audioChunks.value.push(event.data)
      }
    }

    recorder.onstop = () => {
      if (audioChunks.value.length === 0) {
        form.dream_audio = null
        return
      }

      audioBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' })
      form.dream_audio = new File(
        [audioBlob.value],
        `dream-recording-${Date.now()}.webm`,
        { type: 'audio/webm' },
      )

      if (recordedAudioUrl.value) {
        URL.revokeObjectURL(recordedAudioUrl.value)
      }

      recordedAudioUrl.value = URL.createObjectURL(audioBlob.value)
    }

    recorder.start()
    isRecording.value = true
  } catch (error) {
    audioError.value = 'Microphone access was denied or unavailable.'
  }
}

const stopRecording = () => {
  if (!mediaRecorder.value || mediaRecorder.value.state === 'inactive') {
    return
  }

  mediaRecorder.value.stop()
  isRecording.value = false

  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop())
    mediaStream.value = null
  }
}

const transcribeRecording = async () => {
  if (!audioBlob.value) {
    return
  }

  audioError.value = ''
  isTranscribing.value = true

  try {
    const payload = new FormData()
    payload.append('audio', audioBlob.value, 'dream-recording.webm')

    const response = await window.axios.post(route('dreams.transcribe'), payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    const transcript = response.data?.transcript?.trim?.() || ''
    if (!transcript) {
      throw new Error('Transcript was empty.')
    }

    form.dream_content = transcript
  } catch (error) {
    audioError.value = 'Transcription failed. Please retry or enter text manually.'
  } finally {
    isTranscribing.value = false
  }
}

const submitDream = () => {
  form.post(route('dreams.store'), {
    forceFormData: true,
  })
}

onBeforeUnmount(() => {
  if (recordedAudioUrl.value) {
    URL.revokeObjectURL(recordedAudioUrl.value)
  }

  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop())
  }
})
</script>
