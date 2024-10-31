'use strict';

{
    let sortables = document.querySelectorAll('.otys-sortables');

    sortables.forEach(element => {
        new Sortable(element, {
            animation: 150
        });
    });
}

{
    const mediaButtons = document.querySelectorAll('[data-otys-media]');

    mediaButtons.forEach(mediaButton => {
        const mediaName = mediaButton.getAttribute('data-otys-media');

        // Find related elements
        const fillElement = document.querySelectorAll('[name="'+ mediaName +'"]');
        const previewElement = document.querySelectorAll('[data-otys-media-preview-image="'+ mediaName +'"]');
        const previewBlock = document.querySelectorAll('[data-otys-media-preview="'+ mediaName +'"]');
        const resetButton = document.querySelectorAll('[data-otys-media-reset="'+ mediaName +'"]');

        // Hide preview block if there is nothing to preview
        if ((fillElement.length && fillElement[0].value)) {
            if (previewBlock.length) {
                previewBlock[0].style.display = 'block';
            }

            // Show reset button
            if (resetButton.length) {
                resetButton[0].style.display = 'block';
            }
        } else {
            // Show image picker
            mediaButton.style.display = "block";
        }

        // Bind remove button
        if (resetButton.length) {
            resetButton[0].addEventListener('click', (e) => {
                e.preventDefault();

                // Hide preview block
                if (previewBlock.length) {
                    previewBlock[0].style.display = 'none';
                }

                // Empty the preview element
                if (previewElement.length) {
                    previewElement[0].setAttribute('src', '');
                }

                // Hide reset button
                resetButton[0].style.display = 'none';

                // Show image picker
                mediaButton.style.display = "block";

                // Empty attachment url
                if (fillElement.length) {
                    fillElement[0].value = '';
                }
            });
        }


        // Bind media picker
        mediaButton.addEventListener('click', (e) => {
            let wpMedia;
            e.preventDefault();
            
            // If the upload object has already been created, reopen the dialog
            if (wpMedia) {
                wpMedia.open();
                return;
            }

            // Vars
            let wpMediaTitle = mediaButton.getAttribute('data-otys-media-title');
            let wpMediaButtonText = mediaButton.getAttribute('data-otys-media-button-text');
            
            // Extend the wp.media object
            wpMedia = new wp.media.view.MediaFrame.Select({
				// Modal title
				title: wpMediaTitle,

				// Enable/disable multiple select
				multiple: true,

				// Library WordPress query arguments.
				library: {
					order: 'ASC',

					// [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo',
					// 'id', 'post__in', 'menuOrder' ]
					orderby: 'title',

					// mime type. e.g. 'image', 'image/jpeg'
					type: 'image'
				},

				button: {
					text: wpMediaButtonText
				}
			});
                
            wpMedia.open();
            
            // When a file is selected, grab the URL and set it as the text field's value
            wpMedia.on('select', () => {
                let attachment = wpMedia.state().get('selection').first().toJSON();
                
                // console.log(wpMedia);

                // console.log(wp.media.model.settings.post.id);

                // Hide image picker
                mediaButton.style.display = "none";

                // Show reset button
                if (resetButton.length) {
                    resetButton[0].style.display = 'block';
                }

                // Show preview block
                if (previewBlock.length) {
                    previewBlock[0].style.display = 'block';
                }

                // Set preview image
                if (previewElement.length) {
                    previewElement[0].setAttribute('src', attachment.url);
                    previewElement[0].setAttribute('width', 'auto');
                    previewElement[0].setAttribute('height', 'auto');
                }

                // Set attachment url
                if (fillElement.length) {
                    fillElement[0].value = attachment.id;
                }
            });
        });
    });
}