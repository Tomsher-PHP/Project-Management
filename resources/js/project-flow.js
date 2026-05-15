document.addEventListener('DOMContentLoaded', function () {

    const flowSelect = document.getElementById('project-flow-filter');
    const projectSelect = document.getElementById('project-filter');

    if (!flowSelect || !projectSelect) return;

    flowSelect.addEventListener('change', function () {

        let selectedFlows = Array.from(flowSelect.selectedOptions)
            .map(option => option.value);

        const params = new URLSearchParams();

        selectedFlows.forEach(flow => {
            params.append('project_flow[]', flow);
        });

        const url = window.reportConfig.projectsByFlowUrl;
        

        fetch(`${url}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {

                projectSelect.innerHTML = '';

                data.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.id;
                    option.textContent = project.name;
                    projectSelect.appendChild(option);
                });

                if (projectSelect.tomselect) {
                    projectSelect.tomselect.clearOptions();

                    data.projects.forEach(project => {
                        projectSelect.tomselect.addOption({
                            value: project.id,
                            text: project.name
                        });
                    });

                    projectSelect.tomselect.refreshOptions(false);
                }
            });
    });
});