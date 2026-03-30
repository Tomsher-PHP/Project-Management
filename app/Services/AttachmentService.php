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

    /**
     * Upload a file and attach it to a model.
     *
     * @param \Illuminate\Http\UploadedFile|string $file
     *        The uploaded file instance or file path to be stored.
     *
     * @param string $directory
     *        The target directory where the file will be stored
     *        (default: 'attachments').
     *
     * @param \Illuminate\Database\Eloquent\Model $attachable
     *        The Eloquent model instance that the file will be attached to
     *        (polymorphic relationship).
     *
     * @param int|null $uploadedBy
     *        The ID of the user who uploaded the file.
     *        Typically auth()->id(). Nullable.
     *
     * @param string $disk
     *        The storage disk defined in config/filesystems.php
     *        (default: 'public').
     *
     * @param string $visibility
     *        The file visibility ('public' or 'private').
     *        Determines file access level.
     *
     * @param bool $isPrimary
     *        Indicates whether this file should be marked as the primary attachment
     *        for the given model (default: false).
     *
     * @param string|null $category
     *        Optional category used to group or scope the attachment.
     *
     * @return \App\Models\Attachment|null
     *        Returns the created Attachment model instance
     *        or null on failure.
     */
    public function upload($file, $directory = 'attachments', $attachable, $disk = 'public', $visibility = 'public', $isPrimary = false, $category = null)
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
            'category'      => $category,

            'file_name'     => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),

            'disk'          => $disk,
            'visibility'    => $visibility,
            'is_primary'    => $isPrimary ? 1 : 0,
            'status'        => 1,
        ]);
    }

    /**
     * Delete an attachment record and its physical file from storage.
     *
     * @param \App\Models\Attachment $attachment
     *        The Attachment model instance to be deleted.
     *        This includes both the database record and the stored file.
     *
     * @return bool|null
     *        Returns true on successful deletion,
     *        false if deletion fails,
     *        or null if the model delete method returns null.
     */
    public function delete($attachments)
    {
        $deleted = true;

        foreach ($attachments as $attachment) {
            $disk = $attachment->disk ?? 'public';
            if ($attachment->file_path && Storage::disk($disk)->exists($attachment->file_path)) {
                Storage::disk($disk)->delete($attachment->file_path);
            }

            $deleted = $attachment->delete() && $deleted;
        }

        return $deleted;
    }
}
