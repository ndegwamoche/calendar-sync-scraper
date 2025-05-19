// scrape.js
const puppeteer = require('puppeteer');

(async () => {
    const url = 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,42024,14822,4006,4004,,,,';

    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();

    await page.goto(url, { waitUntil: 'networkidle2', timeout: 0 });

    // Wait for the table to appear
    await page.waitForSelector('#ctl00_ContentPlaceHolder1_ShowStandings1_PanelResults table', { timeout: 20000 });

    // Get the HTML content of the table
    const content = await page.$eval('#ctl00_ContentPlaceHolder1_ShowStandings1_PanelResults', el => el.innerHTML);

    console.log(content); // This will be captured by PHP

    await browser.close();
})();
