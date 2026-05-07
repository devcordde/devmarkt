document.addEventListener('DOMContentLoaded', function () {
    var textarea = document.getElementById('desc');
    if (!textarea) return;

    // Build toolbar
    var toolbar = document.createElement('div');
    toolbar.className = 'md-toolbar';

    var buttons = [
        { label: '<b>B</b>', before: '**', after: '**', title: 'Fett' },
        { label: '<i>I</i>', before: '*', after: '*', title: 'Kursiv' },
        { label: '<u>U</u>', before: '__', after: '__', title: 'Unterstrichen' },
        { label: '<s>S</s>', before: '~~', after: '~~', title: 'Durchgestrichen' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 18 22 12"/><polyline points="8 6 2 6 2 12"/><line x1="2" y1="12" x2="22" y2="12"/></svg>', before: '`', after: '`', title: 'Code' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg>', before: '```\n', after: '\n```', title: 'Codeblock' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>', before: '> ', after: '', title: 'Zitat' },
        { label: 'H<sub>1</sub>', before: '# ', after: '', title: 'Überschrift 1' },
        { label: 'H<sub>2</sub>', before: '## ', after: '', title: 'Überschrift 2' },
        { label: 'H<sub>3</sub>', before: '### ', after: '', title: 'Überschrift 3' },
        { label: '<small>-#</small>', before: '-# ', after: '', title: 'Kleintext' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="5" width="14" height="14" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>', before: '||', after: '||', title: 'Spoiler' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>', before: '[', after: '](url)', title: 'Link' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>', before: '- ', after: '', title: 'Aufzählung' },
        { label: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="1" y="9" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">1</text><text x="1" y="15" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">2</text><text x="1" y="21" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">3</text></svg>', before: '1. ', after: '', title: 'Nummerierte Liste' }
    ];

    buttons.forEach(function (btn) {
        var el = document.createElement('button');
        el.type = 'button';
        el.innerHTML = btn.label;
        el.title = btn.title;
        el.addEventListener('click', function (e) {
            e.preventDefault();
            wrapSelection(btn.before, btn.after);
        });
        toolbar.appendChild(el);
    });

    // Build preview
    var preview = document.createElement('div');
    preview.className = 'md-preview';
    preview.innerHTML = '<em>Vorschau...</em>';

    var previewLabel = document.createElement('div');
    previewLabel.className = 'md-label';
    previewLabel.textContent = 'Vorschau';

    // Insert into DOM
    textarea.parentNode.insertBefore(toolbar, textarea);
    var nextSibling = textarea.nextSibling;
    if (nextSibling) {
        textarea.parentNode.insertBefore(previewLabel, nextSibling);
        textarea.parentNode.insertBefore(preview, previewLabel.nextSibling);
    } else {
        textarea.parentNode.appendChild(previewLabel);
        textarea.parentNode.appendChild(preview);
    }

    // Update preview on input
    textarea.addEventListener('input', updatePreview);
    updatePreview();

    function updatePreview() {
        var text = textarea.value;
        if (!text.trim()) {
            preview.innerHTML = '<em>Vorschau...</em>';
            return;
        }
        preview.innerHTML = renderDiscordMarkdown(text);
    }

    function wrapSelection(before, after) {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var selected = textarea.value.substring(start, end);

        textarea.value =
            textarea.value.substring(0, start) +
            before + selected + after +
            textarea.value.substring(end);

        textarea.selectionStart = start + before.length;
        textarea.selectionEnd = end + before.length;
        textarea.focus();
        updatePreview();

        // Trigger input event for character count
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function renderDiscordMarkdown(text) {
        text = escapeHtml(text);

        // Code blocks (```...```)
        text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, function (_, lang, code) {
            return '<pre><code>' + code + '</code></pre>';
        });

        // Inline code (`...`)
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Bold (**...**)
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

        // Italic (*...*)
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');

        // Underline (__...__)
        text = text.replace(/__(.+?)__/g, '<u>$1</u>');

        // Strikethrough (~~...~~)
        text = text.replace(/~~(.+?)~~/g, '<del>$1</del>');

        // Links [text](url)
        text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

        // Spoiler (||...||)
        text = text.replace(/\|\|(.+?)\|\|/g, '<span class="md-spoiler">$1</span>');

        // Headings (# ## ###)
        text = text.replace(/^### (.+)$/gm, '<h5>$1</h5>');
        text = text.replace(/^## (.+)$/gm, '<h4>$1</h4>');
        text = text.replace(/^# (.+)$/gm, '<h3>$1</h3>');

        // Subtext (-# ...)
        text = text.replace(/^-# (.+)$/gm, '<sub>$1</sub>');

        // Multi-line block quotes (>>> ...) - must come before single >
        text = text.replace(/^&gt;&gt;&gt; ([\s\S]+)$/gm, '<blockquote>$1</blockquote>');

        // Block quotes (> ...)
        text = text.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');

        // Unordered lists (- item)
        text = text.replace(/^- (.+)$/gm, '<li>$1</li>');

        // Ordered lists (1. item)
        text = text.replace(/^\d+\. (.+)$/gm, '<li class="ol">$1</li>');

        // Wrap consecutive <li> in <ul> / <ol>
        text = text.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');
        text = text.replace(/((?:<li class="ol">.*<\/li>\n?)+)/g, function (match) {
            return '<ol>' + match.replace(/ class="ol"/g, '') + '</ol>';
        });

        // Newlines (but not after block elements)
        text = text.replace(/\n/g, '<br>');

        // Clean up extra <br> after block elements
        text = text.replace(/(<\/h[345]>)<br>/g, '$1');
        text = text.replace(/(<\/ul>)<br>/g, '$1');
        text = text.replace(/(<\/ol>)<br>/g, '$1');
        text = text.replace(/(<\/pre>)<br>/g, '$1');
        text = text.replace(/(<\/blockquote>)<br>/g, '$1');
        text = text.replace(/(<\/sub>)<br>/g, '$1');
        text = text.replace(/<br>(<ul>)/g, '$1');
        text = text.replace(/<br>(<ol>)/g, '$1');

        // Clean up adjacent blockquotes
        text = text.replace(/<\/blockquote><br><blockquote>/g, '<br>');

        return text;
    }
});
