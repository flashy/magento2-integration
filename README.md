# Magento2 Module for Flashy

## Features
Flashy's module for Magento 2+ adds tracking events (View Content, Cart Updates, Purchases, Page Views and more).
Using the module all your products, orders and subscribers will be sync with the Flashy platform.

## Installation

### Step 1 - Install the module via composer 

```
composer require flashy/magento2-integration
```


### Step 2 -  Enable the module
```
bin/magento module:enable --clear-static-content Flashy_Integration
bin/magento setup:di:compile
bin/magento setup:upgrade
bin/magento cache:flush
```

### Step 3 - Configuration

#### Get Flashy API Key 
1. Login to Flashy Dashboard (https://my.flashy.app/auth/signin)
2. Click on account avatar located on the top-right corner (top-left if your language is Hebrew) 
3. On the menu click on API 
4. Copy the API key

#### Enable the Magento module  
1. Login to Magento Admin 
2. From the admin menu go to Flashy -> Flashy Settings 
3. Set __Enable__ to Yes 
4. Set __Send purchase before payment__ to Yes
4. Paste the API key you copied from Flashy Dashboard to __API Key__
5. Save the configuration 
6. Clear Magento caches. 


