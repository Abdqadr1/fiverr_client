<?php
require_once "vendor/autoload.php";

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

function _dynamicCrawler($link, $timeout = 60, $containsPath, $buttonId = null)
{
    $output = false;
    try {
        $link = str_starts_with($link, 'https://') ? $link : "https://" . $link;

        $host = 'http://localhost:9515'; // this is the link and the port of the chromedriver

        $capabilities = DesiredCapabilities::chrome();

        // Start the Selenium WebDriver with the specified capabilities
        $driver = RemoteWebDriver::create($host, $capabilities);

        // Navigate to the website you want to scrape
        $driver->get($link);

        if ($buttonId) {
            $driver->findElement(WebDriverBy::id($buttonId))->click();
        }

        if ($timeout > 0) {
            // Wait for the page to load
            $driver->wait($timeout)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath($containsPath)
                )
            );
        }

        // $title = $driver->getTitle();
        // $current_url  = $driver->getCurrentURL();

        // Print the scraped data
        // echo "Page Title: $title <br/>";
        // echo "Current URL: $current_url <br/>";

        $output =  $driver->getPageSource();
        // Quit the Selenium WebDriver
    } catch (Exception $ex) {
        echo $ex->getMessage();
    } finally {
        $driver?->quit();
        return $output;
    }
}

// _dynamicCrawler('https://gis.dutchessny.gov/parcelaccess/property-card/?parcelgrid=13020000595400289208930000&parcelid=463');
