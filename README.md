# Purchase Orders for WooCommerce®

The **Purchase Orders for WooCommerce** plugin adds a "Purchase Order" payment gateway to your WooCommerce store, allowing customers to provide a purchase order number during checkout. 

Ideal for B2B transactions, it simplifies order handling for businesses that process purchase orders without requiring upfront payment.

Unlike the $49/year version from Woo, this plugin is 100% free, fully open source, and highly customizable.

* * *

## **Features**

- **Purchase Order Gateway:** Adds a new payment option at checkout.
- **Customizable Behavior:**
    - Define the default order status for purchase orders (e.g., on-hold, processing).
    - Choose whether stock is reduced when a PO order is placed.
- **Order Meta Management:**
    - Display PO numbers in the admin dashboard, order details, and customer emails.
    - Automatically include PO numbers in admin order emails.
- **Admin Settings Page:** Configure behavior easily via the WooCommerce® settings menu.
- **Lightweight:** Focused functionality with no bloat.
- **Open Source:** Extend, modify, or review the code as you like.
* * *

## **Installation**

### **From GitHub**

1. Download the plugin from this repo.
2. In your WordPress® admin, go to **Plugins > Add New > Upload Plugin**.
3. Select the ZIP file and click **Install Now**.
4. Activate the plugin.

## **Usage**

1. **Configure the Plugin:**

    - Go to **WooCommerce > Settings > Purchase Orders** to customize:
        - The default order status for purchase orders.
        - Stock management behavior for PO orders.
    - Save your changes.
2. **Customer Checkout:**

    - Customers selecting "Purchase Order" as a payment method will be prompted to enter their PO number during checkout.
3. **Order Management:**

    - PO numbers are visible in:
        - The WooCommerce admin order details.
        - Thank-you pages and customer emails.
        - Admin emails.

## **Customization**

### **Settings Page**

- **Order Status:** Choose the default status for orders placed with a purchase order.
- **Reduce Stock:** Decide whether stock levels are reduced when a purchase order is placed.

## **FAQs**

### **1. Does this plugin work with custom WooCommerce® workflows?**

Yes. The plugin integrates seamlessly with WooCommerce's order management and stock tracking features.

### **2. Can I add custom fields or modify the gateway behavior?**

Yes. The plugin is open source, so you can extend or modify the code to suit your needs.

### **3. Is this really free?**

Yes. No strings attached, no annual fees. This plugin is part of a commitment to offer valuable tools without unnecessary costs.

## **Why Use This Plugin Instead of Woo's Version?**

- **Cost:** It's free, saving you $49/year.
- **Flexibility:** Offers customization options to fit your workflow.
- **Transparency:** 100% open source with no hidden tricks.
- **Lightweight:** Focused on doing one thing well.

## **Contributing**

Contributions, feature requests, and bug reports are welcome! Submit an [issue](https://github.com/robertdevore/purchase-orders-for-woocommerce/issue/) or [pull request](https://github.com/robertdevore/purchase-orders-for-woocommerce/pulls/).

## **License**

This plugin is licensed under the **GPL-2.0+** license. See the `LICENSE` file for details.