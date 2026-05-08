const PH_ADDRESS_API_BASE = 'api/ph-address.php';
const phAddressCache = {
    regions: [],
    provincesByRegion: {},
    citiesByRegion: {},
    citiesByProvince: {},
    barangaysByCity: {}
};
let recipientAddressInitDone = false;

function getRecipientFormElements() {
    return {
        form: document.getElementById('newRecipientForm'),
        list: document.getElementById('recipientsList'),
        button: document.querySelector('.add-new-recipient-btn'),
        saveBtn: document.querySelector('.save-new-recipient-btn'),
        cancelBtn: document.querySelector('.cancel-new-btn')
    };
}

function getRecipientLocationElements() {
    return {
        region: document.getElementById('region'),
        province: document.getElementById('province'),
        city: document.getElementById('city'),
        district: document.getElementById('district')
    };
}

function resetSelect(selectEl, placeholderText) {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="">${placeholderText}</option>`;
    selectEl.value = '';
    selectEl.disabled = true;
}

function fillSelect(selectEl, placeholderText, entries) {
    if (!selectEl) return;
    const options = [`<option value="">${placeholderText}</option>`];
    entries.forEach((entry) => {
        options.push(`<option value="${entry.code}">${entry.name}</option>`);
    });
    selectEl.innerHTML = options.join('');
    selectEl.disabled = entries.length === 0;
}

function getSelectedOptionText(selectEl) {
    if (!selectEl) return '';
    const option = selectEl.options[selectEl.selectedIndex];
    if (!option || !option.value) return '';
    return option.text.trim();
}

function findCodeByName(entries, wantedName) {
    const target = String(wantedName || '').trim().toLowerCase();
    if (!target) return '';
    const found = entries.find((item) => String(item.name || '').trim().toLowerCase() === target);
    return found ? String(found.code || '') : '';
}

async function fetchPhAddressData(params = {}) {
    const query = new URLSearchParams(params);
    const res = await fetch(`${PH_ADDRESS_API_BASE}?${query.toString()}`, { cache: 'no-store' });
    if (!res.ok) {
        throw new Error('Address data request failed');
    }
    const payload = await res.json();
    if (!payload || !payload.success || !Array.isArray(payload.data)) {
        throw new Error(payload && payload.error ? payload.error : 'Address data request failed');
    }
    return payload.data;
}

function parseSavedRegionProvince(regionRaw) {
    const raw = String(regionRaw || '').trim();
    if (!raw) {
        return { regionName: '', provinceName: '' };
    }
    const parts = raw.split('/').map((p) => p.trim()).filter(Boolean);
    if (parts.length >= 2) {
        return { regionName: parts[0], provinceName: parts.slice(1).join(' / ') };
    }
    return { regionName: raw, provinceName: '' };
}

async function loadRegions() {
    if (phAddressCache.regions.length > 0) {
        return phAddressCache.regions;
    }
    const rows = await fetchPhAddressData({ endpoint: 'regions' });
    phAddressCache.regions = rows.map((row) => ({
        code: String(row.code || ''),
        name: String(row.name || '')
    })).filter((row) => row.code && row.name);
    return phAddressCache.regions;
}

async function loadProvinces(regionCode) {
    if (!regionCode) return [];
    if (phAddressCache.provincesByRegion[regionCode]) {
        return phAddressCache.provincesByRegion[regionCode];
    }
    const rows = await fetchPhAddressData({ endpoint: 'provinces', region_code: regionCode });
    const mapped = rows.map((row) => ({
        code: String(row.code || ''),
        name: String(row.name || '')
    })).filter((row) => row.code && row.name);
    phAddressCache.provincesByRegion[regionCode] = mapped;
    return mapped;
}

async function loadCitiesByRegion(regionCode) {
    if (!regionCode) return [];
    if (phAddressCache.citiesByRegion[regionCode]) {
        return phAddressCache.citiesByRegion[regionCode];
    }
    const rows = await fetchPhAddressData({ endpoint: 'cities', region_code: regionCode });
    const mapped = rows.map((row) => ({
        code: String(row.code || ''),
        name: String(row.name || '')
    })).filter((row) => row.code && row.name);
    phAddressCache.citiesByRegion[regionCode] = mapped;
    return mapped;
}

async function loadCitiesByProvince(provinceCode) {
    if (!provinceCode) return [];
    if (phAddressCache.citiesByProvince[provinceCode]) {
        return phAddressCache.citiesByProvince[provinceCode];
    }
    const rows = await fetchPhAddressData({ endpoint: 'cities', province_code: provinceCode });
    const mapped = rows.map((row) => ({
        code: String(row.code || ''),
        name: String(row.name || '')
    })).filter((row) => row.code && row.name);
    phAddressCache.citiesByProvince[provinceCode] = mapped;
    return mapped;
}

async function loadBarangays(cityCode) {
    if (!cityCode) return [];
    if (phAddressCache.barangaysByCity[cityCode]) {
        return phAddressCache.barangaysByCity[cityCode];
    }
    const rows = await fetchPhAddressData({ endpoint: 'barangays', city_code: cityCode });
    const mapped = rows.map((row) => ({
        code: String(row.code || ''),
        name: String(row.name || '')
    })).filter((row) => row.code && row.name);
    phAddressCache.barangaysByCity[cityCode] = mapped;
    return mapped;
}

async function handleRegionChange(prefill = {}) {
    const { region, province, city, district } = getRecipientLocationElements();
    if (!region || !province || !city || !district) return;

    resetSelect(province, 'Select Province');
    resetSelect(city, 'Select City / Municipality');
    resetSelect(district, 'Select Barangay');

    const regionCode = region.value;
    if (!regionCode) return;

    const provinces = await loadProvinces(regionCode);
    if (provinces.length > 0) {
        fillSelect(province, 'Select Province', provinces);
        const provinceCode = findCodeByName(provinces, prefill.provinceName);
        if (provinceCode) {
            province.value = provinceCode;
        }
    } else {
        const pseudoProvince = [{ code: '__REGION_DIRECT__', name: 'N/A (Direct Region Cities)' }];
        fillSelect(province, 'Select Province', pseudoProvince);
        province.value = '__REGION_DIRECT__';
        province.disabled = true;
    }

    await handleProvinceChange(prefill);
}

async function handleProvinceChange(prefill = {}) {
    const { region, province, city, district } = getRecipientLocationElements();
    if (!region || !province || !city || !district) return;

    resetSelect(city, 'Select City / Municipality');
    resetSelect(district, 'Select Barangay');

    const regionCode = region.value;
    const provinceCode = province.value;
    if (!regionCode || !provinceCode) return;

    let cities = [];
    if (provinceCode === '__REGION_DIRECT__') {
        cities = await loadCitiesByRegion(regionCode);
    } else {
        cities = await loadCitiesByProvince(provinceCode);
    }

    fillSelect(city, 'Select City / Municipality', cities);
    const cityCode = findCodeByName(cities, prefill.cityName);
    if (cityCode) {
        city.value = cityCode;
    }

    await handleCityChange(prefill);
}

async function handleCityChange(prefill = {}) {
    const { city, district } = getRecipientLocationElements();
    if (!city || !district) return;

    resetSelect(district, 'Select Barangay');

    const cityCode = city.value;
    if (!cityCode) return;

    const barangays = await loadBarangays(cityCode);
    fillSelect(district, 'Select Barangay', barangays);
    const barangayCode = findCodeByName(barangays, prefill.barangayName);
    if (barangayCode) {
        district.value = barangayCode;
    }
}

async function initRecipientAddressSelectors(prefill = {}) {
    const { region, province, city } = getRecipientLocationElements();
    if (!region || !province || !city) return;

    if (!recipientAddressInitDone) {
        region.addEventListener('change', () => {
            handleRegionChange({}).catch(() => {});
        });
        province.addEventListener('change', () => {
            handleProvinceChange({}).catch(() => {});
        });
        city.addEventListener('change', () => {
            handleCityChange({}).catch(() => {});
        });
        recipientAddressInitDone = true;
    }

    try {
        const regions = await loadRegions();
        fillSelect(region, 'Select Region', regions);

        const parsed = parseSavedRegionProvince(prefill.region || '');
        const regionCode = findCodeByName(regions, parsed.regionName);
        if (regionCode) {
            region.value = regionCode;
        }

        await handleRegionChange({
            provinceName: parsed.provinceName,
            cityName: prefill.city || '',
            barangayName: prefill.district || ''
        });
    } catch (error) {
        region.innerHTML = '<option value="">Unable to load Philippines locations</option>';
        region.disabled = true;
        resetSelect(province, 'Select Province');
        resetSelect(city, 'Select City / Municipality');
        resetSelect(document.getElementById('district'), 'Select Barangay');
    }
}

function setRecipientFormFields(values = {}) {
    document.getElementById('recipientName').value = values.recipient_name || '';
    document.getElementById('phoneNo').value = values.phone_no || '';
    document.getElementById('streetName').value = values.street_name || '';
    document.getElementById('unitFloor').value = values.unit_floor || '';
    document.getElementById('setAsDefault').checked = !!values.is_default;

    initRecipientAddressSelectors({
        region: values.region || '',
        city: values.city || '',
        district: values.district || ''
    }).catch(() => {});

    // Initialize phone input behavior: allow digits only and cap to 11 digits
    initRecipientPhoneInput();
}

function initRecipientPhoneInput() {
    const phoneEl = document.getElementById('phoneNo');
    if (!phoneEl) return;

    // Prevent attaching multiple handlers
    if (phoneEl._recipientPhoneInitialized) return;
    phoneEl._recipientPhoneInitialized = true;

    phoneEl.addEventListener('input', function (evt) {
        const original = phoneEl.value || '';
        // keep only digits
        const digits = original.replace(/\D/g, '');
        // limit to 11 digits
        const limited = digits.slice(0, 11);
        if (limited !== original) {
            // try to keep caret position sensible
            const pos = phoneEl.selectionStart || limited.length;
            phoneEl.value = limited;
            try { phoneEl.setSelectionRange(pos, pos); } catch (e) {}
        } else {
            phoneEl.value = limited;
        }
    });

    // Optionally prevent paste of non-digit content
    phoneEl.addEventListener('paste', function (evt) {
        evt.stopPropagation();
        evt.preventDefault();
        const text = (evt.clipboardData || window.clipboardData).getData('text') || '';
        const digits = text.replace(/\D/g, '').slice(0, 11);
        phoneEl.value = (phoneEl.value || '').replace(/\D/g, '').slice(0, 11 - digits.length) + digits;
    });

    // Prevent typing additional numeric digits when already at 11 digits
    phoneEl.addEventListener('keydown', function (e) {
        // allow navigation and control keys
        const navKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Home', 'End', 'Enter'];
        if (navKeys.includes(e.key)) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return; // allow copy/paste/select all combos

        // If key is a single character digit, check limit
        if (e.key && e.key.length === 1 && /\d/.test(e.key)) {
            const currentDigits = (phoneEl.value || '').replace(/\D/g, '');
            if (currentDigits.length >= 11) {
                e.preventDefault();
                // small visual feedback: briefly flash the field
                phoneEl.classList.add('input-limit-reached');
                setTimeout(() => phoneEl.classList.remove('input-limit-reached'), 300);
            }
        }
    });
}

function getRecipientFormValues() {
    const { region, province, city, district } = getRecipientLocationElements();
    const regionName = getSelectedOptionText(region);
    const provinceName = getSelectedOptionText(province);
    const cityName = getSelectedOptionText(city);
    const barangayName = getSelectedOptionText(district);

    const composedRegion = provinceName && provinceName !== 'N/A (Direct Region Cities)'
        ? `${regionName} / ${provinceName}`
        : regionName;

    return {
        recipient_name: document.getElementById('recipientName').value.trim(),
        phone_no: document.getElementById('phoneNo').value.trim(),
        street_name: document.getElementById('streetName').value.trim(),
        unit_floor: document.getElementById('unitFloor').value.trim(),
        district: barangayName,
        city: cityName,
        region: composedRegion,
        province: provinceName,
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
    initRecipientAddressSelectors({}).catch(() => {});
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
