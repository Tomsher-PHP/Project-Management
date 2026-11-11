<?php

namespace App\Http\Controllers;

use App\Models\FeatureUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureUpdateController extends Controller
{
    
    /**
     * Display all feature announcements.
     */
    public function index()
    {
        $features = FeatureUpdate::query()
            ->latest()
            ->paginate(15);

        return view('feature-updates.index', compact('features'));
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('feature-updates.create');
    }

    /**
     * Store a new feature announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'feature_type' => [
                'required',
                Rule::in([
                    'new_feature',
                    'improvement',
                    'important_update',
                    'bug_fix',
                ]),
            ],
            'module_name' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'cover_image' => [
                'nullable',
                'image',
                'max:5120',
            ],
            'screenshots' => ['nullable', 'array'],
            'screenshots.*' => [
                'image',
                'max:5120',
            ],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'target_type' => [
                'required',
                Rule::in(['all', 'roles', 'users']),
            ],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high']),
            ],
            'is_mandatory' => ['nullable', 'boolean'],
            'status' => [
                'required',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['title']);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request
                ->file('cover_image')
                ->store('feature-updates/covers', 'public');
        }

        $screenshots = [];

        foreach ($request->file('screenshots', []) as $image) {
            $screenshots[] = $image->store(
                'feature-updates/screenshots',
                'public'
            );
        }

        $validated['screenshots'] = $screenshots;

        FeatureUpdate::create($validated);

        return redirect()
            ->route('feature-settings.index')
            ->with('success', 'Feature announcement created successfully.');
    }

    /**
     * Show the edit form.
     */
    public function edit(FeatureUpdate $featureUpdate)
    {
        return view('feature-updates.edit', compact('featureUpdate'));
    }

    /**
     * Update an existing feature announcement.
     */
    public function update(Request $request, FeatureUpdate $featureUpdate)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'feature_type' => [
                'required',
                Rule::in([
                    'new_feature',
                    'improvement',
                    'important_update',
                    'bug_fix',
                ]),
            ],
            'module_name' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'cover_image' => [
                'nullable',
                'image',
                'max:5120',
            ],
            'screenshots' => ['nullable', 'array'],
            'screenshots.*' => [
                'image',
                'max:5120',
            ],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'target_type' => [
                'required',
                Rule::in(['all', 'roles', 'users']),
            ],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high']),
            ],
            'is_mandatory' => ['nullable', 'boolean'],
            'status' => [
                'required',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');

        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('cover_image')) {
            if ($featureUpdate->cover_image) {
                Storage::disk('public')->delete($featureUpdate->cover_image);
            }

            $validated['cover_image'] = $request
                ->file('cover_image')
                ->store('feature-updates/covers', 'public');
        }

        if ($request->hasFile('screenshots')) {
            $existingScreenshots = $featureUpdate->screenshots ?? [];

            $newScreenshots = [];

            foreach ($request->file('screenshots') as $image) {
                $newScreenshots[] = $image->store(
                    'feature-updates/screenshots',
                    'public'
                );
            }

            $validated['screenshots'] = array_merge(
                $existingScreenshots,
                $newScreenshots
            );
        } else {
            unset($validated['screenshots']);
        }

        $featureUpdate->update($validated);

        return redirect()
            ->route('feature-settings.index')
            ->with('success', 'Feature announcement updated successfully.');
    }

    /**
     * Delete a feature announcement.
     */
    public function destroy(FeatureUpdate $featureUpdate)
    {
        if ($featureUpdate->cover_image) {
            Storage::disk('public')->delete($featureUpdate->cover_image);
        }

        foreach ($featureUpdate->screenshots ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }

        $featureUpdate->delete();

        return redirect()
            ->route('feature-settings.index')
            ->with('success', 'Feature announcement deleted successfully.');
    }

    /**
     * Generate a unique slug.
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (FeatureUpdate::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
