<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Repositories\Contracts\BusinessRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly BusinessRepositoryInterface $businessRepo,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Register a new owner user, atomically create their business shell,
     * link user → business, and return a Sanctum token.
     *
     * Required fields: name, email, password, password_confirmation
     * Optional:        business_name, business_type, business_registration_no
     *
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        // 1. Create the user (password is hashed by the model's cast)
        $user = $this->userRepo->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],   // cast 'hashed' handles bcrypt
            'role'     => 'owner',             // registration always creates an owner
        ]);

        // 2. Create the business shell linked to this owner
        $business = $this->businessRepo->create([
            'name'            => $data['business_name'] ?? "{$data['name']}'s Business",
            'type'            => $data['business_type'] ?? 'general',
            'registration_no' => $data['business_registration_no'] ?? null,
            'owner_id'        => (string) $user->_id,
            'address'         => null,
            'phone'           => null,
            'email'           => $data['email'],
            'compliance_score'=> 0.0,
        ]);

        // 3. Link user → business
        $this->userRepo->update($user, ['business_id' => (string) $business->_id]);
        $user->refresh();

        // 4. Issue JWT token
        $token = auth('api')->login($user);

        // 5. Audit
        $this->auditService->log($user, 'registered', 'User', (string) $user->_id, [
            'email'       => $user->email,
            'business_id' => (string) $business->_id,
        ]);

        return [
            'user'     => $user,
            'business' => $business,
            'token'    => $token,
        ];
    }

    /**
     * Authenticate and return a fresh Sanctum token.
     * Rate limiting is applied at the route level (5 attempts / minute).
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepo->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Invalidate old token (if applicable for JWT, usually we just let it expire or blacklist it, 
        // but since we want only one active session, we can attempt to invalidate current if sent, 
        // however, login doesn't have the old token, so we just generate a new one)
        // With JWT we don't have a simple way to invalidate ALL tokens for a user without custom logic.
        $token = auth('api')->login($user);

        $this->auditService->log($user, 'login', 'User', (string) $user->_id, [
            'ip' => request()->ip(),
        ]);

        return [
            'user'  => $user->load('business'),
            'token' => $token,
        ];
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        auth('api')->logout();
        $this->auditService->log($user, 'logout', 'User', (string) $user->_id);
    }

    /**
     * Return the authenticated user with their business loaded.
     */
    public function me(User $user): User
    {
        return $user->load('business');
    }
}
