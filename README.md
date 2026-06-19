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
4. In the **Configurator Builder** box, keep the default **Visual Builder** tab selected.
5. Click **Add Layer** to add product preview images/layers.
6. Click **Add Option Group** and then **Add Option** to add choices such as sizes, parts, or colors.
7. Use **Choose Image** to select images from the WordPress Media Library.
8. Use the color picker for color choices.
9. Add price adjustments, sort order, and conditional display rules if needed.
10. Add text fields or upload fields with the provided buttons.
11. Click **Publish**.

### Step 2: Configure it on a WooCommerce product

1. Go to **Products → All Products**.
2. Open the WooCommerce product you want customers to customize.
3. In the **Product data** box, open the **Configurator** tab.
4. Tick **Enable Configurator**.
5. Choose a saved template in **Starting Point / Configurator Template**.
6. Optional: enter a manual template code/ID for custom integrations.
7. Set quantity rules such as **Beginning Quantity**, **Minimum Quantity**, **Maximum Quantity**, and **Increment Step Quantity**.
8. Choose whether to require a complete configuration or hide the default Add to Cart button until ready.
9. Choose the **Configurator Position**.
10. Select a **Product Base Preview Image** from the WordPress Media Library.
11. Add product-level option images/layers if this product needs extra choices.
12. Click **Update**.

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

Inside **Configurators → Add New**, the default **Visual Builder** lets admins create the configurator with buttons and simple fields. No JSON writing is required for normal client use.

A basic configurator can include:

- **Color options**: Let customers choose a color.
- **Image/option layers**: Let customers choose product parts or styles.
- **Text fields**: Let customers enter a name, message, initials, etc.
- **Upload fields**: Let customers upload a logo or artwork file.
- **Price adjustments**: Add extra cost for premium choices.
- **Conditional rules**: Show an option only when another option is selected.
- **Layer order**: Control which preview layer appears first, second, third, and so on.

Tip: Start with one layer and one option group, test it on a product, then add more choices.

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

## How client will use it

1. **Create configurator**: Go to **Configurators → Add New**, enter a title, and use the **Visual Builder** tab.
2. **Add product image/layers**: Click **Add Layer**, enter a label, choose a layer image from the Media Library, and set the sort order.
3. **Add options**: Click **Add Option Group**, then **Add Option** for each customer choice.
4. **Set colors/images**: Use the color picker for color choices and **Choose Image** for option thumbnails or layer images.
5. **Set prices**: Add a price adjustment to any option, text field, or upload field that should cost extra.
6. **Add text/upload fields**: Use **Add Text Field** or **Add Upload Field** if customers should enter text or upload a logo/artwork.
7. **Add conditional logic**: Use “Show when field” and “Equals value” when a field should only appear after another option is selected.
8. **Link configurator to product**: Edit the WooCommerce product, go to **Product Data → Configurator**, enable it, select the template, configure product options, and update the product.
9. **Test frontend**: Open the product page, make selections, check the preview and price, add to cart, and place a test order.


### Product Data → Configurator tab workflow

For most client work, the easiest place to manage a product configurator is directly on the WooCommerce product edit page:

1. Open **Products → All Products** and edit a product.
2. Scroll to **Product data**.
3. Click the **Configurator** tab.
4. Enable the configurator.
5. Choose a starting template or enter a manual code/ID if your developer provided one.
6. Set beginning, minimum, maximum, and step quantities.
7. Choose the display position.
8. Select the base preview image.
9. Add product option layers with simple fields for title, type, color, image, price, sort order, and enabled/disabled status.
10. Update the product and test it on the frontend.

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
   - Full configurator data
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

### I see a warning in the builder

The Visual Builder shows simple warnings for common setup issues, such as missing labels, invalid prices, or a preview layer without an image. Fill in the missing field or choose an image, then save again.

### Uploaded image/logo is not appearing

Check that the file is an allowed image type and under the upload size limit. Supported customer upload types are JPG, PNG, GIF, and WebP.

### Price adjustment does not look correct

Check each option's price adjustment in the Visual Builder. The frontend shows the option total, and WooCommerce applies the adjustment to the cart item price.

### WooCommerce is inactive

The plugin is activation-safe. If WooCommerce is inactive, activate WooCommerce first to use product-page, cart, checkout, and order features.

---

## Notes for store admins

- Start with one simple configurator first.
- Test with one product before applying it to many products.
- Place a real order in test mode to confirm order details are saved correctly.
- Replace screenshot placeholders in this README with actual screenshots from the final store setup.

---

## Advanced developer note

The plugin stores configurator data internally as JSON so it remains extensible. The **Advanced JSON** tab is available for developers, but store admins should use the default **Visual Builder** tab.

---

## Detailed guide: Add New Configurator screen

Use this screen when you want to create a reusable configurator template that can be attached to one or more WooCommerce products.

### 1. Open the configurator editor

1. Go to **WordPress Admin → Configurators**.
2. Click **Add Configurator** or **Add New**.
3. Enter a name, for example **Custom Shirt Builder**.
4. Stay on the default **Visual Builder** tab.

### 2. Add Product Preview Layers

Preview layers control what appears in the live product preview.

1. Click **Add Layer**.
2. Fill in **Layer title**, for example `Base Shirt Image`.
3. Leave **Layer ID** empty if you want the plugin to auto-generate it.
4. Choose a **Layer type**:
   - **Image** for a transparent PNG/SVG/JPG preview layer.
   - **Color** for a color overlay style layer.
   - **Text** for a text preview layer.
   - **Upload** for a customer-upload preview layer.
5. Click **Choose Image** to select the layer image from the WordPress Media Library.
6. Set **Sort order**. Lower numbers appear earlier in the preview stack.
7. Keep **Enabled** checked if this layer should be active.
8. Use **Remove layer** only if you want to delete that layer.

### 3. Add Option Groups

Option groups are the choices customers click on the product page, such as color, size, finish, material, or style.

1. Click **Add Option Group**.
2. Enter **Group title**, for example `Choose Color`.
3. Leave **Group ID** empty to auto-generate it.
4. Choose **Display type**:
   - **Buttons** for normal option buttons.
   - **Color swatches** for color choices.
   - **Image swatches** for thumbnail choices.
   - **Dropdown** for a compact select menu.
5. Check **Required** if the customer must choose an option.
6. Set **Sort order**.
7. Keep **Enabled** checked.

### 4. Add options inside a group

1. Inside the option group, click **Add Option**.
2. Enter **Option label**, for example `Black` or `Premium Finish`.
3. Leave **Option ID** empty to auto-generate it.
4. Choose a color with the color picker if this is a color option.
5. Click **Choose Image** beside **Option image** if you want a thumbnail.
6. Click **Choose Image** beside **Layer image** if this option should change the product preview.
7. Enter **Price adjustment** if this option adds cost.
8. Set **Sort order**.
9. Check **Default selected** if this option should be preselected.
10. Keep **Enabled** checked.

### 5. Add text fields

1. Click **Add Text Field**.
2. Enter the field label, such as `Name on product`.
3. Add placeholder text, such as `Enter your name`.
4. Set **Max length**.
5. Add a **Price adjustment** if custom text costs extra.
6. Check **Required** if customers must fill it in.

### 6. Add upload fields

1. Click **Add Upload Field**.
2. Enter a label, such as `Upload Logo`.
3. Set allowed file types, for example `jpg,png,webp`.
4. Set max file size in MB.
5. Add a **Price adjustment** if logo upload costs extra.
6. Check **Required** if upload is mandatory.

### 7. Save and test

1. Click **Publish** or **Update**.
2. Open the WooCommerce product.
3. Go to **Product Data → Configurator**.
4. Enable the configurator and select this template.
5. Update the product.
6. Open the product page on the frontend and test all options.

Tip: If buttons do not respond after an update, hard refresh the WordPress admin page once so the browser loads the latest plugin JavaScript.
