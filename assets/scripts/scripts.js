// Import js for componenents
importAll(require.context("../../blocks/", true, /\/scripts\.js$/));

// Import js for pages
function importAll(r) {
    r.keys().forEach(r);
}

console.log("Hello from scripts.js");
