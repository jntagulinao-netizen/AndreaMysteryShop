function getRecipientFormElements() {
    return {
        form: document.getElementById('newRecipientForm'),
        list: document.getElementById('recipientsList'),
        button: document.querySelector('.add-new-recipient-btn'),
        saveBtn: document.querySelector('.save-new-recipient-btn'),
        cancelBtn: document.querySelector('.cancel-new-btn')
    };
}

function setRecipientFormFields(values = {}) {
    document.getElementById('recipientName').value = values.recipient_name || '';
    document.getElementById('phoneNo').value = values.phone_no || '';
    document.getElementById('streetName').value = values.street_name || '';
    document.getElementById('unitFloor').value = values.unit_floor || '';
    document.getElementById('district').value = values.district || '';
    document.getElementById('city').value = values.city || '';
    document.getElementById('region').value = values.region || '';
    document.getElementById('setAsDefault').checked = !!values.is_default;
}

function getRecipientFormValues() {
    return {
        recipient_name: document.getElementById('recipientName').value.trim(),
        phone_no: document.getElementById('phoneNo').value.trim(),
        street_name: document.getElementById('streetName').value.trim(),
        unit_floor: document.getElementById('unitFloor').value.trim(),
        district: document.getElementById('district').value.trim(),
        city: document.getElementById('city').value.trim(),
        region: document.getElementById('region').value.trim(),
        is_default: document.getElementById('setAsDefault').checked
    };
}

function showRecipientListView() {
    const { form, list, button } = getRecipientFormElements();
    if (form) form.style.display = 'none';
    if (list) list.style.display = 'block';
    if (button) button.style.display = recipients.length >= 3 ? 'none' : 'inline-block';
}

function showRecipientFormView() {
    const { form, list, button } = getRecipientFormElements();
    if (form) form.style.display = 'block';
    if (list) list.style.display = 'none';
    if (button) button.style.display = 'none';
}

function setRecipientSaveMode(isEdit) {
    const { saveBtn, cancelBtn } = getRecipientFormElements();
    if (!saveBtn) return;
    saveBtn.textContent = isEdit ? 'Save Changes' : 'Save Recipient';
    saveBtn.onclick = isEdit ? saveEditedRecipient : saveNewRecipient;
    if (cancelBtn && isEdit) {
        cancelBtn.style.display = 'inline-block';
        cancelBtn.textContent = 'Cancel';
    }
}

function setSearchSectionsVisibility(showResultsOnly) {
    const bestSellerSection = document.getElementById('searchBestSellers')?.closest('.search-section');
    const historySection = document.getElementById('searchHistoryList')?.closest('.search-section');
    const resultsSection = document.getElementById('searchResultsGrid')?.closest('.search-section');
    if (bestSellerSection) bestSellerSection.style.display = showResultsOnly ? 'none' : 'block';
    if (historySection) historySection.style.display = showResultsOnly ? 'none' : 'block';
    if (resultsSection) resultsSection.style.display = 'block';
}
