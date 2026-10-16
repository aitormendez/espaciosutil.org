<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

final class PrivateImageField extends \acf_field
{
    public function initialize()
    {
        $this->name = 'cde_private_image';
        $this->label = 'Imagen privada de presentación';
        $this->category = 'content';
        $this->show_in_rest = false;
    }

    public function input_admin_enqueue_scripts()
    {
        wp_enqueue_script('cde-presentation-editor', plugins_url('editor.js', __FILE__), ['acf-input'], filemtime(__DIR__.'/editor.js'), true);
    }

    public function render_field($field)
    {
        $id = Editor::postId();
        $value = is_string($field['value']) ? $field['value'] : '';
        $url = '';
        if ($value) {
            try {
                $url = Editor::thumbnail($id, $value);
            } catch (\Throwable) {
            }
        }
        echo '<div class="cde-private-image" data-lesson="'.esc_attr((string) $id).'" data-nonce="'.esc_attr(wp_create_nonce('cde_presentation_upload_'.$id)).'">';
        echo '<input type="hidden" class="cde-image-value" name="'.esc_attr($field['name']).'" value="'.esc_attr($value).'">';
        echo '<img class="cde-image-preview" alt="Vista previa de la diapositiva" style="display:'.($url ? 'block' : 'none').';width:240px;max-width:100%;height:auto;margin-bottom:10px"'.($url ? ' src="'.esc_url($url).'"' : '').'>';
        echo '<button type="button" class="button cde-image-select">'.($value ? 'Sustituir imagen' : 'Seleccionar imagen').'</button> ';
        echo '<button type="button" class="button-link cde-image-clear"'.($value ? '' : ' hidden').'>Quitar imagen</button>';
        echo '<input type="file" class="cde-image-file" accept="image/jpeg,image/png,image/webp" hidden>';
        echo '<p class="description">JPEG, PNG o WebP. La imagen permanece privada. Guarda la lección para publicar los cambios.</p><p class="cde-image-status" role="status"></p></div>';
    }

    public function validate_value($valid, $value, $field, $input)
    {
        if ($valid !== true || ! $value) {
            return $valid;
        }
        try {
            $id = Editor::postId();
            if (! current_user_can('edit_post', $id)) {
                return 'No tienes permiso para editar esta presentación.';
            }
            Service::resolve($id, (string) $value);
        } catch (\Throwable) {
            return 'Selecciona una imagen privada válida de esta lección.';
        }

        return true;
    }
}
