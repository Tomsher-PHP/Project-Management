import Alert from '../../alert';

document.addEventListener('DOMContentLoaded', function () {
    const editBtns = document.querySelectorAll('.project-edit-btn');
    const modal = document.getElementById('project-edit-modal');
    
    if (!modal) return;
    
    const form = document.getElementById('projectEditForm');
    
    // We assume there's a global toggleModal function, or we can just trigger it if it exists.
    // In this codebase, modals are typically toggled via classes or data attributes.
    // If <x-form-modal> is used, clicking a button with data-target="#project-edit-modal" and .modal-open opens it.
    // We added the click listener here to populate data BEFORE opening the modal.
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const project = JSON.parse(this.getAttribute('data-project') || this.closest('[data-project]').getAttribute('data-project'));
            
            // Set form action
            form.action = `/projects/${project.id}`;
            
            // Populate fields
            document.getElementById('edit_project_name').value = project.name || '';
            document.getElementById('edit_project_domain').value = project.domain || '';
            document.getElementById('edit_project_start_date').value = project.start_date ? project.start_date.split('T')[0] : '';
            document.getElementById('edit_project_end_date').value = project.end_date ? project.end_date.split('T')[0] : '';
            
            if (document.getElementById('edit_project_customer_end_date')) {
                document.getElementById('edit_project_customer_end_date').value = project.customer_end_date ? project.customer_end_date.split('T')[0] : '';
            }
            
            // AlpineJS state for billable
            const billableWrapper = document.getElementById('edit_project_default_billable_wrapper');
            if (billableWrapper && billableWrapper.__x) {
                billableWrapper.__x.$data.billable = !!project.default_billable;
            } else {
                // fallback if alpine hasn't initialized or we want to force hidden input
                document.getElementById('edit_project_default_billable_input').value = project.default_billable ? '1' : '0';
                if(project.default_billable) {
                    document.getElementById('edit_project_default_billable').classList.add('active');
                } else {
                    document.getElementById('edit_project_default_billable').classList.remove('active');
                }
            }
            
            // Update Estimated Time (minutes)
            // Update Estimated Time (minutes)
            const estTimeInput = document.getElementById('edit_project_estimated_time_minutes');
            if (estTimeInput) {
                estTimeInput.value = project.estimated_time_seconds ? Math.floor(project.estimated_time_seconds / 60) : 0;
                estTimeInput.closest('[data-estimated-time]')?.dispatchEvent(new Event('estimated-time:refresh'));
            }
            
            const custEstTimeInput = document.getElementById('edit_project_customer_estimate_minutes');
            if (custEstTimeInput) {
                custEstTimeInput.value = project.customer_estimate_seconds ? Math.floor(project.customer_estimate_seconds / 60) : 0;
                custEstTimeInput.closest('[data-estimated-time]')?.dispatchEvent(new Event('estimated-time:refresh'));
            }

            // Select fields (Customer, Priority, Parent, Sales Person)
            const customerSelect = document.getElementById('edit_project_customer_id');
            if (customerSelect && customerSelect.tomselect) {
                customerSelect.tomselect.setValue(project.customer_id || '');
            } else if (customerSelect) {
                customerSelect.value = project.customer_id || '';
            }
            
            const prioritySelect = document.getElementById('edit_project_priority');
            if (prioritySelect && prioritySelect.tomselect) {
                prioritySelect.tomselect.setValue(project.priority || '');
            } else if (prioritySelect) {
                prioritySelect.value = project.priority || '';
            }
            
            const parentSelect = document.getElementById('edit_project_parent_project_id');
            if (parentSelect && parentSelect.tomselect) {
                parentSelect.tomselect.clearOptions();
                if (project.parent_project_id && project.parent_project) {
                    parentSelect.tomselect.addOption({
                        value: project.parent_project.id,
                        text: project.parent_project.name,
                        subtype: project.parent_project.project_code || '--'
                    });
                }
                parentSelect.tomselect.setValue(project.parent_project_id || '');
            } else if (parentSelect) {
                parentSelect.value = project.parent_project_id || '';
            }
            
            const salesPersonSelect = document.getElementById('edit_project_sales_person_id');
            if (salesPersonSelect && salesPersonSelect.tomselect) {
                salesPersonSelect.tomselect.setValue(project.sales_person_id || '');
            } else if (salesPersonSelect) {
                salesPersonSelect.value = project.sales_person_id || '';
            }

            // Multiselect fields (Category, Technology)
            const categorySelect = document.getElementById('edit_project_category_ids');
            if (categorySelect && categorySelect.tomselect) {
                // If the project model eager loads project_categories
                const cats = (project.project_categories || project.categories || []).map(c => c.id);
                // Also if we have project_category_ids attribute appended
                const catIds = project.project_category_ids || cats;
                categorySelect.tomselect.setValue(catIds);
            }
            
            const techSelect = document.getElementById('edit_project_technology_ids');
            if (techSelect && techSelect.tomselect) {
                const techs = (project.technologies || []).map(t => t.id);
                techSelect.tomselect.setValue(techs);
            }

            // Open Modal
            modal.classList.remove('hidden');
            
            // Add a class to body to prevent scrolling if needed
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Close modal handling
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    const closeBtns = modal.querySelectorAll('.modal-close');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    // Close on click outside
    modal.addEventListener('click', function (e) {
        if (!e.target.closest('.modal-content')) {
            closeModal();
        }
    });

    // Form submit handling via AJAX
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Updating...';
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST', // POST with _method=PUT from FormData
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json().then(data => ({status: response.status, body: data})))
        .then(res => {
            if (res.status === 200 || res.status === 201) {
                Alert.success(res.body.message || 'Project updated successfully');
                // Reload the page to reflect changes
                setTimeout(() => window.location.reload(), 1000);
            } else if (res.status === 422) {
                // Validation errors
                let errors = res.body.errors;
                let firstError = Object.values(errors)[0][0];
                Alert.error(firstError || 'Validation error');
            } else {
                Alert.error(res.body.message || 'Error updating project');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Alert.error('Network error occurred');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
