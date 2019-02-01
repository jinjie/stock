<?php

/**
 * SiteConfig
 *
 * @package SwiftDevLabs\Stock\Extension
 * @author Kong Jin Jie <jinjie@swiftdev.sg>
 */

namespace SwiftDevLabs\Stock\Extension;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class SiteConfig extends DataExtension
{
    private static $db = [
        'AlphaVantageAPI' => 'Varchar(16)',
        'StockSymbol'     => 'Varchar(10)',
        'Interval'        => 'Enum(array(
            "1min",
            "5min",
            "15min",
            "30min",
            "60min",
        ))',
        'CacheExpiry'     => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.StockWidget',
            TextField::create(
                'AlphaVantageAPI',
                'Alpha Vantage API'
            )->setDescription('
                Get your free AlphaVantage API at<a href="https://www.alphavantage.co/" target="_blank">
                https://www.alphavantage.co/</a>')
        );

        $fields->addFieldToTab(
            'Root.StockWidget',
            TextField::create(
                'StockSymbol',
                'Stock Symbol'
            )
        );

        $fields->addFieldToTab(
            'Root.StockWidget',
            DropdownField::create(
                'Interval',
                'Interval',
                [
                    "1min"  => "1 min",
                    "5min"  => "5 mins",
                    "15min" => "15 mins",
                    "30min" => "30 mins",
                    "60min" => "60 mins",
                ]
            )
        );

        $fields->addFieldToTab(
            'Root.StockWidget',
            TextField::create(
                'CacheExpiry',
                'Cache Expiry (in seconds)'
            )->setDescription('Set the cache expiry. Leave blank or 0 for 60 seconds.')
        );
    }
}
