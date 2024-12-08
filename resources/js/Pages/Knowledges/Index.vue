<template>
  <AppLayout title="Knowledge">
    <div>
      <div class="sm:flex sm:items-center mb-4">
        <div class="sm:flex-auto">
          <h3 class="text-base font-semibold leading-6 text-gray-900">Knowledge</h3>
          <p class="mt-2 text-sm text-gray-700">Create, edit, and delete knowledge.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none" v-if="true">
          <PrimaryButton :href="route('knowledges.create')">
            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
            Add knowledge
          </PrimaryButton>
        </div>
      </div>

      <KnowledgeList :knowledges="$page.props.knowledges" />
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { PlusIcon } from '@heroicons/vue/20/solid';
import KnowledgeList from './Partials/KnowledgeList.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
</script>

