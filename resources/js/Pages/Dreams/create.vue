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
                <FormLabel for="title">Title</FormLabel>
                <Input
                  id="title"
                  v-model="form.title"
                  required
                  placeholder="Midnight train platform"
                />
                <p v-if="form.errors.title" class="mt-2 text-sm text-red-400">
                  {{ form.errors.title }}
                </p>
              </FormItem>
            </FormField>

            <FormField name="dream_location">
              <FormItem>
                <FormLabel for="dream_location">Location (Optional)</FormLabel>
                <div class="relative">
                  <Input
                    id="dream_location"
                    v-model="form.dream_location"
                    autocomplete="off"
                    placeholder="Denver, Colorado"
                    @focus="handleLocationInputFocus"
                    @blur="handleLocationInputBlur"
                  />
                  <div
                    v-if="shouldShowLocationPredictions"
                    class="location-predictor"
                  >
                    <button
                      v-for="prediction in locationPredictions"
                      :key="`${prediction.label}-${prediction.lat}-${prediction.lng}`"
                      type="button"
                      class="location-predictor__item"
                      @mousedown.prevent="applyLocationPrediction(prediction)"
                    >
                      <span class="location-predictor__label">{{ prediction.label }}</span>
                      <span class="location-predictor__meta">
                        {{ prediction.lat.toFixed(2) }}, {{ prediction.lng.toFixed(2) }}
                      </span>
                    </button>
                  </div>
                </div>
                <FormDescription class="text-gray-400">
                  {{ locationDescription }}
                </FormDescription>
                <label
                  v-if="shouldOfferProfileLocationSave"
                  class="mt-3 flex items-start gap-3 rounded-lg border border-slate-700/80 bg-slate-900/70 px-3 py-3"
                >
                  <input
                    v-model="form.save_location_to_profile"
                    type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-slate-500 bg-slate-900 text-sky-400 focus:ring-sky-500"
                  />
                  <span>
                    <span class="block text-sm font-medium text-slate-100">
                      {{ saveLocationToProfileLabel }}
                    </span>
                    <span class="mt-1 block text-xs text-slate-400">
                      Future dream submissions will start with this location already filled in.
                    </span>
                  </span>
                </label>
                <p v-if="geolocationStatus" class="mt-2 text-sm text-sky-300">
                  {{ geolocationStatus }}
                </p>
                <p v-if="isPredictingLocation" class="mt-2 text-sm text-sky-300">
                  Searching for a valid mapped location...
                </p>
                <p v-else-if="locationPredictionStatus" class="mt-2 text-sm text-emerald-300">
                  {{ locationPredictionStatus }}
                </p>
                <p v-if="locationPredictionError" class="mt-2 text-sm text-amber-300">
                  {{ locationPredictionError }}
                </p>
                <p v-if="geolocationError" class="mt-2 text-sm text-amber-300">
                  {{ geolocationError }}
                </p>
                <p v-if="form.errors.dream_location" class="mt-2 text-sm text-red-400">
                  {{ form.errors.dream_location }}
                </p>
                <p
                  v-else-if="form.errors.location || form.errors['location.lat'] || form.errors['location.lng']"
                  class="mt-2 text-sm text-red-400"
                >
                  {{ form.errors.location || form.errors['location.lat'] || form.errors['location.lng'] }}
                </p>
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

              <div
                class="audio-mixer"
                :class="{ 'audio-mixer--active': isRecording }"
              >
                <div class="audio-mixer__meta">
                  <div class="audio-mixer__status">
                    <span class="audio-mixer__dot" />
                    <span>{{ mixerStatusLabel }}</span>
                  </div>
                  <span class="audio-mixer__level">{{ liveInputPercent }}%</span>
                </div>

                <div class="audio-mixer__bars" aria-hidden="true">
                  <span
                    v-for="(level, index) in mixerLevels"
                    :key="`mixer-bar-${index}`"
                    class="audio-mixer__bar"
                    :style="{ '--bar-level': level.toFixed(3) }"
                  />
                </div>
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
              <Button type="submit" :disabled="form.processing || isTranscribing || isResolvingLocation">
                {{ submitLabel }}
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
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
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

const mixerWeights = [0.34, 0.48, 0.66, 0.84, 1, 0.78, 0.56, 0.88, 1, 0.74, 0.52, 0.38]

const createMixerLevels = (baseLevel = 0.08) => {
  return mixerWeights.map((weight) => {
    return Number((baseLevel * (0.58 + weight * 0.42)).toFixed(3))
  })
}

const normalizeLocationText = (value) => {
  if (typeof value !== 'string') {
    return ''
  }

  return value.trim().replace(/\s+/g, ' ')
}

const page = usePage()
const locationPredictorSource = 'location_predictor'
const savedDreamLocation = computed(() => {
  return normalizeLocationText(page.props.auth?.user?.preferred_dream_location)
})

const form = useForm({
  title: '',
  dream_location: savedDreamLocation.value,
  dream_content: '',
  dream_audio: null,
  save_location_to_profile: false,
  location: null,
  is_public: false,
})

const isRecording = ref(false)
const isTranscribing = ref(false)
const isResolvingLocation = ref(false)
const audioBlob = ref(null)
const audioChunks = ref([])
const recordedAudioUrl = ref('')
const audioError = ref('')
const geolocationStatus = ref('')
const geolocationError = ref('')
const isPredictingLocation = ref(false)
const locationPredictionStatus = ref('')
const locationPredictionError = ref('')
const locationPredictions = ref([])
const isLocationInputFocused = ref(false)
const mediaRecorder = ref(null)
const mediaStream = ref(null)
const audioContext = ref(null)
const analyserNode = ref(null)
const analyserData = ref(null)
const analyserSource = ref(null)
const mixerFrameId = ref(null)
const liveInputLevel = ref(0)
const mixerLevels = ref(createMixerLevels())
let locationPredictionTimer = null
let locationBlurTimer = null
let locationPredictionAbortController = null

const recordingStatus = computed(() => {
  if (isRecording.value) {
    return 'Recording in progress...'
  }

  if (audioBlob.value) {
    return 'Recording ready. Transcribe to fill dream content.'
  }

  return 'Press Record to capture an audio dream submission.'
})

const mixerStatusLabel = computed(() => {
  if (isRecording.value) {
    return 'Mic live'
  }

  if (audioBlob.value) {
    return 'Recording captured'
  }

  return 'Mic idle'
})

const liveInputPercent = computed(() => {
  return Math.round(liveInputLevel.value * 100)
})

const locationDescription = computed(() => {
  if (savedDreamLocation.value) {
    return 'Prefilled from your profile. Start typing and choose a valid city or region. If left blank, the browser will request geolocation before submit as a fallback.'
  }

  return 'Start typing a city or region and pick a valid mapped location. If left blank, the browser will request geolocation before submit as a fallback.'
})

const shouldOfferProfileLocationSave = computed(() => {
  const dreamLocation = normalizeLocationText(form.dream_location)

  return dreamLocation !== '' && dreamLocation !== savedDreamLocation.value
})

const saveLocationToProfileLabel = computed(() => {
  if (savedDreamLocation.value) {
    return 'Update my saved profile location'
  }

  return 'Save this location to my profile'
})

const submitLabel = computed(() => {
  if (form.processing) {
    return 'Submitting...'
  }

  if (isResolvingLocation.value) {
    return 'Locating...'
  }

  return 'Submit Dream'
})

const shouldShowLocationPredictions = computed(() => {
  return isLocationInputFocused.value && locationPredictions.value.length > 0
})

const hasLocationCoordinates = (location) => {
  const lat = Number(location?.lat)
  const lng = Number(location?.lng)

  return Number.isFinite(lat)
    && lat >= -90
    && lat <= 90
    && Number.isFinite(lng)
    && lng >= -180
    && lng <= 180
}

const currentResolvedLocationLabel = () => {
  return normalizeLocationText(form.location?.label)
}

const clearLocationPredictionTimer = () => {
  if (locationPredictionTimer !== null) {
    window.clearTimeout(locationPredictionTimer)
    locationPredictionTimer = null
  }
}

const cancelLocationPredictionRequest = () => {
  if (locationPredictionAbortController) {
    locationPredictionAbortController.abort()
    locationPredictionAbortController = null
  }
}

const dismissLocationPredictions = () => {
  locationPredictions.value = []
}

const buildPredictedLocationPayload = (prediction) => {
  return {
    lat: Number(Number(prediction.lat).toFixed(6)),
    lng: Number(Number(prediction.lng).toFixed(6)),
    label: normalizeLocationText(prediction.label),
    source: normalizeLocationText(prediction.source) || locationPredictorSource,
    captured_at: new Date().toISOString(),
  }
}

const normalizePredictionList = (payload) => {
  if (!Array.isArray(payload)) {
    return []
  }

  return payload
    .map((prediction) => {
      const lat = Number(prediction?.lat)
      const lng = Number(prediction?.lng)
      const label = normalizeLocationText(prediction?.label)

      if (!label || !Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null
      }

      return {
        label,
        lat,
        lng,
        source: normalizeLocationText(prediction?.source) || locationPredictorSource,
      }
    })
    .filter(Boolean)
}

const fetchLocationPredictions = async (query) => {
  const normalizedQuery = normalizeLocationText(query)

  clearLocationPredictionTimer()
  cancelLocationPredictionRequest()

  if (normalizedQuery.length < 2) {
    isPredictingLocation.value = false
    dismissLocationPredictions()
    return []
  }

  const controller = new AbortController()
  locationPredictionAbortController = controller
  isPredictingLocation.value = true

  try {
    const response = await window.axios.get(route('locations.predict'), {
      params: { q: normalizedQuery },
      signal: controller.signal,
    })

    const predictions = normalizePredictionList(response.data?.data)

    if (normalizeLocationText(form.dream_location) === normalizedQuery) {
      locationPredictions.value = predictions
    }

    return predictions
  } catch (error) {
    if (error?.name === 'CanceledError' || error?.code === 'ERR_CANCELED') {
      return []
    }

    dismissLocationPredictions()
    locationPredictionError.value = 'Location search is unavailable right now.'
    return []
  } finally {
    if (locationPredictionAbortController === controller) {
      locationPredictionAbortController = null
    }

    isPredictingLocation.value = false
  }
}

const scheduleLocationPredictionLookup = (query) => {
  const normalizedQuery = normalizeLocationText(query)

  clearLocationPredictionTimer()
  cancelLocationPredictionRequest()

  if (normalizedQuery.length < 2) {
    dismissLocationPredictions()
    isPredictingLocation.value = false
    return
  }

  locationPredictionTimer = window.setTimeout(() => {
    fetchLocationPredictions(normalizedQuery)
  }, 280)
}

const applyLocationPrediction = (prediction, statusMessage = null) => {
  const payload = buildPredictedLocationPayload(prediction)

  form.dream_location = payload.label
  form.location = payload
  geolocationStatus.value = ''
  geolocationError.value = ''
  locationPredictionError.value = ''
  locationPredictionStatus.value = statusMessage ?? `Mapped to ${payload.label}.`
  dismissLocationPredictions()
}

const resolveTypedLocation = async () => {
  const dreamLocation = normalizeLocationText(form.dream_location)
  const resolvedLabel = currentResolvedLocationLabel()

  if (dreamLocation === '') {
    return true
  }

  if (hasLocationCoordinates(form.location) && resolvedLabel === dreamLocation) {
    locationPredictionError.value = ''
    return true
  }

  locationPredictionError.value = ''
  locationPredictionStatus.value = 'Validating your location...'
  const predictions = await fetchLocationPredictions(dreamLocation)
  const bestMatch = predictions.find((prediction) => {
    return normalizeLocationText(prediction.label) === dreamLocation
  }) ?? predictions[0]

  if (!bestMatch) {
    locationPredictionStatus.value = ''
    locationPredictionError.value = 'Choose a clearer city or region so the dream can be mapped on the globe.'
    return false
  }

  applyLocationPrediction(bestMatch, `Validated ${normalizeLocationText(bestMatch.label)}.`)

  return true
}

const handleLocationInputFocus = () => {
  if (locationBlurTimer !== null) {
    window.clearTimeout(locationBlurTimer)
    locationBlurTimer = null
  }

  isLocationInputFocused.value = true
}

const handleLocationInputBlur = () => {
  locationBlurTimer = window.setTimeout(() => {
    isLocationInputFocused.value = false
    dismissLocationPredictions()
    locationBlurTimer = null
  }, 120)
}

watch(
  () => form.dream_location,
  (value) => {
    const dreamLocation = normalizeLocationText(value)
    const resolvedLabel = currentResolvedLocationLabel()

    if (dreamLocation === '' || dreamLocation === savedDreamLocation.value) {
      form.save_location_to_profile = false
    }

    if (dreamLocation === '') {
      locationPredictionStatus.value = ''
      locationPredictionError.value = ''
      clearLocationPredictionTimer()
      cancelLocationPredictionRequest()
      isPredictingLocation.value = false
      dismissLocationPredictions()

      if (form.location?.source === locationPredictorSource) {
        form.location = null
      }

      return
    }

    geolocationError.value = ''
    geolocationStatus.value = ''

    if (form.location?.source === 'browser_geolocation') {
      form.location = null
    }

    if (form.location?.source === locationPredictorSource && resolvedLabel !== dreamLocation) {
      form.location = null
    }

    if (hasLocationCoordinates(form.location) && resolvedLabel === dreamLocation) {
      locationPredictionError.value = ''
      dismissLocationPredictions()
      return
    }

    locationPredictionStatus.value = ''
    locationPredictionError.value = ''
    scheduleLocationPredictionLookup(dreamLocation)
  },
)

const stopMixerAnimation = () => {
  if (mixerFrameId.value !== null) {
    cancelAnimationFrame(mixerFrameId.value)
    mixerFrameId.value = null
  }
}

const resetMixer = () => {
  stopMixerAnimation()
  liveInputLevel.value = 0
  mixerLevels.value = createMixerLevels()
}

const teardownAudioMeter = () => {
  stopMixerAnimation()

  if (analyserSource.value) {
    analyserSource.value.disconnect()
    analyserSource.value = null
  }

  if (analyserNode.value) {
    analyserNode.value.disconnect()
    analyserNode.value = null
  }

  analyserData.value = null

  if (audioContext.value) {
    audioContext.value.close().catch(() => {})
    audioContext.value = null
  }

  resetMixer()
}

const tickAudioMeter = () => {
  if (!analyserNode.value || !analyserData.value) {
    resetMixer()
    return
  }

  analyserNode.value.getByteTimeDomainData(analyserData.value)

  let sumSquares = 0

  for (const sample of analyserData.value) {
    const normalizedSample = (sample - 128) / 128
    sumSquares += normalizedSample * normalizedSample
  }

  const rms = Math.sqrt(sumSquares / analyserData.value.length)
  const targetLevel = Math.min(1, Math.max(0, rms * 6.5))
  liveInputLevel.value = liveInputLevel.value * 0.72 + targetLevel * 0.28

  mixerLevels.value = mixerWeights.map((weight) => {
    const floor = isRecording.value ? 0.12 : 0.08
    const level = floor + liveInputLevel.value * weight

    return Number(Math.min(1, level).toFixed(3))
  })

  mixerFrameId.value = requestAnimationFrame(tickAudioMeter)
}

const startAudioMeter = async (stream) => {
  teardownAudioMeter()

  const AudioContextConstructor = window.AudioContext || window.webkitAudioContext

  if (!AudioContextConstructor) {
    resetMixer()
    return
  }

  const context = new AudioContextConstructor()
  const source = context.createMediaStreamSource(stream)
  const analyser = context.createAnalyser()

  analyser.fftSize = 256
  analyser.smoothingTimeConstant = 0.85
  source.connect(analyser)

  audioContext.value = context
  analyserSource.value = source
  analyserNode.value = analyser
  analyserData.value = new Uint8Array(analyser.fftSize)

  if (context.state === 'suspended') {
    await context.resume()
  }

  tickAudioMeter()
}

const startRecording = async () => {
  audioError.value = ''
  audioBlob.value = null
  form.dream_audio = null
  teardownAudioMeter()

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    audioError.value = 'Audio recording is not supported in this browser.'
    return
  }

  try {
    mediaStream.value = await navigator.mediaDevices.getUserMedia({ audio: true })
    audioChunks.value = []

    try {
      await startAudioMeter(mediaStream.value)
    } catch (error) {
      teardownAudioMeter()
    }

    const recorder = new MediaRecorder(mediaStream.value)
    mediaRecorder.value = recorder

    recorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        audioChunks.value.push(event.data)
      }
    }

    recorder.onstop = () => {
      mediaRecorder.value = null

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
    teardownAudioMeter()
    mediaRecorder.value = null

    if (mediaStream.value) {
      mediaStream.value.getTracks().forEach((track) => track.stop())
      mediaStream.value = null
    }
  }
}

const stopRecording = () => {
  if (!mediaRecorder.value || mediaRecorder.value.state === 'inactive') {
    return
  }

  mediaRecorder.value.stop()
  mediaRecorder.value = null
  isRecording.value = false
  teardownAudioMeter()

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

const requestBrowserLocation = () => {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported in this browser.'))
      return
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 600000,
    })
  })
}

const captureBackupLocation = async () => {
  if (form.dream_location.trim() || hasLocationCoordinates(form.location)) {
    return
  }

  geolocationError.value = ''
  geolocationStatus.value = 'Requesting your current location as a backup...'

  try {
    const position = await requestBrowserLocation()

    form.location = {
      lat: Number(position.coords.latitude.toFixed(6)),
      lng: Number(position.coords.longitude.toFixed(6)),
      accuracy: Math.round(position.coords.accuracy),
      source: 'browser_geolocation',
      captured_at: new Date().toISOString(),
    }

    geolocationStatus.value = 'Current coordinates captured for this submission.'
  } catch (error) {
    form.location = null
    geolocationStatus.value = ''
    geolocationError.value = 'Location access was unavailable. Your dream will still submit without a mapped location.'
  }
}

const submitDream = async () => {
  const dreamLocation = normalizeLocationText(form.dream_location)

  isResolvingLocation.value = true

  if (dreamLocation !== '') {
    const isValidLocation = await resolveTypedLocation()

    isResolvingLocation.value = false

    if (!isValidLocation) {
      return
    }
  } else if (!hasLocationCoordinates(form.location)) {
    isResolvingLocation.value = true
    await captureBackupLocation()
    isResolvingLocation.value = false
  } else {
    isResolvingLocation.value = false
  }

  form.post(route('dreams.store'), {
    forceFormData: true,
  })
}

onBeforeUnmount(() => {
  if (recordedAudioUrl.value) {
    URL.revokeObjectURL(recordedAudioUrl.value)
  }

  clearLocationPredictionTimer()

  if (locationBlurTimer !== null) {
    window.clearTimeout(locationBlurTimer)
    locationBlurTimer = null
  }

  cancelLocationPredictionRequest()
  teardownAudioMeter()

  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop())
  }
})
</script>

<style scoped>
.audio-mixer {
  margin-top: 1rem;
  overflow: hidden;
  border-radius: 0.9rem;
  border: 1px solid rgba(71, 85, 105, 0.9);
  background:
    radial-gradient(circle at top, rgba(14, 165, 233, 0.18), transparent 58%),
    linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(2, 6, 23, 0.96));
  padding: 0.9rem 1rem 1rem;
  transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}

.audio-mixer--active {
  border-color: rgba(56, 189, 248, 0.65);
  box-shadow: 0 0 0 1px rgba(14, 165, 233, 0.18), 0 18px 35px rgba(2, 132, 199, 0.14);
}

.audio-mixer__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgb(148 163 184);
}

.audio-mixer__status {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.audio-mixer__dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 9999px;
  background: rgba(148, 163, 184, 0.8);
  box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
  transition: background-color 120ms ease, box-shadow 120ms ease;
}

.audio-mixer--active .audio-mixer__dot {
  background: rgb(34 211 238);
  box-shadow: 0 0 0 0.45rem rgba(34, 211, 238, 0.14);
}

.audio-mixer__level {
  font-variant-numeric: tabular-nums;
  color: rgb(226 232 240);
}

.audio-mixer__bars {
  margin-top: 0.95rem;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  align-items: end;
  gap: 0.4rem;
  height: 5.25rem;
}

.audio-mixer__bar {
  height: calc(0.85rem + var(--bar-level) * 4rem);
  border-radius: 999px 999px 0.35rem 0.35rem;
  background: linear-gradient(180deg, rgba(103, 232, 249, 0.95), rgba(56, 189, 248, 0.82) 45%, rgba(14, 116, 144, 0.9) 100%);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22), 0 0 18px rgba(56, 189, 248, 0.12);
  opacity: 0.56;
  transform-origin: bottom center;
  transition: height 80ms linear, opacity 120ms ease, transform 120ms ease;
}

.audio-mixer--active .audio-mixer__bar {
  opacity: 0.96;
  transform: translateY(calc((1 - var(--bar-level)) * 1px));
}

.location-predictor {
  position: absolute;
  inset: calc(100% + 0.4rem) 0 auto;
  z-index: 20;
  overflow: hidden;
  border-radius: 0.9rem;
  border: 1px solid rgba(51, 65, 85, 0.95);
  background: rgba(2, 6, 23, 0.98);
  box-shadow: 0 18px 40px rgba(2, 6, 23, 0.55);
}

.location-predictor__item {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  border-bottom: 1px solid rgba(30, 41, 59, 0.9);
  padding: 0.9rem 1rem;
  text-align: left;
  transition: background-color 140ms ease;
}

.location-predictor__item:last-child {
  border-bottom: 0;
}

.location-predictor__item:hover {
  background: rgba(15, 23, 42, 0.96);
}

.location-predictor__label {
  color: rgb(226 232 240);
}

.location-predictor__meta {
  font-size: 0.75rem;
  color: rgb(148 163 184);
}

@media (max-width: 640px) {
  .audio-mixer {
    padding-inline: 0.85rem;
  }

  .audio-mixer__bars {
    gap: 0.28rem;
    height: 4.6rem;
  }

  .location-predictor__item {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
