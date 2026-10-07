import Alert from '../../alert.js';

document.body.addEventListener('click', function(e) {
        const addBtn = e.target.closest('.add-timeline-btn');
        if (addBtn) {
            const modal = document.getElementById('create-timeline-modal');
            const form = document.getElementById('projectTimelineCreateForm');
            if (form) {
                form.reset();
                form.querySelectorAll('.tom-select-no-search').forEach(select => {
                    if (select.tomselect) {
                        select.tomselect.setValue(select.value, true);
                    }
                });
            }
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    const nameField = modal.querySelector('[name="name"]');
                    if (nameField) nameField.focus();
                }, 100);
            }
        }

        const editBtn = e.target.closest('.edit-timeline-btn');
        if (editBtn) {
            e.preventDefault();
            const modal = document.getElementById('edit-timeline-modal');
            if (modal) modal.classList.remove('hidden');

            try {
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

                const minDate = editBtn.getAttribute('data-min-date');
                const maxDate = editBtn.getAttribute('data-max-date');

                const setDate = (id, val, minD, maxD) => {
                    const el = document.getElementById(id);
                    if (el) {
                        if (el._flatpickr) {
                            if (minD) el._flatpickr.set('minDate', minD);
                            else el._flatpickr.set('minDate', null);
                            
                            if (maxD) el._flatpickr.set('maxDate', maxD);
                            else el._flatpickr.set('maxDate', null);

                            el._flatpickr.setDate(val);
                        } else {
                            el.value = val;
                        }
                    }
                };

                setDate('edit_timeline_start_date', timeline.start_date ? timeline.start_date.split('T')[0] : '', minDate, maxDate);
                setDate('edit_timeline_end_date', timeline.end_date ? timeline.end_date.split('T')[0] : '', minDate, maxDate);
                setDate('edit_timeline_customer_end_date', timeline.customer_end_date ? timeline.customer_end_date.split('T')[0] : '', minDate, maxDate);

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
            } catch (err) {
                console.error("Error populating edit modal:", err);
            }
        }
    });

    const handleFormSubmit = (formId) => {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const formData = new FormData(form);
            const url = form.getAttribute('action');

            form.querySelectorAll('.error-text').forEach(el => el.remove());
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json().then(data => ({status: response.status, body: data})))
            .then(res => {
                if (res.status === 200 || res.status === 201) {
                    Alert.success(res.body.message || 'Saved successfully.');
                    form.closest('.modal-form').classList.add('hidden');
                    if (res.body.html) {
                        document.getElementById('project-timelines-container').innerHTML = res.body.html;
                    } else {
                        window.location.reload();
                    }
                } else if (res.status === 422) {
                    const errors = res.body.errors;
                    for (const key in errors) {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.classList.add('border-red-500');
                            input.insertAdjacentHTML('afterend', `<span class="text-red-500 error-text mt-1 text-xs block">${errors[key][0]}</span>`);
                        }
                    }
                } else {
                    Alert.error(res.body.message || 'An error occurred.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Alert.error('An error occurred.');
            });
        });
    };

    handleFormSubmit('projectTimelineCreateForm');
    handleFormSubmit('projectTimelineEditForm');

    const container = document.getElementById('project-timelines-container');
    if (container) {
        container.addEventListener('submit', function(e) {
            if (e.target && e.target.classList.contains('delete-form')) {
                e.preventDefault();
                e.stopPropagation();

                const form = e.target;
                const url = form.getAttribute('action');

                Alert.confirm({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this timeline?',
                    confirmText: 'Yes, delete it',
                    cancelText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: new FormData(form)
                        })
                        .then(response => response.json().then(data => ({status: response.status, body: data})))
                        .then(res => {
                            if (res.status === 200 && res.body.success) {
                                Alert.success(res.body.message || 'Deleted successfully.');
                                if (res.body.html) {
                                    document.getElementById('project-timelines-container').innerHTML = res.body.html;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                Alert.error(res.body.message || 'Unable to delete this record.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Alert.error('Unable to delete this record.');
                        });
                    }
                });
            }
        });
    }