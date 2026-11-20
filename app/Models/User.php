<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use App\Models\Daily;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ALL_AI_MODELS = [
        'gemini-4-carbon',
        'gemini-4-argon',
        'gemini-4-neon',
        'gemini-4-hydrogen',
        'gemini-3.5-flash-lite',
        'gemini-3.8-flash',
        'gemini-3.7-flash',
        'gemini-3.1-flash-lite',
        'gemini-flash-lite-latest',
        'gemini-3.1-pro-preview',
        'gemma-4-31b-it',
        'gpt-oss:120b-cloud',
        'qwen3:14b',
    ];

    public const ALL_AI_MODELS_DETAILS = [
        ['id' => 'gemini-4-carbon', 'name' => 'Gemini 4 Carbon', 'tag' => 'Carbon Core'],
        ['id' => 'gemini-4-argon', 'name' => 'Gemini 4 Argon', 'tag' => 'Ultra Fast'],
        ['id' => 'gemini-4-neon', 'name' => 'Gemini 4 Neon', 'tag' => 'Visual Analytics'],
        ['id' => 'gemini-4-hydrogen', 'name' => 'Gemini 4 Hydrogen', 'tag' => 'Deep Reasoning'],
        ['id' => 'gemini-3.5-flash-lite', 'name' => 'Gemini 3.5 Flash-Lite', 'tag' => 'Recommended'],
        ['id' => 'gemini-3.8-flash', 'name' => 'Gemini 3.8 Flash', 'tag' => 'High Performance'],
        ['id' => 'gemini-3.7-flash', 'name' => 'Gemini 3.7 Flash', 'tag' => 'Hybrid Thinking'],
        ['id' => 'gemini-3.1-flash-lite', 'name' => 'Gemini 3.1 Flash-Lite', 'tag' => 'Low Latency'],
        ['id' => 'gemini-flash-lite-latest', 'name' => 'Gemini Flash-Lite Latest', 'tag' => 'Stable'],
        ['id' => 'gemini-3.1-pro-preview', 'name' => 'Gemini 3.1 Pro', 'tag' => 'Complex Logic'],
        ['id' => 'gemma-4-31b-it', 'name' => 'Gemma 4 31B IT', 'tag' => 'Open Weights'],
        ['id' => 'gpt-oss:120b-cloud', 'name' => 'GPT-OSS 120B Cloud', 'tag' => 'Deep Reasoning'],
        ['id' => 'qwen3:14b', 'name' => 'Qwen 3 14B', 'tag' => 'Local'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'password',
        'allowed_ai_crud',
        'can_select_ai_model',
        'allowed_ai_models',
        'default_ai_model',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'allowed_ai_crud' => 'array',
            'can_select_ai_model' => 'boolean',
            'allowed_ai_models' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected $appends = [
        'can_select_ai_model_computed',
        'effective_allowed_ai_models',
        'effective_default_ai_model',
    ];

    protected $with = ["role"];

    function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if this user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->is_super_admin;
    }

    /**
     * Get all supported model IDs.
     */
    public static function allModelIds(): array
    {
        return self::ALL_AI_MODELS;
    }

    /**
     * Check if this user has a specific permission.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role && $this->role->hasPermission($key);
    }

    /**
     * Determine if this user is allowed to select an AI model.
     * Super Admin is ALWAYS allowed by default.
     */
    public function canSelectAIModel(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->can_select_ai_model !== null) {
            return (bool) $this->can_select_ai_model;
        }

        return $this->hasPermission('ai.select_model');
    }

    /**
     * Accessor for computed permission to select AI model.
     */
    public function getCanSelectAiModelComputedAttribute(): bool
    {
        return $this->canSelectAIModel();
    }

    /**
     * Get the list of AI model IDs this user is authorized to use.
     * Super Admin ALWAYS has all models by default.
     */
    public function getAllowedAIModels(): array
    {
        if ($this->isSuperAdmin()) {
            return self::allModelIds();
        }

        // If user has a specific allowed list configured
        if (!empty($this->allowed_ai_models) && is_array($this->allowed_ai_models)) {
            return array_values($this->allowed_ai_models);
        }

        // If role has a specific allowed list configured
        if ($this->role && !empty($this->role->allowed_ai_models) && is_array($this->role->allowed_ai_models)) {
            return array_values($this->role->allowed_ai_models);
        }

        // If user cannot select models, restrict strictly to their default model
        if (!$this->canSelectAIModel()) {
            return [$this->getDefaultAIModel()];
        }

        // By default if allowed to select models and no restriction specified, all models
        return self::allModelIds();
    }

    /**
     * Accessor for effective allowed AI models.
     */
    public function getEffectiveAllowedAiModelsAttribute(): array
    {
        return $this->getAllowedAIModels();
    }

    /**
     * Get the default AI model assigned to this user.
     */
    public function getDefaultAIModel(): string
    {
        if (!empty($this->default_ai_model)) {
            return $this->default_ai_model;
        }

        if ($this->role && !empty($this->role->default_ai_model)) {
            return $this->role->default_ai_model;
        }

        return 'gemini-4-carbon';
    }

    /**
     * Accessor for effective default AI model.
     */
    public function getEffectiveDefaultAiModelAttribute(): string
    {
        return $this->getDefaultAIModel();
    }

    /**
     * Check if a given model is allowed for this user.
     */
    public function isModelAllowed(string $modelId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->canSelectAIModel()) {
            return $modelId === $this->getDefaultAIModel();
        }

        $allowed = $this->getAllowedAIModels();
        return empty($allowed) || in_array($modelId, $allowed, true);
    }
}
