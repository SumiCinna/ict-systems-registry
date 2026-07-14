document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('loginForm');
  const email = document.getElementById('email');
  const password = document.getElementById('password');
  const submitBtn = document.getElementById('submitBtn');

  const state = {
    email: false,
    password: false,
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
    submitBtn.disabled = !Object.values(state).every(Boolean);
  }

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

  // ---------- Password ----------
  password.addEventListener('input', () => {
    const value = password.value;
    if (value === '') {
      state.password = false;
      setError('password', 'Password is required.');
    } else {
      state.password = true;
      setError('password', '');
    }
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
    if (!Object.values(state).every(Boolean)) {
      e.preventDefault();
      updateSubmitState();
    }
  });
});
