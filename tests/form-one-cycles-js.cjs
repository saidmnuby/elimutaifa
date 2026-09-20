const assert = require('node:assert/strict');
const generate = require('../admin/assets/form-one-cycles.js');
assert.equal(generate('2025', 'First selection'), 'https://selection.tamisemi.go.tz/allocations/2025/first-selection/index.html');
assert.equal(generate('2026', ' Second Selection '), 'https://selection.tamisemi.go.tz/allocations/2026/second-selection/index.html');
assert.equal(generate('', 'First selection'), '');
assert.equal(generate('2025', ''), '');
console.log('PASS: cycle URL JavaScript generation.');
