function getProjectFlowIcon(flow) {
    if (!flow) return '';
    const isAgile = flow.toLowerCase() === 'agile';
    if (isAgile) {
        return `<span class="inline-flex shrink-0 items-center justify-center border border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100 h-5 w-5 rounded bg-success-50" title="Project Flow: Agile"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-success-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0v4m0-4l-6 6"></path><path stroke-linecap="round" stroke-linejoin="round" d="M7 17h4m-4 0v-4m0 4l10-10" opacity=".45"></path></svg></span>`;
    } else {
        return `<span class="inline-flex shrink-0 items-center justify-center border border-bgray-200 text-bgray-700 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-100 h-5 w-5 rounded bg-bgray-100" title="Project Flow: Linear"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 8l4 4-4 4"></path></svg></span>`;
    }
}

window.openHandoffViewModal = function(data) {
    document.getElementById('viewModalDate').textContent = data.date;
    document.getElementById('viewModalRequestedBy').textContent = data.requestedBy;
    
    const projectEl = document.getElementById('viewModalProject');
    projectEl.innerHTML = getProjectFlowIcon(data.projectFlow) + '<span>' + data.project + '</span>';
    
    document.getElementById('viewModalMilestone').textContent = data.milestone;
    document.getElementById('viewModalSprint').textContent = data.sprint;
    document.getElementById('viewModalSourceTask').textContent = data.sourceTask;
    document.getElementById('viewModalCreatedTask').textContent = data.createdTask;
    document.getElementById('viewModalPurpose').textContent = data.purpose;
    document.getElementById('viewModalStatus').textContent = data.status;
    document.getElementById('viewModalDescription').textContent = data.description;
    
    const modal = document.getElementById('handoffViewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

window.closeHandoffViewModal = function() {
    const modal = document.getElementById('handoffViewModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}
