let formUi = {};
let reviewUi = {};

function updateSubmitState() {
    const hasReason = (formUi.reasonHidden?.value?.trim()?.length ?? 0) > 0;
    const isAgreed = formUi.agreeCheck?.checked ?? false;

    if (formUi.btnSubmit) {
        if (hasReason && isAgreed) {
            formUi.btnSubmit.removeAttribute('disabled');
        } else {
            formUi.btnSubmit.setAttribute('disabled', 'true');
        }
    }
}

function handleRadioChange(radio) {
    if (!radio.checked) return;

    const val = radio.value;
    if (val === 'Alasan Lainnya') {
        if (formUi.reasonTextarea) {
            formUi.reasonTextarea.value = '';
            formUi.reasonTextarea.focus();
        }
    } else if (formUi.reasonTextarea) {
        formUi.reasonTextarea.value = val;
    }

    if (formUi.reasonHidden && formUi.reasonTextarea) {
        formUi.reasonHidden.value = formUi.reasonTextarea.value;
    }
    updateSubmitState();
}

function handleTextareaInput(value) {
    if (formUi.reasonHidden) {
        formUi.reasonHidden.value = value;
    }
    updateSubmitState();
}

function toggleNotesContent() {
    if (!reviewUi.notesContent || !reviewUi.toggleNotesBtn) return;

    const isHidden = reviewUi.notesContent.classList.toggle('hidden');
    reviewUi.toggleNotesBtn.textContent = isHidden
        ? 'Catatan kebijakan pembatalan'
        : 'Sembunyikan catatan kebijakan pembatalan';
}

export function initializeCancelForm() {
    const cancelForm = document.getElementById('cancelForm');
    if (!cancelForm) return;

    formUi = {
        reasonTextarea: document.getElementById('cancelReason'),
        reasonHidden: document.getElementById('inputReason'),
        agreeCheck: document.getElementById('agreeCheck'),
        btnSubmit: document.getElementById('btnSubmit'),
        radioButtons: document.querySelectorAll('input[name="reason_option"]')
    };

    formUi.radioButtons.forEach(rb => {
        rb.onchange = () => handleRadioChange(rb);
    });

    if (formUi.reasonTextarea) {
        formUi.reasonTextarea.oninput = function () {
            handleTextareaInput(this.value);
        };
    }

    if (formUi.agreeCheck) {
        formUi.agreeCheck.onchange = () => updateSubmitState();
    }

    if (formUi.btnSubmit) {
        formUi.btnSubmit.onclick = () => {
            cancelForm.submit();
        };
    }
}

export function initializeCancelReview() {
    const toggleNotesBtn = document.getElementById('toggleNotes');
    if (!toggleNotesBtn) return;

    reviewUi = {
        toggleNotesBtn: toggleNotesBtn,
        notesContent: document.getElementById('notesContent')
    };

    reviewUi.toggleNotesBtn.onclick = () => toggleNotesContent();
}
