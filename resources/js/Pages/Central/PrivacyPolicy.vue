<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CentralPublicLayout from '@/Layouts/CentralPublicLayout.vue';

const sections = [
    { id: 'introduction', title: '1. Introduction & Multi-Tenant Model' },
    { id: 'data-collected', title: '2. Information We Collect' },
    { id: 'isolation', title: '3. Multi-Database Isolation (Zero Shared Tables)' },
    { id: 'student-privacy', title: '4. Student Records & Guardian Rights' },
    { id: 'fee-processing', title: '5. Fee Challans & Financial Gateways' },
    { id: 'retention-export', title: '6. Data Retention & School Offboarding' },
    { id: 'security-logging', title: '7. Access Control, Logging & PII Masking' },
    { id: 'cookies-tracking', title: '8. Cookies & Session Storage' },
    { id: 'compliance-contact', title: '9. Contact & Data Protection Officer' },
];

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Privacy Policy - Raibix Multi-Tenant Platform" />

    <CentralPublicLayout
        title="Raibix Privacy Policy"
        subtitle="How we protect institutional school records, student privacy, and staff data across our multi-tenant SaaS infrastructure."
        badge="Institutional Data Privacy"
        lastUpdated="October 2026"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- Desktop Sticky Table of Contents -->
            <aside class="hidden lg:block lg:col-span-4 sticky top-24 bg-white border border-[#E7E9EE] rounded-2xl p-6 shadow-xs">
                <div class="text-xs font-bold uppercase tracking-wider text-[#111318] mb-4 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-[#2F6BFF]"></span>
                    <span>Document Outline</span>
                </div>
                <nav class="space-y-1 text-xs">
                    <button
                        v-for="s in sections"
                        :key="s.id"
                        @click="scrollTo(s.id)"
                        class="w-full text-left py-2 px-3 rounded-lg text-[#5B6270] hover:text-[#111318] hover:bg-[#F8F9FA] transition-colors font-medium flex items-center justify-between group"
                    >
                        <span>{{ s.title }}</span>
                        <span class="opacity-0 group-hover:opacity-100 text-[#2F6BFF] transition-opacity">&rarr;</span>
                    </button>
                </nav>

                <div class="mt-6 pt-6 border-t border-[#E7E9EE] text-[11px] text-[#5B6270] space-y-2">
                    <div class="font-semibold text-[#111318]">Need clarification?</div>
                    <p>Our Data Protection team is available for campus compliance reviews.</p>
                    <a href="mailto:privacy@raibix.com" class="inline-block text-[#2F6BFF] font-semibold hover:underline">
                        privacy@raibix.com
                    </a>
                </div>
            </aside>

            <!-- Main Legal Content -->
            <article class="lg:col-span-8 bg-white border border-[#E7E9EE] rounded-2xl p-6 sm:p-10 shadow-xs space-y-12 text-left">
                
                <!-- Notice Banner -->
                <div class="rounded-xl bg-[#F0F5FF] border border-[#D0E0FF] p-5 text-xs text-[#1E3A8A] leading-relaxed">
                    <div class="font-bold mb-1 flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#2F6BFF]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Core Privacy Principle: Physical Data Isolation</span>
                    </div>
                    Raibix operates under a strict database-per-tenant architecture. Student records, academic evaluations, fee vouchers, and guardian details belonging to one educational institution never mix with, reside in, or share database tables with any other campus.
                </div>

                <!-- Section 1 -->
                <section id="introduction" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        1. Introduction & Multi-Tenant Model
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix Platform ("Raibix", "we", "us", or "our") provides School Management System (SMS) and Learning Management System (LMS) software-as-a-service to registered educational institutions ("Subscriber Schools", "Tenants").
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        This Privacy Policy describes how Raibix collects, manages, and safeguards data when Subscriber Schools, administrative staff, teachers, students, and parents access school portals hosted on Raibix infrastructure or subdomains (e.g., <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">schoolname.raibix.com</code>).
                    </p>
                </section>

                <!-- Section 2 -->
                <section id="data-collected" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        2. Information We Collect
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix acts as a data processor on behalf of Subscriber Schools. Depending on the modules enabled by the school, we process:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1.5">
                            <h3 class="text-xs font-bold text-[#111318] uppercase tracking-wide">Student Profiles</h3>
                            <p class="text-xs text-[#5B6270] leading-relaxed">
                                Full legal name, date of birth, gender, admission registration number, section assignment, B-form identifier, and medical or emergency contact details.
                            </p>
                        </div>
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1.5">
                            <h3 class="text-xs font-bold text-[#111318] uppercase tracking-wide">Guardian & Parent Data</h3>
                            <p class="text-xs text-[#5B6270] leading-relaxed">
                                Parent/guardian name, CNIC number, verified mobile number for SMS/WhatsApp notices, email address, relationship, and emergency contacts.
                            </p>
                        </div>
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1.5">
                            <h3 class="text-xs font-bold text-[#111318] uppercase tracking-wide">Academic & Attendance</h3>
                            <p class="text-xs text-[#5B6270] leading-relaxed">
                                Daily attendance checkpoints, biometrics status, marks ledgers, teacher remarks, examination transcripts, certificates, and disciplinary history.
                            </p>
                        </div>
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1.5">
                            <h3 class="text-xs font-bold text-[#111318] uppercase tracking-wide">Staff & Payroll Records</h3>
                            <p class="text-xs text-[#5B6270] leading-relaxed">
                                Faculty names, designation, assigned classrooms, attendance records, basic salary scales, deduction ledgers, and institutional portal credentials.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Section 3 -->
                <section id="isolation" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        3. Multi-Database Isolation (Zero Shared Tables)
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Unlike conventional software architectures where institutional data is commingled in a single database separated only by column filters (such as <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">tenant_id</code>), Raibix utilizes complete multi-database schema isolation:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>
                            <strong class="text-[#111318]">Dedicated Database Schemas:</strong> Every school tenant is provisioned with an isolated SQLite or MySQL/PostgreSQL database instance.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Zero Cross-Tenant Queries:</strong> The application automatically switches runtime connections upon hostname identification. SQL queries originating from one school cannot access or join tables from another.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Protected File Disks:</strong> Uploaded identification documents (such as B-forms, CNICs, and fee receipts) are stored on private, non-public storage disks accessible exclusively via authorization-checked controllers.
                        </li>
                    </ul>
                </section>

                <!-- Section 4 -->
                <section id="student-privacy" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        4. Student Records & Guardian Rights
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        We consider student academic and personal information sacred. Raibix strictly observes educational privacy best practices:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>We never sell, rent, monetize, or broker student or parent data to marketing networks, brokers, or third-party advertisers.</li>
                        <li>No automated behavioral profiling or third-party tracking scripts are embedded inside authenticated school portals.</li>
                        <li>Parents and authorized guardians can inspect their child's attendance, report cards, and fee challans directly through the dedicated Parent Portal.</li>
                        <li>Student record corrections are handled directly by the Subscriber School's registrar or administration office.</li>
                    </ul>
                </section>

                <!-- Section 5 -->
                <section id="fee-processing" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        5. Fee Challans & Financial Gateways
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix facilitates multi-channel fee reconciliation for schools, including 1-Bill national billing, JazzCash, Stripe, and direct bank deposit slips.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        <strong class="text-[#111318]">Cardholder Data Security:</strong> Raibix does not store full credit or debit card numbers, CVVs, or cardholder magnetic stripe data. Online transactions are processed directly by certified Level 1 PCI-DSS payment gateways. Only masked identifiers and settlement transaction references are retained for audit and reconciliation.
                    </p>
                </section>

                <!-- Section 6 -->
                <section id="retention-export" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        6. Data Retention & School Offboarding
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Institutional data is retained for the active duration of the Subscriber School's contract. Upon termination or offboarding:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>
                            <strong class="text-[#111318]">30-Day Export Window:</strong> The school administration is granted a 30-day graceful window to export complete student rosters, historical gradebooks, fee ledgers, and database archives in standard SQL / CSV formats.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Cryptographic Purging:</strong> Following the export window, all tenant-specific database files and stored documents are permanently unlinked and securely deleted from active storage.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Disaster Backups:</strong> System disaster recovery snapshots are rotated and scrubbed according to our 90-day rolling backup cycle.
                        </li>
                    </ul>
                </section>

                <!-- Section 7 -->
                <section id="security-logging" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        7. Access Control, Logging & PII Masking
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Access to tenant portals is governed by Spatie Role-Based Access Control (RBAC). Staff, teachers, parents, and students are restricted strictly to authorized views.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        <strong class="text-[#111318]">Automated PII Masking in Logs:</strong> Raibix incorporates automated redaction filters in all application logging channels. Passwords, session authentication tokens, payment card figures, and sensitive contact numbers are scrubbed before persistence to disk.
                    </p>
                </section>

                <!-- Section 8 -->
                <section id="cookies-tracking" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        8. Cookies & Session Storage
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        We use essential session cookies strictly necessary to maintain authenticated portal sessions, preserve CSRF token protection, and support subdomain tenant routing. We do not use third-party cross-site advertising cookies.
                    </p>
                </section>

                <!-- Section 9 -->
                <section id="compliance-contact" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        9. Contact & Data Protection Officer
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        For questions regarding this policy, institutional data processing agreements (DPAs), or to exercise student data rights, please contact our Data Protection Office:
                    </p>
                    <div class="border border-[#E7E9EE] rounded-xl p-5 bg-[#FAFAFC] text-xs text-[#5B6270] space-y-1">
                        <div class="font-bold text-[#111318]">Raibix Data Protection Office</div>
                        <div>Email: <a href="mailto:privacy@raibix.com" class="text-[#2F6BFF] font-semibold hover:underline">privacy@raibix.com</a></div>
                        <div>General Inquiries: <a :href="'mailto:' + ($page.props.platformContact?.email || 'support@raibix.com')" class="text-[#2F6BFF] hover:underline">{{ $page.props.platformContact?.email || 'support@raibix.com' }}</a></div>
                        <div>Operations Center: Islamabad, Pakistan</div>
                    </div>
                </section>

            </article>

        </div>
    </CentralPublicLayout>
</template>
