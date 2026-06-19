# Custom WooCommerce Product Configurator

A client-friendly, self-hosted WooCommerce plugin that lets shoppers customize a product before adding it to the cart. Store admins can create reusable configurator templates, connect them to WooCommerce products, and collect the customer's selected options, colors, custom text, uploaded logo/image, and preview details with the order.

This plugin is built with original code and does **not** depend on any external SaaS product.

---

## What this plugin does

Use this plugin when you sell products that customers can personalize, for example:

- T-shirts with color choices, text, or logo uploads
- Mugs with custom names or artwork
- Bags, hats, labels, stationery, gifts, or promotional products
- Any WooCommerce product that needs option-based pricing and a visual preview

Customers see a configurator on the product page, choose options, see the preview update, and add the configured product to the WooCommerce cart.

---

## Requirement: WooCommerce

WooCommerce must be installed and active for product-page, cart, checkout, and order features to work.

If WooCommerce is not active, this plugin will not break your site. You can still see the plugin admin notice, but product/cart/order integrations will stay disabled until WooCommerce is activated.

---

## Installation steps

1. Copy the plugin folder to your WordPress site at:
   `wp-content/plugins/custom-woocommerce-product-configurator/`
2. In WordPress admin, go to **Plugins → Installed Plugins**.
3. Find **Custom WooCommerce Product Configurator**.
4. Click **Activate**.
5. After activation, you should see a message: **“Go to Configurators to create your first configurator.”**
6. Make sure WooCommerce is installed and active.

---

## Quick start: WordPress admin step-by-step guide

### Step 1: Create your first configurator

1. In WordPress admin, go to **Configurators**.
2. Click **Add New**.
3. Enter a clear title, for example: **Custom T-Shirt Configurator**.
4. In the **Configurator Builder** box, click **Load Example** if you want a starter setup.
5. Review or edit the JSON fields for:
   - Layers
   - Options
   - Option titles
   - Colors
   - Price adjustments
   - Text fields
   - Upload fields
   - Conditional rules
   - Preview layer order
6. Click **Format JSON** to make the setup easier to read.
7. Click **Publish**.

### Step 2: Link the configurator to a WooCommerce product

1. Go to **Products → All Products**.
2. Open the WooCommerce product you want customers to customize.
3. Find the **Product Configurator** box on the product edit screen.
4. Tick **Enable configurator**.
5. Choose your configurator template from the **Template** dropdown.
6. Leave the default position hook unless you know you want a different placement.
   - Default recommended hook: `woocommerce_before_add_to_cart_button`
7. Optional: tick **Require complete configuration** if you want customers to complete the configurator before purchase.
8. Click **Update**.

### Step 3: Test on the storefront

1. Open the product page on the frontend.
2. The configurator should appear before the Add to Cart button.
3. Select options, colors, add text, or upload an image/logo.
4. Confirm the preview and options total update.
5. Add the product to the cart.
6. Check the cart and checkout pages for the customization summary.

---

## How to create a configurator

A configurator is a template that describes the customization fields shown to customers.

Inside **Configurators → Add New**, the current builder uses a JSON schema. This keeps the plugin flexible and makes it easier to add more fields later.

A basic configurator can include:

- **Color options**: Let customers choose a color.
- **Image/option layers**: Let customers choose product parts or styles.
- **Text fields**: Let customers enter a name, message, initials, etc.
- **Upload fields**: Let customers upload a logo or artwork file.
- **Price adjustments**: Add extra cost for premium choices.
- **Conditional rules**: Show an option only when another option is selected.
- **Layer order**: Control which preview layer appears first, second, third, and so on.

Tip: Start by clicking **Load Example**, then change the labels and prices for your own product.

---

## How to link a configurator to a product

Each WooCommerce product can be linked to one configurator template.

1. Edit the product.
2. Find **Product Configurator**.
3. Enable the configurator.
4. Select the configurator template.
5. Save/update the product.

After that, customers will see the configurator on that product page.

---

## Customer flow on the product page

When a customer visits a product with a configurator enabled, they can:

1. View the product preview area.
2. Choose available parts/options.
3. Change colors.
4. Add custom text.
5. Upload a logo or image.
6. See the preview update in real time.
7. See the option price total update.
8. Click **Reset** if they want to start again.
9. Add the configured product to the cart.

The plugin stores the configuration in a hidden field before the product is added to the cart.

---

## Cart, checkout, and order details

### Cart and checkout

The cart and checkout pages show a readable customization summary, such as selected options, custom text, uploaded files, and preview links when available.

The plugin also applies option-based price adjustments to the WooCommerce cart item price.

### Admin order page

After checkout, customization details are saved as WooCommerce order item meta. To view them:

1. Go to **WooCommerce → Orders**.
2. Open the order.
3. Find the purchased product line item.
4. Expand or review the item meta/details.
5. You should see:
   - Full configurator JSON data
   - Selected option summary
   - Custom text
   - Uploaded file links
   - Preview image reference when available

---

## Shortcode usage

You can manually render a configurator with this shortcode:

```text
[cwpc_configurator product_id="123" configurator_id="456"]
```

Use this when you want to place the configurator inside custom page content, a page builder, or a special product layout.

Parameters:

- `product_id`: WooCommerce product ID.
- `configurator_id`: Configurator template ID.

If `configurator_id` is not provided, the plugin will try to use the configurator linked to the product.

Example:

```text
[cwpc_configurator product_id="123"]
```

---

## Screenshots

Add real screenshots to this section when the plugin is installed on the client site.

1. **Screenshot 1: Configurator list**  
   Placeholder: WordPress admin screen showing **Configurators** list.

2. **Screenshot 2: Create configurator screen**  
   Placeholder: **Add New Configurator** screen with the builder box.

3. **Screenshot 3: Product link setting**  
   Placeholder: WooCommerce product edit page showing the **Product Configurator** box.

4. **Screenshot 4: Frontend product configurator**  
   Placeholder: Product page showing preview, options, text, upload, and price update.

5. **Screenshot 5: Order customization details**  
   Placeholder: WooCommerce order admin screen showing selected customization details.

---

## Common issues and troubleshooting

### I do not see the configurator on the product page

Check these items:

1. WooCommerce is active.
2. The product has **Enable configurator** checked.
3. A configurator template is selected for the product.
4. The product was updated after selecting the configurator.
5. Your theme supports the selected WooCommerce hook.
6. Try the shortcode as a fallback in the product description or a custom layout.

### I do not see the Configurators menu

Make sure the plugin is active under **Plugins → Installed Plugins**. The menu is called **Configurators** in the WordPress admin sidebar.

### My JSON will not save

The builder currently stores an extensible JSON schema. If the JSON is invalid, WordPress will not save it as a valid configurator schema.

Try this:

1. Click **Format JSON**.
2. Fix any message shown by your browser.
3. Make sure commas, quotes, and brackets are correct.
4. Start again with **Load Example** if needed.

### Uploaded image/logo is not appearing

Check that the file is an allowed image type and under the upload size limit. Supported customer upload types are JPG, PNG, GIF, and WebP.

### Price adjustment does not look correct

Check each option's `price` value in the configurator JSON. The frontend shows the option total, and WooCommerce applies the adjustment to the cart item price.

### WooCommerce is inactive

The plugin is activation-safe. If WooCommerce is inactive, activate WooCommerce first to use product-page, cart, checkout, and order features.

---

## Notes for store admins

- Start with one simple configurator first.
- Test with one product before applying it to many products.
- Place a real order in test mode to confirm order details are saved correctly.
- Replace screenshot placeholders in this README with actual screenshots from the final store setup.

---

## Current builder note

The first version uses a JSON-based builder for flexibility. This is useful for setup and testing, but a future version can add a more visual drag-and-drop admin builder if the client wants a fully no-code editing experience.
