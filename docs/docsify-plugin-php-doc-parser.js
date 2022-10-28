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
            code += '```php\n    ' + match[0] + "\n```\n";
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
