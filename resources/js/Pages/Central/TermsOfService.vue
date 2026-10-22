<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CentralPublicLayout from '@/Layouts/CentralPublicLayout.vue';

const sections = [
    { id: 'acceptance', title: '1. Acceptance of Terms & Authority' },
    { id: 'provisioning', title: '2. Campus Provisioning & Subdomains' },
    { id: 'plans-billing', title: '3. Subscription Tiers & Billing' },
    { id: 'quotas-limits', title: '4. Student Enrollment Quotas' },
    { id: 'acceptable-use', title: '5. Acceptable Use & Security Guardrails' },
    { id: 'sla-uptime', title: '6. Platform Availability & 99.9% SLA' },
    { id: 'ip-ownership', title: '7. Data Ownership & Intellectual Property' },
    { id: 'grace-suspension', title: '8. Grace Period, Overdue & Termination' },
    { id: 'liability', title: '9. Limitation of Liability & Disclaimers' },
    { id: 'governing-law', title: '10. Governing Law & Dispute Resolution' },
];

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Terms of Service - Raibix Multi-Tenant Platform" />

    <CentralPublicLayout
        title="Raibix Terms of Service"
        subtitle="Master SaaS agreement governing institutional subscriptions, school tenant portals, and platform operations."
        badge="Master Subscription Agreement"
        lastUpdated="October 2026"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- Desktop Sticky Table of Contents -->
            <aside class="hidden lg:block lg:col-span-4 sticky top-24 bg-white border border-[#E7E9EE] rounded-2xl p-6 shadow-xs">
                <div class="text-xs font-bold uppercase tracking-wider text-[#111318] mb-4 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-[#2F6BFF]"></span>
                    <span>Agreement Outline</span>
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
                    <div class="font-semibold text-[#111318]">Institutional Procurement?</div>
                    <p>For custom multi-branch franchise contracts or physical SLA signatures, reach out to legal counsel.</p>
                    <a href="mailto:legal@raibix.com" class="inline-block text-[#2F6BFF] font-semibold hover:underline">
                        legal@raibix.com
                    </a>
                </div>
            </aside>

            <!-- Main Legal Content -->
            <article class="lg:col-span-8 bg-white border border-[#E7E9EE] rounded-2xl p-6 sm:p-10 shadow-xs space-y-12 text-left">
                
                <!-- Notice Banner -->
                <div class="rounded-xl bg-[#F8FAFC] border border-[#E2E8F0] p-5 text-xs text-[#334155] leading-relaxed">
                    <div class="font-bold mb-1 flex items-center gap-2 text-[#0F172A]">
                        <svg class="h-4 w-4 text-[#2F6BFF]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Binding Educational Subscription Agreement</span>
                    </div>
                    By completing the checkout process, submitting an institutional onboarding request, or using the Raibix platform, the Subscriber School agrees to be bound by these Terms of Service.
                </div>

                <!-- Section 1 -->
                <section id="acceptance" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        1. Acceptance of Terms & Authority
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        These Terms of Service ("Agreement") are entered into by Raibix Platform ("Provider", "we", "us") and the educational institution, trust, academy, or school district ("Subscriber", "School", "Customer") identified during registration.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        The individual agreeing to this Agreement represents and warrants that they are a Principal, Director, Trustee, or authorized administrative representative with legal capacity to bind the institution to this contract.
                    </p>
                </section>

                <!-- Section 2 -->
                <section id="provisioning" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        2. Campus Provisioning & Subdomains
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Upon successful plan selection, Raibix provisions a dedicated school tenant environment:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>
                            <strong class="text-[#111318]">Subdomain Allocation:</strong> Subscriber is assigned a subdomain (e.g. <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">campus.raibix.com</code>). Subdomain names must not infringe upon registered trademarks or violate public decency.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Custom Domain Mapping:</strong> Subscriber Schools subscribed to Signature plans may map apex domains or institutional subdomains (e.g. <code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">portal.yourschool.edu.pk</code>) with automated SSL certificate provisioning.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Initial Activation:</strong> Instant provisioning creates the tenant schema. Live access is unlocked upon subscription verification or payment confirmation.
                        </li>
                    </ul>
                </section>

                <!-- Section 3 -->
                <section id="plans-billing" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        3. Subscription Tiers & Billing
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix services are billed on a recurring monthly or discounted annual cycle:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs">
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1">
                            <div class="font-bold text-[#111318]">Starter Plan</div>
                            <div class="text-[#2F6BFF] font-semibold">PKR 20,000 / month</div>
                            <div class="text-[#5B6270]">Up to 300 active students. Core SIS, admissions & fee vouchers.</div>
                        </div>
                        <div class="border border-[#2F6BFF]/40 rounded-xl p-4 bg-[#F0F5FF] space-y-1">
                            <div class="font-bold text-[#111318]">Essential Plan</div>
                            <div class="text-[#2F6BFF] font-semibold">PKR 40,000 / month</div>
                            <div class="text-[#5B6270]">Up to 1,000 students. 1-Bill integration, LMS, biometric attendance.</div>
                        </div>
                        <div class="border border-[#E7E9EE] rounded-xl p-4 bg-[#FAFAFC] space-y-1">
                            <div class="font-bold text-[#111318]">Signature Plan</div>
                            <div class="text-[#2F6BFF] font-semibold">PKR 50,000 / month</div>
                            <div class="text-[#5B6270]">Up to 5,000 students. Full 16 ERP modules, custom domain, 24/7 SLA.</div>
                        </div>
                    </div>
                    <p class="text-sm text-[#5B6270] leading-relaxed pt-2">
                        Invoices are issued 7 days prior to billing renewal. Payments are accepted via 1-Bill national clearing, verified bank wire transfers, or direct online gateway checkout.
                    </p>
                </section>

                <!-- Section 4 -->
                <section id="quotas-limits" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        4. Student Enrollment Quotas
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Each subscription tier enforces a maximum active student enrollment quota (<code class="bg-[#F3F4F6] px-1.5 py-0.5 rounded text-[#111318]">max_students</code>).
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        When a school reaches 100% of its enrollment ceiling, the system prevents adding additional student admissions until the subscription is upgraded to a higher tier or inactive student records are archived. Existing student and portal access is never interrupted due to reaching quota limits.
                    </p>
                </section>

                <!-- Section 5 -->
                <section id="acceptable-use" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        5. Acceptable Use & Security Guardrails
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Subscribers and their end users agree not to:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>Attempt to bypass multi-tenant isolation, tamper with database connections, or inspect other tenants' infrastructure.</li>
                        <li>Send unsolicited bulk communications (SPAM) through the SMS broadcasting or email notification modules.</li>
                        <li>Upload malicious payloads, viruses, or unauthorized executable files to school storage disks.</li>
                        <li>Share administrative root or Super Admin credentials across unauthorized personnel.</li>
                    </ul>
                </section>

                <!-- Section 6 -->
                <section id="sla-uptime" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        6. Platform Availability & 99.9% SLA
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix strives to deliver 99.9% application uptime, excluding scheduled off-peak maintenance (typically executed between 02:00 AM and 05:00 AM PKT with prior 48-hour administrator notification).
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Daily automated database backups and off-site snapshots are maintained for every tenant to ensure rapid disaster recovery and business continuity.
                    </p>
                </section>

                <!-- Section 7 -->
                <section id="ip-ownership" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        7. Data Ownership & Intellectual Property
                    </h2>
                    <div class="space-y-3 text-sm text-[#5B6270] leading-relaxed">
                        <p>
                            <strong class="text-[#111318]">Subscriber Ownership:</strong> As between the parties, the Subscriber School retains sole and exclusive ownership of all student records, academic ledgers, examination question banks, institutional logos, and fee transaction data uploaded or generated within its tenant database.
                        </p>
                        <p>
                            <strong class="text-[#111318]">Provider Ownership:</strong> Raibix retains all rights, title, and intellectual property in the underlying software, source code, tenancy engine, database schemas, user interfaces, documentation, and platform branding.
                        </p>
                    </div>
                </section>

                <!-- Section 8 -->
                <section id="grace-suspension" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        8. Grace Period, Overdue & Termination
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        To protect continuous academic operations, Raibix provides a safety buffer for overdue invoices:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-[#5B6270]">
                        <li>
                            <strong class="text-[#111318]">14-Day Grace Period:</strong> If a subscription invoice is unpaid on its due date, the tenant enters a 14-day grace period. School portals remain fully functional with reminder banners visible only to administrators.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Suspension Notice:</strong> Accounts unpaid after 14 days may be temporarily suspended. Users visiting the school subdomain will see an administrative contact notice.
                        </li>
                        <li>
                            <strong class="text-[#111318]">Data Export Right:</strong> Suspended accounts retain the right to request a full database archive and student records export for 30 days prior to permanent decommission.
                        </li>
                    </ul>
                </section>

                <!-- Section 9 -->
                <section id="liability" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        9. Limitation of Liability & Disclaimers
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        To the maximum extent permitted by applicable law, neither party shall be liable for indirect, incidental, consequential, special, or punitive damages arising from the use or inability to use the platform.
                    </p>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        Raibix's aggregate liability under this agreement is limited to the total subscription fees actually paid by the Subscriber in the three (3) months preceding the event giving rise to liability.
                    </p>
                </section>

                <!-- Section 10 -->
                <section id="governing-law" class="scroll-mt-28 space-y-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#111318] tracking-tight">
                        10. Governing Law & Dispute Resolution
                    </h2>
                    <p class="text-sm text-[#5B6270] leading-relaxed">
                        This Agreement shall be governed by and construed in accordance with the laws of the Islamic Republic of Pakistan. Any dispute arising out of or relating to this Agreement shall first be submitted to good-faith mutual executive negotiation before submission to competent courts in Islamabad, Pakistan.
                    </p>
                    <div class="border border-[#E7E9EE] rounded-xl p-5 bg-[#FAFAFC] text-xs text-[#5B6270] space-y-1">
                        <div class="font-bold text-[#111318]">Raibix Legal & Compliance Department</div>
                        <div>Email: <a href="mailto:legal@raibix.com" class="text-[#2F6BFF] font-semibold hover:underline">legal@raibix.com</a></div>
                        <div>Official Address: Raibix Platform, Islamabad, Pakistan</div>
                    </div>
                </section>

            </article>

        </div>
    </CentralPublicLayout>
</template>
