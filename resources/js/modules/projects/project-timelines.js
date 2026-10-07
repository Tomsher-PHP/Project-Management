document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-timeline-btn');
        if (editBtn) {
            const timeline = JSON.parse(editBtn.getAttribute('data-timeline'));
            const action = editBtn.getAttribute('data-action');

            document.getElementById('projectTimelineEditForm').setAttribute('action', action);
            document.getElementById('edit_timeline_name').value = timeline.name || '';
            
            const setSelectValue = (id, val) => {
                const el = document.getElementById(id);
                if (el) {
                    if (el.tomselect) {
                        el.tomselect.setValue(val);
                    } else {
                        el.value = val;
                    }
                }
            };

            setSelectValue('edit_timeline_type', timeline.type || '');
            setSelectValue('edit_timeline_status', timeline.status || '');

            const setDate = (id, val) => {
                const el = document.getElementById(id);
                if (el) {
                    if (el._flatpickr) {
                        el._flatpickr.setDate(val);
                    } else {
                        el.value = val;
                    }
                }
            };

            setDate('edit_timeline_start_date', timeline.start_date ? timeline.start_date.split('T')[0] : '');
            setDate('edit_timeline_end_date', timeline.end_date ? timeline.end_date.split('T')[0] : '');
            setDate('edit_timeline_customer_end_date', timeline.customer_end_date ? timeline.customer_end_date.split('T')[0] : '');

            let estInput = document.getElementById('edit_timeline_estimated_time_minutes');
            if (estInput) {
                let totalMin = timeline.estimated_time_seconds ? Math.floor(timeline.estimated_time_seconds / 60) : 0;
                estInput.value = totalMin;
                estInput.closest('[data-estimated-time]')?.dispatchEvent(new Event('estimated-time:refresh'));
            }

            let custEstInput = document.getElementById('edit_timeline_customer_estimate_minutes');
            if (custEstInput) {
                let totalCustMin = timeline.customer_estimate_seconds ? Math.floor(timeline.customer_estimate_seconds / 60) : 0;
                custEstInput.value = totalCustMin;
                custEstInput.closest('[data-estimated-time]')?.dispatchEvent(new Event('estimated-time:refresh'));
            }

            document.getElementById('edit_timeline_notes').value = timeline.notes || '';
            
            // If it's original, lock the type select
            const typeEl = document.getElementById('edit_timeline_type');
            if (timeline.type === 'original') {
                if (typeEl && typeEl.tomselect) {
                    typeEl.tomselect.lock();
                } else if (typeEl) {
                    typeEl.setAttribute('readonly', 'readonly');
                    typeEl.style.pointerEvents = 'none';
                }
            } else {
                if (typeEl && typeEl.tomselect) {
                    typeEl.tomselect.unlock();
                } else if (typeEl) {
                    typeEl.removeAttribute('readonly');
                    typeEl.style.pointerEvents = 'auto';
                }
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
});