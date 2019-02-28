<?php

/**
 * SiteTree
 *
 * @package SwiftDevLabs\Stock\Extension
 * @author Kong Jin Jie <jinjie@swiftdev.sg>
 */

namespace SwiftDevLabs\Stock\Extension;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;

class SiteTree extends DataExtension
{
    public function getStockWidget()
    {
        $siteConfig  = SiteConfig::current_site_config();
        $apiKey      = $siteConfig->AlphaVantageAPI;
        $stockSymbol = $siteConfig->StockSymbol;
        $interval    = $siteConfig->Interval;
        $cacheExpiry = intval($siteConfig->CacheExpiry ? : 60);
        
        if (! $apiKey || ! $stockSymbol || ! $interval) {
            return "Please complete the setup of AlphaVantage in Settings";
        }

        $cache    = Injector::inst()->get(CacheInterface::class . '.stockCache');

        // Setup AlphaVantage Client
        $avOption = new \AlphaVantage\Options();
        $avOption->setApiKey($apiKey);

        $avClient = new \AlphaVantage\Client($avOption);
        
        // Time Series Intraday
        $cacheKey = 'intraday' . $stockSymbol . $interval . $cacheExpiry;
        $data     = $cache->get($cacheKey);

        if (! $data) {
            $data = $avClient->timeSeries()->intraday(
                $stockSymbol,
                $interval
            );

            if (isset($data['Note'])) {
                return $data['Note'];
            }

            $cache->set(
                $cacheKey,
                $data,
                $cacheExpiry
            );
        }

        $lastPrice            = floatval(current($data["Time Series ({$interval})"])['1. open']);
        $volume               = intval(current($data["Time Series ({$interval})"])['5. volume']);
        $lastUpdated          = $data['Meta Data']['3. Last Refreshed'];
        $lastUpdatedTimestamp = strtotime($lastUpdated);

        // Time Series Daily
        $cacheKey = 'daily' . $stockSymbol . $interval . $cacheExpiry;
        $data     = $cache->get($cacheKey);

        if (! $data) {
            $data = $avClient->timeSeries()->daily(
                $stockSymbol
            );

            if (isset($data['Note'])) {
                return $data['Note'];
            }

            $cache->set(
                $cacheKey,
                $data,
                $cacheExpiry
            );
        }

        $days = array_keys($data['Time Series (Daily)']);
        $previousDay = $days[1];

        $previousClose = floatval($data["Time Series (Daily)"][$previousDay]['4. close']);
        $tickStatus = 'even';

        if ($previousClose > $lastPrice) {
            $tickStatus = 'down';
        } elseif ($previousClose < $lastPrice) {
            $tickStatus = 'up';
        }

        // Convert API timezone to local time zone
        $convertDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $lastUpdated,
            new \DateTimeZone('US/Eastern')
        );

        $convertDateTime->setTimeZone(new \DateTimezone(date_default_timezone_get()));

        $priceDifference = $lastPrice - $previousClose;
        $percentDifference = ($priceDifference / $previousClose) * 100;

        return ArrayData::create([
            'Symbol'            => $stockSymbol,
            'LastPrice'         => DBCurrency::create()->setValue($lastPrice),
            'LastUpdated'       => DBDatetime::create()->setValue($convertDateTime->format('Y-m-d H:i:s')),
            'PriceDifference'   => DBDecimal::create()->setValue($priceDifference),
            'PercentDifference' => DBDecimal::create()->setValue($percentDifference),
            'PreviousClose'     => DBCurrency::create()->setValue($previousClose),
            'Volume'            => DBInt::create()->setValue($volume),
            'TickStatus'        => $tickStatus,
        ])->renderWith('Stock/StockWidget');
    }
}
