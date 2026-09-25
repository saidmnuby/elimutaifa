const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Element {
    constructor(tag) { this.tag = tag; this.childNodes = []; this.dataset = {}; this.hidden = true; this.events = {}; this.classList = { add() {}, remove() {} }; }
    appendChild(child) { this.childNodes.push(child); return child; }
    append(...children) { children.forEach(child => this.appendChild(child)); }
    replaceChildren(fragment) { this.childNodes = [...fragment.childNodes]; }
    setAttribute() {}
    addEventListener(type, handler) { this.events[type] = handler; }
    remove() {}
    set innerHTML(_) { throw new Error('HTML injection is prohibited'); }
}
const top = new Element('div'); top.dataset.etPlacementSlot = 'top';
const bottom = new Element('div'); bottom.dataset.etPlacementSlot = 'bottom';
const entry = { slot: 'bottom', format: 'card', kind: 'sponsor', sponsor_name: 'Test sponsor', href: 'https://example.com/notice', title: '<script>not executable</script>', description: 'Description', external: true };
const popupEntry = { ...entry, id: 55, slot: 'popup', title: 'Popup title' };
const preview = { textContent: JSON.stringify({ items: [{ ...entry, slot: 'top', format: 'banner' }, entry, entry, entry, popupEntry] }) };
const stored = new Map();
const window = { sessionStorage: { getItem: key => stored.get(key) || null, setItem: (key,value) => stored.set(key,value) }, localStorage: { getItem: key => stored.get(key) || null, setItem: (key,value) => stored.set(key,value) }, setTimeout: fn => { fn(); return 1; }, setInterval: fn => { fn(); return 1; }, clearInterval() {} };
const document = {
    currentScript: { src: 'http://localhost/get-results-faster/assets/js/placements.js' },
    head: new Element('head'), body: new Element('body'), documentElement: new Element('html'), readyState: 'complete',
    createElement: tag => new Element(tag), createDocumentFragment: () => new Element('fragment'),
    querySelectorAll: () => [top, bottom], getElementById: () => preview
};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/js/placements.js'), 'utf8'),
    { document, window, URL, location: { href: 'http://localhost/get-results-faster/', origin: 'http://localhost' } });
assert.equal(top.childNodes.length, 1);
assert.equal(bottom.childNodes.length, 2);
assert.equal(bottom.hidden, false);
const link = bottom.childNodes[0].childNodes[0];
assert.equal(link.rel, 'sponsored noopener noreferrer');
assert.equal(link.target, '_blank');
assert.equal(link.childNodes[0].childNodes[0].textContent, 'Sponsors · Test sponsor');
assert.equal(link.childNodes[0].childNodes[1].textContent, entry.title);
assert.equal(document.head.childNodes[0].href, 'http://localhost/get-results-faster/assets/css/placements.css');
assert.equal(document.body.childNodes.length, 1);
assert.equal(document.documentElement.dataset.etPlacementPopup, '1');
assert.equal(document.body.childNodes[0].childNodes[1].childNodes[0].rel, 'sponsored noopener noreferrer');
document.body.childNodes[0].childNodes[0].events.click();
assert.equal(stored.get('elimutaifa-placement-popup-55'), 'seen');
top.childNodes = []; bottom.childNodes = []; top.hidden = true; bottom.hidden = true;
document.body.childNodes = []; delete document.documentElement.dataset.etPlacementPopup;
preview.textContent = JSON.stringify({ items: [{ ...entry, href: 'javascript:alert(1)' }] });
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/js/placements.js'), 'utf8'),
    { document, window, URL, location: { href: 'http://localhost/get-results-faster/', origin: 'http://localhost' } });
assert.equal(bottom.childNodes.length, 0);
assert.equal(bottom.hidden, true);
console.log('PASS: placement DOM rendering, sponsor labels/links, literal text safety, slot limits and asset base path.');
