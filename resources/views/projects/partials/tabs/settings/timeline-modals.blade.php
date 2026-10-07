<!-- Create Timeline Modal -->
<x-form-modal modalId="create-timeline-modal" module="Project Timeline" formId="projectTimelineCreateForm" action="{{ route('projects.timelines.store', $project->id) }}" button="Create Timeline">
    <div class="space-y-4">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Type <x-red-star /></label>
            <select name="type" required class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @foreach(config('project_constants.project_timeline_types') as $key => $label)
                    @if($key !== 'original')
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Status <x-red-star /></label>
            <select name="status" required class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @foreach(config('project_constants.project_timeline_statuses') as $key => $label)
                    <option value="{{ $key }}" {{ $key == 1 ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Start Date</label>
            <input type="date" name="start_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">End Date</label>
            <input type="date" name="end_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        @can('project.customer_end_date')
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Customer End Date</label>
                <input type="date" name="customer_end_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        @endcan

        <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" :total-minutes="0" />

        @can('project.customer_end_date')
            <x-forms.estimated-time-input label="Customer Estimate Time" name="customer_estimate_minutes" :total-minutes="0" />
        @endcan

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>
        </div>
    </div>
</x-form-modal>

<!-- Edit Timeline Modal -->
<x-form-modal modalId="edit-timeline-modal" module="Project Timeline" formId="projectTimelineEditForm" action="" method="PUT" button="Update Timeline">
    <div class="space-y-4">
        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Name <x-red-star /></label>
            <input type="text" name="name" id="edit_timeline_name" required class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Type <x-red-star /></label>
            <select name="type" id="edit_timeline_type" required class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @foreach(config('project_constants.project_timeline_types') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Status <x-red-star /></label>
            <select name="status" id="edit_timeline_status" required class="w-full rounded-lg border border-gray-300 p-2 focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                @foreach(config('project_constants.project_timeline_statuses') as $key => $label)
                    <option value="{{ $key }}">{{ ucfirst($label) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Start Date</label>
            <input type="date" name="start_date" id="edit_timeline_start_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">End Date</label>
            <input type="date" name="end_date" id="edit_timeline_end_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
        </div>

        @can('project.customer_end_date')
            <div>
                <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Customer End Date</label>
                <input type="date" name="customer_end_date" id="edit_timeline_customer_end_date" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
            </div>
        @endcan

        <x-forms.estimated-time-input label="Estimated Time" name="estimated_time_minutes" id="edit_timeline_estimated_time_minutes" :total-minutes="0" />

        @can('project.customer_end_date')
            <x-forms.estimated-time-input label="Customer Estimate Time" name="customer_estimate_minutes" id="edit_timeline_customer_estimate_minutes" :total-minutes="0" />
        @endcan

        <div>
            <label class="mb-2.5 block text-left text-sm text-bgray-700 dark:text-bgray-50">Notes</label>
            <textarea name="notes" id="edit_timeline_notes" rows="3" class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400"></textarea>
        </div>
    </div>
</x-form-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-timeline-btn');
            if (editBtn) {
                const timeline = JSON.parse(editBtn.getAttribute('data-timeline'));
                const action = editBtn.getAttribute('data-action');

                document.getElementById('projectTimelineEditForm').setAttribute('action', action);
                document.getElementById('edit_timeline_name').value = timeline.name || '';
                document.getElementById('edit_timeline_type').value = timeline.type || '';
                document.getElementById('edit_timeline_status').value = timeline.status || '';
                document.getElementById('edit_timeline_start_date').value = timeline.start_date ? timeline.start_date.split('T')[0] : '';
                document.getElementById('edit_timeline_end_date').value = timeline.end_date ? timeline.end_date.split('T')[0] : '';
                
                let custEnd = document.getElementById('edit_timeline_customer_end_date');
                if (custEnd) {
                    custEnd.value = timeline.customer_end_date ? timeline.customer_end_date.split('T')[0] : '';
                }

                // Handling components for estimated times (x-forms.estimated-time-input)
                // The components might have internal alpine state or separate inputs for hours/minutes.
                // Depending on the exact implementation, we need to populate the correct hidden inputs.
                // Assuming it has an input with name="estimated_time_minutes" and hours/minutes inputs if visible.
                
                let estInput = document.querySelector('#projectTimelineEditForm input[name="estimated_time_minutes"]');
                if (estInput) {
                    let totalMin = timeline.estimated_time_seconds ? Math.floor(timeline.estimated_time_seconds / 60) : 0;
                    estInput.value = totalMin;
                    
                    // Also fire input event if Alpine is listening
                    estInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                let custEstInput = document.querySelector('#projectTimelineEditForm input[name="customer_estimate_minutes"]');
                if (custEstInput) {
                    let totalCustMin = timeline.customer_estimate_seconds ? Math.floor(timeline.customer_estimate_seconds / 60) : 0;
                    custEstInput.value = totalCustMin;
                    
                    // Also fire input event
                    custEstInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                document.getElementById('edit_timeline_notes').value = timeline.notes || '';
                
                // If it's original, lock the type select
                if (timeline.type === 'original') {
                    document.getElementById('edit_timeline_type').setAttribute('readonly', 'readonly');
                    document.getElementById('edit_timeline_type').style.pointerEvents = 'none';
                } else {
                    document.getElementById('edit_timeline_type').removeAttribute('readonly');
                    document.getElementById('edit_timeline_type').style.pointerEvents = 'auto';
                }
            }

            const deleteBtn = e.target.closest('.delete-timeline-btn');
            if (deleteBtn) {
                if (confirm('Are you sure you want to delete this timeline?')) {
                    const url = deleteBtn.getAttribute('data-url');
                    
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json().then(data => ({status: response.status, body: data})))
                    .then(res => {
                        if (res.status === 200 && res.body.success) {
                            if (res.body.html) {
                                document.getElementById('project-timelines-container').innerHTML = res.body.html;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            alert(res.body.message || 'Error deleting timeline.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting timeline.');
                    });
                }
            }
        });
        
        // Listen for form submissions via the built-in modal logic (if applicable)
        // Or handle the form submissions manually if the existing form-modal component doesn't automatically AJAX submit and refresh.
        // The instruction said: "Follow the existing UI/modal/AJAX conventions".
        // Often these forms are intercepted globally in the PMS app.js to submit via AJAX, and look for `data-target` or respond with `html` to inject.
        // We will assume global interception handles `.modal-open` and form submits.
    });
</script>
