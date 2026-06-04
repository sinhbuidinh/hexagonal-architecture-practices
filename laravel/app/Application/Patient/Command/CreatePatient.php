<?php

declare(strict_types=1);

namespace App\Application\Patient\Command;

use App\Application\Port\PatientCommandPort;
use App\Domain\Shared\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreatePatient
{
    public function __construct(private PatientCommandPort $patients)
    {
    }

    /**
     * @return array{patient_id: string, name: string, preferred_language: string|null, date_of_birth: string|null, phone: string|null, user_id: int|null}
     */
    public function execute(
        string $name,
        ?int $userId = null,
        ?string $preferredLanguage = null,
        ?string $dateOfBirth = null,
        ?string $phone = null,
    ): array {
        if ($userId !== null) {
            $this->assertPortalUserCanLinkPatient($userId);
        }

        $patient = $this->patients->create(
            name              : $name,
            userId            : $userId,
            preferredLanguage : $preferredLanguage,
            dateOfBirth       : $dateOfBirth,
            phone             : $phone,
        );

        return $patient->toArray();
    }

    private function assertPortalUserCanLinkPatient(int $userId): void
    {
        $user = User::query()->find($userId);
        if ($user === null) {
            throw new \InvalidArgumentException(sprintf('User %d not found.', $userId));
        }

        if ((string) $user->role !== UserRole::PATIENT->value) {
            throw new \InvalidArgumentException('Only patient portal accounts can link to a patient profile.');
        }

        if (DB::table('patients')->where('user_id', $userId)->exists()) {
            throw new \InvalidArgumentException(sprintf('User %d is already linked to a patient profile.', $userId));
        }
    }
}
