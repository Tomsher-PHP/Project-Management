<?php

namespace App\Http\Controllers;

use App\Models\LeaveNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class LeaveNotificationSettingController extends Controller
{
    public function edit()
    {
        $setting = LeaveNotificationSetting::firstOrCreate(
            ['id' => 1],
            [
                'user_ids' => [],
                'external_emails' => [],
                'is_enabled' => true,
            ]
        );

        $users = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $selectedUserIds = $setting->user_ids ?? [];
        $externalEmails = $setting->external_emails ?? [];

        // Build a single recipient list for the combined input.
        $selectedRecipients = [];

        foreach ($selectedUserIds as $userId) {
            $user = $users->firstWhere('id', (int) $userId);

            if ($user) {
                $selectedRecipients[] = 'user:' . $user->id;
            }
        }

        foreach ($externalEmails as $email) {
            $selectedRecipients[] = 'email:' . strtolower(trim($email));
        }

        return view('settings.leave_notifications.edit', [
            'setting' => $setting,
            'users' => $users,
            'selectedRecipients' => $selectedRecipients,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'recipients' => ['nullable', 'array'],
            'recipients.*' => ['required', 'string', 'distinct', 'max:255'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $userIds = [];
        $externalEmails = [];

        foreach ($validated['recipients'] ?? [] as $recipient) {
            if (str_starts_with($recipient, 'user:')) {
                $userId = substr($recipient, 5);

                if (!ctype_digit($userId)) {
                    throw ValidationException::withMessages([
                        'recipients' => 'Invalid PMS user selected.',
                    ]);
                }

                $userIds[] = (int) $userId;
            } elseif (str_starts_with($recipient, 'email:')) {
                $email = strtolower(trim(substr($recipient, 6)));

                $validator = Validator::make(
                    ['email' => $email],
                    ['email' => ['required', 'email', 'max:255']]
                );

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'recipients' => "The email address '{$email}' is invalid.",
                    ]);
                }

                $externalEmails[] = $email;
            } else {
                throw ValidationException::withMessages([
                    'recipients' => 'Invalid recipient selected.',
                ]);
            }
        }

        // Confirm every selected PMS user still exists and has an email.
        $validUserIds = User::query()
            ->whereIn('id', array_unique($userIds))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (count($validUserIds) !== count(array_unique($userIds))) {
            throw ValidationException::withMessages([
                'recipients' => 'One or more selected PMS users are invalid or have no email address.',
            ]);
        }

        $userIds = array_values(array_unique($userIds));
        $externalEmails = array_values(array_unique($externalEmails));

        // Avoid sending twice to the same address if it is selected
        // as both a PMS user and an external recipient.
        $userEmails = User::whereIn('id', $userIds)
            ->pluck('email')
            ->map(fn($email) => strtolower(trim($email)))
            ->all();

        $externalEmails = array_values(array_diff(
            $externalEmails,
            $userEmails
        ));

        LeaveNotificationSetting::updateOrCreate(
            ['id' => 1],
            [
                'user_ids' => $userIds,
                'external_emails' => $externalEmails,
                'is_enabled' => $request->boolean('is_enabled'),
            ]
        );

        return redirect()
            ->route('settings.leave-notifications.edit')
            ->with('success', 'Leave notification settings saved successfully.');
    }
}
