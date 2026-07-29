<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function update(User $user, array $data): bool;
    public function all(): Collection;
}
