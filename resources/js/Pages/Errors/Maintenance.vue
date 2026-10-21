<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({
    message: {
        type: String,
        default: 'LMS Cloud is currently undergoing scheduled system upgrades and maintenance to improve services. We will be back online shortly.'
    }
});
</script>

<template>
    <Head title="Under Maintenance | LMS Cloud" />
    
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-center p-6 select-none font-sans relative overflow-hidden">
        <!-- Background Ambient Glow -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-md w-full text-center space-y-8 relative z-10">
            <!-- Icon / Animation -->
            <div class="flex justify-center">
                <div class="relative">
                    <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-xl scale-125 animate-pulse"></div>
                    <div class="h-24 w-24 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-4xl text-indigo-400 relative z-10 shadow-lg">
                        <i class="fa-solid fa-screwdriver-wrench animate-bounce"></i>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-400 animate-ping"></span>
                    Scheduled Maintenance
                </span>
                <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    Under Maintenance
                </h1>
                <p class="text-sm text-slate-400 leading-relaxed max-w-sm mx-auto">
                    {{ message }}
                </p>
            </div>

            <!-- Visual Ticker of Systems -->
            <div class="bg-slate-900/40 border border-slate-800/50 rounded-2xl p-5 text-left space-y-3.5 shadow-sm">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">System Status</h3>
                
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-300 font-medium">Central Cloud Nodes</span>
                        <span class="text-indigo-400 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-spinner animate-spin"></i> Upgrading
                        </span>
                    </div>
                    <div class="w-full bg-slate-850 rounded-full h-1.5 border border-slate-800">
                        <div class="bg-indigo-500 h-1.5 rounded-full animate-pulse" style="width: 75%"></div>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs pt-1.5">
                        <span class="text-slate-300 font-medium">Tenant Databases</span>
                        <span class="text-emerald-400 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle text-[8px] animate-pulse"></i> Backed Up
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer Notes -->
            <p class="text-xs text-slate-500">
                Please check back in a few minutes. We apologize for any inconvenience.
            </p>
        </div>
    </div>
</template>
