<template>
    <div>
        <TransitionRoot as="template" :show="open">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="$emit('close')">
                <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0"
                    enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100"
                    leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-900/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild as="template" enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full" enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
                        leave-to="-translate-x-full">
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0"
                                enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100"
                                leave-to="opacity-0">
                                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                                    <button type="button" class="-m-2.5 p-2.5" @click="$emit('close')">
                                        <span class="sr-only">Close sidebar</span>
                                        <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                                    </button>
                                </div>
                            </TransitionChild>
                            <!-- Sidebar component, swap this element with another sidebar if you like -->
                            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
                                <div class="flex h-16 shrink-0 items-center">
                                    <ApplicationLogo class="block h-9 w-auto" />
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                        <li>
                                            <ul role="list" class="-mx-2 space-y-1">
                                                <li v-for="item in navigation" :key="item.name">
                                                    <Link :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                                    <component :is="item.icon"
                                                        :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                                        aria-hidden="true" />
                                                    {{ item.name }}
                                                    </Link>
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <div class="text-xs font-semibold leading-6 text-gray-400">Your teams</div>
                                            <ul role="list" class="-mx-2 mt-2 space-y-1">
                                                <li v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                    <a @click.prevent="switchToTeam(team)" href="#"
                                                        :class="[!route().current('teams.create') && team.id === $page.props.auth.user.current_team_id ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                                    <span
                                                        :class="[!route().current('teams.create') && team.id === $page.props.auth.user.current_team_id ? 'text-indigo-600 border-indigo-600' : 'text-gray-400 border-gray-200 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">{{
                                                            team.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                    <span class="truncate">{{ team.name }}</span>
                                                    </a>
                                                </li>
                                                <li v-if="$page.props.jetstream.canCreateTeams">
                                                    <Link :href="route('teams.create')"
                                                        :class="[route().current('teams.create') ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                                    <span
                                                        :class="[route().current('teams.create') ? 'text-indigo-600 border-indigo-600' : 'text-gray-400 border-gray-200 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">
                                                        +
                                                    </span>
                                                    <span class="truncate">Create New Team</span>
                                                    </Link>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="mt-auto">
                                            <Link :href="route('profile.show')"
                                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                                            <Cog6ToothIcon
                                                class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-indigo-600"
                                                aria-hidden="true" />
                                            Settings
                                            </Link>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <ApplicationLogo class="block h-9 w-auto" />
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li v-for="item in navigation" :key="item.name">
                                    <Link :href="item.href"
                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                    <component :is="item.icon"
                                        :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                        aria-hidden="true" />
                                    {{ item.name }}
                                    </Link>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <div class="text-xs font-semibold leading-6 text-gray-400">Your teams</div>
                            <ul role="list" class="-mx-2 mt-2 space-y-1">
                                <li v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                    <a @click.prevent="switchToTeam(team)" href="#"
                                        :class="[!route().current('teams.create') && team.id === $page.props.auth.user.current_team_id ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                    <span
                                        :class="[!route().current('teams.create') && team.id === $page.props.auth.user.current_team_id ? 'text-indigo-600 border-indigo-600' : 'text-gray-400 border-gray-200 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">{{
                                            team.name.charAt(0).toUpperCase() }}
                                    </span>
                                    <span class="truncate">{{ team.name }}</span>
                                    </a>
                                </li>
                                <li v-if="$page.props.jetstream.canCreateTeams">
                                    <Link :href="route('teams.create')"
                                        :class="[route().current('teams.create') ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                    <span
                                        :class="[route().current('teams.create') ? 'text-indigo-600 border-indigo-600' : 'text-gray-400 border-gray-200 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium bg-white']">
                                        +
                                    </span>
                                    <span class="truncate">Create New Team</span>
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <ul>
                        <li class="mt-auto" v-for="item in bottomNavigation" :key="item.name">
                            <Link :href="item.href"
                                :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:text-indigo-600 hover:bg-gray-50', 'group -mx-2 flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                <component :is="item.icon"
                                    :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']"
                                    aria-hidden="true" />
                                {{ item.name }}
                            </Link>
                        </li>
                  </ul>
                </nav>
            </div>
        </div>
    </div>
</template>
<script setup>
import { usePage, Link, router } from '@inertiajs/vue3';
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue'
import {
    ChartPieIcon,
    Cog6ToothIcon,
    DocumentDuplicateIcon,
    HomeIcon,
    UserGroupIcon,
    XMarkIcon,
    CreditCardIcon,
} from '@heroicons/vue/24/outline'
import { LinkIcon } from '@heroicons/vue/20/solid'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import BotIcon from '@/Assets/Icons/BotIcon.svg';

const page = usePage();

const navigation = [
    { name: 'Dashboard', href: route('dashboard'), icon: HomeIcon, current: route().current('dashboard') },
    { name: 'Bot Management', href: route('bots.index'), icon: BotIcon, current: route().current('bots*') },
    { name: 'Knowledge', href: route('knowledges.index'), icon: DocumentDuplicateIcon, current: route().current('knowledges*') },
    // { name: 'Reports', href: route('reports'), icon: ChartPieIcon, current: route().current('reports*') },
    { name: 'Channels', href: route('channels.index'), icon: LinkIcon, current: route().current('channels*') },
]

const bottomNavigation = [
    { name: 'Team Settings', href: route('teams.show', page.props.auth.user.current_team), icon: UserGroupIcon, current: route().current('teams.show') },
    { name: 'Settings', href: route('profile.show'), icon: Cog6ToothIcon, current: route().current('profile.show') },
    // { name: 'Billing & Usage', href: route('billing.index'), icon: CreditCardIcon, current: route().current('billing*') },
]

const switchToTeam = (team) => {
    if (team.id === page.props.auth.user.current_team_id) {
        return router.visit(route('teams.show', team));
    }

    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

defineProps({
    open: {
        type: Boolean,
        required: true,
    },
})
</script>