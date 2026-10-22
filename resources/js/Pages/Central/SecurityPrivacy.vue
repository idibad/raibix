<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CentralPublicLayout from '@/Layouts/CentralPublicLayout.vue';

const pillars = [
    {
        title: 'Multi-Database Isolation',
        icon: 'database',
        badge: 'Zero Shared Tables',
        desc: 'Stancl Tenancy dynamically separates every school into its own isolated database schema. Zero data leakage across institutions.',
        color: '#2F6BFF'
    },
    {
        title: 'End-to-End Encryption',
        icon: 'lock',
        badge: 'TLS 1.3 & AES-256',
        desc: 'All communications are protected in transit with modern TLS 1.3. Stored database archives and credentials use AES-256.',
        color: '#8B5CF6'
    },
    {
        title: 'Spatie RBAC & 2FA',
        icon: 'shield',
        badge: '7 School Roles',
        desc: 'Granular role-based permissions for Principals, Teachers, Students, Parents, and Accountants, with 2FA for root administrators.',
        color: '#10B981'
    },
    {
        title: 'Protected File Storage',
        icon: 'folder',
        badge: 'Non-Public Disks',
        desc: 'Student B-forms, CNICs, and fee receipts are stored on non-public disks and served exclusively via authorized download endpoints.',
        color: '#F59E0B'
    },
];

const sections = [
    { id: 'isolation-model', title: '1. Multi-Database Isolation Architecture' },
    { id: 'encryption-transit-rest', title: '2. Encryption In Transit & At Rest' },
    { id: 'rbac-auth', title: '3. Identity, Roles & 2-Factor Authentication' },
    { id: 'private-storage', title: '4. Non-Public Document Storage & Streaming' },
    { id: 'pii-masking', title: '5. Audit Logging & PII Sanitization' },
    { id: 'backups-ha', title: '6. Automated Backups & High Availability' },
    { id: 'payment-security', title: '7. Payment Gateways & Financial Security' },
    { id: 'responsible-disclosure', title: '8. Vulnerability Reporting Program' },
];

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Security & Privacy Architecture - Raibix Multi-Tenant Platform" />

    <CentralPublicLayout
        title="Security & Privacy Architecture"
        subtitle="How Raibix secures educational institutions with multi-database isolation, AES-256 encryption, role-based controls, and automated compliance."
        badge="Enterprise Security Architecture"
        lastUpdated="October 2026"
    >
        <!-- Top 4 Security Pillars Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-14">
            <div 
                v-for="p in pillars" 
                :key="p.title"
                class="bg-white border border-[#E7E9EE] rounded-2xl p-6 shadow-xs flex flex-col justify-between hover:border-[#111318]/20 transition-all text-left"
            >
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="h-10 w-10 rounded-xl bg-[#FAFAFC] border border-[#E7E9EE] flex items-center justify-center text-[#111318]">
                            <!-- Database Icon -->
                            <svg v-if="p.icon === 'database'" class="h-5 w-5 text-[#2F6BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <ellipse cx="12" cy="5" rx="9" ry="3" stroke-width="2"></ellipse>
                                <path stroke-width="2" d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path stroke-width="2" d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                            <!-- Lock Icon -->
                            <svg v-else-if="p.icon === 'lock'" class="h-5 w-5 text-[#8B5CF6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"></rect>
                                <path stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"></path>
                            </svg>
                            <!-- Shield Icon -->
                            <svg v-else-if="p.icon === 'shield'" class="h-5 w-5 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <!-- Folder Icon -->
                            <svg v-else class="h-5 w-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#F3F4F6] text-[#5B6270]">
                            {{ p.badge }}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-[#111318]">
                        {{ p.title }}
                    </h3>

                    <p class="text-xs text-[#5B6270] leading-relaxed">
                        {{ p.desc }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Detailed Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- Desktop Sticky Table of Contents -->
            <aside class="hidden lg:block lg:col-span-4 sticky top-24 bg-white border border-[#E7E9EE] rounded-2xl p-6 shadow-xs">
                <div class="text-xs font-bold uppercase tracking-wider text-[#111318] mb-4 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-[#2F6BFF]"></span>
                    <span>Security Blueprint</span>
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
                    <div class="font-semibold text-[#111318]">Security & Vulnerability Team</div>
                    <p>Report security observations or coordinate penetration testing audits.</p>
                    <a href="mailto:security@raibix.com" class="inline-block text-[#2F6BFF] font-semibold hover:underline">
                        security@raibix.com
                    </a>
                </div>
            </aside>

            <!-- Main Architecture Content -->
            <article class="lg:col-span-8 bg-white border border-[#E7E9EE] rounded-2xl p-6 sm:p-10 shadow-xs space-y-12 text-left">
                
                <!-- Section 1 -->
                <section id="isolation-model" class="scroll-mt-28 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">
                        <span>Pillar 1: Data Isolation</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        1. Multi-Database Isolation Architecture
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        In traditional single-database SaaS platforms, a single programming bug or missing <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">WHERE tenant_id = ?</code> clause can expose an entire school's student directory to another school.
                    </p>
                    <div class="rounded-xl border border-[#E7E9EE] bg-[#FAFAFC] p-5 space-y-3">
                        <div class="font-bold text-xs text-[#111318] uppercase tracking-wide">
                            How Raibix Prevents Cross-Tenant Data Leaks:
                        </div>
                        <ul class="list-disc pl-5 space-y-2 text-xs text-[#5B6270] leading-relaxed">
                            <li>
                                <strong class="text-[#111318]">Stancl Tenancy Bootstrapper:</strong> When a request reaches <code class="text-[#111318]">schoolname.raibix.com</code>, the identification middleware resolves the school tenant and executes a hardware-level connection swap to that school's isolated database file or schema.
                            </li>
                            <li>
                                <strong class="text-[#111318]">Central Connection Segregation:</strong> Models operating on the central database (such as SaaS plans and tenant registrations) explicitly implement the <code class="text-[#111318]">CentralConnection</code> trait. Tenant controllers are physically prevented from reading central models inadvertently.
                            </li>
                            <li>
                                <strong class="text-[#111318]">Database Queue Isolation:</strong> Asynchronous background jobs (e.g. bulk fee challan generation, SMS notifications) serialize tenant context in job payloads to restore correct database connections before execution.
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Section 2 -->
                <section id="encryption-transit-rest" class="scroll-mt-28 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#8B5CF6]">
                        <span>Pillar 2: Cryptographic Controls</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        2. Encryption In Transit & At Rest
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        All web traffic between user browsers, mobile interfaces, and the Raibix cloud is encrypted using Transport Layer Security (TLS 1.3 / TLS 1.2) with strict HSTS (HTTP Strict Transport Security) headers.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Passwords are hashed using industry-standard <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">bcrypt</code> with high cost work factors. Database backup archives stored on off-site cloud storage are encrypted with AES-256 before replication.
                    </p>
                </section>

                <!-- Section 3 -->
                <section id="rbac-auth" class="scroll-mt-28 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#10B981]">
                        <span>Pillar 3: Access Control</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        3. Identity, Roles & 2-Factor Authentication
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Within each school tenant, authorization is governed through Spatie Role-Based Access Control (RBAC). Seven distinct roles enforce the principle of least privilege:
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-center text-xs">
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Principal</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Teacher</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Student</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Parent</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Accountant</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Librarian</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Staff Admin</div>
                        <div class="p-2.5 rounded-lg border border-[#E7E9EE] bg-[#FAFAFC] font-semibold text-[#111318]">Super Admin</div>
                    </div>
                    <p class="text-sm text-[#5B6270] leading-relaxed pt-2">
                        <strong class="text-[#111318]">Super Admin 2FA:</strong> Access to the central multi-tenant management portal requires an OTP two-factor verification code dispatched to the verified administrator email on every new session.
                    </p>
                </section>

                <!-- Section 4 -->
                <section id="private-storage" class="scroll-mt-28 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#F59E0B]">
                        <span>Pillar 4: Document Isolation</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        4. Non-Public Document Storage & Streaming
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Educational portals handle highly sensitive PII, including government student B-forms, birth certificates, parent CNIC scans, and fee bank deposit slips.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix strictly prohibits storing private student documents inside public web directories (<code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">public/uploads/</code>). All sensitive uploads are saved to private storage disks and streamed to authorized users only after verifying tenant identification, active authentication, and role authorization.
                    </p>
                </section>

                <!-- Section 5 -->
                <section id="pii-masking" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        5. Audit Logging & PII Sanitization
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        To safeguard privacy even in internal operational monitoring, Raibix logs undergo automatic redaction:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>Passwords and secret tokens are stripped from request and error traces.</li>
                        <li>Student contact numbers and parent CNICs are masked (<code class="text-[#111318]">35201-*******-1</code>).</li>
                        <li>Payment receipt logs mask card and account numbers to adhere to banking clearing requirements.</li>
                    </ul>
                </section>

                <!-- Section 6 -->
                <section id="backups-ha" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        6. Automated Backups & High Availability
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Each school tenant's database is backed up on an automated schedule:
                    </p>
                    <div class="rounded-xl border border-[#E7E9EE] bg-[#FAFAFC] p-4 text-xs text-[#5B6270] space-y-2">
                        <div>&bull; <strong class="text-[#111318]">Daily Snapshots:</strong> Stored locally and pushed to encrypted cloud buckets.</div>
                        <div>&bull; <strong class="text-[#111318]">Point-in-Time Recovery:</strong> In the event of human error (e.g. accidental mass student deletion), school administrators can trigger point-in-time restoration.</div>
                        <div>&bull; <strong class="text-[#111318]">Independent Tenant Health:</strong> A database issue on one tenant has zero collateral impact on any other school tenant.</div>
                    </div>
                </section>

                <!-- Section 7 -->
                <section id="payment-security" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        7. Payment Gateways & Financial Security
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Fee vouchers generated in Raibix support Pakistan's 1-Bill national billing infrastructure, enabling parents to pay fees through any ATM, mobile banking app, or OTC branch.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Bank clearing responses and transaction webhooks are verified via cryptographic signatures and idempotent processing to eliminate duplicate ledger postings or payment fraud.
                    </p>
                </section>

                <!-- Section 8 -->
                <section id="responsible-disclosure" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        8. Vulnerability Reporting Program
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        We welcome security researchers and institutional IT auditors who discover potential vulnerabilities to report them responsibly:
                    </p>
                    <div class="border border-[#E7E9EE] rounded-xl p-5 bg-[#FAFAFC] text-xs text-[#5B6270] space-y-2">
                        <div class="font-bold text-[#111318]">Raibix Security Operations</div>
                        <div>Direct Security Dispatch: <a href="mailto:security@raibix.com" class="text-[#2F6BFF] font-semibold hover:underline">security@raibix.com</a></div>
                        <p class="pt-1">
                            Please provide detailed steps to reproduce, HTTP requests, and affected endpoints. We pledge to acknowledge reports within 24 hours and never pursue legal action against researchers acting in good faith.
                        </p>
                    </div>
                </section>

            </article>

        </div>
    </CentralPublicLayout>
</template>
