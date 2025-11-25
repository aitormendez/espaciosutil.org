<x-html-forms :form="$form" class="my-20">
    <input name="NAME" type="text" placeholder="Nombre completo"
        class="bg-morado4 border-gris3 mb-6 block w-full rounded border px-4 py-3" required>

    <input name="EMAIL" type="email" placeholder="Correo electrónico"
        class="bg-morado4 border-gris3 mb-6 block w-full rounded border px-4 py-3" required>

    <div>
        <label class="mb-2 block" for="contacto-TEMA">Tema</label>
        <select name="TEMA[]" multiple id="contacto-TEMA" required>
            <option>Asesoramiento para iniciación espiritual</option>
            <option>Prospección vital</option>
            <option>Autorerapia en grupo</option>
            <option>Autoterapia individual</option>
            <option>Sesiones de sanación energética</option>
            <option>Otro motivo de contacto</option>
        </select>
    </div>

    <textarea name="MESSAGE" placeholder="Mensaje" required rows="6"
        class="bg-morado4 border-gris3 mb-6 block w-full rounded border px-4 py-3" required></textarea>

    <div class="mb-6">
        <label class="mb-2 block" for="contacto-captcha">Prueba de seguridad</label>
        <input name="CAPTCHA" id="contacto-captcha" type="text" inputmode="text" autocomplete="off"
            placeholder="Escribe la palabra “luz”" class="bg-morado4 border-gris3 block w-full rounded border px-4 py-3" required>
        <p class="mt-2 text-sm text-gris3">Escribe la palabra luz para confirmar que eres humano.</p>
    </div>

    <input type="submit" value="Enviar" class="bg-morado3 hover:bg-negro cursor-pointer rounded px-4 py-3 font-sans" />
</x-html-forms>
