<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Configuration extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'timezone',
        'date_format',
        'time_format',
    ];

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'link', 'link_type', 'link_id');
    }

    public function getLogoAttribute()
    {
        return $this->logoAttachment()?->file_path;
    }

    public function getLogoUrlAttribute()
    {
        $attachment = $this->logoAttachment();

        return $attachment
            ? Storage::disk($attachment->disk)->url($attachment->file_path)
            : null;
    }

    private function logoAttachment()
    {
        return $this->attachments()
            ->where('file_path', 'like', 'configurations/logo/%')
            ->latest('id')
            ->first();
    }
}
