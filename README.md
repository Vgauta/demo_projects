# Dining Table Set Configurator for WooCommerce

This plugin provides a ready-made **Dining Table Set Configurator** for WooCommerce products. It is built for stores that sell dining table sets where customers choose table color, chair design, extra chairs, chair color, and addons.

It is intentionally not a generic Kickflip-style builder. The client works from the normal WooCommerce product edit page and does not need to write JSON.

---

## What the default configurator includes

On plugin activation, the plugin stores a default template named:

**Dining Table Set Configurator**

The default frontend flow is:

1. **Choose Table Color**
   - White
   - Dark Grey
   - Champagne

2. **Choose Chair Design**
   - Luna
   - Tuna
   - Sano
   - X Design

3. **Extra Chairs**
   - 6 chairs included in price
   - Add 2 extra chairs
   - Add 4 extra chairs
   - Add 6 extra chairs

4. **Chair Colors**
   - All chairs are the same color
   - Choosing a color combination for chairs

5. **Choose Chair Color**
   - Shows only colors available for the selected chair variant
   - Example colors: Light Grey, Green, Mustard, Black

6. **Addons**
   - Chair cushion
   - Waterproof cover

---

## WooCommerce requirement

WooCommerce must be installed and active. Products should be normal WooCommerce products. Variable products are recommended if the store also wants to manage variations, stock, or base pricing with WooCommerce.

---

## Installation and activation

1. Upload the plugin folder to `wp-content/plugins/custom-woocommerce-product-configurator/`.
2. Go to **WordPress Admin → Plugins → Installed Plugins**.
3. Activate **Custom WooCommerce Product Configurator**.
4. Make sure WooCommerce is active.
5. The plugin automatically prepares the default **Dining Table Set Configurator** settings.

---

## Client workflow

Use this workflow for every dining table product:

1. Go to **Products → All Products**.
2. Edit the dining table set product.
3. Scroll to **Product Data**.
4. Open the **Dining Set Configurator** tab.
5. Check **Enable Dining Set Configurator**.
6. Click **Load Default Dining Set Template**.
7. Replace names, colors, images, and prices as needed.
8. Click **Update**.
9. On the frontend, the customer clicks **Customize & Add to Cart**.
10. The popup opens.
11. Customer configures the dining set.
12. Customer adds the product to cart from inside the popup.

---

## Dining Set Configurator tab

The product edit tab lets the client edit the default template with form fields.


### Dynamic canvas recolor preview

The Dining Set Configurator is not only a variation image swap. The client does **not** upload Luna White, Luna Black, Tuna Green, and every other color combination. Instead, the product uses one table base image, one table mask, one chair base image per chair variant, one chair mask per chair variant, and color hex codes. The frontend canvas tints the masks live, similar to a simple Canva/Kickflip-style recolor workflow.

Recommended image setup:

- Table preview/base image: one white/light neutral table image with shadows/details.
- Table mask image: transparent PNG where only the table recolor area is visible.
- Table X/Y/width/height: positions the table layer in the canvas.
- Chair thumbnail image: used only for the chair model selector.
- Chair preview/base image: one white/light neutral chair image per chair variant.
- Chair mask image: one transparent PNG mask per chair variant.
- Chair X/Y/width/height: positions that chair variant in the canvas.

No image upload field is needed inside table color items or chair color items. Colors are controlled by name, hex color code, and optional extra price.

### Product admin sections

When editing a product, open **Product Data → Dining Set Configurator** and use these sections:

1. **General Settings**
   - Enable Configurator
   - Popup button text
   - Base included chairs count

2. **Table Preview**
   - Table preview/base image
   - Table mask image
   - Table X position
   - Table Y position
   - Table Width
   - Table Height

3. **Table Colors**
   - Color name
   - Hex color code
   - Optional extra price
   - No per-color image upload

4. **Chair Variants**
   - Chair name
   - Thumbnail image
   - Chair preview/base image
   - Chair mask image
   - Chair X position
   - Chair Y position
   - Chair Width
   - Chair Height
   - Optional extra price

5. **Chair Colors Per Chair Variant**
   - Add the colors available for that exact chair variant.
   - Each color uses: color name, hex color code, and optional extra price.
   - Different chair variants can have different color lists.
   - No per-color image upload is needed.

6. **Extra Chair Options**
   - Label
   - Quantity
   - Extra price

7. **Chair Color Mode Options**
   - Enable “All chairs are the same color”
   - Enable “Choose a color combination for chairs”
   - Set the placeholder text for mixed color notes

8. **Cover / Addons**
   - Addon name
   - Price
   - Enabled checkbox

### Advanced fallback image mapping

Normal client setup does **not** require images for every chair color. The correct setup is one chair base image and one chair mask per variant, then unlimited colors from hex color codes. Older fallback mapping can remain only inside the advanced area for legacy products.

Image selections are saved with the WordPress Media Library attachment ID whenever possible. This keeps Hebrew and other Unicode filenames safe because the frontend asks WordPress for the final image URL instead of rebuilding URLs from filenames. Hebrew labels, option names, colors, chair names, addon names, and dropdown labels are saved as normal UTF-8 text, so the client can type Hebrew directly in the product editor.

---

## Frontend customer flow

The product page shows one clean **Customize & Add to Cart** button when the configurator is enabled. Clicking it opens a popup.

Inside the popup:

- **Left side:** large live preview area
- **Right side:** numbered step-by-step options panel
- The actual Add to Cart action is inside the popup

The customer flow is:

1. Click **Customize & Add to Cart**.
2. Choose a table color; the table recolors live on the canvas.
3. Choose a chair model; the chair base/mask changes in the preview.
4. Choose additional chairs for a fee.
5. Choose chair color mode: **All chairs are the same color** or **Choosing a color combination for chairs**.
6. Choose a chair color from the selected chair model’s available swatches; the chair recolors live.
7. If mixed color mode is selected, enter chair color combination notes.
8. Choose cover/addon options.
9. Review live price update.
10. Add to cart from inside the popup.

---

## Cart, checkout, and order saving

When the customer adds the product to cart, the plugin saves:

- Table Color
- Chair Design
- Extra Chairs
- Chair Color mode
- Chair Color
- Mixed chair color notes when entered
- Addons
- Price adjustment
- Preview layer image URLs when available

This data appears in cart/checkout item data and is saved to WooCommerce order item meta.

---

## How admin views customization in an order

1. Go to **WooCommerce → Orders**.
2. Open the order.
3. Find the dining table set line item.
4. Review the line item meta/details.
5. The selected table color, chair design, extra chairs, chair color, addons, and preview link are saved there.

---

## Troubleshooting

### Configurator does not show on product page

Check:

1. WooCommerce is active.
2. Product is published.
3. Product is a WooCommerce product.
4. In **Product Data → Dining Set Configurator**, **Enable Dining Set Configurator** is checked.
5. Product was updated after enabling.
6. Clear cache and reload the product page.

### Images do not show

1. Open **Product Data → Dining Set Configurator**.
2. Confirm preview images or thumbnails are selected.
3. Click **Update**.
4. Clear frontend cache.

### Prices do not update

Check the price fields for table colors, chair designs, extra chairs, chair colors, and addons. Then test with a fresh cart.

---

## Screenshots placeholder section

Add screenshots after installation:

1. Product Data → Dining Set Configurator tab
2. Load Default Dining Set Template button
3. Table color and chair design admin fields
4. Frontend Customize & Add to Cart button
5. Popup live preview and numbered steps
6. WooCommerce order item customization details

---

## Notes for client/store admin

- Use **Load Default Dining Set Template** first.
- Replace placeholder images with real product images.
- Keep color names consistent.
- Test one product fully before enabling more products.
- Do not edit developer JSON or advanced configurator screens.
