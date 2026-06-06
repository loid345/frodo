# Frodo Antifraud for Magento 2.4.8

`Frodo_Antifraud` blocks suspicious checkout attempts before the order is submitted.
The module supports Magento multi-website installations, Russian and English admin/customer messages, and stores that accept several display currencies.

## Features

- Enable or disable checks per default, website, or store scope.
- Daily order-count limit per customer e-mail.
- Daily order-amount limit per customer e-mail.
- E-mail whitelist that bypasses limits and blacklists.
- E-mail blacklist that blocks checkout by customer e-mail.
- Customer ID blacklist that blocks registered customer accounts.
- IP blacklist that blocks checkout by exact IPv4/IPv6 address or IPv4 CIDR range.
- Daily totals are calculated for the current website's stores and the current store day/time zone.
- Amount limits are compared with `sales_order.base_grand_total`, so configure the limit in the website base currency. This keeps checks stable when customers pay in different display currencies.

## Installation

Copy the repository contents into the Magento root and run:

```bash
bin/magento module:enable Frodo_Antifraud
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

Open **Stores → Configuration → Frodo → Antifraud → General**.

| Setting | Description |
| --- | --- |
| Enabled | Enables antifraud checks for the selected scope. |
| Daily order count limit | Maximum number of non-canceled/non-closed orders per e-mail per store day. Empty or `0` disables this check. |
| Daily amount limit | Maximum daily total per e-mail in the website base currency. Empty or `0` disables this check. |
| Email whitelist | E-mails that bypass all checks. Separate values with a new line, comma, semicolon, or space. |
| Email blacklist | E-mails that are blocked from checkout. Separate values with a new line, comma, semicolon, or space. |
| Customer ID blacklist | Registered customer IDs that are blocked from checkout. Guest orders are checked by e-mail and IP only. |
| IP blacklist | Exact IP addresses or IPv4 CIDR ranges to block. Separate values with a new line, comma, semicolon, or space. |
