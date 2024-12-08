<template>
  <AppLayout :title="isEditing ? 'Knowledge Update' : 'Knowledge Create'">
    <form @submit.prevent="submit" class="w-full max-w-4xl">
      <div class="space-y-12">
        <div class="mb-6">
          <h2 class="text-base font-semibold leading-7 text-gray-900">
            {{ isEditing ? 'Update knowledge' : 'Create a new knowledge' }}
          </h2>
          <p class="mt-1 text-sm leading-6 text-gray-600">
            {{ isEditing ? 'Update the information of your knowledge.' : 'This information will be used to create your knowledge.' }}
          </p>
        </div>
      </div>

      <div class="mb-6">
        <InputLabel for="name" value="Name" />
        <TextInput id="name" v-model="form.name" type="text" required autofocus />
        <InputError class="mt-2" :message="form.errors.name" />
      </div>

      <div class="mb-6">
        <InputLabel for="type" value="Type" />
        <fieldset class="mt-2">
          <legend class="sr-only">Type</legend>
          <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
            <div v-for="item in knowledgeTypes" :key="item.id" class="flex items-center">
              <input :id="item.id" v-model="form.type" :value="item.id" name="type" type="radio"
                class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-600" />
              <label :for="item.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">
                {{ item.title }}
              </label>
            </div>
          </div>
        </fieldset>
        <InputError class="mt-2" :message="form.errors.type" />
      </div>

      <div class="mb-6">
        <InputLabel for="data" value="Data" />
        <TextArea id="data" v-model="form.text" type="text" required />
        <InputError :message="form.errors.text" class="mt-2" />

      <!-- Status Badge (for edit mode) -->
      <div class="mt-6" v-if="knowledge && knowledge.status">
        <InputLabel value="Indexing Status" />

        <p class="mt-2 text-sm text-gray-500">
          This content will be automatically split and indexed for better search results.
        </p>

        <div class="mt-1">
          <StatusBadge :status="knowledge.status" />
          <span v-if="knowledge.status === 'failed'" class="ml-2 text-sm text-red-600">
            Indexing failed. The content will be re-indexed when you save changes.
          </span>
        </div>
      </div>

      </div>

      <div class="flex flex-row text-right items-center">
        <SecondaryButton class="mr-2" @click="goBack">
          Cancel
        </SecondaryButton>
        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
          {{ isEditing ? 'Save' : 'Create' }}
        </PrimaryButton>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextArea from '@/Components/TextArea.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  knowledge: {
    type: Object,
    default: () => ({
      id: null,
      name: '',
      type: 'text',
      text: '',
    }),
  },
  bot_id: {
    type: [String, Number],
    default: null,
  }
});

const isEditing = !!props.knowledge.id;

const form = useForm({
  name: props.knowledge.name,
  type: props.knowledge.type,
  text: props.knowledge.text,
  bot_id: props.bot_id,
});

const knowledgeTypes = [
  { id: 'text', title: 'Text' },
  // { id: 'qa', title: 'Question & Answer' },
  // { id: 'file', title: 'File' },
];

const submit = () => {
  if (isEditing) {
    form.put(route('knowledges.update', props.knowledge.id));
  } else {
    form.post(route('knowledges.store'));
  }
};

const goBack = () => {
  history.back();
};
</script>