<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\FeeChallan;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendFeeReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all tenant databases for overdue fee challans, auto-escalate status, apply late penalties, and send automated parent notifications.';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info("Scanning school tenants for overdue fee challans...");

        // Fetch all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Processing School: {$tenant->id} ({$tenant->name})...");

            try {
                // Initialize tenant database context
                tenancy()->initialize($tenant);

                $today = Carbon::today();
                
                // Find unpaid challans
                $overdueChallans = FeeChallan::whereIn('status', ['unpaid', 'overdue'])
                    ->with(['student.user', 'student.parent'])
                    ->get();

                $notifiedCount = 0;
                $escalatedCount = 0;

                foreach ($overdueChallans as $challan) {
                    $dueDate = Carbon::parse($challan->due_date);

                    // 1. Auto-escalate status to overdue if past due date
                    if ($challan->status === 'unpaid' && $dueDate->isPast()) {
                        $challan->status = 'overdue';
                        
                        // Apply simulated late fee penalty (e.g. 500 PKR default late fee if not already added)
                        if ($challan->fine_amount == 0) {
                            $challan->fine_amount = 500;
                            $challan->total_amount += 500; 
                            $this->line("Applied PKR 500 late penalty to Challan #{$challan->challan_number}");
                        }
                        
                        $challan->save();
                        $escalatedCount++;
                    }

                    // 2. Send reminder if due date is today OR past due date (recurring reminder)
                    if ($dueDate->isToday() || ($dueDate->isPast() && $today->day % 3 === 0)) { // Remind every 3 days once overdue
                        $student = $challan->student;
                        if (!$student) continue;

                        $parent = $student->parent;
                        $parentPhone = $parent ? $parent->phone : null;
                        $parentEmail = $parent ? $parent->user?->email : null;

                        $studentName = $student->user ? $student->user->name : 'Your child';
                        $amount = number_format($challan->total_amount);
                        $message = "Dear Parent, this is a reminder that the school fee challan (#{$challan->challan_number}) of PKR {$amount} for {$studentName} is unpaid. Due date: {$challan->due_date}. Please pay promptly to avoid service suspension.";

                        // Dispatch SMS/WhatsApp via Pluggable Notification service
                        if ($parentPhone) {
                            $notificationService->sendSms($parentPhone, $message);
                            $notificationService->sendWhatsApp($parentPhone, $message);
                        }

                        // Also send default mail notice if email is available
                        if ($parentEmail) {
                            // Queue fee defalter mailable notice
                            try {
                                \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($parentEmail, $challan) {
                                    $mail->to($parentEmail)
                                        ->subject("URGENT: School Fee Payment Reminder - Challan #{$challan->challan_number}");
                                });
                            } catch (\Exception $e) {
                                Log::error("Failed to send fee mailable reminder to {$parentEmail}: " . $e->getMessage());
                            }
                        }

                        $notifiedCount++;
                    }
                }

                $this->info("School {$tenant->id} completed: Escalated {$escalatedCount} challans, sent {$notifiedCount} parent alerts.");

                // Revert to central context
                tenancy()->end();

            } catch (\Exception $e) {
                $this->error("Error processing school tenant {$tenant->id}: " . $e->getMessage());
                Log::error("Artisan fee reminder command failed for tenant {$tenant->id}: " . $e->getMessage());
                
                // Ensure we clean tenancy context on error
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->info("Fee reminder scan completed successfully.");
    }
}
