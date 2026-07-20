document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('appSysForm') || document.getElementById('ictProjForm');
  if (!form) return;

  function fieldCard(input) {
    return input.closest('.entry-card');
  }

  function showError(input, message) {
    const card = fieldCard(input);
    if (!card) return;
    let el = card.querySelector('[data-error-for="' + input.name + '"]');
    if (!el) {
      el = document.createElement('p');
      el.className = 'text-xs text-red-700 mt-1';
      el.setAttribute('data-error-for', input.name);
      input.parentNode.appendChild(el);
    }
    el.textContent = message || '';
    input.classList.toggle('field-invalid', !!message);
  }

  function clearError(input) {
    showError(input, '');
    input.classList.remove('field-invalid');
  }

  function validateInput(input) {
    const value = (input.value || '').trim();

    if (input.hasAttribute('required') && value === '') {
      showError(input, 'This field is required.');
      return false;
    }

    if (value !== '') {
      if (input.type === 'number' || input.dataset.type === 'number') {
        const num = Number(value);
        if (Number.isNaN(num) || num < 0) {
          showError(input, 'Enter a valid amount.');
          return false;
        }
      }
      if (input.type === 'date' && !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        showError(input, 'Enter a valid date.');
        return false;
      }
    }

    clearError(input);
    return true;
  }

  const inputs = form.querySelectorAll('input, select, textarea');
  inputs.forEach((input) => {
    input.addEventListener('input', () => validateInput(input));
    input.addEventListener('change', () => validateInput(input));
  });

  form.addEventListener('submit', function (e) {
    let firstInvalid = null;
    const allInputs = form.querySelectorAll('input, select, textarea');
    allInputs.forEach((input) => {
      if (!validateInput(input) && !firstInvalid) {
        firstInvalid = input;
      }
    });

    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.focus();
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
});
