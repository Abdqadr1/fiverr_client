<?php
require_once "vendor/autoload.php";


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;

function _dynamicCrawler($link, $timeout = 60)
{

    $driver_path = './geckodriver-v0.33.0-win/geckodriver.exe';

    $capabilities = array(
        WebDriverCapabilityType::BROWSER_NAME => 'firefox',
    );


    // Start the Selenium WebDriver with the specified capabilities
    $driver = RemoteWebDriver::create($driver_path, $capabilities);

    $driver = RemoteWebDriver::create($host, $capabilities, 5000, 10000, null, null, null, $driver_path);

    // Navigate to the website you want to scrape
    $driver->get($link);

    // Wait for the page to load
    $driver->wait($timeout)->until(
        WebDriverExpectedCondition::titleContains('Example Domain')
    );

    // Find the element(s) on the page that contain the data you want to scrape
    $element = $driver->findElement(WebDriverBy::tagName('h1'));

    // Extract the text content of the element(s)
    $text = $element->getText();

    // Print the scraped data
    echo $text;

    // Quit the Selenium WebDriver
    $driver->quit();
}
