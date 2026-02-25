<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function upload($file, $directory = 'attachments', $attachable, $uploadedBy = null, $disk = 'public', $visibility = 'public', $isPrimary = false)
    {
        // 1. Generate Unique File Name
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // 2. Store File
        // $path = Storage::disk($disk)->putFileAs($directory, $file, $fileName, $visibility);
        $path = $file->storeAs($directory, $fileName, $disk);

        // 3. If Primary, Remove Old Primary
        if ($isPrimary) {
            Attachment::where('link_id', $attachable->id)
                ->where('link_type', get_class($attachable))
                ->where('is_primary', 1)
                ->update(['is_primary' => 0]);
        }

        // 4. Create Attachment Record
        return Attachment::create([
            'link_id'       => $attachable->id,
            'link_type'     => get_class($attachable),

            'file_name'     => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),

            'disk'          => $disk,
            'visibility'    => $visibility,
            'is_primary'    => $isPrimary ? 1 : 0,
            'status'        => 1,
            'uploaded_by'   => $uploadedBy,
        ]);
    }

    public function delete($attachment)
    {
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();
    }
}
