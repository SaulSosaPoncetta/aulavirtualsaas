import Quill from 'quill';
import 'quill/dist/quill.snow.css';

/**
 * Editor de texto enriquecido.
 * Uso: <textarea name="contenido" data-editor="completo|simple">...</textarea>
 * El textarea se oculta y, al enviar el formulario, recibe el HTML del editor.
 */
const BARRAS = {
    completo: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
        [{ align: [] }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean'],
    ],
    simple: [
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean'],
    ],
};

async function subirImagen(quill) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/png,image/jpeg,image/gif,image/webp';
    input.onchange = async () => {
        const archivo = input.files[0];
        if (!archivo) return;

        if (archivo.size > 4 * 1024 * 1024) {
            alertaEditor('La imagen puede pesar como máximo 4 MB.');
            return;
        }

        const datos = new FormData();
        datos.append('imagen', archivo);

        const rango = quill.getSelection(true);
        quill.insertText(rango.index, 'Subiendo imagen…', 'italic', true);

        try {
            const r = await window.axios.post('/editor/imagen', datos);
            quill.deleteText(rango.index, 'Subiendo imagen…'.length);
            quill.insertEmbed(rango.index, 'image', r.data.url, 'user');
            quill.setSelection(rango.index + 1);
        } catch (e) {
            quill.deleteText(rango.index, 'Subiendo imagen…'.length);
            alertaEditor(e.response?.data?.message || 'No se pudo subir la imagen.');
        }
    };
    input.click();
}

function alertaEditor(texto) {
    const div = document.createElement('div');
    div.className = 'alert alert-danger alert-dismissible fade show mt-2';
    div.innerHTML = texto + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.querySelector('.ql-container')?.after(div);
}

function iniciar(textarea) {
    const tipo = textarea.dataset.editor in BARRAS ? textarea.dataset.editor : 'completo';

    const caja = document.createElement('div');
    caja.className = 'editor-caja editor-' + tipo + (textarea.classList.contains('is-invalid') ? ' is-invalid' : '');
    textarea.before(caja);
    textarea.style.display = 'none';

    const quill = new Quill(caja, {
        theme: 'snow',
        placeholder: textarea.placeholder || '',
        modules: {
            toolbar: {
                container: BARRAS[tipo],
                handlers: tipo === 'completo' ? { image: () => subirImagen(quill) } : {},
            },
        },
    });

    // Contenido inicial
    if (textarea.value.trim() !== '') {
        quill.setContents(quill.clipboard.convert({ html: textarea.value }), 'silent');
    }

    // Las imágenes pegadas como datos (base64) no se guardan: hay que usar el botón de imagen
    // (se escucha en fase de captura sobre el contenedor para adelantarse a Quill)
    quill.container.addEventListener('paste', (e) => {
        const items = [...(e.clipboardData?.items || [])];
        const imagen = items.find(i => i.kind === 'file' && i.type.startsWith('image/'));
        if (imagen) {
            e.preventDefault();
            e.stopPropagation();
            if (tipo === 'completo') subirImagenDesdeArchivo(quill, imagen.getAsFile());
        }
    }, true);

    textarea.form?.addEventListener('submit', () => {
        const vacio = quill.getText().trim() === '' && !quill.root.querySelector('img');
        textarea.value = vacio ? '' : quill.getSemanticHTML().replace(/&nbsp;/g, ' ');
    });
}

async function subirImagenDesdeArchivo(quill, archivo) {
    if (!archivo) return;
    const datos = new FormData();
    datos.append('imagen', archivo, 'pegada.png');
    const rango = quill.getSelection(true);
    try {
        const r = await window.axios.post('/editor/imagen', datos);
        quill.insertEmbed(rango.index, 'image', r.data.url, 'user');
    } catch (e) {
        alertaEditor(e.response?.data?.message || 'No se pudo subir la imagen pegada.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea[data-editor]').forEach(iniciar);
});
