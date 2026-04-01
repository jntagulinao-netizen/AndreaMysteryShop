let editingRecipientId = null; // Track which recipient is being edited

async function loadRecipients() {
    try {
        const res = await fetch('api/get-recipients.php');
        if (!res.ok) throw new Error('Failed to load recipients');

        const data = await res.json();
        recipients = data.recipients || [];
        const defaultRecipient = data.default_recipient;

        const list = document.getElementById('recipientsList');
        const container = document.getElementById('recipientsContainer');
        const form = document.getElementById('newRecipientForm');

        if (recipients.length > 0) {
            list.style.display = 'block';
            form.style.display = 'none';

            // Show only first recipient initially
            const firstRecipient = recipients[0];
            const otherRecipients = recipients.slice(1);

            // Build recipient options with collapsible UI
            let html = `
                <div class="recipient-option${firstRecipient.is_default ? ' is-default' : ''}">
                    <label class="recipient-option-label">
                        <input type="radio" name="recipient" value="${firstRecipient.recipient_id}" onchange="selectedRecipientId = ${firstRecipient.recipient_id}" checked>
                        <div class="recipient-info">
                            <div class="recipient-name">${firstRecipient.recipient_name} ${firstRecipient.is_default ? '<span class="recipient-default-badge">★ DEFAULT</span>' : ''}</div>
                            <div>${firstRecipient.street_name}${firstRecipient.unit_floor ? ', ' + firstRecipient.unit_floor : ''}</div>
                            <div>${firstRecipient.district || ''} ${firstRecipient.city}, ${firstRecipient.region}</div>
                            <div class="recipient-phone">${firstRecipient.phone_no}</div>
                        </div>
                    </label>
                    <div class="recipient-action-row">
                        <button type="button" class="edit-recipient-btn recipient-action-btn" onclick="startEditRecipient(${firstRecipient.recipient_id})">✎ Edit</button>
                        <button type="button" class="delete-recipient-btn recipient-action-btn" onclick="deleteRecipient(${firstRecipient.recipient_id})">🗑 Delete</button>
                    </div>
                </div>
            `;

            // Add collapsible section for other recipients
            if (otherRecipients.length > 0 || recipients.length >= 3) {
                html += `
                    <div class="recipient-expand-section">
                        <button type="button" class="recipient-expand-btn" onclick="toggleOtherRecipients()">
                            <span>Other Recipients & Options</span>
                            <span class="recipient-expand-arrow">▼</span>
                        </button>
                        <div class="recipient-expand-content">
                `;

                // Add other recipients
                otherRecipients.forEach(r => {
                    html += `
                        <div class="recipient-option recipient-option-secondary${r.is_default ? ' is-default' : ''}">
                            <label class="recipient-option-label">
                                <input type="radio" name="recipient" value="${r.recipient_id}" onchange="selectedRecipientId = ${r.recipient_id}">
                                <div class="recipient-info">
                                    <div class="recipient-name">${r.recipient_name} ${r.is_default ? '<span class="recipient-default-badge">★ DEFAULT</span>' : ''}</div>
                                    <div>${r.street_name}${r.unit_floor ? ', ' + r.unit_floor : ''}</div>
                                    <div>${r.district || ''} ${r.city}, ${r.region}</div>
                                    <div class="recipient-phone">${r.phone_no}</div>
                                </div>
                            </label>
                            <div class="recipient-action-row">
                                <button type="button" class="edit-recipient-btn recipient-action-btn" onclick="startEditRecipient(${r.recipient_id})">✎ Edit</button>
                                <button type="button" class="delete-recipient-btn recipient-action-btn" onclick="deleteRecipient(${r.recipient_id})">🗑 Delete</button>
                            </div>
                        </div>
                    `;
                });

                // Add button to add more recipients if less than 3
                if (recipients.length < 3) {
                    html += `
                        <button type="button" class="add-new-recipient-btn recipient-add-inline-btn" onclick="toggleNewRecipientForm()">+ Add New Recipient</button>
                    `;
                }

                html += `
                        </div>
                    </div>
                `;
            }

            // If there are no "other recipients", still show add button when below limit.
            if (otherRecipients.length === 0 && recipients.length < 3) {
                html += `
                    <button type="button" class="add-new-recipient-btn recipient-add-inline-btn" onclick="toggleNewRecipientForm()">+ Add New Recipient</button>
                `;
            }

            container.innerHTML = html;

            // Select default recipient if exists
            if (defaultRecipient) {
                const defaultRadio = document.querySelector(`input[value="${defaultRecipient.recipient_id}"]`);
                if (defaultRadio) {
                    defaultRadio.checked = true;
                    selectedRecipientId = defaultRecipient.recipient_id;
                }
            } else if (recipients.length > 0) {
                document.querySelector('input[name="recipient"]').checked = true;
                selectedRecipientId = recipients[0].recipient_id;
            }

            // Show/hide Add New Recipient button based on limit
            const addBtn = document.querySelector('.add-new-recipient-btn');
            if (addBtn) {
                if (recipients.length >= 3) {
                    addBtn.style.display = 'none';
                } else {
                    addBtn.style.display = 'inline-block';
                }
            }
        } else {
            list.style.display = 'none';
            form.style.display = 'block';
            setRecipientFormFields();
            setRecipientSaveMode(false);
        }
    } catch (err) {
        console.error('loadRecipients', err);
        document.getElementById('recipientsList').style.display = 'none';
        document.getElementById('newRecipientForm').style.display = 'block';
        setRecipientFormFields();
        setRecipientSaveMode(false);
    }
}

function toggleOtherRecipients() {
    const expandSection = document.querySelector('.recipient-expand-section');
    if (!expandSection) return;

    expandSection.classList.toggle('open');
}

function toggleNewRecipientForm() {
    const { form } = getRecipientFormElements();

    if (form.style.display === 'none') {
        // Check if we can add more recipients
        if (recipients.length >= 3) {
            showLocalSweetAlert('warning', 'Recipient Limit', 'You can only save up to 3 recipients.', 1600);
            return;
        }

        showRecipientFormView();
        setRecipientFormFields();
        setRecipientSaveMode(false);
    } else {
        showRecipientListView();
    }
}

async function saveNewRecipient() {
    console.log('=== saveNewRecipient() called ===');
    const formData = getRecipientFormValues();

    // Validate required fields
    if (!formData.recipient_name || !formData.phone_no || !formData.street_name || !formData.region || !formData.province || !formData.city || !formData.district) {
        await showLocalSweetAlert('warning', 'Missing Required Fields', 'Please fill in all required fields (marked with *).', 1700);
        return;
    }

    try {
        const body = new URLSearchParams();
        body.append('recipient_name', formData.recipient_name);
        body.append('phone_no', formData.phone_no);
        body.append('street_name', formData.street_name);
        body.append('unit_floor', formData.unit_floor);
        body.append('district', formData.district);
        body.append('city', formData.city);
        body.append('region', formData.region);
        body.append('is_default', formData.is_default ? 'true' : 'false');

        console.log('Sending recipient data...', {
            recipient_name: formData.recipient_name,
            phone_no: formData.phone_no,
            street_name: formData.street_name,
            city: formData.city,
            region: formData.region,
            is_default: formData.is_default
        });

        const res = await fetch('api/add-recipient.php', {
            method: 'POST',
            body
        });

        if (!res.ok) {
            const errorData = await res.json();
            throw new Error(errorData.error || 'Failed to save recipient');
        }

        const data = await res.json();
        console.log('Recipient saved successfully:', data);

        await showLocalSweetAlert('success', 'Recipient Saved', 'Recipient saved successfully!', 1300);

        // Reload recipients list
        await loadRecipients();

        showRecipientListView();

    } catch (err) {
        console.error('saveNewRecipient error:', err);
        await showLocalSweetAlert('error', 'Save Failed', 'Error saving recipient: ' + err.message, 2000);
    }
}

function startEditRecipient(recipientId) {
    console.log('=== startEditRecipient() called for ID:', recipientId);
    editingRecipientId = recipientId;

    // Find the recipient data
    const recipient = recipients.find(r => r.recipient_id === recipientId);
    if (!recipient) {
        showLocalSweetAlert('error', 'Recipient Not Found', 'Recipient not found.', 1600);
        return;
    }

    setRecipientFormFields(recipient);
    showRecipientFormView();
    setRecipientSaveMode(true);
}

function cancelEditRecipient() {
    editingRecipientId = null;
    showRecipientListView();
    setRecipientSaveMode(false);
}

function handleCancelRecipient() {
    if (editingRecipientId) {
        cancelEditRecipient();
    } else {
        showRecipientListView();
        setRecipientFormFields();
        setRecipientSaveMode(false);
    }
}

async function saveEditedRecipient() {
    console.log('=== saveEditedRecipient() called for ID:', editingRecipientId);

    if (!editingRecipientId) {
        await showLocalSweetAlert('error', 'Edit Failed', 'No recipient selected for editing.', 1700);
        return;
    }

    const formData = getRecipientFormValues();

    // Validate required fields
    if (!formData.recipient_name || !formData.phone_no || !formData.street_name || !formData.region || !formData.province || !formData.city || !formData.district) {
        await showLocalSweetAlert('warning', 'Missing Required Fields', 'Please fill in all required fields (marked with *).', 1700);
        return;
    }

    try {
        const body = new URLSearchParams();
        body.append('recipient_id', editingRecipientId);
        body.append('recipient_name', formData.recipient_name);
        body.append('phone_no', formData.phone_no);
        body.append('street_name', formData.street_name);
        body.append('unit_floor', formData.unit_floor);
        body.append('district', formData.district);
        body.append('city', formData.city);
        body.append('region', formData.region);
        body.append('is_default', formData.is_default ? 'true' : 'false');

        console.log('Sending updated recipient data...');

        const res = await fetch('api/update-recipient.php', {
            method: 'POST',
            body
        });

        if (!res.ok) {
            const errorData = await res.json();
            throw new Error(errorData.error || 'Failed to update recipient');
        }

        const data = await res.json();
        console.log('Recipient updated successfully:', data);

        await showLocalSweetAlert('success', 'Recipient Updated', 'Recipient updated successfully!', 1300);

        // Reset editing state
        editingRecipientId = null;

        // Reload recipients list
        await loadRecipients();

        showRecipientListView();
        setRecipientSaveMode(false);

    } catch (err) {
        console.error('saveEditedRecipient error:', err);
        await showLocalSweetAlert('error', 'Update Failed', 'Error updating recipient: ' + err.message, 2000);
    }
}

async function deleteRecipient(recipientId) {
    const confirmed = await showLocalConfirmModal(
        'Delete Recipient',
        'Delete this recipient? This action cannot be undone.',
        'Continue',
        'Cancel'
    );
    if (!confirmed) {
        return;
    }

    try {
        const body = new URLSearchParams();
        body.append('recipient_id', recipientId);

        const res = await fetch('api/remove-recipient.php', {
            method: 'POST',
            body
        });

        if (!res.ok) {
            const errorData = await res.json();
            throw new Error(errorData.error || 'Failed to delete recipient');
        }

        const result = await res.json();
        console.log('Recipient deleted:', result);
        await showLocalSweetAlert('success', 'Recipient Deleted', 'Recipient deleted successfully.', 1300);

        // Reset editing state if deleting currently edited
        if (editingRecipientId === recipientId) {
            editingRecipientId = null;
        }

        await loadRecipients();

        // Close edit/new form after delete and show list
        showRecipientListView();

    } catch (err) {
        console.error('deleteRecipient error:', err);
        await showLocalSweetAlert('error', 'Delete Failed', 'Error deleting recipient: ' + err.message, 2000);
    }
}
