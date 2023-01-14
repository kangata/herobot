<template>
    <main class="relative flex flex-1 flex-col overflow-hidden py-8 px-4 sm:px-6 lg:px-8">
        <div class="absolute inset-x-0 top-[-10rem] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[-20rem]"><svg class="relative left-[calc(50%-11rem)] -z-10 h-[21.1875rem] max-w-none -translate-x-1/2 rotate-[30deg] sm:left-[calc(50%-30rem)] sm:h-[42.375rem]" viewBox="0 0 1155 678" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="url(#45de2b6b-92d5-4d68-a6a0-9b9b2abad533)" fill-opacity=".3" d="M317.219 518.975L203.852 678 0 438.341l317.219 80.634 204.172-286.402c1.307 132.337 45.083 346.658 209.733 145.248C936.936 126.058 882.053-94.234 1031.02 41.331c119.18 108.451 130.68 295.337 121.53 375.223L855 299l21.173 362.054-558.954-142.079z"></path><defs><linearGradient id="45de2b6b-92d5-4d68-a6a0-9b9b2abad533" x1="1155.49" x2="-78.208" y1=".177" y2="474.645" gradientUnits="userSpaceOnUse"><stop stop-color="#9089FC"></stop><stop offset="1" stop-color="#FF80B5"></stop></linearGradient></defs></svg></div>
        <div
            class="absolute inset-0 text-slate-900/[0.07] [mask-image:linear-gradient(to_bottom_left,white,transparent,transparent)]">
            <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid-bg" width="32" height="32" patternUnits="userSpaceOnUse" x="100%"
                        patternTransform="translate(0 -1)">
                        <path d="M0 32V.5H32" fill="none" stroke="currentColor"></path>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid-bg)"></rect>
            </svg>
        </div>
        <div class="relative flex flex-1 flex-col items-center justify-center pt-12 pb-16">
            <div class="mx-auto mb-16 w-auto text-slate-900">
                <slot name="logo" />
            </div>
            <h1 class="sr-only">Log in to your Tailwind UI account</h1>
            <form action="/login" class="w-full max-w-sm">
                <div class="mb-6"><label for="email" class="block text-sm font-semibold leading-6 text-gray-900">Email
                        address</label><input type="email" id="email"
                        class="mt-2 appearance-none text-slate-900 bg-white rounded-md block w-full px-3 h-10 shadow-sm sm:text-sm focus:outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500 ring-1 ring-slate-200"
                        required="" value=""></div>
                <div class="mb-6"><label for="password"
                        class="block text-sm font-semibold leading-6 text-gray-900">Password</label><input
                        type="password" id="password"
                        class="mt-2 appearance-none text-slate-900 bg-white rounded-md block w-full px-3 h-10 shadow-sm sm:text-sm focus:outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-sky-500 ring-1 ring-slate-200"
                        required="" value=""></div><button type="submit"
                    class="inline-flex justify-center rounded-lg text-sm font-semibold py-2.5 px-4 bg-slate-900 text-white hover:bg-slate-700 w-full"><span>Sign
                        in to account</span></button>
                <p class="mt-8 text-center"><a href="/password/reset" class="text-sm hover:underline">Forgot
                        password?</a></p>
            </form>
        </div>
        <footer class="relative shrink-0">
            <div
                class="space-y-4 text-sm text-gray-900 sm:flex sm:items-center sm:justify-center sm:space-y-0 sm:space-x-4">
                <p class="text-center sm:text-left">Don't have an account?</p><a
                    class="inline-flex justify-center rounded-lg text-sm font-semibold py-2.5 px-4 text-slate-900 ring-1 ring-slate-900/10 hover:ring-slate-900/20"
                    href="/all-access"><span>Get access <span aria-hidden="true">→</span></span></a>
            </div>
        </footer>
    </main>
</template>
