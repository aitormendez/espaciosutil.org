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
            <option>Sesiones de lectura de aura</option>
            <option>Sesiones de gestación y crianza</option>
            <option>Orientación terapéutica</option>
            <option>Colaboración con el “Curso de desarrollo espiritual”</option>
            <option>Curso básico de limpieza y protección energéticas</option>
            <option>Curso básico de iniciación al Eneagrama</option>
            <option>Práctica de divulgación a través de Nuevas Voces</option>
            <option>Mensaje para algún miembro del equipo de Espacio Sutil</option>
            <option>Otro motivo de contacto</option>
        </select>
    </div>

    <textarea name="MESSAGE" placeholder="Mensaje" required rows="6"
        class="bg-morado4 border-gris3 mb-6 block w-full rounded border px-4 py-3" required></textarea>

    <input type="submit" value="Enviar" class="bg-morado3 hover:bg-negro cursor-pointer rounded px-4 py-3 font-sans" />
</x-html-forms>
