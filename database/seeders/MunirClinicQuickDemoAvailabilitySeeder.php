<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo availability for "Dr. Munir Clinic" (expected user id 14, email doctor@clinic.local).
 * Window: 90 calendar days starting from yesterday (inclusive), weekdays only (profile off days),
 * 09:00–17:00 available plus a lunch break 12:00–13:00 unavailable.
 *
 * Run after bulk appointment seeders so future demo bookings do not hide all hours for this doctor.
 */
class MunirClinicQuickDemoAvailabilitySeeder extends Seeder
{
    /** Inclusive span: yesterday + (DAYS - 1) = DAYS days total. */
    private int $days = 90;

    public function run(): void
    {
        $doctor = User::query()
            ->where('role', 'doctor')
            ->where(function ($q) {
                $q->where('id', 14)
                    ->orWhere('email', 'doctor@clinic.local');
            })
            ->orderByRaw('CASE WHEN users.id = 14 THEN 0 ELSE 1 END')
            ->first();

        if (! $doctor) {
            $this->command?->error('Demo doctor not found: expected id 14 or email doctor@clinic.local. Run BaselineSeeder first.');

            return;
        }

        if ($doctor->id !== 14) {
            $this->command?->warn("Using doctor id {$doctor->id} (doctor@clinic.local) — id 14 not present in this database.");
        }

        $rangeStart = Carbon::yesterday()->startOfDay();
        $rangeEnd = $rangeStart->copy()->addDays($this->days - 1);

        $profile = $doctor->doctorProfile;
        if (! $profile || $profile->status !== 'approved') {
            $this->command?->error("Doctor {$doctor->id} has no approved profile. Cannot seed availability.");

            return;
        }

        $startTime = $profile->work_start_time
            ? Carbon::parse($profile->work_start_time)->format('H:i')
            : '09:00';
        $endTime = $profile->work_end_time
            ? Carbon::parse($profile->work_end_time)->format('H:i')
            : '17:00';

        $off = [
            strtolower((string) ($profile->off_day_1 ?: 'friday')),
            strtolower((string) ($profile->off_day_2 ?: 'saturday')),
        ];

        $rangeStartStr = $rangeStart->toDateString();
        $rangeEndStr = $rangeEnd->toDateString();

        Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('appointment_date', [$rangeStartStr, $rangeEndStr])
            ->delete();

        DoctorAvailability::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('work_date', '>=', $rangeStartStr)
            ->whereDate('work_date', '<=', $rangeEndStr)
            ->delete();

        $cursor = $rangeStart->copy();

        while ($cursor->lessThanOrEqualTo($rangeEnd)) {
            $dayName = strtolower($cursor->englishDayOfWeek);

            if (in_array($dayName, $off, true)) {
                $cursor->addDay();

                continue;
            }

            DoctorAvailability::create([
                'doctor_id' => $doctor->id,
                'work_date' => $cursor->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_available' => true,
            ]);

            DoctorAvailability::create([
                'doctor_id' => $doctor->id,
                'work_date' => $cursor->toDateString(),
                'start_time' => '12:00',
                'end_time' => '13:00',
                'is_available' => false,
            ]);

            $cursor->addDay();
        }

        $this->command?->info(sprintf(
            'Seeded doctor_availabilities for %s (id %d): %s → %s (%d calendar days, %d weekdays with 09–17 + lunch break).',
            $doctor->name,
            $doctor->id,
            $rangeStart->toDateString(),
            $rangeEnd->toDateString(),
            $this->days,
            DoctorAvailability::query()
                ->where('doctor_id', $doctor->id)
                ->whereDate('work_date', '>=', $rangeStartStr)
                ->whereDate('work_date', '<=', $rangeEndStr)
                ->where('is_available', true)
                ->count()
        ));
    }
}
