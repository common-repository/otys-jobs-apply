window.addEventListener("load",function(){
    'use strict'

    {
        /**
         * Add TinyMCE WYSIWYG to all OTYS Texteditors
         */
        const editors = document.getElementsByClassName('otys-widget-texteditor');
    
        if (editors) {
            function initEditors() {
                for (let i = 0; i < editors.length; i++) {
                    // get the #id of the element
                    let editorId = editors[i].getAttribute('id');
                    
                    // First remove editor if any
                    wp.editor.remove(editorId);

                    // Init editor
                    let instance = wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            theme: 'modern',
                    
                            toolbar: [
                                'undo redo | styleselect | bold italic | link image alignleft aligncenter alignright'
                            ]
                        },
                        quicktags: true,
                        mediaButtons: true,
                    });

                    // Trigger events on key up
                    tinymce.get(editorId).on('keyup', (event, test) => {
                        // Create manual events to trigger on the area
                        let onChangeEvent = new Event('change', {
                            bubbles: true
                        });
            
                        let onInputEvent = new Event('input', {
                            bubbles: true
                        });
                        
                        /**
                         * Dispatch events so the eventlisteners get triggered
                         * This will make sure the behaviour of the editor is the same
                         * as other fields.
                         *  */
                        editors[i].dispatchEvent(onChangeEvent);
                        editors[i].dispatchEvent(onInputEvent);

                        // Get current value of the tinymce editor
                        let content = tinymce.get(editorId).getContent();

                        // Assign current tinymce value to the textarea
                        editors[i].innerHTML = content;
                    });

                }
            }

            /**
             * Reinit the editors when the widget-update event has been fired
             * this event gets triggered when a widget is saved with ajax
             */
            jQuery(document).on('widget-updated widget-added', function () {
                initEditors();
            });

            /**
             * Init editors first time
             */
            initEditors();
        }
    }
});