const dropArea = document.getElementById('drop-area');
const input = document.getElementById('profile-image');
const preview = document.getElementById('preview');
const placeholder = document.getElementById('placeholder');
const removeBtn = document.getElementById('remove-btn');
const removeInput = document.getElementById('remove_profile_image');

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, e => e.preventDefault());
});

dropArea.addEventListener('dragover', () => {
    dropArea.classList.add('border-indigo-500');
});

dropArea.addEventListener('dragleave', () => {
    dropArea.classList.remove('border-indigo-500');
});

dropArea.addEventListener('drop', (e) => {
    dropArea.classList.remove('border-indigo-500');
    const file = e.dataTransfer.files[0];
    handleFile(file);
});

input.addEventListener('change', () => {
    handleFile(input.files[0]);
});

function handleFile(file) {
    if (!file || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
        removeInput.value = "0"; // new upload means don't delete
    };
    reader.readAsDataURL(file);
}

removeBtn.addEventListener('click', () => {
    preview.src = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');
    input.value = '';

    // Important: mark for deletion if editing
    removeInput.value = "1";
});