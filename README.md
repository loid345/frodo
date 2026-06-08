# Frodo Antifraud for Magento 2.4.8

`Frodo_Antifraud` blocks suspicious checkout attempts before the order is submitted.
The module supports Magento multi-website installations, Russian and English admin/customer messages, and stores that accept several display currencies.

## Features

- Enable or disable checks per default, website, or store scope.
- Daily order-count limit per customer e-mail.
- Daily order-amount limit per customer e-mail.
- E-mail whitelist that bypasses daily amount and order-count limits only; blacklists still apply.
- E-mail blacklist that blocks checkout by customer e-mail.
- Customer ID blacklist that blocks registered customer accounts.
- Customer admin buttons to block/unblock order placement and apply/remove a 24-hour daily-limit restriction.
- IP blacklist that blocks checkout by exact IPv4/IPv6 address or CIDR range.
- Daily totals are calculated for the current website's stores and the current store day/time zone.
- Amount limits are compared with `sales_order.base_grand_total`, so configure the limit in the website base currency. This keeps checks stable when customers pay in different display currencies.
- Customer-facing antifraud status names: blacklisted customers see `Order placement is blocked`; customers over a daily limit or temporary admin limit see `Daily limit reached`.

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
| Email whitelist | E-mails that bypass daily amount and order-count limits only. Blacklists still apply. Separate values with a new line, comma, semicolon, or space. |
| Email blacklist | E-mails that are blocked from checkout. Separate values with a new line, comma, semicolon, or space. |
| Customer ID blacklist | Registered customer IDs that are blocked from checkout. Guest orders are checked by e-mail and IP only. |
| Temporary limited customer IDs | Customer IDs limited for 24 hours by customer admin buttons. Entries are stored as `customer_id:expires_at`. |
| IP blacklist | Exact IP addresses or CIDR ranges to block. Separate values with a new line, comma, semicolon, or space. |

Blocked customers are described as **Order placement is blocked**. Customers temporarily limited by daily count, amount, or admin-applied 24-hour limits are described as **Daily limit reached**. Removing an admin-applied limit also adds the customer e-mail to the whitelist so regular daily limits are bypassed.

## Customer admin actions

Open a registered customer in the Magento admin customer edit page. The module adds two toggle buttons:

- **Block Orders / Unblock Orders**: adds or removes the customer ID from the Customer ID blacklist.
- **Limit for 24 Hours / Remove Daily Limit**: adds a one-day temporary customer restriction or removes it. Removing the restriction adds the customer e-mail to the Email whitelist.
