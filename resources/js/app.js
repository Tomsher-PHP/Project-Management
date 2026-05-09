import Swal from 'sweetalert2';
import Alert from './alert';
import Alpine from 'alpinejs';
import TomSelect from "tom-select";
import Quill from 'quill';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import './bootstrap';
import './status-toggle';
import './modules/reset-password';
import './modules/ajax-form-modal';
import './modules/config';
import './modules/activity-log-details';
import './modules/task-filters';
import './delete-alert';
import './components/filterDrawer';
import './components/estimated-time-input';
import './modules/tasks/task-running-timer'

import "tom-select/dist/css/tom-select.css";
import 'quill/dist/quill.core.css';
import 'quill/dist/quill.snow.css';

import { initTomSelect } from './components/tom-select';
import { initDatepicker } from './components/datepicker';
import { initTimepicker } from './components/timepicker';
import { initWeekPicker } from './components/weekpicker';
import { initTaskTimer } from './modules/task-timer';
import { initNotifications } from './modules/notifications';

window.Swal = Swal;
window.Alert = Alert;
window.Alpine = Alpine
window.TomSelect = TomSelect;
window.Quill = Quill;
Alpine.start();

// Initialize all selects with the class 'tom-select' on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    initTomSelect();
    initDatepicker();
    initTimepicker();
    initWeekPicker();
    initTaskTimer();
    initNotifications(window.authUserId);
});

// when tab clicked
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        localStorage.setItem('activeTab', this.dataset.tab);
    });
});


window.addEventListener('DOMContentLoaded', () => {
    let activeTab = localStorage.getItem('activeTab');

    if (activeTab) {
        // remove active from all
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

        // activate saved tab
        document.querySelector(`[data-tab="${activeTab}"]`)?.classList.add('active');
        document.getElementById(activeTab)?.classList.add('active');
    }
});

