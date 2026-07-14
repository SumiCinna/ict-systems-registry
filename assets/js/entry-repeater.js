function initEntryRepeater(config) {
  const container = document.getElementById(config.containerId);
  const template = document.getElementById(config.templateId);
  const addBtn = document.getElementById(config.addBtnId);
  let counter = container.querySelectorAll('.entry-card').length;

  function relabel() {
    const cards = container.querySelectorAll('.entry-card');
    cards.forEach((card, idx) => {
      const label = card.querySelector('.entry-label');
      if (label) {
        label.textContent = config.labelPrefix + (idx + 1);
      }
      const removeBtn = card.querySelector('.remove-entry');
      if (removeBtn) {
        removeBtn.classList.toggle('hidden', cards.length <= 1);
      }
    });
  }

  addBtn.addEventListener('click', () => {
    const html = template.innerHTML
      .split('__INDEX__').join(String(counter))
      .split('__INDEX_LABEL__').join(String(counter + 1));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    container.appendChild(wrapper.firstElementChild);
    counter++;
    relabel();
  });

  container.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-entry');
    if (!btn) return;
    const cards = container.querySelectorAll('.entry-card');
    if (cards.length <= 1) return;
    btn.closest('.entry-card').remove();
    relabel();
  });

  relabel();
}