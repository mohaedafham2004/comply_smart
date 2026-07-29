<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepo,
    ) {}

    /**
     * Create a notification for all users of a business.
     */
    public function createForBusiness(
        string $businessId,
        string $type,
        string $title,
        string $message,
    ): void {
        $users = User::where('business_id', $businessId)->get();

        foreach ($users as $user) {
            $this->notificationRepo->create([
                'user_id'     => $user->_id,
                'business_id' => $businessId,
                'type'        => $type,
                'title'       => $title,
                'message'     => $message,
                'read_at'     => null,
            ]);
        }
    }

    /**
     * Create a notification for a single user.
     */
    public function createForUser(
        string $userId,
        string $businessId,
        string $type,
        string $title,
        string $message,
    ): Notification {
        return $this->notificationRepo->create([
            'user_id'     => $userId,
            'business_id' => $businessId,
            'type'        => $type,
            'title'       => $title,
            'message'     => $message,
            'read_at'     => null,
        ]);
    }

    public function listForUser(string $userId, bool $unreadOnly = false): \Illuminate\Support\Collection
    {
        return $this->notificationRepo->findForUser($userId, $unreadOnly);
    }

    public function markAsRead(string $notificationId, User $user): void
    {
        $notification = $this->notificationRepo->findById($notificationId);
        if ($notification && $notification->user_id === $user->_id) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(User $user): void
    {
        $this->notificationRepo->markAllReadForUser($user->_id);
    }
}
