<?php

namespace App\Models;

use App\Traits\LogsTransactions;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, LogsTransactions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'student_number',
        'email',
        'password',
        'google2fa_secret',
        'google2fa_enabled',
        'provider',
        'provider_id',
        'provider_token',
        'profile_type',
        'profile_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Encryption mutators
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = $value ? encrypt($value) : null;
    }

    public function getFirstNameAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            return $value; // Return original if decryption fails
        }
    }

    public function setMiddleNameAttribute($value)
    {
        $this->attributes['middle_name'] = $value ? encrypt($value) : null;
    }

    public function getMiddleNameAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            return $value; // Return original if decryption fails
        }
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = $value ? encrypt($value) : null;
    }

    public function getLastNameAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            return $value; // Return original if decryption fails
        }
    }

    public function setStudentNumberAttribute($value)
    {
        $this->attributes['student_number'] = $value ? encrypt($value) : null;
    }

    public function getStudentNumberAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            return $value; // Return original if decryption fails
        }
    }

    public function setProviderTokenAttribute($value)
    {
        $this->attributes['provider_token'] = $value ? encrypt($value) : null;
    }

    public function getProviderTokenAttribute($value)
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (\Exception $e) {
            return $value; // Return original if decryption fails
        }
    }

    public function courseDiscussions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseDiscussion::class, 'user_id');
    }

    public function uploadedCourseResources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CourseResource::class, 'uploaded_by');
    }

    public function chatGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_user', 'user_id', 'chat_group_id')
            ->withPivot(['role_in_group', 'joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function chatMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatMessage::class, 'user_id');
    }

    /**
     * Polymorphic profile relationship.
     * Can be Student, Teacher, or Admin.
     */
    public function profile(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the student profile if this user is a student.
     */
    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the teacher profile if this user is a teacher.
     */
    public function teacher(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the admin profile if this user is an admin.
     */
    public function admin(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get the full name from profile or fallback to name field.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->profile) {
            return $this->profile->full_name ?? $this->name;
        }
        return $this->name;
    }

    /**
     * Get the role-specific ID (student_number or employee_id).
     */
    public function getRoleIdAttribute(): ?string
    {
        if ($this->profile) {
            return $this->profile->student_number ?? $this->profile->employee_id ?? null;
        }
        return $this->student_number ?? null;
    }

    /**
     * Check if user has a profile.
     */
    public function hasProfile(): bool
    {
        return $this->profile !== null;
    }

    /**
     * Create profile based on role.
     */
    public function createProfile(array $data): Model
    {
        $profile = match ($this->getRoleNames()->first()) {
            'Student' => $this->student()->create($data),
            'Teacher' => $this->teacher()->create($data),
            'Admin' => $this->admin()->create($data),
            default => throw new \InvalidArgumentException('Unknown role'),
        };

        $this->update([
            'profile_type' => $profile->getMorphClass(),
            'profile_id' => $profile->id,
        ]);

        return $profile;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
