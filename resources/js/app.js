import './bootstrap';
import Swal from 'sweetalert2';
import Alert from './alert';
import './status-toggle';
import './reset-password';
import './schedule-shift';
import Alpine from 'alpinejs';

import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import { initTomSelect } from './components/tom-select';

import { initDatepicker } from './components/datepicker';
import { initTimepicker } from './components/timepicker';
import { initWeekPicker } from './components/weekpicker';

window.Swal = Swal;
window.Alert = Alert;
window.TomSelect = TomSelect;
window.Alpine = Alpine
Alpine.start();

// Initialize all selects with the class 'tom-select' on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    initTomSelect();
    initDatepicker();
    initTimepicker();
    initWeekPicker();
});