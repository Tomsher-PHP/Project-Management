import Swal from 'sweetalert2';
import Alert from './alert';
import Alpine from 'alpinejs';
import TomSelect from "tom-select";

import './bootstrap';
import './status-toggle';
import './modules/reset-password';
import './modules/ajax-form-modal';
import './delete-alert';
import './components/filterDrawer';

import "tom-select/dist/css/tom-select.css";
import { initTomSelect, autoTomSelect } from './components/tom-select';

import { initDatepicker } from './components/datepicker';
import { initTimepicker } from './components/timepicker';
import { initWeekPicker } from './components/weekpicker';

window.Swal = Swal;
window.Alert = Alert;
window.TomSelect = TomSelect;
window.Alpine = Alpine
window.autoTomSelect = autoTomSelect;
Alpine.start();

// Initialize all selects with the class 'tom-select' on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
    initTomSelect();
    initDatepicker();
    initTimepicker();
    initWeekPicker();
});