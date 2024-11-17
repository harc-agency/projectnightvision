<script setup>
import { ErrorMessage } from 'vee-validate';
import { toValue } from 'vue';
import { useFormField } from './useFormField';

const { name, formMessageId } = useFormField();
</script>

<template>
<p
v-if="name && formMessageId"
:id="formMessageId"
class="text-sm font-medium text-destructive" >
    {{ toValue(name) }}
</p>
</template>
