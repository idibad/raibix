<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAbsentSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $studentId;
    protected $attendanceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $studentId, int $attendanceId)
    {
        $this->studentId = $studentId;
        $this->attendanceId = $attendanceId;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $student = Student::with(['parent.user', 'currentClass', 'currentSection', 'user'])->find($this->studentId);
        $attendance = Attendance::find($this->attendanceId);

        if ($student && $attendance && $attendance->status === 'absent') {
            $notificationService->sendAbsenceAlert($student, $attendance);
        }
    }
}
