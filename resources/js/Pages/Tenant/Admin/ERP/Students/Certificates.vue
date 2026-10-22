<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    templates: Array,
    issuedCertificates: Array,
    students: Array,
});

const page = usePage();

// Global Dialog/Notification State
const toastMessage = ref('');
const toastType = ref('success');
const showToast = ref(false);

const triggerToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

// Listen to page flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        triggerToast(flash.success, 'success');
    }
    if (flash?.error) {
        triggerToast(flash.error, 'error');
    }
}, { deep: true, immediate: true });

const activeTab = ref('generator'); // 'generator', 'templates', 'issued'
const showTemplateModal = ref(false);

// Live Generator State
const selectedStudentId = ref('');
const selectedTemplateId = ref('');
const customContent = ref('');
const issueDateInput = ref(new Date().toISOString().split('T')[0]);

// Find objects
const selectedStudent = computed(() => {
    return props.students.find(s => s.id === parseInt(selectedStudentId.value)) || null;
});

const selectedTemplate = computed(() => {
    return props.templates.find(t => t.id === parseInt(selectedTemplateId.value)) || null;
});

// Initialize custom text when template changes
watch(selectedTemplate, (newVal) => {
    if (newVal) {
        customContent.value = newVal.content;
    } else {
        customContent.value = '';
    }
});

// Parsed Content Preview with Safe HTML badging
const parsedContentPreview = computed(() => {
    if (!selectedTemplate.value) {
        return '<div class="text-gray-400 italic py-8">Please select an active student and select a certificate template from the left configuration panel to generate the live interactive preview.</div>';
    }
    let text = customContent.value;
    
    // HTML Escaping Helper
    const escapeHtml = (unsafe) => {
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    const student = selectedStudent.value;
    if (student) {
        const studentNameSpan = `<span class="text-indigo-950 font-bold border-b-2 border-indigo-950/40 px-3 uppercase tracking-wide font-sans text-sm inline-block mx-1 my-0.5 select-all bg-indigo-50/50 rounded-md">${escapeHtml(student.user?.name || '')}</span>`;
        const fatherNameSpan = `<span class="text-gray-900 font-bold border-b border-gray-300 px-1.5 inline-block mx-0.5">${escapeHtml(student.parent?.father_name || 'N/A')}</span>`;
        const rollNumSpan = `<span class="font-mono bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded text-2xs font-bold text-gray-700 mx-1 select-all">${escapeHtml(student.roll_number || 'N/A')}</span>`;
        const classNameSpan = `<span class="text-indigo-900 font-extrabold font-sans mx-0.5">${escapeHtml(student.current_class?.name || 'N/A')}</span>`;
        const sectionNameSpan = `<span class="text-indigo-900 font-extrabold font-sans mx-0.5">${escapeHtml(student.current_section?.name || 'N/A')}</span>`;
        
        text = text.replace(/\{\{student_name\}\}/g, studentNameSpan);
        text = text.replace(/\{\{father_name\}\}/g, fatherNameSpan);
        text = text.replace(/\{\{roll_number\}\}/g, rollNumSpan);
        text = text.replace(/\{\{class_name\}\}/g, classNameSpan);
        text = text.replace(/\{\{section_name\}\}/g, sectionNameSpan);
    } else {
        const placeholderSpan = `<span class="text-amber-600 font-semibold italic border-b border-dashed border-amber-400 px-2 mx-1 select-none">[Choose Student]</span>`;
        text = text.replace(/\{\{student_name\}\}/g, placeholderSpan);
        text = text.replace(/\{\{father_name\}\}/g, placeholderSpan);
        text = text.replace(/\{\{roll_number\}\}/g, placeholderSpan);
        text = text.replace(/\{\{class_name\}\}/g, placeholderSpan);
        text = text.replace(/\{\{section_name\}\}/g, placeholderSpan);
    }
    
    const formattedDate = new Date(issueDateInput.value).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
    const dateSpan = `<span class="font-semibold underline text-gray-900 mx-0.5">${escapeHtml(formattedDate)}</span>`;
    text = text.replace(/\{\{issue_date\}\}/g, dateSpan);
    
    const refNoSpan = `<span class="font-mono bg-gray-50 border border-gray-200 px-2 py-0.5 rounded text-3xs font-bold text-gray-600 select-all">CERT-${new Date().getFullYear()}-TEMP</span>`;
    text = text.replace(/\{\{reference_number\}\}/g, refNoSpan);
    
    const schoolName = page.props.tenant?.name || 'School Name';
    const schoolSpan = `<span class="font-extrabold text-indigo-950">${escapeHtml(schoolName)}</span>`;
    text = text.replace(/\{\{school_name\}\}/g, schoolSpan);
    
    return text;
});

// Forms
const templateForm = useForm({
    name: '',
    type: 'character',
    content: '',
    variables: ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}'],
});

const issueForm = useForm({
    student_id: '',
    template_id: '',
    issue_date: '',
});

// Bulk Issuance State
const selectedClassId = ref('');
const selectedSectionId = ref('');
const bulkSelectedStudentIds = ref([]);
const selectAllBulk = ref(true);

const classes = computed(() => {
    const list = [];
    const ids = new Set();
    props.students.forEach(s => {
        if (s.current_class && !ids.has(s.current_class.id)) {
            ids.add(s.current_class.id);
            list.push(s.current_class);
        }
    });
    return list;
});

const sections = computed(() => {
    if (!selectedClassId.value) return [];
    const list = [];
    const ids = new Set();
    props.students.forEach(s => {
        if (s.current_class_id === parseInt(selectedClassId.value) && s.current_section && !ids.has(s.current_section.id)) {
            ids.add(s.current_section.id);
            list.push(s.current_section);
        }
    });
    return list;
});

const filteredBulkStudents = computed(() => {
    if (!selectedClassId.value) return [];
    return props.students.filter(s => {
        const matchesClass = s.current_class_id === parseInt(selectedClassId.value);
        const matchesSection = !selectedSectionId.value || s.current_section_id === parseInt(selectedSectionId.value);
        return matchesClass && matchesSection;
    });
});

watch(filteredBulkStudents, (newStudents) => {
    if (selectAllBulk.value) {
        bulkSelectedStudentIds.value = newStudents.map(s => s.id);
    } else {
        bulkSelectedStudentIds.value = [];
    }
}, { immediate: true });

const toggleSelectAllBulk = () => {
    if (bulkSelectedStudentIds.value.length === filteredBulkStudents.value.length) {
        bulkSelectedStudentIds.value = [];
        selectAllBulk.value = false;
    } else {
        bulkSelectedStudentIds.value = filteredBulkStudents.value.map(s => s.id);
        selectAllBulk.value = true;
    }
};

const bulkIssueForm = useForm({
    student_ids: [],
    template_id: '',
    issue_date: new Date().toISOString().split('T')[0],
});

const submitBulkIssue = () => {
    bulkIssueForm.student_ids = bulkSelectedStudentIds.value;
    if (bulkIssueForm.student_ids.length === 0) {
        triggerToast('Please select at least one student.', 'error');
        return;
    }
    if (!bulkIssueForm.template_id) {
        triggerToast('Please select a certificate template.', 'error');
        return;
    }

    bulkIssueForm.post(route('tenant.admin.certificates.bulk-issue'), {
        onSuccess: () => {
            selectedClassId.value = '';
            selectedSectionId.value = '';
            bulkSelectedStudentIds.value = [];
        }
    });
};

const submitTemplate = () => {
    templateForm.post(route('tenant.admin.certificates.templates.store'), {
        onSuccess: () => {
            showTemplateModal.value = false;
            templateForm.reset();
            triggerToast('Certificate template created successfully!');
        }
    });
};

const handleGenerateAndPrint = () => {
    if (!selectedStudentId.value || !selectedTemplateId.value) {
        triggerToast('Please select both a student and a template first.', 'error');
        return;
    }
    
    issueForm.student_id = selectedStudentId.value;
    issueForm.template_id = selectedTemplateId.value;
    issueForm.issue_date = issueDateInput.value;
    
    issueForm.post(route('tenant.admin.certificates.issue'), {
        onSuccess: () => {
            triggerToast('Certificate successfully logged. Preparing printer...');
            setTimeout(() => {
                window.print();
            }, 500);
        }
    });
};

const handleQuickPrint = () => {
    if (!selectedStudentId.value || !selectedTemplateId.value) {
        triggerToast('Please select both a student and a template to print.', 'error');
        return;
    }
    window.print();
};

const loadPreset = (type) => {
    if (type === 'transfer') {
        templateForm.name = 'School Leaving / Transfer Certificate';
        templateForm.type = 'transfer';
        templateForm.content = 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has been a student of {{school_name}} in Class {{class_name}} (Section: {{section_name}}). Their conduct during their stay at this institution was exemplary. They are leaving the school on {{issue_date}} because of completion of studies / parental relocation.';
    } else if (type === 'character') {
        templateForm.name = 'Character Certificate';
        templateForm.type = 'character';
        templateForm.content = 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has successfully completed their academic term in Class {{class_name}} (Section: {{section_name}}). During their enrollment, they demonstrated excellent moral character, high discipline, responsibility, and an active participation in extracurricular activities.';
    } else if (type === 'appreciation') {
        templateForm.name = 'Certificate of Appreciation';
        templateForm.type = 'appreciation';
        templateForm.content = 'This is to certify that {{student_name}} son/daughter of {{father_name}} has demonstrated exceptional diligence, dedication, and academic brilliance in Class {{class_name}}. This certificate is awarded on {{issue_date}} in recognition of their outstanding performance and contribution to school life.';
    }
};
</script>

<template>
    <Head title="Certificate Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Certificate Center</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'generator'" :class="activeTab === 'generator' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-bold text-xs transition-all focus:outline-none uppercase tracking-wider">
                    Interactive Generator
                </button>
                <button @click="activeTab = 'bulk'" :class="activeTab === 'bulk' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-bold text-xs transition-all focus:outline-none uppercase tracking-wider">
                    Bulk Issuance
                </button>
                <button @click="activeTab = 'templates'" :class="activeTab === 'templates' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-bold text-xs transition-all focus:outline-none uppercase tracking-wider">
                    Templates Catalogue
                </button>
                <button @click="activeTab = 'issued'" :class="activeTab === 'issued' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-bold text-xs transition-all focus:outline-none uppercase tracking-wider">
                    Historic Issuance Logs
                </button>
            </div>

            <!-- TAB: GENERATOR -->
            <div v-if="activeTab === 'generator'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 print:block">
                <!-- Left Configuration Panel (Hidden in print) -->
                <div class="lg:col-span-5 space-y-6 print:hidden animate-fadeIn">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-2xs space-y-4">
                        <h3 class="font-bold text-gray-900 text-sm border-b border-gray-100 pb-3">Certificate Configuration</h3>
                        
                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Select Student</label>
                            <select v-model="selectedStudentId" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2.5 font-bold">
                                <option value="">Choose active student...</option>
                                <option v-for="student in students" :key="student.id" :value="student.id">
                                    {{ student.user?.name }} (Roll: {{ student.roll_number }}) - Grade: {{ student.current_class?.name || '-' }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Certificate Template</label>
                            <select v-model="selectedTemplateId" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2.5 font-bold">
                                <option value="">Choose layout template...</option>
                                <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">
                                    {{ tmpl.name }} ({{ tmpl.type }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Issue Date</label>
                            <input type="date" v-model="issueDateInput" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2 font-semibold" />
                        </div>
                    </div>

                    <div v-if="selectedTemplate" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-2xs space-y-4">
                        <h3 class="font-bold text-gray-900 text-sm border-b border-gray-100 pb-3">Wording Customizer</h3>
                        <p class="text-3xs text-amber-600 bg-amber-50 p-2 border border-amber-100 rounded-lg">
                            <i class="fa-solid fa-triangle-exclamation"></i> Editing this template's wording here updates the preview below dynamically without altering the base template.
                        </p>
                        <textarea v-model="customContent" rows="6" class="w-full rounded-xl border-gray-300 text-xs font-serif focus:ring-indigo-600 focus:border-indigo-600 p-3 leading-relaxed"></textarea>
                    </div>

                    <!-- Print Actions -->
                    <div class="flex gap-3">
                        <button @click="handleGenerateAndPrint" :disabled="!selectedStudentId || !selectedTemplateId" class="flex-1 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-print"></i> Save & Print Certificate
                        </button>
                        <button @click="handleQuickPrint" :disabled="!selectedStudentId || !selectedTemplateId" class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                            Quick Print
                        </button>
                    </div>
                </div>

                <!-- Right Live Preview Panel -->
                <div class="lg:col-span-7 flex justify-center print:block print:p-0">
                    <div class="print-certificate-container relative w-[750px] h-[520px] bg-white border-[12px] border-double border-indigo-950 p-10 shadow-lg flex flex-col justify-between items-center text-center font-serif print:shadow-none print:border-indigo-950 print:mx-auto">
                        <!-- Decorative corner elements -->
                        <div class="absolute top-2 left-2 text-indigo-955 text-base font-bold select-none">✦</div>
                        <div class="absolute top-2 right-2 text-indigo-955 text-base font-bold select-none">✦</div>
                        <div class="absolute bottom-2 left-2 text-indigo-955 text-base font-bold select-none">✦</div>
                        <div class="absolute bottom-2 right-2 text-indigo-955 text-base font-bold select-none">✦</div>

                        <!-- Starburst golden crest seal -->
                        <div v-if="selectedTemplate" class="absolute bottom-8 left-16 opacity-85 print:opacity-100 select-none pointer-events-none">
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

                        <div class="space-y-2 mt-4">
                            <h1 class="text-2xl font-bold text-indigo-950 uppercase tracking-widest">{{ selectedTemplate ? selectedTemplate.name : 'Certificate' }}</h1>
                            <div class="h-0.5 w-40 bg-indigo-950 mx-auto my-1"></div>
                            <span class="text-3xs text-gray-400 font-sans tracking-widest font-bold">{{ ($page.props.tenant?.name || 'School Portal').toUpperCase() }}</span>
                        </div>

                        <!-- Certificate Body text -->
                        <div v-html="parsedContentPreview" class="my-6 text-sm text-gray-700 leading-relaxed max-w-lg font-serif italic">
                        </div>

                        <!-- Authentications -->
                        <div class="w-full flex justify-between items-end px-6 mb-4">
                            <div class="flex flex-col items-center">
                                <div class="w-32 border-b border-gray-300 mb-1"></div>
                                <span class="text-4xs text-gray-400 font-sans">Verification Officer</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-indigo-950 text-sm font-bold italic mb-0.5">Authenticated</span>
                                <div class="w-32 border-b border-gray-300 mb-1"></div>
                                <span class="text-4xs text-gray-500 font-bold font-sans font-sans-serif">Principal / Registrar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: BULK GENERATOR -->
            <div v-if="activeTab === 'bulk'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-fadeIn">
                <!-- Left Configuration Panel -->
                <div class="lg:col-span-5 space-y-6">
                    <form @submit.prevent="submitBulkIssue" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-2xs space-y-4">
                        <h3 class="font-bold text-gray-900 text-sm border-b border-gray-100 pb-3">Bulk Issuance Settings</h3>
                        
                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Select Class</label>
                            <select v-model="selectedClassId" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2.5 font-bold" required>
                                <option value="">Choose class...</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Select Section (Optional)</label>
                            <select v-model="selectedSectionId" :disabled="!selectedClassId" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2.5 font-bold">
                                <option value="">All Sections</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    Section: {{ sec.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Certificate Template</label>
                            <select v-model="bulkIssueForm.template_id" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2.5 font-bold" required>
                                <option value="">Choose layout template...</option>
                                <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">
                                    {{ tmpl.name }} ({{ tmpl.type }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold text-gray-400 uppercase mb-1">Issue Date</label>
                            <input type="date" v-model="bulkIssueForm.issue_date" class="w-full rounded-xl border-gray-300 text-xs focus:ring-indigo-600 focus:border-indigo-600 py-2 font-semibold" required />
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="bulkSelectedStudentIds.length === 0 || !bulkIssueForm.template_id || bulkIssueForm.processing" class="w-full px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-print"></i> 
                                <span>{{ bulkIssueForm.processing ? 'Generating...' : `Save & Print ${bulkSelectedStudentIds.length} Certificates` }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Student Checklist Panel -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-2xs space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <h3 class="font-bold text-gray-900 text-sm">Target Students Checklist</h3>
                            <button 
                                v-if="filteredBulkStudents.length > 0"
                                @click="toggleSelectAllBulk" 
                                type="button"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-bold transition-colors cursor-pointer"
                            >
                                {{ bulkSelectedStudentIds.length === filteredBulkStudents.length ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>

                        <div class="max-h-[420px] overflow-y-auto space-y-2 pr-1">
                            <div v-if="!selectedClassId" class="text-center py-12 text-gray-400 text-xs">
                                <i class="fa-solid fa-user-group text-3xl mb-3 block text-gray-200"></i>
                                Please choose a Class from the left configuration panel to view and select students.
                            </div>
                            <div v-else-if="filteredBulkStudents.length === 0" class="text-center py-12 text-gray-400 text-xs">
                                <i class="fa-solid fa-face-frown text-3xl mb-3 block text-gray-200"></i>
                                No active students found matching this criteria.
                            </div>
                            <div 
                                v-for="student in filteredBulkStudents" 
                                :key="student.id" 
                                class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100/70 border border-gray-200 rounded-xl transition-all cursor-pointer select-none"
                                @click="bulkSelectedStudentIds.includes(student.id) ? bulkSelectedStudentIds = bulkSelectedStudentIds.filter(id => id !== student.id) : bulkSelectedStudentIds.push(student.id)"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox" 
                                        :value="student.id" 
                                        v-model="bulkSelectedStudentIds"
                                        @click.stop
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" 
                                    />
                                    <div>
                                        <div class="font-bold text-gray-900 text-xs">{{ student.user?.name }}</div>
                                        <div class="text-3xs text-gray-500 font-mono mt-0.5">Roll: {{ student.roll_number }} • Parent: {{ student.parent?.father_name || 'N/A' }}</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-0.5 text-3xs font-extrabold rounded-full border bg-white border-gray-200 text-gray-600">
                                    Section {{ student.current_section?.name || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: TEMPLATES -->
            <div v-if="activeTab === 'templates'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-sm">Templates Catalogue</h3>
                    <button @click="showTemplateModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs cursor-pointer">
                        + Add Template
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">
                    <div v-for="tmpl in templates" :key="tmpl.id" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex justify-between items-start">
                                <h4 class="text-base font-bold text-gray-900">{{ tmpl.name }}</h4>
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border capitalize bg-indigo-50 border-indigo-200 text-indigo-700">
                                    {{ tmpl.type }}
                                </span>
                            </div>
                            <p class="text-3xs text-gray-400 italic">Variables: {{ tmpl.variables ? tmpl.variables.join(', ') : 'None' }}</p>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-xs text-gray-600 max-h-36 overflow-y-auto whitespace-pre-wrap font-serif">
                                {{ tmpl.content }}
                            </div>
                        </div>
                    </div>
                    <div v-if="templates.length === 0" class="col-span-2 bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
                        No templates created. Please define a template (e.g. Character Certificate) first.
                    </div>
                </div>
            </div>

            <!-- TAB: LOGS -->
            <div v-if="activeTab === 'issued'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-sm">Certificate Issuance Log</h3>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden animate-fadeIn">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Reference No</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Student Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Certificate Template</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Issue Date</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="cert in issuedCertificates" :key="cert.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900 font-mono">{{ cert.reference_number }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ cert.student?.user?.name }}</td>
                                <td class="px-6 py-4 text-gray-500 capitalize">{{ cert.template?.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(cert.issue_date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a :href="route('tenant.admin.certificates.print', cert.id)" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 font-bold rounded shadow-3xs transition-all">
                                        Open & Print
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="issuedCertificates.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No certificates issued yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Template Modal -->
        <div v-if="showTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-2xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Define Certificate Template</h3>
                    <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                
                <!-- Quick Preset Loading Buttons -->
                <div class="mb-4 space-y-2">
                    <label class="block text-2xs font-bold text-gray-400 uppercase">Load Standard Presets</label>
                    <div class="flex gap-2">
                        <button type="button" @click="loadPreset('character')" class="px-3 py-1.5 bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] text-xs font-bold rounded-lg transition-all">
                            Character Certificate
                        </button>
                        <button type="button" @click="loadPreset('transfer')" class="px-3 py-1.5 bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] text-xs font-bold rounded-lg transition-all">
                            Leaving / Transfer
                        </button>
                        <button type="button" @click="loadPreset('appreciation')" class="px-3 py-1.5 bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] text-xs font-bold rounded-lg transition-all">
                            Appreciation Certificate
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitTemplate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Template Name</label>
                        <input type="text" v-model="templateForm.name" placeholder="Certificate Template Name" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Certificate Type</label>
                        <select v-model="templateForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="character">Character Certificate</option>
                            <option value="transfer">School Leaving / Transfer Certificate</option>
                            <option value="appreciation">Appreciation Certificate</option>
                            <option value="custom">Custom Template</option>
                        </select>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 space-y-1">
                        <p class="font-bold">Available Variables (Dynamic placeholders):</p>
                        <p class="font-mono">
                            {{templateForm.variables.join(', ')}}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Certificate Content HTML/Text</label>
                        <textarea v-model="templateForm.content" rows="8" placeholder="This is to certify that {{student_name}} son/daughter of {{father_name}} bearing roll number {{roll_number}} has successfully completed studies..." required class="w-full rounded-lg border-gray-300 text-sm font-serif leading-relaxed"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showTemplateModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Template</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Global Toast Notification -->
        <div v-if="showToast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300">
            <div :class="toastType === 'success' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-red-600 border-red-500 text-white'" class="flex items-center gap-3 px-5 py-3.5 rounded-xl border shadow-xl">
                <i :class="toastType === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'" class="text-lg"></i>
                <span class="text-xs font-semibold">{{ toastMessage }}</span>
            </div>
        </div>
    </TenantAdminLayout>
</template>

<style>
@media print {
    /* Hide everything on the page */
    body * {
        visibility: hidden;
    }
    
    /* Reveal only the certificate container */
    .print-certificate-container, .print-certificate-container * {
        visibility: visible;
    }
    
    /* Position the container at the top left of the print sheet */
    .print-certificate-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        height: 100% !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
