<?php

declare(strict_types=1);

namespace App\Application\Doctor\Command;

use App\Application\Port\DoctorAppointmentSettingsCommandPort;
use App\Application\Port\DoctorCommandPort;
use App\Application\Scheduling\Command\MaterializeBookableSlots;
use App\Domain\Shared\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateDoctor
{
    public function __construct(
        private DoctorCommandPort $doctors,
        private DoctorAppointmentSettingsCommandPort $appointmentSettings,
        private MaterializeBookableSlots $materializeBookableSlots,
    ) {
    }

    /**
     * @param list<string> $specialties
     * @param list<string> $languages
     *
     * @return array{doctor_id: int, name: string, specialties: list<string>, languages: list<string>, license_number: string|null, accepting_new_patients: bool, user_id: int|null}
     */
    public function execute(
        string $name,
        ?int $userId = null,
        array $specialties = [],
        array $languages = [],
        ?string $licenseNumber = null,
        bool $acceptingNewPatients = true,
    ): array {
        if ($userId !== null) {
            $this->assertPortalUserCanLinkDoctor($userId);
        }

        $doctor = $this->doctors->create(
            name                : $name,
            userId              : $userId,
            specialties         : $specialties,
            languages           : $languages,
            licenseNumber       : $licenseNumber,
            acceptingNewPatients: $acceptingNewPatients,
        );

        $this->appointmentSettings->ensureDefaults($doctor->id);
        $this->materializeBookableSlots->execute($doctor->id->value);

        return $doctor->toArray();
    }

    private function assertPortalUserCanLinkDoctor(int $userId): void
    {
        $user = User::query()->find($userId);
        if ($user === null) {
            throw new \InvalidArgumentException(sprintf('User %d not found.', $userId));
        }

        if ((string) $user->role !== UserRole::DOCTOR->value) {
            throw new \InvalidArgumentException('Only doctor portal accounts can link to a doctor profile.');
        }

        if (DB::table('doctors')->where('user_id', $userId)->exists()) {
            throw new \InvalidArgumentException(sprintf('User %d is already linked to a doctor profile.', $userId));
        }
    }
}
