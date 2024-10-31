<?php

/*
 * This file is part of the Yabe package.
 *
 * (c) Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace Yabe\Webfont\Builder\Builderius;

use Yabe\Webfont\Admin\AdminPage;
use Yabe\Webfont\Builder\BuilderInterface;
use Yabe\Webfont\Utils\Font;
/**
 * @author Joshua Gugun Siagian <suabahasa@gmail.com>
 *
 * @todo Built-in Google Fonts are not disabled.
 */
class Main implements BuilderInterface
{
    public function __construct()
    {
        \add_action('admin_menu', static fn() => AdminPage::add_redirect_submenu_page('builderius'), 1000001);
        \add_action('wp_enqueue_scripts', fn() => $this->enqueue_scripts(), 1000001);
    }
    public function get_name() : string
    {
        return 'builderius';
    }
    public function enqueue_scripts()
    {
        if (!\wp_script_is('builderius-builder', 'registered')) {
            return;
        }
        $fonts = Font::get_fonts();
        $inline_script_content = <<<JS
    const moduleTypography = builderiusBackend.settingsList?.module?.advanced?.typography?.find((item) => item.name === 'font');

    if (moduleTypography) {
        moduleTypography.options.fontType.values.push('Yabe Webfont');
        moduleTypography.options.genericFamily.values['Yabe Webfont'] = ['Yabe Webfont'];
        moduleTypography.options.fontFamily.values['Yabe Webfont.Yabe Webfont'] = yabeWebfontBuilderiusFonts;
    }

    const GlobalTypography = builderiusBackend.settingsList?.global?.advanced?.typography?.find((item) => item.name === 'font');

    if (GlobalTypography) {
        GlobalTypography.options.fontType.values.push('Yabe Webfont');
        GlobalTypography.options.genericFamily.values['Yabe Webfont'] = ['Yabe Webfont'];
        GlobalTypography.options.fontFamily.values['Yabe Webfont.Yabe Webfont'] = yabeWebfontBuilderiusFonts;
    }

    const templateTypography = builderiusBackend.settingsList?.template?.advanced?.typography?.find((item) => item.name === 'font');

    if (templateTypography) {
        templateTypography.options.fontType.values.push('Yabe Webfont');
        templateTypography.options.genericFamily.values['Yabe Webfont'] = ['Yabe Webfont'];
        templateTypography.options.fontFamily.values['Yabe Webfont.Yabe Webfont'] = yabeWebfontBuilderiusFonts;
    }
JS;
        \wp_add_inline_script('builderius-builder', 'const yabeWebfontBuilderiusFonts = ' . \json_encode(\array_column($fonts, 'family'), \JSON_THROW_ON_ERROR), 'before');
        \wp_add_inline_script('builderius-builder', $inline_script_content, 'before');
    }
}
