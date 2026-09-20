const assert = require('node:assert/strict');
const normalize = require('../assets/js/selection-students.js');
assert.equal(normalize(' S3884/0001 '), normalize('S3884.0001'));
const rows = ['S3884.0001.2025', 'S3884.0006.2025'].map(candidate => ({dataset: {selectionStudent: candidate}, hidden: false}));
let listener;
const input = {value: '', addEventListener: (event, callback) => {assert.equal(event, 'input'); listener = callback;}};
const count = {}, empty = {};
global.document = {
    addEventListener: (event, callback) => callback(),
    getElementById: id => ({'selection-student-search': input, 'selection-student-count': count, 'selection-student-empty': empty})[id],
    querySelectorAll: () => rows
};
delete require.cache[require.resolve('../assets/js/selection-students.js')];
require('../assets/js/selection-students.js');
input.value = 's3884/0001'; listener();
assert.deepEqual(rows.map(row => row.hidden), [false, true]);
assert.equal(count.textContent, '1 / 2 wanafunzi');
input.value = '9999'; listener(); assert.equal(empty.hidden, false);
input.value = ''; listener(); assert.deepEqual(rows.map(row => row.hidden), [false, false]);
assert.equal(empty.hidden, true);
delete global.document;
console.log('PASS: student filtering, slash/dot formats, counts, empty state and clearing.');
