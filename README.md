# Magento2 Module Flashy Integration

    ``flashyapp/module-integration``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)


## Main Functionalities
Flashy's extension adds tracking events (add to cart, purchase). It also allows you to sync products and order statuses.

## Installation
 - Install required lib via composer with command:
    - composer require cbox/flashy

 - Copy module files into \app\code\Flashy\Integration

 - Run magento 2 standard commands:
    - php bin/magento setup:upgrade
    - php bin/magento setup:di:compile
    - php bin/magento setup:static-content:deploy

 - Flush cache if needed.
    - php bin/magento cache:flush

## Configuration
		
 - Flashy Integration
	- Enabled (flashy/flashy/active)
	- Send purchase before payment (flashy/flashy/log)
	- Enabled logging (flashy/flashy/purchase)
	- API Key (flashy/flashy/flashy_key)
 
 - Flashy Lists
	- Flashy List (flashy/flashy_lists/flashy_list)


## Specifications

 - Helper
	- Flashy\Integration\Helper\Data

 - Model
	- Carthash

