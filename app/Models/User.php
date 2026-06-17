<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'mobile',
        'password_hash',
        'role',
        'blood_group',
        'city',
        'district',
        'address',
        'weight',
        'date_of_birth',
        'last_donated_date',
        'profile_picture',
        'available_for_donation',
        'reward_points',
        'lives_saved',
        'total_donations',
        'status',
        'expo_push_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the password name for authentication.
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'last_donated_date' => 'date:Y-m-d',
            'available_for_donation' => 'boolean',
            'reward_points' => 'integer',
            'lives_saved' => 'integer',
            'total_donations' => 'integer',
        ];
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email): ?array
    {
        $user = self::where('email', $email)->first();
        return $user ? $user->toArray() : null;
    }

    /**
     * Find a user by mobile number.
     *
     * @param string $mobile
     * @return array|null
     */
    public static function findByMobile(string $mobile): ?array
    {
        $user = self::where('mobile', $mobile)->first();
        return $user ? $user->toArray() : null;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $user = self::find($id);
        return $user ? $user->toArray() : null;
    }

    /**
     * Update user profile data.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateProfile(int $id, array $data): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        
        $allowedFields = [
            'full_name', 'blood_group', 'city', 'district', 
            'address', 'weight', 'date_of_birth', 'last_donated_date', 
            'profile_picture'
        ];

        $updates = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        return $user->update($updates);
    }

    /**
     * Toggle availability for donation.
     *
     * @param int $id
     * @return bool
     */
    public static function toggleAvailability(int $id): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        $user->available_for_donation = !$user->available_for_donation;
        return $user->save();
    }

    /**
     * Update Expo push notification token.
     *
     * @param int $id
     * @param string|null $token
     * @return bool
     */
    public static function updatePushToken(int $id, ?string $token): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        $user->expo_push_token = $token;
        return $user->save();
    }

    /**
     * Update user status (Active, Suspended, etc.).
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus(int $id, string $status): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        $user->status = $status;
        return $user->save();
    }

    /**
     * Increment a numeric statistic column.
     *
     * @param int $id
     * @param string $field
     * @param int $amount
     * @return bool
     */
    public static function incrementStats(int $id, string $field, int $amount = 1): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }
        $allowed = ['reward_points', 'lives_saved', 'total_donations'];
        if (!in_array($field, $allowed, true)) {
            return false;
        }
        $user->increment($field, $amount);
        return true;
    }

    /**
     * Search compatible donors.
     *
     * @param array $filters
     * @param int|null $excludeId
     * @return array
     */
    public static function searchDonors(array $filters, ?int $excludeId = null): array
    {
        $query = self::where('available_for_donation', true)
            ->where('status', 'Active');

        if (!empty($filters['bloodGroup'])) {
            $query->where('blood_group', $filters['bloodGroup']);
        }

        if (!empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->toArray();
    }

    /**
     * Fetch all users in the system (Admin dashboard).
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::orderBy('created_at', 'desc')->get()->toArray();
    }
}
