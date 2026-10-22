<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    certificates: Array,
});

onMounted(() => {
    // Automatically trigger print dialog when loaded
    setTimeout(() => {
        window.print();
    }, 1000);
});
</script>

<template>
    <Head title="Print Certificates Bulk" />

    <div class="min-h-screen bg-gray-100 py-12 px-4 space-y-12 flex flex-col items-center justify-start print:bg-white print:py-0 print:px-0 print:space-y-0">
        <!-- Loop over certificates -->
        <div 
            v-for="cert in certificates" 
            :key="cert.id" 
            class="print-page bg-white p-6 shadow-lg border border-gray-200 rounded-2xl print:shadow-none print:border-none print:p-0 print:m-0"
        >
            <div class="print-certificate-container relative w-[750px] h-[520px] bg-white border-[12px] border-double border-indigo-950 p-10 flex flex-col justify-between items-center text-center font-serif print:border-indigo-955 print:mx-auto">
                <!-- Decorative corner elements -->
                <div class="absolute top-2 left-2 text-indigo-955 text-base font-bold select-none">✦</div>
                <div class="absolute top-2 right-2 text-indigo-955 text-base font-bold select-none">✦</div>
                <div class="absolute bottom-2 left-2 text-indigo-955 text-base font-bold select-none">✦</div>
                <div class="absolute bottom-2 right-2 text-indigo-955 text-base font-bold select-none">✦</div>

                <!-- Starburst golden crest seal -->
                <div class="absolute bottom-8 left-16 opacity-85 print:opacity-100 select-none pointer-events-none">
                    <div class="relative flex items-center justify-center">
                        <svg class="w-16 h-16 text-amber-500 fill-current" viewBox="0 0 100 100">
                            <polygon points="50,9 59,27 79,18 73,38 91,38 78,53 88,70 69,70 69,90 53,78 38,91 38,73 20,79 27,59 9,69 22,53 11,35 30,38 30,18" />
                        </svg>
                        <div class="absolute w-9 h-9 rounded-full border border-amber-600 flex items-center justify-center bg-amber-400">
                            <i class="fa-solid fa-graduation-cap text-amber-900 text-xs"></i>
                        </div>
                        <svg class="absolute top-12 left-1.5 w-6 h-8 text-amber-600 fill-current opacity-80" viewBox="0 0 24 24">
                            <path d="M4 2v18l8-4 8 4v-18z"/>
                        </svg>
                    </div>
                </div>

                <!-- Header -->
                <div class="space-y-2 mt-4">
                    <h1 class="text-2xl font-bold text-indigo-955 uppercase tracking-widest">{{ cert.template_name }}</h1>
                    <div class="h-0.5 w-40 bg-indigo-955 mx-auto my-1"></div>
                    <span class="text-3xs text-gray-400 font-sans tracking-widest font-bold">{{ ($page.props.tenant?.name || 'School Portal').toUpperCase() }}</span>
                </div>

                <!-- Parsed Content Body -->
                <div class="my-6 text-sm text-gray-700 leading-relaxed max-w-lg font-serif italic" v-html="cert.parsedContent">
                </div>

                <!-- Footer / Signatures -->
                <div class="w-full flex justify-between items-end px-6 mb-4">
                    <div class="flex flex-col items-center">
                        <div class="w-32 border-b border-gray-300 mb-1"></div>
                        <span class="text-4xs text-gray-400 font-sans">Verification Officer</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-indigo-955 text-sm font-bold italic mb-0.5">Authenticated</span>
                        <div class="w-32 border-b border-gray-300 mb-1"></div>
                        <span class="text-4xs text-gray-500 font-bold font-sans">Principal / Registrar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Print Button (Hidden during print) -->
        <button @click="window.print()" class="fixed bottom-6 right-6 px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-all print:hidden cursor-pointer flex items-center gap-2">
            <i class="fa-solid fa-print"></i>
            <span>Print All Certificates</span>
        </button>
    </div>
</template>

<style>
@media print {
    body {
        background: white;
    }
    .print-page {
        page-break-after: always;
        page-break-inside: avoid;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
