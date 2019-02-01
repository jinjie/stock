# SilverStripe Stock Widget

Display stock widget using [AlphaVantage API](alphavantage.co)

A very simple stock widget for my own use in a project. Please contribute to improve this module. 

Require SilverStripe ^4

## Installation

`composer require jinjie/stock`

## Usage

Run `dev/build` and go to `Settings > Stock Widget` to configure the widget, which includes the API key.

## Template

### Display

Put $StockWidget anywhere in your template where you want the widget to be rendered.

### Edit the template

Copy content in `vendor/jinjie/stock` to `themes/<your theme>/templates/Stock/StockWidget.ss`. Edit accordingly.

## Cache

This module uses cache so it doesn't need to call the API that often. It is recommended to leave it as 60 seconds or
more.
