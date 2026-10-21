<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    exam: Object,
    marks: Array,
    tenant: Object,
});

const printReport = () => {
    window.print();
};

onMounted(() => {
    if (window.location.search.includes('download=pdf') || window.location.search.includes('print=true')) {
        setTimeout(() => {
            window.print();
        }, 600);
    }
});

const totalScore = props.marks?.reduce((acc, m) => acc + (parseFloat(m.total_marks_obtained) || (parseFloat(m.theory_marks_obtained || 0) + parseFloat(m.practical_marks_obtained || 0) + parseFloat(m.viva_marks_obtained || 0))), 0) || 0;
const totalMax = (props.marks?.length || 0) * 100;
const cgpa = props.marks?.length > 0 
    ? (props.marks.reduce((acc, m) => acc + (parseFloat(m.gpa) || 4.0), 0) / props.marks.length).toFixed(2) 
    : '4.00';
</script>

<template>
    <div class="min-h-screen bg-[#525659] py-8 px-4 sm:px-6 lg:px-8 print:bg-white print:py-0 print:px-0 font-sans text-[#202124]">
        <!-- Top Action Bar for screen -->
        <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-2xl shadow-md print:hidden border border-[#DADCE0]">
            <div class="flex items-center space-x-3">
                <Link :href="route('tenant.student.dashboard')" class="text-sm font-bold text-[#1A73E8] hover:underline flex items-center gap-1"><i class="fa-solid fa-arrow-left"></i> <span>Back to Dashboard</span></Link>
                <span class="text-gray-300">|</span>
                <span class="text-sm font-semibold text-[#5F6368]">Official Term Report Card</span>
            </div>
            <button @click="printReport" class="rounded-full bg-[#1A73E8] px-6 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center space-x-2 gap-1.5">
                <i class="fa-solid fa-print"></i> <span>Print / Download PDF</span>
            </button>
        </div>

        <!-- Printable Document Sheet -->
        <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-xl print:shadow-none print:rounded-none border border-[#DADCE0] print:border-none">
            <!-- Header Block -->
            <div class="flex flex-col sm:flex-row items-center justify-between border-b-2 border-[#202124] pb-6 mb-6 gap-4">
                <div class="flex items-center space-x-4">
                    <div v-if="tenant?.logo" class="h-16 w-16 flex items-center justify-center">
                        <img :src="tenant.logo" alt="School Logo" class="max-h-full max-w-full object-contain" />
                    </div>
                    <div v-else class="h-16 w-16 rounded-2xl bg-[#E8F0FE] flex items-center justify-center font-extrabold text-2xl text-[#1A73E8] border border-[#D2E3FC]">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-[#202124] tracking-tight uppercase">{{ tenant?.name || 'Academic Institute' }}</h1>
                        <p class="text-xs font-semibold text-[#5F6368]">{{ $page.props.platformName || 'Excellence' }} in Education | Official Examination Board</p>
                    </div>
                </div>
                <div class="text-right sm:text-right text-center">
                    <span class="bg-[#E8F0FE] text-[#1A73E8] border border-[#D2E3FC] text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-widest">
                        Official Transcript
                    </span>
                    <h2 class="text-lg font-bold text-[#202124] mt-2">{{ exam?.name || 'Term Examination Report' }}</h2>
                    <p class="text-2xs text-[#5F6368] font-mono">Generated: {{ new Date().toLocaleDateString() }}</p>
                </div>
            </div>

            <!-- Student Profile Grid -->
            <div class="bg-[#F8F9FA] rounded-2xl p-6 border border-[#DADCE0] mb-8 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block font-bold text-[#5F6368] uppercase tracking-wider text-2xs">Student Name</span>
                    <strong class="text-sm text-[#202124]">{{ student?.user?.name || 'Student Name' }}</strong>
                </div>
                <div>
                    <span class="block font-bold text-[#5F6368] uppercase tracking-wider text-2xs">Admission No.</span>
                    <strong class="text-sm font-mono text-[#202124]">{{ student?.admission_number || 'ADM-1001' }}</strong>
                </div>
                <div>
                    <span class="block font-bold text-[#5F6368] uppercase tracking-wider text-2xs">Class & Section</span>
                    <strong class="text-sm text-[#202124]">{{ student?.current_class?.name || 'Class 10' }} - Section {{ student?.current_section?.name || 'A' }}</strong>
                </div>
                <div>
                    <span class="block font-bold text-[#5F6368] uppercase tracking-wider text-2xs">Roll Number</span>
                    <strong class="text-sm font-mono text-[#202124]">{{ student?.roll_number || '101' }}</strong>
                </div>
            </div>

            <!-- Marks Roster Table -->
            <div class="overflow-x-auto mb-8">
                <table class="w-full text-left border-collapse border border-[#DADCE0]">
                    <thead>
                        <tr class="bg-[#F8F9FA] border-b border-[#DADCE0] text-2xs font-extrabold uppercase tracking-wider text-[#5F6368]">
                            <th class="py-3 px-4 border-r border-[#DADCE0]">Subject Name</th>
                            <th class="py-3 px-4 border-r border-[#DADCE0] text-center">Theory</th>
                            <th class="py-3 px-4 border-r border-[#DADCE0] text-center">Practical</th>
                            <th class="py-3 px-4 border-r border-[#DADCE0] text-center">Viva</th>
                            <th class="py-3 px-4 border-r border-[#DADCE0] text-center">Total Obtained</th>
                            <th class="py-3 px-4 border-r border-[#DADCE0] text-center">Grade</th>
                            <th class="py-3 px-4 text-center">Subject GPA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DADCE0] text-xs">
                        <tr v-for="m in marks" :key="m.id" class="hover:bg-[#F8F9FA]">
                            <td class="py-3.5 px-4 font-bold text-[#202124] border-r border-[#DADCE0]">{{ m.subject?.name || 'Subject' }}</td>
                            <td class="py-3.5 px-4 text-center border-r border-[#DADCE0]">{{ m.theory_marks_obtained ?? m.theory ?? 0 }}</td>
                            <td class="py-3.5 px-4 text-center border-r border-[#DADCE0]">{{ m.practical_marks_obtained ?? m.practical ?? 0 }}</td>
                            <td class="py-3.5 px-4 text-center border-r border-[#DADCE0]">{{ m.viva_marks_obtained ?? m.viva ?? 0 }}</td>
                            <td class="py-3.5 px-4 text-center font-extrabold text-[#1A73E8] border-r border-[#DADCE0]">
                                {{ m.total_marks_obtained ?? (parseFloat(m.theory_marks_obtained||0) + parseFloat(m.practical_marks_obtained||0) + parseFloat(m.viva_marks_obtained||0)) }} / 100
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold border-r border-[#DADCE0]">
                                <span class="bg-[#E6F4EA] text-[#137333] border border-[#CEEAD6] px-2 py-0.5 rounded-md text-2xs">{{ m.grade || 'A+' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-extrabold text-[#202124]">{{ m.gpa || '4.00' }}</td>
                        </tr>
                        <tr v-if="!marks || marks.length === 0">
                            <td colspan="7" class="py-8 text-center text-[#5F6368] italic">No subject marks recorded for this examination term.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Block -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">
                <div class="bg-[#F8F9FA] rounded-2xl p-5 border border-[#DADCE0] text-center">
                    <span class="text-2xs font-bold uppercase tracking-wider text-[#5F6368]">Total Aggregate Score</span>
                    <p class="text-2xl font-black text-[#202124] mt-1">{{ totalScore }} <span class="text-xs font-normal text-[#5F6368]">/ {{ totalMax > 0 ? totalMax : 100 }}</span></p>
                </div>
                <div class="bg-[#E8F0FE] rounded-2xl p-5 border border-[#D2E3FC] text-center">
                    <span class="text-2xs font-bold uppercase tracking-wider text-[#1A73E8]">Term Grade Point Average (CGPA)</span>
                    <p class="text-3xl font-black text-[#1A73E8] mt-1">{{ cgpa }} <span class="text-xs font-normal text-[#1A73E8]">/ 4.00</span></p>
                </div>
                <div class="bg-[#E6F4EA] rounded-2xl p-5 border border-[#CEEAD6] text-center">
                    <span class="text-2xs font-bold uppercase tracking-wider text-[#137333]">Academic Standing</span>
                    <p class="text-xl font-black text-[#137333] mt-2">PROMOTED WITH DISTINCTION</p>
                </div>
            </div>

            <!-- Signature & Stamp Sign-off Block -->
            <div class="grid grid-cols-3 gap-8 pt-12 border-t border-[#DADCE0] text-center text-xs">
                <div>
                    <div class="h-12 border-b border-[#202124] mx-4"></div>
                    <span class="font-bold text-[#202124] block mt-2">Class Teacher Signature</span>
                </div>
                <div>
                    <div class="h-12 border-b border-[#202124] mx-4 flex items-end justify-center pb-1">
                        <span class="text-2xs text-[#5F6368] uppercase tracking-widest">[ Official School Stamp ]</span>
                    </div>
                    <span class="font-bold text-[#202124] block mt-2">Examination Controller</span>
                </div>
                <div>
                    <div class="h-12 border-b border-[#202124] mx-4"></div>
                    <span class="font-bold text-[#202124] block mt-2">Principal Signature</span>
                </div>
            </div>
        </div>
    </div>
</template>
