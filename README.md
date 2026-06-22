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

4. **Chair Color**
   - Same as table color
   - Choose different chair color

5. **Choose Chair Color**
   - Mixed colors
   - Dark Grey
   - Black
   - Mustard
   - Green
   - Light Grey

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

### 1. Table colors

For each table color, the client can edit:

- Color name
- Color code
- Price adjustment
- Table preview image

### 2. Chair designs

For each chair design, the client can edit:

- Chair design name
- Thumbnail image
- Preview image
- Price adjustment
- Available chair colors for that design

### 3. Extra chairs

For each extra chair dropdown option, the client can edit:

- Label
- Quantity
- Extra price

### 4. Chair colors

For each chair color, the client can edit:

- Color name
- Color code
- Price adjustment
- Chair preview image

Chair color swatches show only when the customer selects **Choose different chair color**.

### 5. Addons

For each addon, the client can edit:

- Addon name
- Price
- Enabled/disabled status

---

## Frontend customer flow

The product page shows one clean **Customize & Add to Cart** button when the configurator is enabled. Clicking it opens a popup.

Inside the popup:

- **Left side:** large live preview area
- **Right side:** numbered step-by-step options panel
- The actual Add to Cart action is inside the popup

The customer flow is:

1. Click **Customize & Add to Cart**.
2. Choose table color.
3. Choose chair design.
4. Choose extra chairs.
5. Choose whether chair color is the same as table color or different.
6. If different, choose chair color.
7. Choose addons.
8. Review live price update.
9. Add to cart from inside the popup.

---

## Cart, checkout, and order saving

When the customer adds the product to cart, the plugin saves:

- Table Color
- Chair Design
- Extra Chairs
- Chair Color mode
- Chair Color if different
- Addons
- Price adjustment
- Preview image URL when available

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
