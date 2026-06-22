# Custom WooCommerce Product Configurator

Custom WooCommerce Product Configurator is a self-hosted WordPress plugin for WooCommerce stores that sell customizable products. It lets a store admin create a product configurator, attach it to a WooCommerce product, and collect the customer's selected options, colors, custom text, uploads, and preview data with the order.

The plugin does not require an external SaaS service.

---

## What this plugin does

Use this plugin when customers need to personalize a product before buying it. Example use cases include:

- Shirts, uniforms, hats, bags, or mugs with color choices
- Products with optional parts, layers, images, finishes, or styles
- Personalized gifts with custom text
- Products that require a customer logo or artwork upload
- Products where some options add an extra price

On the product page, the customer sees a visual configurator before the product is added to the cart. Their configuration is saved into the WooCommerce cart, checkout, and order.

---

## WooCommerce requirement

WooCommerce must be installed and active for product-page display, Add to Cart, cart, checkout, and order saving to work.

If WooCommerce is inactive, the plugin will not crash the site. It shows an admin notice and disables WooCommerce-specific features until WooCommerce is active again.

---

## Installation steps

1. Upload the plugin folder to:
   `wp-content/plugins/custom-woocommerce-product-configurator/`
2. Go to **WordPress Admin → Plugins → Installed Plugins**.
3. Find **Custom WooCommerce Product Configurator**.
4. Click **Activate**.
5. Make sure **WooCommerce** is also installed and active.

---

## Activation steps

After activation, WordPress should show this onboarding message:

> Go to Configurators to create your first configurator.

Click the link in that notice, or go manually to **WordPress Admin → Configurators**.

---

## Where the settings appear

The plugin has two main admin areas:

### 1. Configurator templates

Go to **WordPress Admin → Configurators**.

This is where you create reusable configurator templates with the **Visual Builder**. A template can contain preview layers, option groups, text fields, upload fields, prices, and conditional logic.

### 2. WooCommerce product settings

Go to **Products → Edit Product → Product Data → Configurator**.

This is where you enable the configurator for a specific WooCommerce product, select the template, set quantity rules, choose the display position, and add product-specific option images/layers if needed.

---

## How to create or edit a configurator template

1. Go to **WordPress Admin → Configurators**.
2. Click **Add Configurator** or edit an existing configurator.
3. Enter a clear title, for example **Custom Shirt Builder**.
4. Use the default **Visual Builder** tab.
5. Click **Add Layer** for product preview layers.
6. Click **Add Option Group** for customer choices like color, size, finish, or material.
7. Inside an option group, click **Add Option** for each selectable choice.
8. Click **Add Text Field** if the customer should enter custom text.
9. Click **Add Upload Field** if the customer should upload a logo or artwork.
10. Use **Choose Image** to select images from the WordPress Media Library.
11. Use the color picker for color choices.
12. Add price adjustments where needed.
13. Click **Publish** or **Update**.

Normal store admins should not edit JSON. The **Advanced JSON** tab is only for developers.

---

## Detailed guide: Add New Configurator screen

### Product Preview Layers

Preview layers control what appears in the live product preview.

1. Click **Add Layer**.
2. Enter **Layer title**, for example `Base Shirt Image`.
3. Leave **Layer ID** empty unless your developer gave you a specific ID.
4. Choose a **Layer type**: image, color, text, or upload.
5. Click **Choose Image** to select the layer image.
6. Set **Sort order**. Lower numbers appear earlier.
7. Keep **Enabled** checked.

### Option Groups

Option groups are sets of choices customers can select.

1. Click **Add Option Group**.
2. Enter **Group title**, for example `Choose Color`.
3. Leave **Group ID** empty to auto-generate it.
4. Choose **Display type**: buttons, color swatches, image swatches, or dropdown.
5. Check **Required** if customers must select an option.
6. Set **Sort order**.
7. Keep **Enabled** checked.

### Options inside a group

1. Click **Add Option**.
2. Enter **Option label**, for example `Black`.
3. Leave **Option ID** empty to auto-generate it.
4. Pick a color if this is a color option.
5. Select **Option image** if you want a thumbnail.
6. Select **Layer image** if this option should change the preview.
7. Add **Price adjustment** if this option costs extra.
8. Check **Default selected** if it should be preselected.
9. Keep **Enabled** checked.

### Text fields

1. Click **Add Text Field**.
2. Enter the field label, for example `Name on product`.
3. Add placeholder text.
4. Set maximum length.
5. Add a price adjustment if custom text costs extra.
6. Check **Required** if needed.

### Upload fields

1. Click **Add Upload Field**.
2. Enter the field label, for example `Upload Logo`.
3. Set allowed file types, for example `jpg,png,webp`.
4. Set the maximum file size in MB.
5. Add a price adjustment if upload costs extra.
6. Check **Required** if needed.

---

## How to connect a configurator to a WooCommerce product

Use this exact flow:

**Product → Edit Product → Product Data → Configurator tab → Enable Configurator → Select Configurator → Update**

Step by step:

1. Go to **Products → All Products**.
2. Edit the product you want to customize.
3. Scroll to the **Product Data** box.
4. Open the **Configurator** tab.
5. Tick **Enable Configurator**.
6. In **Starting Point / Configurator Template**, select the configurator template.
7. Optional: add a manual template code/ID only if your developer asks for it.
8. Set **Beginning Quantity**. Default is `1`.
9. Optional: set **Minimum Quantity**, **Maximum Quantity**, and **Increment Step Quantity**.
10. Optional: enable **Require Complete Configuration**.
11. Optional: enable **Hide Default Add To Cart Until Ready**.
12. Choose **Configurator Position**. Recommended default: **Before add to cart button**.
13. Optional: choose a **Product Base Preview Image**.
14. Optional: add product-specific option images/layers.
15. Click **Update**.
16. Open the product page on the frontend and confirm the configurator appears.

---

## How customers see it on the frontend

On a product with the configurator enabled, customers can:

1. See the preview area.
2. Select options, colors, images, or product parts.
3. Enter custom text.
4. Upload a logo or image if upload fields are enabled.
5. See the option price total update.
6. Reset the configuration if needed.
7. Add the configured product to the cart.

By default, the configurator appears before the Add to Cart button when the product setting is set to **Before add to cart button**.

---

## Cart, checkout, and order saving

When the customer adds the product to the cart, the plugin saves the configuration data with the WooCommerce cart item.

The cart and checkout can show:

- Selected options
- Custom text
- Uploaded file links
- Preview image link if available
- Option-based price adjustment

When the order is placed, the plugin saves the full configuration as WooCommerce order item meta.

---

## How admin can view customization in WooCommerce order

1. Go to **WooCommerce → Orders**.
2. Open the order.
3. Find the configured product line item.
4. Expand or review the product item details/meta.
5. Look for configurator details such as selected options, custom text, upload links, preview link, and full configuration data.

---

## Shortcode fallback

If your theme does not show the selected WooCommerce hook correctly, you can render the configurator manually with a shortcode.

Basic shortcode:

```text
[cwpc_configurator product_id="123"]
```

Shortcode with a specific configurator template:

```text
[cwpc_configurator product_id="123" configurator_id="456"]
```

Replace `123` with the WooCommerce product ID and `456` with the configurator template ID.

If you set the product's **Configurator Position** to **Shortcode only**, use this shortcode in the product description, a page builder, or a custom template location.

---

## Troubleshooting

### Configurator selected but not showing on frontend

Check these items first:

1. WooCommerce is active.
2. The product is a WooCommerce product and is published.
3. Go to **Products → Edit Product → Product Data → Configurator**.
4. Confirm **Enable Configurator** is checked.
5. Confirm a template is selected in **Starting Point / Configurator Template**, or product-level options/layers exist.
6. Confirm **Configurator Position** is not set to **Shortcode only** unless you are using the shortcode.
7. Click **Update** on the product after making changes.
8. Clear any cache plugin and browser cache.
9. Open the single product page again.
10. If the theme does not output the selected WooCommerce hook, set the position to **Before add to cart button** or use the shortcode fallback.

If `WP_DEBUG` is enabled, the plugin writes safe debug messages to the PHP error log with the enabled value, selected configurator ID, selected hook, and whether schema data was found.

### The Visual Builder buttons do not respond

1. Hard refresh the WordPress admin page.
2. Clear cache/minification plugins for admin scripts.
3. Confirm JavaScript is enabled in the browser.
4. Check whether another admin plugin is blocking WordPress media or color picker scripts.

### Images do not appear in the preview

1. Make sure the image URL is saved.
2. Use images from the WordPress Media Library when possible.
3. For best results, use transparent PNG/SVG layers.
4. Clear frontend cache.

### Price adjustment does not look correct

1. Check each option's price adjustment in the Visual Builder.
2. Update the configurator template.
3. Update the product.
4. Re-test with a new cart session.

### Upload field does not accept a file

1. Confirm the file type is allowed.
2. Confirm the file size is below the configured maximum.
3. Confirm WordPress uploads are working on the site.

---

## Screenshots placeholder section

Add real screenshots here after installing the plugin on the client site.

1. **Screenshot 1: Configurator list** — WordPress admin list of configurator templates.
2. **Screenshot 2: Create configurator screen** — Visual Builder with layers and option groups.
3. **Screenshot 3: Product link setting** — Product Data → Configurator tab.
4. **Screenshot 4: Frontend product configurator** — Product page with preview and options.
5. **Screenshot 5: Order customization details** — WooCommerce order item meta with selected options.

---

## Notes for client/store admin

- Start with one simple configurator and one test product.
- Test the full flow: product page → cart → checkout → order admin.
- Use the **Visual Builder** for normal work.
- Do not edit the **Advanced JSON** tab unless a developer asks you to.
- If the configurator does not appear, first check the product's **Product Data → Configurator** tab.
- Keep image files optimized for faster product page loading.

---

## Advanced developer section

Configurator data is stored internally as JSON in post meta so developers can extend or migrate it later. This is not required for normal client use.
