function yabe_webfont_add_fonts(options) {
	const { __ } = wp.i18n;
	const yabe_webfonts = [
		{
			type: 'group',
			label: __('Yabe Webfont', 'yabe-webfont'),
			options: yabeWebfontKadenceBlocks.fonts,
		},
	];

	options = yabe_webfonts.concat(options);

	return options;
}
wp.hooks.addFilter('kadence.typography_options', 'yabe/webfont/add_fonts', yabe_webfont_add_fonts);