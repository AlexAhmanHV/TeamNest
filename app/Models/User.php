<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_user_id');
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by_user_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_user_id');
    }

    public function workspaceNotifications(): HasMany
    {
        return $this->hasMany(WorkspaceNotification::class);
    }

    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'uploaded_by_user_id');
    }

    public function taskSavedViews(): HasMany
    {
        return $this->hasMany(TaskSavedView::class);
    }

    public function roleInWorkspace(Workspace $workspace): ?string
    {
        $membership = $this->workspaces->firstWhere('id', $workspace->id);

        return $membership?->pivot?->role;
    }

    public function isAdminInWorkspace(Workspace $workspace): bool
    {
        return $this->roleInWorkspace($workspace) === 'admin';
    }
}
