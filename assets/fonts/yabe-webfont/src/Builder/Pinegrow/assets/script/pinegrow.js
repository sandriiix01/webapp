document.addEventListener('DOMContentLoaded', async function () {
    await new Promise(resolve => {
        let waitingForEditor = setInterval(() => {

            if (
                window.frames[0].pinegrow?.getSelectedPage() !== null
                && window.frames[0].pinegrow?.getSelectedPage() !== undefined
            ) {
                clearInterval(waitingForEditor);
                resolve();
            }
        }, 3000);
    });

    // add stylesheet
    window.frames[0].pinegrow.getSelectedPage().addStylesheet(window.yabeWebfontPinegrow.stylesheet_url);

    // style tab
    await new Promise(resolve => {
        let waitingForFontFamily = setInterval(() => {
            try {
                if (
                    window.frames[0].$.fn.crsa.defaults.rulesDefinition.sections.text.fields['font-family'] !== null
                    && window.frames[0].$.fn.crsa.defaults.rulesDefinition.sections.text.fields['font-family'] !== undefined
                ) {
                    clearInterval(waitingForFontFamily);
                    resolve();
                }
            } catch (err) { }
        }, 100);
    });

    window.frames[0].$.fn.crsa.defaults.rulesDefinition.sections.text.fields['font-family'].options = function () {
        return window.yabeWebfontPinegrow.font_families || [];
    };

    // design tab
    await new Promise(resolve => {
        let waitingForFontLibrary = setInterval(() => {
            try {
                if (
                    window.frames[0].pinegrow.fontLibrary !== null
                    && window.frames[0].pinegrow.fontLibrary !== undefined
                ) {
                    clearInterval(waitingForFontLibrary);
                    resolve();
                }
            } catch (err) { }
        }, 100);
    });

    const preservedSystemFonts = [];

    window.frames[0].pinegrow.fontLibrary.forEachFont(font => {
        if (font.type === 'system') {
            preservedSystemFonts.push(font);
        }
    });

    const fontSystemClass = window.frames[0].pinegrow.fontLibrary.getFontClassForType('system');

    window.frames[0].pinegrow.fontLibrary.removeAll();

    window.yabeWebfontPinegrow.font_families.forEach(font => {
        const pfs = new fontSystemClass({
            type: 'system',
            family: font.family,
            usage: 'all',
            category: 'sans-serif',
            w: '100, 200, 300, 400, 500, 600, 700, 800, 900'.split(/,\s?/)
        });

        window.frames[0].pinegrow.fontLibrary.addFont(pfs);
    });

    preservedSystemFonts.forEach(font => {
        window.frames[0].pinegrow.fontLibrary.addFont(font);
    });

    window.frames[0].pinegrow.fontLibrary.save();
});