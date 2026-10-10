const fs = require('fs');
const html = fs.readFileSync('index.php','utf8');
const css = fs.readFileSync('assets/style.css','utf8');

// find elements with inline width/style that could overflow
const matches = html.match(/style=['"][^'"]*(?:100|width|vw)[^'"]*['"]/gi) || [];
console.log('Inline width styles:', matches.length);
matches.forEach(function(m){ console.log('  ', m); });

// find negative margins
const lines = css.split('\n');
lines.forEach(function(line, i) {
  if (/margin[^;]*-\s*\d/.test(line)) console.log('  CSS line ' + (i+1) + ': ' + line.trim().substring(0,100));
});
