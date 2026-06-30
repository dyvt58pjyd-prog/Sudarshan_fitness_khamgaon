const jsdom = require("jsdom");
const { JSDOM } = jsdom;
const fs = require('fs');

const phpFile = fs.readFileSync('Files/dashboard/admin/new_entry.php', 'utf8');
console.log("File loaded successfully");
