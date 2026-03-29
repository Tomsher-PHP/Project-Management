import Swal from 'sweetalert2';
import Alert from './alert';
import Alpine from 'alpinejs';
import TomSelect from "tom-select";
import Quill from 'quill';

import './bootstrap';
import './status-toggle';
import './modules/reset-password';
import './modules/ajax-form-modal';
import './modules/config';
import './delete-alert';
import './components/filterDrawer';

import "tom-select/dist/css/tom-select.css";
import 'quill/dist/quill.core.css';
import 'quill/dist/quill.snow.css';

import { initTomSelect } from './components/tom-select';
import { initDatepicker } from './components/datepicker';
import { initTimepicker } from './components/timepicker';
import { initWeekPicker } from './components/weekpicker';

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
});
