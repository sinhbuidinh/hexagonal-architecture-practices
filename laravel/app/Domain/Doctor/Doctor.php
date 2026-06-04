<?php

declare(strict_types=1);

namespace App\Domain\Doctor;

use App\Domain\Shared\PractitionerId;

/** Registered clinician; {@see PractitionerId} is the scheduling resource id. */
final readonly class Doctor
{
    /**
     * @param list<string> $specialties e.g. Cardiology, Pediatrics
     * @param list<string> $languages    e.g. English, Spanish
     * @param int|null     $userId       Optional portal account link; null when admin pre-provisions schedule
     */
    public function __construct(
        public PractitionerId $id,
        public string $name,
        public array $specialties = [],
        public array $languages = [],
        public ?string $licenseNumber = null,
        public bool $acceptingNewPatients = true,
        public ?int $userId = null,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Doctor name cannot be empty.');
        }
    }

    /** @return array{doctor_id: int, name: string, specialties: list<string>, languages: list<string>, license_number: string|null, accepting_new_patients: bool, user_id: int|null} */
    public function toArray(): array
    {
        return [
            'doctor_id'              => $this->id->value,
            'name'                   => $this->name,
            'specialties'            => $this->specialties,
            'languages'              => $this->languages,
            'license_number'         => $this->licenseNumber,
            'accepting_new_patients' => $this->acceptingNewPatients,
            'user_id'                => $this->userId,
        ];
    }

    /** @param list<string> $values @return list<string> */
    public static function normalizeStringList(array $values): array
    {
        return array_values(array: array_filter(
            array   : array_map(callback: static fn (mixed $value): string => trim((string) $value), array: $values),
            callback: static fn (string $value): bool => $value !== '',
        ));
    }
}
