<?php

namespace App\Http\Controllers;

use App\Models\MeetingLocation;
use App\Models\MeetingTag;
use App\Models\MeetingType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MeetingSettingsController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Meeting Settings';
        $this->subTitle = 'Manage reusable meeting types, locations and tags';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));

        $createPermission = 'meeting_settings.create';
        $editPermission = 'meeting_settings.edit';
        $deletePermission = 'meeting_settings.delete';
        $togglePermission = 'meeting_settings.edit';

        if ($request->routeIs('settings.meeting-types.index')) {
            $records = MeetingType::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) MeetingType::max('sort_order')) + 1;
            $currentTab = 'types';
            $entityLabel = 'Meeting Type';
            $entityPluralLabel = 'Meeting Types';
            $storeRoute = route('settings.meeting-types.store');
            $updateRouteName = 'settings.meeting-types.update';
            $destroyRouteName = 'settings.meeting-types.destroy';
            $toggleRoute = 'settings.meeting_type.toggleStatus';
        } elseif ($request->routeIs('settings.meeting-locations.index')) {
            $records = MeetingLocation::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) MeetingLocation::max('sort_order')) + 1;
            $currentTab = 'locations';
            $entityLabel = 'Meeting Location';
            $entityPluralLabel = 'Meeting Locations';
            $storeRoute = route('settings.meeting-locations.store');
            $updateRouteName = 'settings.meeting-locations.update';
            $destroyRouteName = 'settings.meeting-locations.destroy';
            $toggleRoute = 'settings.meeting_location.toggleStatus';
        } elseif ($request->routeIs('settings.meeting-tags.index')) {
            $records = MeetingTag::filter($request->all())->sort($request->all())->paginate($perPage)->withQueryString();
            $nextSortOrder = ((int) MeetingTag::max('sort_order')) + 1;
            $currentTab = 'tags';
            $entityLabel = 'Meeting Tag';
            $entityPluralLabel = 'Meeting Tags';
            $storeRoute = route('settings.meeting-tags.store');
            $updateRouteName = 'settings.meeting-tags.update';
            $destroyRouteName = 'settings.meeting-tags.destroy';
            $toggleRoute = 'settings.meeting_tag.toggleStatus';
        } else {
            abort(403);
        }

        return view('settings.meeting-settings.index', [
            'records' => $records,
            'perPage' => $perPage,
            'nextSortOrder' => $nextSortOrder,
            'currentTab' => $currentTab,
            'entityLabel' => $entityLabel,
            'entityPluralLabel' => $entityPluralLabel,
            'createPermission' => $createPermission,
            'editPermission' => $editPermission,
            'deletePermission' => $deletePermission,
            'togglePermission' => $togglePermission,
            'storeRoute' => $storeRoute,
            'updateRouteName' => $updateRouteName,
            'destroyRouteName' => $destroyRouteName,
            'toggleRoute' => $toggleRoute,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->routeIs('settings.meeting-types.store')) {
            $data = app(\App\Http\Requests\MeetingTypeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingType::class);
                }

                return MeetingType::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting type created successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.meeting-locations.store')) {
            $data = app(\App\Http\Requests\MeetingLocationRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingLocation::class);
                }

                return MeetingLocation::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting location created successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.meeting-tags.store')) {
            $data = app(\App\Http\Requests\MeetingTagRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');
            $record = DB::transaction(function () use ($data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingTag::class);
                }

                return MeetingTag::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting tag created successfully.',
                'data' => $record,
            ]);
        }

        abort(403);
    }

    public function update(Request $request, $id)
    {
        if ($request->routeIs('settings.meeting-types.update')) {
            $data = app(\App\Http\Requests\MeetingTypeRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = MeetingType::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingType::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting type updated successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.meeting-locations.update')) {
            $data = app(\App\Http\Requests\MeetingLocationRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = MeetingLocation::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingLocation::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting location updated successfully.',
                'data' => $record,
            ]);
        } elseif ($request->routeIs('settings.meeting-tags.update')) {
            $data = app(\App\Http\Requests\MeetingTagRequest::class)->validated();
            $data['is_default'] = $request->boolean('is_default');

            $record = MeetingTag::findOrFail($id);
            $record = DB::transaction(function () use ($record, $data) {
                if ($data['is_default']) {
                    $this->clearExistingDefaults(MeetingTag::class, $record->id);
                }

                $record->update($data);

                return $record->refresh();
            });

            return response()->json([
                'status' => true,
                'message' => 'Meeting tag updated successfully.',
                'data' => $record,
            ]);
        }

        abort(403);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->routeIs('settings.meeting-types.destroy')) {
            $record = MeetingType::findOrFail($id);
            $routeName = 'settings.meeting-types.index';
            $entityName = 'Meeting type';
        } elseif ($request->routeIs('settings.meeting-locations.destroy')) {
            $record = MeetingLocation::findOrFail($id);
            $routeName = 'settings.meeting-locations.index';
            $entityName = 'Meeting location';
        } elseif ($request->routeIs('settings.meeting-tags.destroy')) {
            $record = MeetingTag::findOrFail($id);
            $routeName = 'settings.meeting-tags.index';
            $entityName = 'Meeting tag';
        } else {
            abort(403);
        }

        if ($record->is_system) {
            return redirect()
                ->route($routeName)
                ->with('error', "System {$entityName} cannot be deleted.");
        }

        $record->delete();

        return redirect()
            ->route($routeName)
            ->with('success', "{$entityName} deleted successfully.");
    }

    public function toggleStatusMeetingType(Request $request)
    {
        $record = MeetingType::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusMeetingLocation(Request $request)
    {
        $record = MeetingLocation::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    public function toggleStatusMeetingTag(Request $request)
    {
        $record = MeetingTag::findOrFail($request->id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json([
            'success' => true,
            'is_active' => $record->is_active,
            'message' => 'Status updated successfully',
        ], Response::HTTP_OK);
    }

    protected function clearExistingDefaults(string $modelClass, ?int $exceptId = null): void
    {
        $query = $modelClass::query();

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
