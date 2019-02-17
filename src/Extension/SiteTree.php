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
        $lastUpdated          = $data['Meta Data']['3. Last Refreshed'];
        $lastUpdatedTimestamp = strtotime($lastUpdated);
        $previousDay          = date('Y-m-d', strtotime('-1 day', $lastUpdatedTimestamp));

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

        return ArrayData::create([
            'Symbol'        => $stockSymbol,
            'LastPrice'     => DBCurrency::create()->setValue($lastPrice),
            'LastUpdated'   => DBDatetime::create()->setValue($convertDateTime->format('Y-m-d H:i:s')),
            'PreviousClose' => DBCurrency::create()->setValue($previousClose),
            'TickStatus'    => $tickStatus,
        ])->renderWith('Stock/StockWidget');
    }
}
