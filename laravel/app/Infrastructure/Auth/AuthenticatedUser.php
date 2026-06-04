<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Shared\UserRole;

final readonly class AuthenticatedUser
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public UserRole $role,
    ) {
    }

    /** @return array{id: string, name: string, email: string, role: string} */
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role->value,
        ];
    }
}
