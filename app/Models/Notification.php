<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'v4_user_id',
        'title',
        'message',
        'icon',
        'icon_type',
        'icon_color',
        'image_url',
        'type',
        'data',
        'read_at',
        'sent_via',
        'status',
        'redirect_url',
        'reference_type',
        'reference_id',
        'action_type',
    ];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'v4_user_id' => 'string', // Cast UUID as string
    ];

    // Icon types
    const ICON_TYPE_DEFAULT  = 'default';
    const ICON_TYPE_URL      = 'url';
    const ICON_TYPE_ASSET    = 'asset';
    const ICON_TYPE_MATERIAL = 'material';
    const ICON_TYPE_CUSTOM   = 'custom';

    // Default icons for different notification types
    const DEFAULT_ICONS = [
        'order_created'   => '📦',
        'order_confirmed' => '✅',
        'order_shipped'   => '🚚',
        'order_delivered' => '🎁',
        'order_cancelled' => '❌',
        'payment_success' => '💳',
        'payment_failed'  => '⚠️',
        'new_message'     => '💬',
        'friend_request'  => '👤',
        'friend_accepted' => '🤝',
        'post_created'    => '📝',
        'post_updated'    => '✏️',
        'comment_added'   => '💭',
        'like_received'   => '❤️',
        'announcement'    => '📢',
        'warning'         => '⚠️',
        'info'            => 'ℹ️',
        'success'         => '✅',
        'error'           => '❌',
        'general'         => '🔔',
    ];

    // Material icons for different types
    const MATERIAL_ICONS = [
        'order_created'   => 'inventory_2',
        'order_confirmed' => 'check_circle',
        'order_shipped'   => 'local_shipping',
        'order_delivered' => 'package',
        'order_cancelled' => 'cancel',
        'payment_success' => 'payment',
        'payment_failed'  => 'error',
        'new_message'     => 'message',
        'friend_request'  => 'person_add',
        'friend_accepted' => 'people',
        'post_created'    => 'post_add',
        'post_updated'    => 'edit',
        'comment_added'   => 'comment',
        'like_received'   => 'favorite',
        'announcement'    => 'campaign',
        'warning'         => 'warning',
        'info'            => 'info',
        'success'         => 'check_circle',
        'error'           => 'error',
        'general'         => 'notifications',
    ];

    public function user()
    {
        return $this->belongsTo(V4User::class, 'v4_user_id');
    }

    // Polymorphic relationship for reference to other models
    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $v4UserId)
    {
        return $query->where('v4_user_id', $v4UserId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithReference($query, $referenceType, $referenceId)
    {
        return $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }

    // Include trashed notifications in queries when needed
    public function scopeWithTrashed($query)
    {
        return $query->whereNull('deleted_at')->orWhereNotNull('deleted_at');
    }

    public function scopeOnlyTrashed($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
        return $this;
    }

    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    public function isRead()
    {
        return ! is_null($this->read_at);
    }

    public function isUnread()
    {
        return $this->read_at === null;
    }

    // Soft delete instead of permanent delete
    public function softDelete()
    {
        return $this->delete();
    }

    // Restore soft deleted notification
    public function restoreNotification()
    {
        return $this->restore();
    }

    // Icon Methods
    public function getIconAttribute($value)
    {
        // If no icon is set, return default based on type
        if (is_null($value)) {
            return $this->getDefaultIcon();
        }

        return $value;
    }

    public function getIconTypeAttribute($value)
    {
        return $value ?: self::ICON_TYPE_DEFAULT;
    }

    /**
     * Get the complete icon URL/path based on type
     */
    public function getIconUrl()
    {
        switch ($this->icon_type) {
            case self::ICON_TYPE_URL:
                return $this->icon;

            case self::ICON_TYPE_ASSET:
                return asset($this->icon);

            case self::ICON_TYPE_MATERIAL:
                return $this->icon; // Material icon name

            case self::ICON_TYPE_CUSTOM:
                return $this->icon;

            default: // default type - emoji or default icon
                return $this->icon;
        }
    }

    /**
     * Get default icon based on notification type
     */
    protected function getDefaultIcon()
    {
        return self::DEFAULT_ICONS[$this->type] ?? self::DEFAULT_ICONS['general'];
    }

    /**
     * Get material icon based on notification type
     */
    public function getMaterialIcon()
    {
        return self::MATERIAL_ICONS[$this->type] ?? self::MATERIAL_ICONS['general'];
    }

    /**
     * Set icon based on type and provided value
     */
    public function setIcon($icon, $type = self::ICON_TYPE_DEFAULT, $color = null)
    {
        $this->update([
            'icon'       => $icon,
            'icon_type'  => $type,
            'icon_color' => $color,
        ]);

        return $this;
    }

    /**
     * Set default icon based on notification type
     */
    public function setDefaultIcon()
    {
        $icon = $this->getDefaultIcon();
        return $this->setIcon($icon, self::ICON_TYPE_DEFAULT);
    }

    /**
     * Set material icon
     */
    public function setMaterialIcon($iconName = null, $color = null)
    {
        $icon = $iconName ?: $this->getMaterialIcon();
        return $this->setIcon($icon, self::ICON_TYPE_MATERIAL, $color);
    }

    /**
     * Set URL icon
     */
    public function setUrlIcon($url, $color = null)
    {
        return $this->setIcon($url, self::ICON_TYPE_URL, $color);
    }

    /**
     * Set asset icon
     */
    public function setAssetIcon($assetPath, $color = null)
    {
        return $this->setIcon($assetPath, self::ICON_TYPE_ASSET, $color);
    }

    // Get redirect URL based on type and reference
    public function getRedirectUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Generate dynamic URL based on reference
        return $this->generateDynamicUrl();
    }

    protected function generateDynamicUrl()
    {
        $baseUrl = config('app.url');

        switch ($this->type) {
            case 'order_created':
            case 'order_updated':
            case 'order_completed':
                return "{$baseUrl}/orders/{$this->reference_id}";

            case 'post_created':
            case 'post_updated':
                return "{$baseUrl}/posts/{$this->reference_id}";

            case 'comment_added':
                return "{$baseUrl}/posts/{$this->getPostIdFromComment()}";

            case 'message_received':
                return "{$baseUrl}/messages/{$this->reference_id}";

            case 'friend_request':
                return "{$baseUrl}/profile/{$this->reference_id}";

            case 'announcement':
                return "{$baseUrl}/announcements/{$this->reference_id}";

            default:
                return "{$baseUrl}/notifications";
        }
    }

    protected function getPostIdFromComment()
    {
        if ($this->reference_type === 'App\Models\Comment' && $this->reference) {
            return $this->reference->post_id;
        }
        return null;
    }

    // Get action type for mobile apps
    public function getActionType()
    {
        return $this->action_type ?: $this->type;
    }

    /**
     * Get image URL for notification
     */
    public function getImageUrl()
    {
        if ($this->image_url) {
            if (Str::startsWith($this->image_url, ['http://', 'https://'])) {
                return $this->image_url;
            }
            return asset($this->image_url);
        }

        return null;
    }

    /**
     * Set notification image
     */
    public function setImage($urlOrPath, $isFullUrl = false)
    {
        $imageUrl = $isFullUrl ? $urlOrPath : $urlOrPath;

        $this->update(['image_url' => $imageUrl]);
        return $this;
    }
}
