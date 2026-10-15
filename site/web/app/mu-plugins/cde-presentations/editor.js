(() => {
  const pending = new Set();
  window.acf.addFilter('validation_complete', (result) => {
    if (pending.size) {
      result.valid = 0;
      result.errors = [...(result.errors || []), {input: '', message: 'Espera a que terminen las subidas de imágenes.'}];
    }
    return result;
  });
  const changed = (input) => window.jQuery(input).trigger('change');
  document.addEventListener('click', (event) => {
    const root = event.target.closest('.cde-private-image');
    if (!root) return;
    if (event.target.closest('.cde-image-select')) root.querySelector('.cde-image-file').click();
    if (event.target.closest('.cde-image-clear')) {
      const input = root.querySelector('.cde-image-value'); input.value = ''; changed(input);
      const image = root.querySelector('.cde-image-preview'); image.removeAttribute('src'); image.style.display = 'none';
      root.querySelector('.cde-image-clear').hidden = true;
      root.querySelector('.cde-image-select').textContent = 'Seleccionar imagen';
    }
  });
  document.addEventListener('change', async (event) => {
    if (!event.target.matches('.cde-image-file')) return;
    const file = event.target.files[0]; if (!file) return;
    const root = event.target.closest('.cde-private-image');
    const status = root.querySelector('.cde-image-status');
    const button = root.querySelector('.cde-image-select');
    const input = root.querySelector('.cde-image-value');
    const clear = root.querySelector('.cde-image-clear');
    const data = new FormData();
    data.append('action', 'cde_presentation_upload'); data.append('lesson', root.dataset.lesson);
    data.append('nonce', root.dataset.nonce); data.append('image', file);
    button.disabled = clear.disabled = true;
    status.textContent = 'Preparando imagen…';
    // Evitar guardar una fila a medio subir, también en el editor de bloques.
    const lock = 'cde-image-' + Math.random().toString(36).slice(2);
    const editor = window.wp?.data?.dispatch('core/editor'); editor?.lockPostSaving(lock);
    pending.add(lock);
    try {
      const response = await fetch(window.ajaxurl, {method: 'POST', credentials: 'same-origin', body: data});
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.data?.message || 'No se ha podido subir la imagen.');
      if (!root.isConnected) return;
      input.value = result.data.value; changed(input);
      const image = root.querySelector('.cde-image-preview'); image.src = result.data.url; image.style.display = 'block';
      clear.hidden = false; button.textContent = 'Sustituir imagen';
      status.textContent = 'Imagen preparada. Guarda la lección para aplicar el cambio.';
    } catch (error) { status.textContent = error.message; }
    finally {
      button.disabled = clear.disabled = false; event.target.value = '';
      pending.delete(lock);
      editor?.unlockPostSaving(lock);
    }
  });
})();
