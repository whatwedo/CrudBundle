(function () {
    const parseCode = async (repo, file, search) => {
        const response = await fetch(window.whatwedoOptions.fullPath(repo, file));
        const fileContent = await response.text();

        const regexOne = /\/\*\*(.|\n)+?/;
        const regexTwo = new RegExp('(?=' + search + ')')
        const regexThree = /.+;$/;
        const regex = new RegExp(regexOne.source + regexTwo.source + regexThree.source, 'gm');
        const matches = [...fileContent.matchAll(regex)];

        let code = '';
        for (const match of matches) {

            const lines = match[0].split('\n');
            const codeLine = lines.pop().trim().split(' ').filter((line) => line.startsWith('OPT_'))[0];

            const removeCommentsRegex = /\/?[^\S\r\n]*\*[^\S\r\n]*\/?/g;
            lines.shift();
            code += '<h4>' + codeLine + '</h4><p>' + lines.join('<br />').replaceAll(removeCommentsRegex, '') + '</p>';
        }
        return code;
    };
    const plugin = (hook, vm) => {
        hook.beforeEach(async function (markdown, next) {
            try {
                const regex = /\[php-doc-parser\((.+):(.+):(.+)\)]/g;
                const matches = [...markdown.matchAll(regex)];
                for (const match of matches) {
                    const parsed = await parseCode(match[1], match[2], match[3]);
                    markdown = markdown.replace(match[0], parsed);
                }
            } catch (err) {
                console.error(err);
            } finally {
                next(markdown);
            }
        });
    };
    // Add plugin to docsify's plugin array
    const $docsify = window.$docsify || {};
    $docsify.plugins = [].concat($docsify.plugins || [], plugin);
})();
