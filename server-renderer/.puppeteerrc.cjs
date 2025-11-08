const { join } = require('path');

/**
 * @type {import("puppeteer").Configuration}
 */
module.exports = {
  // Use the local cache directory
  cacheDirectory: join(__dirname, '.cache'),
};
