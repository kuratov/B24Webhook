# B24Webhook
B24Webhook is a miniature library for speeding up work with incoming webhooks of the Bitrix24 CRM service

The library requires php 5.6+

**Attention! The library currently includes the following existing requests to Bitrix24:**
- crm.lead.add
- crm.duplicate.findbycomm
- crm.lead.productrows.set
- crm.product.list

# Using B24Webhook

Before using the library, you need to create incoming webhooks in Bitrix. [How to do it](https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=99&LESSON_ID=8581).

`Secret` means this part of the incoming webhook:
For example, https://sitename.bitrix24.ru/rest/1/173glory345hfgruy/crm.lead.add.json
The `secret` is `173glory345hfgruy`

Create an instance of the B24Webhook class

```php
use kuratovru\B24Webhook\B24Webhook;
$connector = new B24Webhook('bitrix24.site.ru');
```

##Adding a lead

Use the `addLead` method to add a lead to Bitrix24.
You can see the set of fields for `leadData` [on the official Bitrix website](https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_fields.php)

```php
$secret = 'secretcode';

$leadData = [
    'NAME' => 'TestLeadName',
    "ASSIGNED_BY_ID" => 1,
    "EMAIL" => [
        [
            'VALUE' => 'test@ya.ru',
            'VALUE_TYPE' => 'WORK',
        ]
    ],
    'PHONE' => [
        [
            'VALUE' => '99-99-99',
            'VALUE_TYPE' => 'WORK',
        ]
    ],
    "COMMENTS" => 'MyComment',
];

$leadId = $connector->addLead($secret, $leadData);
```

##Checking for duplicates

So when adding a lead through webhooks, there is no check for duplicates, it must be done manually
Two functions will help us with this: `checkDuplicateByPhone` and `checkDuplicateByEmail`

```php
$secret = 'secretcode';

$emails = [
    'test@ya.ru',
    'test1@ya.ru'
];

if ( !$connector->checkDuplicateByEmail($secret, $emails) ):
    echo 'not a duplicate';
endif;

$phones = [
    '99-99-99',
    '13-23-33'
];

if ( !$connector->checkDuplicateByPhone($secret, $phones) ):
    echo 'not a duplicate';
endif;
```

##Getting a list of products

Use the `GetProducts` function to get information about products in Bitrix24.

You can view additional information on the request parameters [here](https://dev.1c-bitrix.ru/rest_help/crm/products/crm_product_list.php)

```php
$secret = 'secretcode';

$arOrder = [
    'PRICE' => 'ASC'
];

$arFilter = [
    'NAME' => 'DN 32%'
];

$arSelect = [
    'ID',
    'NAME',
    'PRICE',
    'PROPERTY_EXTENDED_TEXT'
];

$result = $connector->getProducts($secret, $arOrder, $arFilter, $arSelect);

foreach ($result as $product) {
    echo "Name - {$product['NAME']}".PHP_EOL;
    echo "Price - {$product['PRICE']}".PHP_EOL;
    echo "Custom property - {$product['PROPERTY_EXTENDED_TEXT_VALUE']}".PHP_EOL;
}
```


##Adding a product item to the lead

Use the `addProductsToLead ' function to add product items to the lead

```php
$secret = 'secretcode';

$leadId = 1234;

$rows = [
    [
        'PRODUCT_ID' => 11,
        'PRICE' => 150,
        'QUANTITY' => 3,
    ],
    [
        'PRODUCT_ID' => 117,
        'PRICE' => 120,
        'QUANTITY' => 1,
    ],
];

$connector->addProductsToLead($secret, $leadId, $rows);
```