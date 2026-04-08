<?php

return [
    'sales_order' => [
        'external_ref' => 'client_order_ref',
        'customer_id' => 'partner_id',
        'order_date' => 'date_order',
        'notes' => 'note',
        'currency' => 'currency_id',
        'payment_term' => 'payment_term_id',
        'lines' => [
            'product_id' => 'product_id',
            'quantity' => 'product_uom_qty',
            'price' => 'price_unit',
            'description' => 'name',
            'discount' => 'discount',
        ],
    ],

    'customer' => [
        'name' => 'name',
        'email' => 'email',
        'phone' => 'phone',
        'address' => 'street',
        'city' => 'city',
        'country' => 'country_id',
        'vat' => 'vat',
    ],

    'product' => [
        'name' => 'name',
        'description' => 'description',
        'price' => 'list_price',
        'sku' => 'default_code',
        'category' => 'categ_id',
    ],

    'defaults' => [
        'sales_order' => [
            'state' => 'draft',
            'order_policy' => 'manual',
        ],
    ],
];
