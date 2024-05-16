// copy2clipboard
// Refresh the site every 5 seconds

// implemented as '<button style=\"padding: 0; border: none; background: none;\" onclick=\"copy2clipboard('text to copy')\"><img style=\"height: 1.5em;\" src=\"/img/copy2clipboard.gif\"></button>'

function copy2clipboard(ClipboardContent) {
	navigator.clipboard.writeText(ClipboardContent);
}
