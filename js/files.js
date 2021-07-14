window.addEventListener('DOMContentLoaded', () => {
	if (OCA.Theming) {
		OC.MimeType._mimeTypeIcons['dir-virtual'] = OC.generateUrl('/apps/theming/img/virtualfolder/folder-virtual.svg?v=' + OCA.Theming.cacheBuster);
	} else {
		OC.MimeType._mimeTypeIcons['dir-virtual'] = OC.imagePath('virtualfolder', 'folder-virtual');
	}
})
