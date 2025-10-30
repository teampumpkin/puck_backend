<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

/**
 * Each uploaded video (history) of a submission.
 *
 * Fields: id, submission_id, file_path, file_meta (json), uploaded_by (user id), uploaded_at, notes
 * Keep every version so re-uploads don't require re-payment.
 */
class EvaluationSubmissionVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'file_path',
        'file_meta',
        'uploaded_by',
        'notes',
        'report_id',
        'consultation_date',
        'consultation_time',
        'mentorship_weekday',
    ];

    protected $casts = [
        'file_meta' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public $timestamps = true;

    protected $dates = [
        'created_at',
        'updated_at',
        'uploaded_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uploaded_at = now();
        });
    }

    /* --------------------
     | Relationships
     --------------------*/
    public function submission()
    {
        return $this->belongsTo(EvaluationSubmission::class, 'submission_id');
    }

    public function uploader()
    {
        return $this->belongsTo(V4User::class, 'uploaded_by');
    }

    /* --------------------
     | Scopes
     --------------------*/
    public function scopeForSubmission($q, $submissionId)
    {
        return $q->where('submission_id', $submissionId);
    }

    public function scopeByUploader($q, $uploaderId)
    {
        return $q->where('uploaded_by', $uploaderId);
    }

    public function scopeLatest($q)
    {
        return $q->orderBy('created_at', 'desc');
    }

    public function scopeOldest($q)
    {
        return $q->orderBy('created_at', 'asc');
    }

    /* --------------------
     | Helper Methods
     --------------------*/
    public function makeCurrentVersion()
    {
        $this->submission->update(['current_version_id' => $this->id]);
        return $this;
    }

    public function isCurrentVersion()
    {
        return $this->submission && $this->submission->current_version_id === $this->id;
    }

    public function getVersionNumber()
    {
        return $this->submission->versions()
            ->where('created_at', '<=', $this->created_at)
            ->count();
    }

    /* --------------------
     | Accessors
     --------------------*/
    public function getFileSizeAttribute()
    {
        return $this->file_meta['size'] ?? null;
    }

    public function getFileTypeAttribute()
    {
        return $this->file_meta['type'] ?? null;
    }

    public function getFileDurationAttribute()
    {
        return $this->file_meta['duration'] ?? null;
    }

    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        // Generate URL from storage path
        return Storage::disk('s3')->url($this->file_path);
    }

    public function getFormattedFileSizeAttribute()
    {
        $size = $this->file_size;
        if (!$size)
            return null;

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getFormattedDurationAttribute()
    {
        $duration = $this->file_duration;
        if (!$duration)
            return null;

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function report()
    {
        return $this->belongsTo(Evaluation::class, 'report_id');
    }

    public function consultationFeedbacks()
    {
        return $this->hasMany(V4ConsultationFeedback::class);
    }

    public function consultationRequest()
    {
        return $this->hasOne(V4ConsultationRequest::class);
    }
}
