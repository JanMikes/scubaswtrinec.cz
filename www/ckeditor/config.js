/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	config.uiColor = "#eeeeee";
	config.allowedContent = true;

	config.toolbar = [
		{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },
		{ name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
		{ name: 'tools', items: [ 'Maximize' ] },
		"/",
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', '-', 'RemoveFormat' ] },
		{ name: 'links', items: [ 'Link', 'Unlink' ] },
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList' ] },
		{ name: 'styles', items: [ 'Styles' ] }
	];

	var baseUri = $("body").data("baseuri");

	config.filebrowserBrowseUrl = baseUri + '/kcfinder/browse.php?type=files';
	config.filebrowserImageBrowseUrl = baseUri + '/kcfinder/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = baseUri + '/kcfinder/browse.php?type=flash';
	config.filebrowserUploadUrl = baseUri + '/kcfinder/upload.php?type=files';
	config.filebrowserImageUploadUrl = baseUri + '/kcfinder/upload.php?type=images';
	config.filebrowserFlashUploadUrl = baseUri + '/kcfinder/upload.php?type=flash';

	CKEDITOR.stylesSet.add( 'styles', [
		// Block-level styles
		{ name: 'Nadpis', element: 'h2' },
		{ name: 'Podnadpis' , element: 'h3' },
		{ name: 'Odstavec' , element: 'p' },
		{ name: 'Menší text', element: 'span' , attributes: { "class": "small"} },

	]);

	config.stylesSet = 'styles';

};
