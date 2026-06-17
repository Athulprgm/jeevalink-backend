<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    // Disable standard Laravel timestamps because the table has only created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'recipient_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Create a new notification.
     * Overriding or providing a static creation wrapper.
     *
     * @param array $data
     * @return int Inserted ID
     */
    public static function createNotification(array $data): int
    {
        $notif = self::create([
            'recipient_id' => $data['recipient_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'is_read' => false
        ]);
        return $notif->id;
    }

    /**
     * Fetch notifications for a recipient.
     *
     * @param int $recipientId
     * @return array
     */
    public static function getByRecipient(int $recipientId): array
    {
        return self::where('recipient_id', $recipientId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @param int $recipientId
     * @return bool
     */
    public static function markAsRead(int $id, int $recipientId): bool
    {
        $notif = self::where('id', $id)
            ->where('recipient_id', $recipientId)
            ->first();
        if ($notif) {
            $notif->is_read = true;
            return $notif->save();
        }
        return false;
    }

    /**
     * Mark all notifications for a recipient as read.
     *
     * @param int $recipientId
     * @return bool
     */
    public static function markAllAsRead(int $recipientId): bool
    {
        return self::where('recipient_id', $recipientId)
            ->update(['is_read' => true]) >= 0;
    }
}
