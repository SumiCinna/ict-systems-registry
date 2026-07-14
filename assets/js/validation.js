document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('registerForm');
  const agencyName = document.getElementById('agency_name');
  const lastName = document.getElementById('last_name');
  const firstName = document.getElementById('first_name');
  const middleInitial = document.getElementById('middle_initial');
  const positionDesignation = document.getElementById('position_designation');
  const telephoneNumber = document.getElementById('telephone_number');
  const email = document.getElementById('email');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirm_password');
  const submitBtn = document.getElementById('submitBtn');
  const matchMessage = document.getElementById('matchMessage');
  const checklistItems = document.querySelectorAll('#passwordChecklist .rule-item');

  const state = {
    agency_name: false,
    last_name: false,
    first_name: false,
    middle_initial: true, // optional
    position_designation: false,
    telephone_number: false,
    email: false,
    password: false,
    confirm_password: false,
  };

  function setError(field, message) {
    const el = document.querySelector('[data-error-for="' + field + '"]');
    const input = document.getElementById(field);
    if (el) el.textContent = message || '';
    if (input) {
      input.classList.toggle('field-invalid', !!message);
      input.classList.toggle('field-valid', !message && input.value.trim() !== '');
    }
  }

  function updateSubmitState() {
    const allValid = Object.values(state).every(Boolean);
    submitBtn.disabled = !allValid;
  }

  // ---------- Free-text fields (agency, position/designation) ----------
  function validateFreeText(input, key, label, min, max) {
    const value = input.value.trim();
    if (value.length < min) {
      state[key] = false;
      setError(key, label + ' is required.');
    } else if (value.length > max) {
      state[key] = false;
      setError(key, label + ' is too long.');
    } else {
      state[key] = true;
      setError(key, '');
    }
    updateSubmitState();
  }

  agencyName.addEventListener('input', () =>
    validateFreeText(agencyName, 'agency_name', 'Name of Agency', 2, 191));

  positionDesignation.addEventListener('input', () =>
    validateFreeText(positionDesignation, 'position_designation', 'Position/Designation', 2, 150));

  // ---------- Telephone number ----------
  const phonePattern = /^[0-9+\-() ]{7,20}$/;
  telephoneNumber.addEventListener('input', () => {
    const value = telephoneNumber.value.trim();
    const digitCount = (value.match(/[0-9]/g) || []).length;
    if (value === '') {
      state.telephone_number = false;
      setError('telephone_number', 'Telephone number is required.');
    } else if (!phonePattern.test(value) || digitCount < 7) {
      state.telephone_number = false;
      setError('telephone_number', 'Enter a valid telephone number.');
    } else {
      state.telephone_number = true;
      setError('telephone_number', '');
    }
    updateSubmitState();
  });

  // ---------- Name fields ----------
  const namePattern = /^[A-Za-zÀ-ÿ' -]{1,100}$/;

  function validateNameField(input, key, label) {
    const value = input.value.trim();
    if (value === '') {
      state[key] = false;
      setError(key, label + ' is required.');
    } else if (!namePattern.test(value)) {
      state[key] = false;
      setError(key, 'Use letters only.');
    } else {
      state[key] = true;
      setError(key, '');
    }
    updateSubmitState();
  }

  lastName.addEventListener('input', () => validateNameField(lastName, 'last_name', 'Last name'));
  firstName.addEventListener('input', () => validateNameField(firstName, 'first_name', 'First name'));

  middleInitial.addEventListener('input', () => {
    const value = middleInitial.value.trim();
    const mPattern = /^[A-Za-z]{1,5}\.?$/;
    if (value === '' || mPattern.test(value)) {
      state.middle_initial = true;
      setError('middle_initial', '');
    } else {
      state.middle_initial = false;
      setError('middle_initial', 'Letters only.');
    }
    updateSubmitState();
  });

  // ---------- Email ----------
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  email.addEventListener('input', () => {
    const value = email.value.trim();
    if (value === '') {
      state.email = false;
      setError('email', 'Email address is required.');
    } else if (!emailPattern.test(value)) {
      state.email = false;
      setError('email', 'Enter a valid email address.');
    } else {
      state.email = true;
      setError('email', '');
    }
    updateSubmitState();
  });

  // ---------- Password rules (real-time checklist) ----------
  function evaluatePassword(value) {
    return {
      length: value.length >= 8 && value.length <= 20,
      upper: /[A-Z]/.test(value),
      lower: /[a-z]/.test(value),
      number: /[0-9]/.test(value),
    };
  }

  password.addEventListener('input', () => {
    const results = evaluatePassword(password.value);

    checklistItems.forEach((item) => {
      const rule = item.getAttribute('data-rule');
      item.classList.toggle('met', !!results[rule]);
    });

    const allMet = Object.values(results).every(Boolean);
    state.password = allMet && password.value.length > 0;

    password.classList.toggle('field-valid', state.password);
    password.classList.toggle('field-invalid', !state.password && password.value.length > 0);

    // Re-check confirm password whenever password changes
    validateConfirmPassword();
    updateSubmitState();
  });

  // ---------- Confirm password (real-time match) ----------
  function validateConfirmPassword() {
    const value = confirmPassword.value;

    if (value === '') {
      state.confirm_password = false;
      matchMessage.classList.add('hidden');
      confirmPassword.classList.remove('field-valid', 'field-invalid');
      return;
    }

    matchMessage.classList.remove('hidden');

    if (value === password.value && state.password) {
      state.confirm_password = true;
      matchMessage.textContent = 'Passwords match.';
      matchMessage.className = 'mt-2 text-xs text-green-700';
      confirmPassword.classList.add('field-valid');
      confirmPassword.classList.remove('field-invalid');
    } else {
      state.confirm_password = false;
      matchMessage.textContent = 'Passwords do not match.';
      matchMessage.className = 'mt-2 text-xs text-red-600';
      confirmPassword.classList.add('field-invalid');
      confirmPassword.classList.remove('field-valid');
    }
  }

  confirmPassword.addEventListener('input', () => {
    validateConfirmPassword();
    updateSubmitState();
  });

  // ---------- Eye toggles ----------
  document.querySelectorAll('.eye-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      const openIcon = btn.querySelector('.eye-open');
      const closedIcon = btn.querySelector('.eye-closed');

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      openIcon.classList.toggle('hidden', isPassword);
      closedIcon.classList.toggle('hidden', !isPassword);
      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  });

  // ---------- Final guard on submit ----------
  form.addEventListener('submit', (e) => {
    const allValid = Object.values(state).every(Boolean);
    if (!allValid) {
      e.preventDefault();
      updateSubmitState();
    }
  });
});