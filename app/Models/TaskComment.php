<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'body', 'type', 'reply_to_id'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'reply_to_id');
    }

    /**
     * API uses snake_case (reply_to); Eloquent relation key is replyTo.
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        if ($this->relationLoaded('replyTo')) {
            $parent = $this->getRelation('replyTo');
            $data['reply_to'] = $parent ? $parent->toArray() : null;
        }

        unset($data['replyTo']);

        return $data;
    }
}
