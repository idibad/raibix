<script setup>
import { computed, onMounted } from 'vue';

const props = defineProps({
    tenant: Object,
    challan: Object,
});

onMounted(() => {
    // Automatically open print dialog after a brief delay to ensure styles are loaded
    setTimeout(() => {
        window.print();
    }, 500);
});

// We render 3 identical copies: Bank Copy, School Copy, Student Copy
const copies = [
    { title: 'Bank Copy', id: 'bank' },
    { title: 'School Copy', id: 'school' },
    { title: 'Student Copy', id: 'student' }
];

const totalAmount = computed(() => {
    return (
        parseFloat(props.challan.tuition_fee) +
        parseFloat(props.challan.admission_fee) +
        parseFloat(props.challan.registration_fee) +
        parseFloat(props.challan.security_fee) +
        parseFloat(props.challan.lab_fee) +
        parseFloat(props.challan.transport_fee) +
        parseFloat(props.challan.library_fine) +
        parseFloat(props.challan.fine_amount) -
        parseFloat(props.challan.discount_amount)
    ).toFixed(2);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <div class="print-container min-h-screen bg-white text-black font-sans">
        <!-- Action bar hidden in print -->
        <div class="no-print bg-slate-100 p-4 flex justify-between items-center shadow-sm border-b">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Fee Challan: {{ challan.challan_number }}</h1>
                <p class="text-sm text-slate-500">For best results, set margins to "None" and paper size to A4 (Portrait).</p>
            </div>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                <i class="fa-solid fa-print mr-2"></i> Print Now
            </button>
        </div>

        <!-- The A4 Page Wrapper -->
        <div class="page-wrapper max-w-[210mm] mx-auto bg-white flex flex-col justify-between" style="min-height: 297mm; padding: 10mm 15mm;">
            
            <div v-for="(copy, idx) in copies" :key="copy.id" class="flex-1 flex flex-col justify-center relative py-4">
                
                <!-- Dashed cut line except for the last item -->
                <div v-if="idx > 0" class="absolute top-0 left-0 w-full border-t-2 border-dashed border-gray-400 my-2 flex items-center justify-center">
                    <span class="bg-white px-2 text-gray-400 text-[10px] uppercase tracking-widest -mt-3"><i class="fa-solid fa-scissors"></i> Cut Here</span>
                </div>

                <div class="border-2 border-gray-800 rounded-lg p-4 flex flex-col h-full bg-white relative">
                    <!-- Copy Badge -->
                    <div class="absolute top-0 right-0 bg-gray-800 text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-sm">
                        {{ copy.title }}
                    </div>

                    <!-- Header Section -->
                    <div class="flex items-center gap-4 border-b-2 border-gray-800 pb-3 mb-3">
                        <div v-if="tenant?.logo" class="h-14 w-14 border border-gray-300 p-1 rounded-md">
                            <img :src="tenant.logo" class="h-full w-full object-contain grayscale" />
                        </div>
                        <div class="flex-1">
                            <h2 class="text-xl font-black uppercase tracking-tight text-black leading-none">{{ tenant?.name || 'School Name' }}</h2>
                            <p class="text-xs text-gray-600 mt-1 font-semibold">Official Fee Collection Voucher</p>
                        </div>
                        <div class="text-right">
                            <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${challan.challan_number}`" class="h-14 w-14 inline-block" alt="QR Code" />
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="flex justify-between text-xs mb-3">
                        <div class="space-y-1">
                            <div><span class="font-bold inline-block w-20">Student Name:</span> <span class="font-semibold">{{ challan.student?.student_name }}</span></div>
                            <div><span class="font-bold inline-block w-20">Class/Grade:</span> <span class="font-semibold">{{ challan.student?.current_class?.name || 'N/A' }}</span></div>
                            <div><span class="font-bold inline-block w-20">Challan ID:</span> <span class="font-bold">{{ challan.challan_number }}</span></div>
                        </div>
                        <div class="space-y-1 text-right">
                            <div><span class="font-bold inline-block w-20">Billing Month:</span> <span class="font-semibold uppercase">{{ challan.month_year }}</span></div>
                            <div><span class="font-bold inline-block w-20">Issue Date:</span> <span class="font-semibold">{{ formatDate(challan.issue_date) }}</span></div>
                            <div class="text-red-700 bg-red-50 px-1"><span class="font-bold inline-block w-20">Due Date:</span> <span class="font-extrabold">{{ formatDate(challan.due_date) }}</span></div>
                        </div>
                    </div>

                    <!-- Fee Breakdown Table -->
                    <table class="w-full text-xs border-collapse border border-gray-800 mb-2">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-800 text-left p-1.5 font-bold w-2/3">Fee Particulars</th>
                                <th class="border border-gray-800 text-right p-1.5 font-bold">Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-800 p-1 px-2">Tuition Fee</td>
                                <td class="border border-gray-800 p-1 px-2 text-right">{{ Number(challan.tuition_fee).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="challan.lab_fee > 0">
                                <td class="border border-gray-800 p-1 px-2">Lab / Practical Charges</td>
                                <td class="border border-gray-800 p-1 px-2 text-right">{{ Number(challan.lab_fee).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="challan.transport_fee > 0">
                                <td class="border border-gray-800 p-1 px-2">Transport Charges</td>
                                <td class="border border-gray-800 p-1 px-2 text-right">{{ Number(challan.transport_fee).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="challan.fine_amount > 0">
                                <td class="border border-gray-800 p-1 px-2">Overdue / Late Fine</td>
                                <td class="border border-gray-800 p-1 px-2 text-right">{{ Number(challan.fine_amount).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="challan.discount_amount > 0">
                                <td class="border border-gray-800 p-1 px-2 italic text-gray-600">Less: Scholarship / Discount</td>
                                <td class="border border-gray-800 p-1 px-2 text-right italic text-gray-600">-{{ Number(challan.discount_amount).toFixed(2) }}</td>
                            </tr>
                            <tr class="bg-gray-100 font-extrabold text-sm">
                                <td class="border border-gray-800 p-1.5 px-2 text-right">TOTAL PAYABLE:</td>
                                <td class="border border-gray-800 p-1.5 px-2 text-right">{{ formatCurrency(totalAmount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Footer Details -->
                    <div class="mt-auto flex justify-between items-end text-[10px] text-gray-600 pt-2 border-t border-gray-300">
                        <div>
                            <p><strong>Bank Details:</strong> Deposit at any branch of Meezan Bank Ltd.</p>
                            <p><strong>Account Title:</strong> {{ tenant?.name || 'School Name' }}</p>
                        </div>
                        <div class="text-right flex items-end gap-4">
                            <div class="border-t border-gray-800 w-32 text-center pt-1 font-bold">Cashier / Bank Stamp</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .print-container {
        background-color: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .page-wrapper {
        box-shadow: none !important;
        border: none !important;
        width: 210mm !important;
        height: 297mm !important;
        padding: 0mm !important;
        margin: 0 !important;
    }
    @page {
        size: A4 portrait;
        margin: 5mm; /* Very small margin so it fits */
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
